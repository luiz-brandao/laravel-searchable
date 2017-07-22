<?php

namespace Searchable\Tests;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Quote
 * @package Searchable\Tests
 */
class Quote extends Model
{
    protected $fillable = ['quote'];

    protected $searchable = ['quote'];
}