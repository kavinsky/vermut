<?php

require __DIR__."/../vendor/autoload.php";

date_default_timezone_set('Europe/Madrid');

use Illuminate\Redis\Database;
use Kavinsky\Vermut\Vermut;

$redis = new Database([
    'cluster' => false,
    'default' => [
        'host' => 'localhost',
        'password' => null,
        'port' => 6379,
        'database' => 0,
    ]
]);

$vermut = new Vermut($redis, [
    'prefix' => 'vermut',
    'events' => [
        'user:testing' => [
            'granularity' => Vermut::GRANULARITY_I,
        ]
    ]
]);

/**
 * Single operation
 */
$bench = new Ubench();
$bench->start();

$vermut->mark('user:logged', mt_rand(1, 500));
$vermut->sentUpstreamOperations();

$bench->end();

echo "Single operation: ".$bench->getTime().PHP_EOL;

/**
 * 100 Operation benchmark
 */
$bench = new Ubench();
$bench->start();

$i = 0;
while ($i < 100) {
    $vermut->mark('user:logged', mt_rand(1, 500));
    $i++;
}


$vermut->sentUpstreamOperations();
$bench->end();

echo "100 operations: ".$bench->getTime().PHP_EOL;

/**
 * Using presaved granularity
 */
$bench = new Ubench();
$bench->start();

$vermut->mark('user:testing', mt_rand(1, 500));
$vermut->sentUpstreamOperations();

$bench->end();

echo "Saving granularity on config: ".$bench->getTime().PHP_EOL;

/**
 * Single incr op
 */
$bench = new Ubench();
$bench->start();

$vermut->incr('user:visits');
$vermut->sentUpstreamOperations();

$bench->end();

echo "Single incr op: ".$bench->getTime().PHP_EOL;


/**
 * 100 incr op
 */
$bench = new Ubench();
$bench->start();

$i = 0;
while ($i < 100) {
    $vermut->incr('user:visits'.mt_rand(1,9));
    $i++;
}

$vermut->sentUpstreamOperations();

$bench->end();

echo "100 incr op: ".$bench->getTime().PHP_EOL;
