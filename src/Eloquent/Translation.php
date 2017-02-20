<?php

namespace Shanginn\Yalt\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Translation extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
//    public $incrementing = false;

    /**
     * The parent model of the relationship.
     *
     * @var Model
     */
    protected $parent;

    /**
     * The name of the foreign key column.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the "other key" column.
     *
     * @var string
     */
    protected $relatedKey;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $hidden = ['id'];

    /**
     * Create a new Localization pivot model instance.
     *
     * @param  array   $attributes
     * @param  Model  $parent
     */
    public function __construct(array $attributes = [], Model $parent)
    {
        parent::__construct($attributes);

        if ($parent) {
            $this->parent = $parent;

            $this->setTable($this->guessTranslationsTable($parent));
        }

        // The pivot model is a "dynamic" model since we will set the tables dynamically
        // for the instance. This allows it work for any intermediate tables for the
        // many to many relationship that are defined by this developer's classes.
        if (count($attributes)) {
            $this->forceFill($attributes)
                ->syncOriginal();
        }
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = new static((array) $attributes, $this->parent);

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        return $model;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Model $parent)
    {
        $this->parent = $parent;
    }

    protected function guessTranslatableModelName()
    {
        $caller = Arr::first(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function ($trace) {
            return $trace['function'] === 'translations';
        });

        dd($caller);

        return !is_null($caller) ? class_basename($caller['class']) : null;
    }

    public function guessTranslationsTable($parent)
    {
        return implode('_', [
            Str::snake(Str::plural(class_basename($parent))),
            Str::snake(Str::plural(config('translatable.translation_suffix', 'Translation')))
        ]);
    }
}
