<?php
namespace Searcher;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait Searchable
 * @package Searcher
 */
trait Searchable
{
    /**
     * @param Builder $query
     * @param $terms
     * @param null $language
     * @return Builder
     */
    public function scopeSearch(Builder $query, $terms, $language = null)
    {
        $searcher = new Search($query);

        $searcher->fields($this->searchable);

        if ($language) {
            $searcher->language($language);
        }

        return $searcher->search($terms);
    }
}
