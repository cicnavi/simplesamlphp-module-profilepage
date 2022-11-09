#!/usr/bin/env php
<?php
// TODO mivanci remove this file
declare(strict_types=1);

use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\Job;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\Repository;
use SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis\RedisStore;

require 'vendor/autoload.php';

$helpersManager = new HelpersManager();

$start = new DateTime();

$newLine = "\n";

echo "Start: " . $start->format(DateTime::ATOM);
echo $newLine;

$job = new Job(new Event(new State(SimpleSAML\Test\Module\accounting\Constants\StateArrays::FULL)));

$options = getopt('c:');

$numberOfItems = $options['c'] ?? 1000;

echo 'Number of items: ' . $numberOfItems;
echo $newLine;

$spinnerChars = ['|', '/', '-', '\\'];

/**
echo 'Starting simulating MySQL: ';
$mysqlStartTime = new DateTime();
echo $mysqlStartTime->format(DateTime::ATOM);
echo $newLine;

$mysqlParameters = [
    'driver' => 'pdo_mysql', // (string): The built-in driver implementation to use
    'user' => 'apps', // (string): Username to use when connecting to the database.
    'password' => 'apps', // (string): Password to use when connecting to the database.
    'host' => '127.0.0.1', // (string): Hostname of the database to connect to.
    'port' => 33306, // (integer): Port of the database to connect to.
    'dbname' => 'accounting', // (string): Name of the database/schema to connect to.
    //'unix_socket' => 'unix_socet', // (string): Name of the socket used to connect to the database.
    'charset' => 'utf8', // (string): The charset used when connecting to the database.
    //'url' => 'mysql://user:secret@localhost/mydb?charset=utf8', // ...alternative way of providing parameters.
    // Additional parameters not originally avaliable in Doctrine DBAL
    'table_prefix' => '', // (string): Prefix for each table.
];

$logger = new Logger();

$jobsStoreRepository = new Repository(new Connection($mysqlParameters), 'job', $logger);
$mysqlDurationInSeconds = (new DateTime())->getTimestamp() - $mysqlStartTime->getTimestamp();
$mysqlItemsInCurrentSecond = 0;
$mysqlItemsPerSecond = [];
for ($i = 1; $i <= $numberOfItems; $i++) {
    $mysqlUpdatedDurationInSeconds = (new DateTime())->getTimestamp() - $mysqlStartTime->getTimestamp();
    if ($mysqlDurationInSeconds === $mysqlUpdatedDurationInSeconds) {
        $mysqlItemsInCurrentSecond++;
    } else {
        $mysqlItemsPerSecond[] = $mysqlItemsInCurrentSecond;
        $mysqlItemsInCurrentSecond = 0;
    }
    $mysqlItemsInCurrentSecond = $mysqlDurationInSeconds === $mysqlUpdatedDurationInSeconds ?
        $mysqlItemsInCurrentSecond++ : 0;
    $mysqlDurationInSeconds = (new DateTime())->getTimestamp() - $mysqlStartTime->getTimestamp();

    $mysqlItemsPerSeconds = count($mysqlItemsPerSecond) ?
        array_sum($mysqlItemsPerSecond) / count($mysqlItemsPerSecond) : 0;
    $mysqlPercentage = $i / $numberOfItems  * 100;
    $spinnerChar = $spinnerChars[array_rand($spinnerChars)];
    $line = sprintf(
        '%1$s percentage: %2$ 3d%%, items/s: %3$04d, duration: %4$ss',
        $spinnerChar, $mysqlPercentage, $mysqlItemsPerSeconds, $mysqlDurationInSeconds
    );
    echo $line;
    echo "\r";
    $jobsStoreRepository->insert($job);
}
echo $newLine;
echo $newLine;

*/
echo 'Starting simulating Redis: ';
$redisStartTime = new DateTime();
echo $redisStartTime->format(DateTime::ATOM);
echo $newLine;

$redisClient = new Redis();
$redisClient->connect(
    '127.0.0.1',
    6379,
    1,
    null,
    500,
    1
);
$redisClient->auth('apps');
$redisClient->setOption(Redis::OPT_PREFIX, 'ssp_accounting:');


$redisDurationInSeconds = (new DateTime())->getTimestamp() - $redisStartTime->getTimestamp();
$redisItemsInCurrentSecond = 0;
$redisItemsPerSecond = [];
for ($i = 1; $i <= $numberOfItems; $i++) {
    $redisUpdatedDurationInSeconds = (new DateTime())->getTimestamp() - $redisStartTime->getTimestamp();
    if ($redisDurationInSeconds === $redisUpdatedDurationInSeconds) {
        $redisItemsInCurrentSecond++;
    } else {
        $redisItemsPerSecond[] = $redisItemsInCurrentSecond;
        $redisItemsInCurrentSecond = 0;
    }
    $redisItemsInCurrentSecond = $redisDurationInSeconds === $redisUpdatedDurationInSeconds ?
        $redisItemsInCurrentSecond++ : 0;

    $redisDurationInSeconds = $redisUpdatedDurationInSeconds;

    $redisItemsPerSeconds = count($redisItemsPerSecond) ?
        array_sum($redisItemsPerSecond) / count($redisItemsPerSecond) : 0;
    $redisPercentage = $i / $numberOfItems  * 100;
    $spinnerChar = $spinnerChars[array_rand($spinnerChars)];
    $line = sprintf(
        '%1$s percentage: %2$ 3d%%, items/s: %3$04d, duration: %4$ss',
        $spinnerChar, $redisPercentage, $redisItemsPerSeconds, $redisDurationInSeconds
    );
    echo $line;
    echo "\r";
    $redisClient->rPush(RedisStore::LIST_KEY_JOB . ':' . sha1($job->getType()), serialize($job));
//    $redisClient->rPush(RedisStore::LIST_KEY_JOB, serializgit add .e($job));
}
echo $newLine;
echo 'End: ' . (new DateTime())->format(DateTime::ATOM);
echo $newLine;