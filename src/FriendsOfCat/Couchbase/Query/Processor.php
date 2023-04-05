<?php

namespace FriendsOfCat\Couchbase\Query;

use Couchbase\QueryResult;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
    /**
     * Process the results of a "select" query.
     *
     * @param Builder $query
     * @param \stdClass $results
     * @return QueryResult
     */
    public function processSelectWithMeta(Builder $query, $results)
    {
        //      $reflection = new \ReflectionProperty($results, 'rows');
        //      $reflection->setAccessible(true);
        //      $reflection->setValue();
        return $results;
    }
}
