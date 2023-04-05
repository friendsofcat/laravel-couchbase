<?php

namespace FriendsOfCat\Couchbase\Query;

use Couchbase\Exception;
use Illuminate\Support\Arr;
use FriendsOfCat\Couchbase\Helper;
use Illuminate\Support\Collection;
use FriendsOfCat\Couchbase\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

class Builder extends BaseBuilder
{
    /**
     * The column projections.
     *
     * @var array
     */
    public $projections;

    public $forIns = [];

    /**
     * @var string
     */
    public $type;

    /**
     * The cursor timeout value.
     *
     * @var int
     */
    public $timeout;

    /**
     * The cursor hint value.
     *
     * @var int
     */
    public $hint;

    /**
     * Indicate if we are executing a pagination query.
     *
     * @var bool
     */
    public $paginating = false;

    /**
     * @var array
     */
    public $options;

    /**
     * All of the available clause operators.
     *
     * see https://docs.couchbase.com/server/current/n1ql/n1ql-language-reference/comparisonops.html
     *
     * @var array
     */
    public $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        'like',
        'not like',
        'between',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        'rlike',
        'regexp',
        'not regexp',
        'exists',
        'type',
        'mod',
        'where',
        'all',
        'size',
        'regex',
        'text',
        'slice',
        'elemmatch',
        'geowithin',
        'geointersects',
        'near',
        'nearsphere',
        'geometry',
        'maxdistance',
        'center',
        'centersphere',
        'box',
        'polygon',
        'uniquedocs',
    ];

    /**
     * Operator conversion.
     *
     * @var array
     */
    protected $conversion = [
        '=' => '=',
        '!=' => '$ne',
        '<>' => '$ne',
        '<' => '$lt',
        '<=' => '$lte',
        '>' => '$gt',
        '>=' => '$gte',
    ];

    /**
     * Check if we need to return Collections instead of plain arrays (laravel >= 5.3 )
     *
     * @var boolean
     */
    protected $useCollections;

    /**
     * Keys used via 'USE KEYS'
     * @var null|array|string
     */
    public $keys = null;

    /**
     * Var used because it is called by magic for compileUse() / has to be not null
     * @var true
     */
    public $use = true;

    /**
     * Indexes used via 'USE INDEX'
     * @var array
     */
    public $indexes = [];

    /** @var string[]  returning-clause */
    public $returning = ['*'];

    /**
     * Create a new query builder instance.
     *
     * @param ConnectionInterface $connection
     * @param BaseGrammar $grammar
     * @param Processor $processor
     * @throws \Exception
     * @return void
     */
    public function __construct(
        ConnectionInterface $connection,
        BaseGrammar $grammar = null,
        Processor $processor = null
    ) {
        if (! ($connection instanceof Connection)) {
            throw new \Exception('Argument 1 passed to ' . get_class($this) . '::__construct() must be an instance of ' . Connection::class . ', instance of ' . get_class($connection) . ' given.');
        }

        if (! ($grammar === null || $grammar instanceof Grammar)) {
            throw new \Exception('Argument 2 passed to ' . get_class($this) . '::__construct() must be an instance of ' . Grammar::class . ', instance of ' . get_class($grammar) . ' given.');
        }

        parent::__construct($connection, $grammar, $processor);
        $this->useCollections = $this->shouldUseCollections();
        $this->returning([$this->connection->getBucketName() . '.*']);
    }

    /**
     * @param array|string $keys
     *
     * @throws Exception
     * @return $this
     */
    public function useKeys($keys)
    {
        if (! empty($this->indexes)) {
            throw new Exception('Only one of useKeys or useIndex can be used, not both.');
        }

        if (is_null($keys)) {
            $keys = [];
        }
        $this->keys = $keys;

        return $this;
    }

    /**
     * @param $name
     * @throws Exception
     * @return $this
     */
    public function useIndex($name)
    {
        if ($this->keys !== null) {
            throw new Exception('Only one of useKeys or useIndex can be used, not both.');
        }
        $this->indexes[] = [
            'name' => $name,
            'type' => Grammar::INDEX_TYPE_GSI,
        ];

        return $this;
    }

    /**
     * @param array $column
     *
     * @return $this
     */
    public function returning(array $column = ['*'])
    {
        $this->returning = $column;

        return $this;
    }

    /**
     * Returns true if Laravel or Lumen >= 5.3
     *
     * @return bool
     */
    protected function shouldUseCollections()
    {
        if (function_exists('app')) {
            $version = app()->version();
            $version = filter_var(
                explode(')', $version)[0],
                FILTER_SANITIZE_NUMBER_FLOAT,
                FILTER_FLAG_ALLOW_FRACTION
            ); // lumen

            return version_compare($version, '5.3', '>=');
        }

        return false;
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param string $type
     * @return $this
     */
    public function from($table, $as = null)
    {
        $this->from = $this->connection->getBucketName();
        $this->type = $table;

        if (! is_null($table)) {
            $this->where(Helper::TYPE_NAME, $table);
        }

        return $this;
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function forNestedWhere()
    {
        // ->from($this->from) is wrong, and ->from($this->type) is redundant in nested where
        return $this->newQuery()->from(null);
    }

    /**
     * Set the projections.
     *
     * @param array $columns
     * @return $this
     */
    public function project($columns)
    {
        $this->projections = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return \stdClass
     */
    public function getWithMeta($columns = ['*'])
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        /** @var Processor $processor */
        $processor = $this->processor;
        $results = $processor->processSelectWithMeta($this, $this->runSelectWithMeta());

        $this->columns = $original;

        if (isset($results->rows)) {
            $results->rows = collect($results->rows);
        } else {
            $reflection = new \ReflectionProperty($results, 'rows');
            $reflection->setAccessible(true);
            $reflection->setValue($results, collect());
        }

        return $results;
    }

    /**
     * Set the cursor timeout in seconds.
     *
     * @param int $seconds
     * @return $this
     */
    public function timeout($seconds)
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set the cursor hint.
     *
     * @param mixed $index
     * @return $this
     */
    public function hint($index)
    {
        $this->hint = $index;

        return $this;
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param mixed $id
     * @param array $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        if (is_array($id) === true) {
            return $this->useKeys($id)->get($columns);
        }

        return $this->useKeys($id)->first($columns);
    }

    /**
     * Generate the unique cache key for the current query.
     *
     * @return string
     */
    public function generateCacheKey()
    {
        $key = [
            'bucket' => $this->from,
            'type' => $this->type,
            'wheres' => $this->wheres,
            'columns' => $this->columns,
            'groups' => $this->groups,
            'orders' => $this->orders,
            'offset' => $this->offset,
            'limit' => $this->limit,
            'aggregate' => $this->aggregate,
        ];

        return md5(serialize(array_values($key)));
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param string $function
     * @param array $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        // added orders to ignore...
        $results = $this->cloneWithout(['orders', 'columns'])
            ->cloneWithoutBindings(['select'])
            ->setAggregate($function, $columns)
            ->get($columns);

        if (! $results->isEmpty()) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        return ! is_null($this->first([Grammar::VIRTUAL_META_ID_COLUMN]));
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string|\Illuminate\Database\Query\Expression  $column
     * @param  iterable  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        $type = 'between';

        $this->wheres[] = compact('column', 'type', 'boolean', 'values', 'not');

        $this->addBinding($values, 'where');

        return $this;
    }

    /**
     * Set the bindings on the query builder.
     *
     * @param array $bindings
     * @param string $type
     * @throws \InvalidArgumentException
     * @return $this
     *
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        return parent::setBindings($bindings, $type);
    }

    /**
     * Add a binding to the query.
     *
     * @param mixed $value
     * @param string $type
     * @throws \InvalidArgumentException
     * @return $this
     *
     */
    public function addBinding($value, $type = 'where')
    {
        return parent::addBinding($value, $type);
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function forPage($page, $perPage = 15)
    {
        $this->paginating = true;

        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Insert a new record into the database.
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will have to detect
        // if the user is inserting a single document or an array of documents.
        $batch = true;

        foreach ($values as $key => $value) {
            // As soon as we find a value that is not an array we assume the user is
            // inserting a single document.
            if (! is_array($value) || is_string($key)) {
                $batch = false;

                break;
            }
        }

        if (is_null($this->keys)) {
            $this->useKeys(Helper::getUniqueId($this->type));
        }

        if ($batch) {
            foreach ($values as &$value) {
                $value[Helper::TYPE_NAME] = $this->type;
                $key = Helper::getUniqueId($this->type);
                $result = $this
                    ->connection
                    ->getBucket()
                    ->defaultCollection()
                    ->upsert($key, Grammar::removeMissingValue($value));
            }
        } else {
            $values[Helper::TYPE_NAME] = $this->type;
            $result = $this
                ->connection
                ->getBucket()
                ->defaultCollection()
                ->upsert($this->keys, Grammar::removeMissingValue($values));
        }

        return $result;
    }

    /**
     * Update a record in the database.
     *
     * @param array $values
     * @return int
     */
    public function update(array $values)
    {
        // replace MissingValue in 2nd or deeper levels
        foreach ($values as $key => $value) {
            $values[$key] = Grammar::removeMissingValue($value);
        }

        return parent::update($values);
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param array $values
     * @param string $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        if (! is_null($sequence) && isset($values[$sequence])) {
            $this->useKeys((string) $values[$sequence]);
        } elseif (isset($values['_id'])) {
            $this->useKeys((string) $values['_id']);
        }
        $this->insert($values);

        return $this->keys;
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        $results = $this->get(is_null($key) ? [$column] : [$column, $key]);

        // Convert ObjectID's to strings
        if ($key == '_id') {
            $results = $results->map(function ($item) {
                $item['_id'] = (string) $item['_id'];

                return $item;
            });
        }

        $p = Arr::pluck($results, $column, $key);

        return $this->useCollections ? new Collection($p) : $p;
    }

    /**
     * Run a truncate statement on the table.
     */
    public function truncate()
    {
        return $this->delete();
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string $key
     * @return array
     * @deprecated
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key);
    }

    /**
     * Append one or more values to an array.
     *
     * @param mixed $column
     * @param mixed $value
     * @param bool $unique
     *
     * @return array|\Couchbase\Document
     */
    public function push($column, $value = null, $unique = false)
    {
        $obj = $this->connection->getBucket()->get($this->keys);

        if (! isset($obj->value->{$column})) {
            $obj->value->{$column} = [];
        }

        if (is_array($value) && count($value) === 1) {
            $obj->value->{$column}[] = reset($value);
        } else {
            $obj->value->{$column}[] = $value;
        }

        if ($unique) {
            $array = array_map('json_encode', $obj->value->{$column});
            $array = array_unique($array);
            $obj->value->{$column} = array_map('json_decode', $array);
        }

        return $this->connection->getBucket()->upsert($this->keys, $obj->value);
    }

    /**
     * Remove one or more values from an array.
     *
     * @param mixed $column
     * @param mixed $value
     *
     * @throws Exception
     * @return array|\Couchbase\Document|null
     */
    public function pull($column, $value = null)
    {
        try {
            $obj = $this->connection->getBucket()->get($this->keys);
        } catch (Exception $e) {
            if ($e->getCode() === COUCHBASE_KEY_ENOENT) {
                trigger_error(
                    'Tying to pull a value from non existing document ' . json_encode($this->keys) . '.',
                    E_USER_WARNING
                );

                return null;
            }

            throw $e;
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        if (! isset($obj->value->{$column})) {
            trigger_error(
                'Tying to pull a value from non existing column ' . json_encode($column) . ' in document ' . json_encode($this->keys) . '.',
                E_USER_WARNING
            );

            return null;
        }
        $filtered = collect($obj->value->{$column})->reject(function ($val, $key) use ($value) {
            $match = false;

            if (is_object($val)) {
                foreach ($value as $matchKey => $matchValue) {
                    if ($val->{$matchKey} === $value[$matchKey]) {
                        $match = true;
                    }
                }
            } else {
                $match = in_array($val, $value);
            }

            return $match;
        });
        $obj->value->{$column} = $filtered->flatten()->toArray();

        return $this->connection->getBucket()->upsert($this->keys, $obj->value);
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param array $bindings
     * @return array
     */
    public function cleanBindings(array $bindings): array
    {
        return array_values(array_filter(
            parent::cleanBindings($bindings),
            function ($binding) {
                return ! ($binding instanceof MissingValue);
            }
        ));
    }

    /**
     * Remove one or more fields.
     *
     * @param mixed $columns
     * @return int
     */
    public function drop($columns)
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $query = $this->getGrammar()->compileUnset($this, $columns);
        $bindings = $this->getBindings();

        return $this->connection->update($query, $bindings);
    }

    /**
     * @return Grammar
     */
    public function getGrammar(): Grammar
    {
        return parent::getGrammar();
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return new Builder($this->connection, $this->grammar, $this->processor);
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return \stdClass
     */
    protected function runSelectWithMeta()
    {
        return $this->connection->selectWithMeta(
            $this->toSql(),
            $this->getBindings(),
            ! $this->useWritePdo
        );
    }

    /**
     * Convert a key to ObjectID if needed.
     *
     * @param mixed $id
     * @return mixed
     */
    public function convertKey($id)
    {
        return $id;
    }

    /**
     * Add a FOR ... IN query
     *
     * @param string $column
     * @param mixed $value
     * @param string $alias
     * @param array $values
     * @throws \InvalidArgumentException
     * @return \Illuminate\Database\Query\Builder|static
     *
     */
    public function forIn($column, $value, $alias, $values)
    {
        $this->forIns[] = compact('column', 'value', 'alias', 'values');

        return $this;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @throws \InvalidArgumentException
     * @return \Illuminate\Database\Query\Builder|static
     *
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === '_id') {
            $column = $this->grammar->getMetaIdExpression($this);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param string $sql
     * @param mixed $bindings
     * @param string $boolean
     * @return $this
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean, 'bindings' => $bindings];

        $this->addBinding((array) $bindings, 'where');

        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        if ($column === '_id') {
            if ($not) {
                // the meta().id of a document is never null
                // so where condition "meta().id is not null" makes no changes to the result
                return $this;
            }
            $column = $this->grammar->getMetaIdExpression($this);
        }

        return parent::whereNull($column, $boolean, $not);
    }

    /**
     * Add a "where is missing" clause to the query.
     * In other words, no value for field found
     *
     * @param  string|array  $columns
     * @param string $boolean
     * @param bool $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereIsMissing($columns, $boolean = 'and', $not = false)
    {
        $type = $not ? 'IsNotMissing' : 'IsMissing';

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * Add a "where is not missing" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereIsNotMissing($columns, $boolean = 'and')
    {
        return $this->whereIsMissing($columns, $boolean, true);
    }

    /**
     * Add a "where is valued" clause to the query.
     * In other words, value for field found. Value is neither missing nor NULL
     *
     * @param  string|array  $columns
     * @param string $boolean
     * @param bool $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereIsValued($columns, $boolean = 'and', $not = false)
    {
        $type = $not ? 'IsNotValued' : 'IsValued';

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * Add a "where is not valued" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereIsNotValued($columns, $boolean = 'and')
    {
        return $this->whereIsValued($columns, $boolean, true);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @return $this
     */
    public function whereAnyIn($column, $values, $boolean = 'and')
    {
        $type = 'AnyIn';

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        foreach ($values as $value) {
            if (! $value instanceof Expression) {
                $this->addBinding($value, 'where');
            }
        }

        return $this;
    }

    /**
     * Set custom options for the query.
     *
     * @param array $options
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string|int|array $values
     *
     * @return array
     */
    protected function detectValues($values)
    {
        foreach ($values as &$value) {
            $value[Helper::TYPE_NAME] = $this->type;
        }

        return [$values];
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method == 'unset') {
            return call_user_func_array([$this, 'drop'], $parameters);
        }

        return parent::__call($method, $parameters);
    }
}
