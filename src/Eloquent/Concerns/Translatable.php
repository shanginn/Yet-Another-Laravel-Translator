<?php

namespace Shanginn\Yalt\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Shanginn\Yalt\Eloquent\Scopes\JoinTranslationsTable;
use Shanginn\Yalt\Eloquent\Translation;
use Shanginn\Yalt\Http\Exceptions\UnsupportedLocaleException;
use Yalt;

/**
 * @property array $translatable Array with the fields translated in the Localizations table.
 * @property \Illuminate\Database\Eloquent\Collection|Translation $translations
 *
 * @mixin Model
 */
trait Translatable
{
    /**
     * Set $translationModel if you want to overwrite the convention
     * for the name of the translation Model. Use full namespace if applied.
     *
     * The convention is to add "Translation" to the name of the class extending Translatable.
     * Example: Country => CountryTranslation
     *
     * @var $translationModelName string
     */

    /**
     * This is the foreign key used to define the translation relationship.
     * Set this if you want to overwrite the laravel default for foreign keys.
     *
     * @var $translationForeignKey string
     */

    /**
     * The database field being used to define the locale parameter in the translation model.
     * Defaults to 'locale'.
     *
     * @var $localeKey string
     */

    protected $defaultLocale;

    protected $translatableTable;

    /**
     * Sign on model events.
     */
    public static function bootTranslatable()
    {
        static::saved(function ($model) {
            $model->saveTranslations();
        });
    }

    /**
     * Save all related translations
     *
     * @return \Traversable|array
     */
    public function saveTranslations()
    {
        return $this->translations()->saveMany($this->translations);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        /** @var Translation $instance */
        $instance = $this->newTranslation();

        $localKey = $this->getKeyName();
        $foreignKey = $instance->getTable() . '.' . $this->getForeignKey();

        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        return $this->isTranslatable($key) && ($translated = $this->getTranslatedAttributes()[$key] ?? false) ?
            $translated : parent::getAttribute($key);
    }

    /**
     * Get translatable attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirtyTranslations()
    {
        $dirty = [];

        $dirtyTranslations = $this->translations->reduce(function ($array, Model $translation) {
            $array[$translation->locale] = $translation->getDirty();

            return $array;
        }, []);

        foreach ($dirtyTranslations as $locale => $translations) {
            foreach ($translations as $key => $value) {
                !isset($dirty[$key]) && $dirty[$key] = [];

                $dirty[$key][$locale] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        $dirty = array_merge($this->getDirty(), $this->getDirtyTranslations());

        // If no specific attributes were provided, we will just see if the dirty array
        // already contains any attributes. If it does we will just return that this
        // count is greater than zero. Else, we need to check specific attributes.
        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        $attributes = is_array($attributes)
            ? $attributes : func_get_args();

        // Here we will spin through every attribute and see if this is in the array of
        // dirty attributes. If it is, we will return true and if we make it through
        // all of the attributes for the entire array we will return false at end.
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new Translation model instance for a related model.
     *
     * @param array $attributes
     * @return Translation
     */
    public function newTranslation(array $attributes = [])
    {
        return tap(new Translation($attributes, $this), function (Translation $instance) {
            if (!$instance->getConnectionName() && $this->connection) {
                $instance->setConnection($this->connection);
            }
        });
    }

    /**
     * Get translatable attributes of a given array.
     *
     * @param  array  $attributes
     * @return array
     */
    public function translatableFromArray(array $attributes)
    {
        return count($this->getTranslatable()) ?
            array_intersect_key($attributes, array_flip($this->getTranslatable())) : [];
    }

    /**
     * Filter the translations from attributes.
     *
     * @param  array  &$attributes
     * @return array
     */
    protected function extractTranslationsFromAttributes(array &$attributes)
    {
        $translations = [];

        foreach ($attributes as $key => $value) {
            if ($this->isTranslatable($key)) {
                foreach ($value as $locale => $localization) {
                    if (Yalt::isValidLocale($locale)) {
                        $translations[$locale][$key] = $localization;
                    } else {
                        throw new UnsupportedLocaleException($locale);
                    }
                }

                unset($attributes[$key]);
            }
        }

        return $translations;
    }

    /**
     * Get the translatable attributes for the model.
     *
     * @return array
     */
    public function getTranslatable()
    {
        return $this->translatable ?? [];
    }

    /**
     * Determine if the given attribute may be localized.
     *
     * @param  string  $key
     * @return bool
     */
    public function isTranslatable($key)
    {
        return in_array($key, $this->getTranslatable());
    }

