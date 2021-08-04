<?php

namespace FriendsOfCat\Couchbase\Events;

class QueryFired
{
    /** @var string */
    protected $query;

    /** @var array */
    protected $options;

    /**
     * QueryFired constructor.
     * @param string $query
     * @param array $options
     */
    public function __construct(string $query, array $options)
    {
        $this->query = $query;
        $this->options = $options;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getPositionalParams()
    {
        return $this->options['positionalParams'] ?? [];
    }

    public function getConsistency()
    {
        return $this->options['consistency'] ?? [];
    }

    public function isSuccessful()
    {
        return isset($this->options['isSuccessful']) && $this->options['isSuccessful'];
    }
}
