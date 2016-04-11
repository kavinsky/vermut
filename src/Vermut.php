<?php

namespace Kavinsky\Vermut;

use Illuminate\Contracts\Redis\Database as RedisContract;
use Illuminate\Support\Arr;

class Vermut
{
    const GRANULARITY_Y = 0;
    const GRANULARITY_M = 1;
    const GRANULARITY_D = 2;
    const GRANULARITY_H = 3;
    const GRANULARITY_I = 4;

    /**
     * Granularity levels
     *
     * @var array
     */
    protected static $granularity = [
        'y',
        'y-m',
        'y-m-d',
        'y-m-d-H',
        'y-m-d-H-i',
    ];

    /**
     * @var RedisContract
     */
    protected $redis;

    /**
     * The Upstream command queue to be pipelined
     *
     * @var array
     */
    protected $operation_queue = [];

    /**
     * @var array
     */
    protected $event_dictionary = [];

    /**
     * Vermut constructor.
     * @param RedisContract $redis
     * @param array $options
     */
    public function __construct(RedisContract $redis, array $options)
    {
        $this->redis = $redis;
        $this->prefix = Arr::pull($options, 'prefix', 'vermut');
        $this->event_dictionary = Arr::pull($options, 'events', []);
    }

    /**
     * Mark a unique event
     *
     * @param string $event
     * @param integer $id
     * @param int $level
     */
    public function mark($event, $id, $level = self::GRANULARITY_I)
    {
        $level = $this->defaultGranularity($event, $level);

        $granularity = 0;
        while ($granularity <= $level) {
            $key = $this->makeKey('mark:'.$event.':'.$this->makeGranularity($granularity));

            $this->pushOperation('setbit', [$key, $id, 1]);
            $this->addToKeyDict($key);

            $granularity++;
        }
    }

    /**
     * Increment a event count
     *
     * @param string $event
     * @param int $count
     * @param int $level
     */
    public function incr($event, $count = 1, $level = self::GRANULARITY_I)
    {
        $level = $this->defaultGranularity($event, $level);

        $granularity = 0;
        while ($granularity <= $level) {
            $key = $this->makeKey('atom:'.$event.':'.$this->makeGranularity($granularity));

            if ($count > 1) {
                $this->pushOperation('incrby', [$key, $count]);
            } else {
                $this->pushOperation('incr', [$key]);
            }

            $this->addToKeyDict($key);

            $granularity++;
        }
    }

    /**
     * Decrement a event count
     *
     * @param string $event
     * @param int $count
     * @param int $level
     */
    public function decr($event, $count = 1, $level = self::GRANULARITY_I)
    {
        $level = $this->defaultGranularity($event, $level);

        $granularity = 0;
        while ($granularity <= $level) {
            $key = $this->makeKey('atom:'.$event.':'.$this->makeGranularity($granularity));

            if ($count > 1) {
                $this->pushOperation('decrby', [$key, $count]);
            } else {
                $this->pushOperation('decr', [$key]);
            }

            $this->addToKeyDict($key);

            $granularity++;
        }
    }

    /**
     * Pushes a redis command to be pipelining
     *
     * @param string $command command name
     * @param array $params Parameters of the command
     */
    protected function pushOperation($command, $params)
    {
        array_push($this->operation_queue, [$command, $params]);
    }

    /**
     * Add Key to Key dictionary
     *
     * @param string $key
     */
    protected function addToKeyDict($key)
    {
        // ill not use key dict for the moment
        // but just in case
        //$this->pushOperation('sadd', [$this->makeKey('keys'), $key]);
    }

    /**
     * Sent upstream queue
     * @param int $throttle
     * @return int
     */
    public function sentUpstreamOperations($throttle = 100)
    {
        $chunks = array_chunk($this->operation_queue, $throttle);
        $chunk_count = count($chunks);

        $operations = 0;

        for ($c = 0; $c < $chunk_count; $c++) {

            /** @var \Predis\Pipeline\Pipeline $pipe */
            $pipe = $this->redis->command('pipeline');

            for ($op = count($chunks[$c]); $op > 0; $op--) {
                list($command, $params) = array_shift($chunks[$c]);

                call_user_func_array([$pipe, $command], $params);
                $operations++;
            }

            $pipe->execute();
        }

        $this->operation_queue = [];

        return $operations;
    }

    /**
     * Makes a key
     *
     * @param $key
     * @return string
     */
    protected function makeKey($key)
    {
        return $this->prefix.':'.$key;
    }

    /**
     * Makes a datetime string from a granularity level
     *
     * @param int $level
     * @return string
     */
    protected function makeGranularity($level)
    {
        return date(static::$granularity[$level]);
    }

    /**
     * Get default granularity for event name
     *
     * @param $event
     * @param $default
     * @return int
     */
    protected function defaultGranularity($event, $default)
    {
        if (array_key_exists($event, $this->event_dictionary)) {
            return array_get($this->event_dictionary[$event], 'granularity', $default);
        }

        return $default;
    }
}
