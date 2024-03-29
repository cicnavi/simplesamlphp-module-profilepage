#!/usr/bin/env php
<?php

declare(strict_types=1);

//exit(1); // This script was used to generate fake data...

use SimpleSAML\Module\profilepage\Data\Trackers\Activity\DoctrineDbal\CurrentDataTracker as ActivityTracker;
use SimpleSAML\Module\profilepage\Data\Trackers\ConnectedServices\DoctrineDbal\CurrentDataTracker as ConnectedServicesTracker;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\Logger;


require 'vendor/autoload.php';

$helpersManager = new HelpersManager();
$logger = new Logger();
$configDir = __DIR__ . '/config';
putenv("SIMPLESAMLPHP_CONFIG_DIR=$configDir");
$moduleConfiguration = new ModuleConfiguration();
$sampleUsers = include 'sampleUsers.php';
/** @noinspection PhpUnhandledExceptionInspection */
$activityTracker = new ActivityTracker($moduleConfiguration, $logger);
/** @noinspection PhpUnhandledExceptionInspection */
$connectedServicesTracker = new ConnectedServicesTracker($moduleConfiguration, $logger);
$start = new DateTime();
$newLine = "\n";

echo "Start: " . $start->format(DateTimeInterface::ATOM);
echo $newLine;

$options = getopt('c:o:a::');

$spMetadata = [];

if (isset($options['c'])) {
    $numberOfItems = max((int) $options['c'], 100);
    /** @noinspection PhpUnhandledExceptionInspection */
    doSampleUsers($numberOfItems, $spMetadata, $sampleUsers, $activityTracker, $connectedServicesTracker);
}

if (isset($options['o'])) {
    $numberOfUsers = max((int) $options['o'], 100);
    $numberOfAuthentications = (int) ($options['a'] ?? 1000);
    /** @noinspection PhpUnhandledExceptionInspection */
    doRandomUsers($numberOfUsers, $numberOfAuthentications, $spMetadata, $activityTracker, $connectedServicesTracker);
}


echo $newLine;
echo 'End: ' . (new DateTime())->format(DateTimeInterface::ATOM);
echo $newLine;

/**
 * @throws StoreException
 * @throws \Doctrine\DBAL\Exception
 */
function doSampleUsers(
        int $numberOfAuthentications,
        array &$spMetadata,
        $sampleUsers,
        ActivityTracker $activityTracker,
        ConnectedServicesTracker $connectedServicesTracker
): void
{
    echo "Doing $numberOfAuthentications authentications for sample users. \n";

    if (count($spMetadata) < 100) {
        $spMetadata[] = prepareSampleSpMetadata();
    }
    $happenedAt = new DateTimeImmutable('-12 months');

    for ($i = 1; $i <= $numberOfAuthentications; $i++) {
        printSingleLine('Doing item ' . $i);

        if ($i % ((int)($numberOfAuthentications / 100)) === 0) {
            $happenedAt = $happenedAt->add(new DateInterval('P1D'));
            if (count($spMetadata) < 100) {
                $spMetadata[] = prepareSampleSpMetadata();
            }
        }


        $event = prepareEvent(
            Saml2::DESIGNATION,
            $sampleUsers[array_rand($sampleUsers)],
            $spMetadata[array_rand($spMetadata)],
            $happenedAt
        );

        $activityTracker->process($event);
        $connectedServicesTracker->process($event);

        $happenedAt = $happenedAt->add(new DateInterval('PT1H'));
    }
}


/**
 * @throws StoreException
 * @throws \Doctrine\DBAL\Exception
 */