    /**
     *
     * @param string $locale
     * @param bool   $withFallback
     *
     * @return array
     */
    public function getTranslationsFor($locale, $withFallback = null)
    {
        $withFallback = $withFallback ?? $this->withTranslationFallback();
        $fallbackLocale = Yalt::getFallbackLocale($locale);

        $translations = $this->getTranslationFor($locale) ??
            (($withFallback && $fallbackLocale) ? $this->getTranslationFor($fallbackLocale) : null);

        $translations = $translations ? $translations->toArray() : [];

        return array_intersect_key($translations, array_flip($this->translatable));
    }

    /**
     * Gets existing translation for locale or null
     *
     * @param $locale
     * @return Translation|null
     */
    protected function getTranslationFor($locale)
    {
        return $this->translations->where($this->getLocaleKey(), $locale)->first();
    }

    /**
     * Provides existing or create new translation for givvin locale
     *
     * @param $locale
     * @return Translation
     */
    public function translationTo($locale)
    {
        if (is_null($translation = $this->getTranslationFor($locale))) {
            $translation = $this->newTranslation(['locale' => $locale]);

            $this->translations->add($translation);
        }

        return $translation;
    }

    /**
     * @param array $attributes
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     * @return $this
     */
    public function fill(array $attributes)
    {
        if (count($this->translatableFromArray($attributes))) {
            $translatableAttributesKeys = array_flip($this->getTranslatable());

            foreach ($this->extractTranslationsFromAttributes($attributes) as $locale => $values) {
                // Get existing translation or make new and fill it with values (add later)
                $translation = ($this->getTranslationFor($locale) ?? $this->newTranslation(['locale' => $locale]))
                    ->fill($values);

                // Get values of translatable attributes
                $translatedAttributes = array_intersect_key($translation->getAttributes(), $translatableAttributesKeys);

                // Delete row if all of the values are NULL's
                if (!array_filter($translatedAttributes)) {
                    if ($translationRow = $this->translations()->find($translation->id)) {
                        $translationRow->delete();
                        //TODO: unset deleted row from $this->translations
                    }
                } elseif (!$translation->exists) {
                    $this->translations->add($translation);
                }
            }
        }

        return parent::fill($attributes);
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        if ($this->relationLoaded('translations') || config('yalt.loads_translations')) {
            $translated = $this->getTranslatedAttributes();

            unset($attributes['translations']);
        }

        return array_merge($attributes, $translated);
    }

    /**
     * @return array
     */
    protected function getTranslatedAttributes()
    {
        $translated = [];

        foreach ($this->translations as $translation) {
            foreach ($this->getTranslatable() as $field) {
                isset($translated[$field]) || $translated[$field] = (object) [];
                !is_null($translation->$field) && $translated[$field]->{$translation->locale} = $translation->$field;
            }
        }

        return $translated;
    }

    /**
     * @return bool|null
     */
    public function withTranslationFallback()
    {
        return $this->useTranslationFallback ?? Yalt::useFallback();
    }

    /**
     * @return string
     */
    protected function locale()
    {
        return $this->defaultLocale ??
            $this->setDefaultLocale(
                config('yalt.locale') ?? app('translator')->getLocale()
            );
    }

    /**
     * Set the default locale on the model.
     *
     * @param $locale
     *
     * @return string
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;

        return $locale;
    }

    /**
     * Get the default locale on the model.
     *
     * @return mixed
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @return string
     */
    public function getLocaleKey()
    {
        return $this->localeKey ?? config('yalt.locale_key', 'locale');
    }

    public static function joinTranslationsTable(\Closure $callback = null, $joinType = 'right')
    {
        static::addGlobalScope(new JoinTranslationsTable($callback, $joinType));
    }

    /**
     * Scope a query to order results by one or many translatable fields.
     *
     * @param array $orders
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function addOrderByTranslatableScope($orders)
    {
        $model = new static;
        $orders = $model->translatableFromArray($orders);
        $table = $model->newTranslation()->getTable();

        static::joinTranslationsTable(function (Builder $builder) use ($orders, $table) {
            if (empty((array) $builder->getQuery()->columns)) {
                $builder->select((new static)->getTable() . '.*');
            }

            foreach ($orders as $column => $rules) {
                $transOrderColumn = '_order-by-translated-' . $column;

                $builder
                    ->addSelect($table . '.' . $column . ' as ' . $transOrderColumn)
                    ->where($table . '.' . 'locale', '=', $rules['lang'])
                    ->orderBy($transOrderColumn, $rules['direction']);
            }
        }, 'right');
    }
}