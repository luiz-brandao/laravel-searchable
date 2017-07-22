<?php

namespace App\Searchable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Wamania\Snowball\Stemmer;

/**
 * Class EloquentSearch
 * @package App\Searcher
 */
class Search
{
    /**
     * @var string
     */
    protected $language = 'english';

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $queryString;

    /**
     * @var array
     */
    protected $fields;

    /**
     * Searcher constructor.
     * @param $modelOrBuilder
     */
    public function __construct($modelOrBuilder)
    {
        if($modelOrBuilder instanceof Builder){
            $this->builder = $modelOrBuilder;
            $this->model = $this->builder->getModel();

        } else if($modelOrBuilder instanceof Model){
            $this->model = $modelOrBuilder;
            $this->builder = $this->model->newQuery();

        } else {
            throw new \InvalidArgumentException();
        };
    }

    /**
     * @param $language
     * @return $this
     */
    public function language($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param array|string $fields
     * @return $this
     */
    public function fields($fields)
    {
        $this->fields = is_array($fields) ? $fields : [$fields];

        return $this;
    }

    /**
     * @param $terms
     * @return Builder
     */
    public function search($terms)
    {
        return $this->booleanSearch($terms);
    }

    /**
     * @param $terms
     * @return $this
     */
    public function booleanSearch($terms)
    {
        $queryString = $this->getQueryString($this->getStemmerFor($this->language), $terms);

        $fields = $this->getSearchableFields();

        return $this->builder->whereRaw("MATCH($fields) AGAINST(? IN BOOLEAN MODE)", $queryString);
    }

    /**
     * @param $language
     * @return mixed
     */
    protected function getStemmerFor($language)
    {
        $className = '\Wamania\Snowball\\' . Str::ucfirst($language);

        return new $className;
    }

    /**
     * @param $stemmer
     * @param $searchString
     * @return string
     */
    protected function getQueryString(Stemmer $stemmer, $searchString)
    {
        return (new Boolify($stemmer, $searchString))->getSearchQueryString();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getSearchableFields()
    {
        $fields = $this->model->searchable ?? $this->fields;

        if(!$fields){
            throw new \Exception('No searchable fields specified');
        }

        return implode(',', $fields);
    }
}