function doRandomUsers(
    int $numberOfUsers,
    int $numberOfAuthentications,
    array $spMetadata,
    ActivityTracker $activityTracker,
    ConnectedServicesTracker $connectedServicesTracker
): void
{
    echo "Doing $numberOfUsers random users with $numberOfAuthentications authentications.\n";

    while (count($spMetadata) < 100) {
        $spMetadata[] = prepareSampleSpMetadata();
    }

    for ($i = 0; $i < $numberOfUsers; $i++) {
        $randomUser = prepareRandomUser();

        if (count($spMetadata) < 500) {
            $spMetadata[] = prepareSampleSpMetadata();
        }

        $startForUser = new DateTimeImmutable();

        $happenedAt = new DateTimeImmutable('-12 months');

        for($j = 0; $j < $numberOfAuthentications; $j++) {
            if ($numberOfAuthentications >= 500 && ($j % 500) === 0) {
                versionUser($randomUser);
            }

            printSingleLine('Doing user ' . $i . ', authentication ' . $j);

            if ($i % ((int)($numberOfAuthentications / 10)) === 0) {
                $happenedAt = $happenedAt->add(new DateInterval('P1D'));
            }

            $event = prepareEvent(
                Saml2::DESIGNATION,
                $randomUser,
                $spMetadata[array_rand($spMetadata)],
                $happenedAt
            );

            $activityTracker->process($event);
            $connectedServicesTracker->process($event);

            $happenedAt = $happenedAt->add(new DateInterval('PT1H'));
        }
        echo "\n";
        echo 'Done in ' . ((new DateTimeImmutable())->getTimestamp() - $startForUser->getTimestamp()) . " seconds.\n";
    }
}

function prepareRandomUser(): array
{
    $faker = Faker\Factory::create();
    $firstName = $faker->firstName();
    $lastName = $faker->lastName();
    $userName = strtolower(str_replace(' ', '', $firstName . '.' . $lastName));

    return [
        'uid' => [$userName],
        'sn' => [$lastName],
        'givenName' => [$firstName],
        'mail' => [$userName . '@' . $faker->domainName()],
        'hrEduPersonPersistentID' => [$faker->regexify('[A-Z0-9]{64}')],
    ];
}

function versionUser(array &$user): void
{
    $faker = Faker\Factory::create();
    // Simulate addition of another email address.
    $user['mail'][] = $faker->email();
}

function printSingleLine(string $message): void
{
    $spinnerChars = ['|', '/', '-', '\\'];
    $spinnerChar = $spinnerChars[array_rand($spinnerChars)];

    $line = sprintf('%1$s ' . $message, $spinnerChar);
    echo $line;
    echo "\r";
}

function prepareEvent(
    string $protocol = Saml2::DESIGNATION,
    array $userAttributes = null,
    $spMetadata = null,
    DateTimeImmutable $happenedAt = null
): Event {
    $happenedAt = $happenedAt ?? new DateTimeImmutable();

    if ($protocol == Saml2::DESIGNATION) {
        $state = SimpleSAML\Test\Module\profilepage\Constants\StateArrays::SAML2_FULL;
        if ($userAttributes) {
            $state['Attributes'] = $userAttributes;
        }
        if ($spMetadata) {
            $state['SPMetadata'] = $spMetadata;
            $state['Destination'] = $spMetadata;
        }

        return new Event(new Event\State\Saml2($state), $happenedAt);
    }

    $state = SimpleSAML\Test\Module\profilepage\Constants\StateArrays::OIDC_FULL;
    if ($userAttributes) {
        $state['Attributes'] = $userAttributes;
    }
    if ($spMetadata) {
        $state['Oidc']['RelyingPartyMetadata'] = $spMetadata;
    }

    return new Event(new Event\State\Oidc($state));
}


function prepareSampleSpMetadata(string $protocol = Saml2::DESIGNATION)
{
    $faker = Faker\Factory::create();

    if ($protocol == Saml2::DESIGNATION) {
        $spMetadata = SimpleSAML\Test\Module\profilepage\Constants\StateArrays::SAML2_FULL['SPMetadata'];

        $spMetadata['entityid'] = $faker->url();
        $spMetadata['name'] = $faker->company();

        return $spMetadata;
    }

    $rpMetadata = SimpleSAML\Test\Module\profilepage\Constants\StateArrays::OIDC_FULL['Oidc']['RelyingPartyMetadata'];

    $rpMetadata['id'] = $faker->uuid();
    $rpMetadata['name'] = $faker->company();

    return $rpMetadata;
}
