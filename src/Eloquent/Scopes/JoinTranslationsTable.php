<?php

namespace Shanginn\Yalt\Eloquent\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class JoinTranslationsTable implements Scope
{
    protected $callback;

    /**
     * JoinTranslationsTable constructor.
     * @param callable|null $callback
     */
    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $query, Model $model)
    {
        /** @var $model \Shanginn\Yalt\Eloquent\Concerns\Translatable */
        $query->join(
            $table = $model->newTranslation()->getTable(),
            $model->getTable() . '.' . $model->getKeyName(),
            '=',
            $table . '.' . $model->getForeignKey(),
            'right'
        );

        $this->callback && call_user_func($this->callback, $query);
    }
}