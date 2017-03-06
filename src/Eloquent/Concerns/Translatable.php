<?php

namespace Shanginn\Yalt\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Shanginn\Yalt\Eloquent\Translation;
use Yalt;

/**
 * @property array $translatable Array with the fields translated in the Localizations table.
 * @property Collection[Translation] $this->translations
 *
 * @mixin \Illuminate\Database\Eloquent\Model
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
        $foreignKey = $instance->getTable() . '.' . ($this->translationForeignKey ?? $this->getForeignKey());

        return new HasMany(
            $instance->newQuery(), $this, $foreignKey, $localKey
        );
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
     * Get the translatable attributes of a given array.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function translatableFromArray(array $attributes)
    {
        if (count($this->getTranslatable()) > 0) {
            return array_intersect_key($attributes, array_flip($this->getTranslatable()));
        }

        return [];
    }

    /**
     * Filter the translations from attributes.
     *
     * @param  array  &$attributes
     * @return array
     */
    public function translationsFromArray(array &$attributes)
    {
        $translations = [];

        foreach ($attributes as $key => $value) {
            if ($this->isTranslatable($key)) {
                if (!is_array($value)) {
                    $translations[$this->locale()][$key] = $value;

                    continue;
                }

                foreach ($value as $locale => $localization) {
                    if (Yalt::isValidLocale($locale)) {
                        $translations[$locale][$key] = $localization;
                    }
                }
            } elseif (Yalt::isValidLocale($key)) {
                $this->translationTo($key)->fill($value);
            } else continue;

            unset($attributes[$key]);
        }

        return $translations;
    }

    public function translationTo($locale)
    {
        if (is_null($translation = $this->getTranslationFor($locale))) {
            $translation = $this->newTranslation(['locale' => $locale]);

            $this->translations->add($translation);
        }

        return $translation;
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
     * @param bool        $withFallback
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
     * @param $locale
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getTranslationFor($locale)
    {
        return $this->translations->where($this->getLocaleKey(), $locale)->first();
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
            foreach($this->translationsFromArray($attributes) as $locale => $values) {
                $this->translationTo($locale)->fill($values);
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
            $attributes = array_merge(
                $attributes,
                //$this->getArrayableItems() TODO:
                $this->getTranslationsFor($this->locale())
            );
        }

        return $attributes;
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
}