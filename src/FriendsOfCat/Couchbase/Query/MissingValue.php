<?php

namespace FriendsOfCat\Couchbase\Query;

final class MissingValue
{
    /**
     * MissingValue constructor.
     * @deprecated Please use getMissingValue() instead
     */
    public function __construct()
    {
    }

    public static function getMissingValue()
    {
        static $missingValue;

        if (! isset($missingValue)) {
            $missingValue = new self();
        }

        return $missingValue;
    }
}
