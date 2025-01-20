[![Test](https://github.com/cicnavi/simplesamlphp-module-profilepage/actions/workflows/test.yml/badge.svg)](https://github.com/cicnavi/simplesamlphp-module-profilepage/actions/workflows/test.yml)

# simplesamlphp-module-profilepage
SimpleSAMLphp module providing user "Profile Page" and profilepage functionality using SimpleSAMLphp authentication
processing filters feature.

## Features
- Enables tracking of authentication events, synchronously (during authentication event) or
asynchronously (in a separate process using SimpleSAMLphp Cron feature)
- Provides endpoints for end users to check their personal data, summary on connected
Service Providers, and list of authentication events
- Comes with default [DBAL backend storage](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html),
meaning the following database vendors can be used: MySQL, Oracle, Microsoft SQL Server, PostgreSQL, SQLite. Other
backend storages can be added by following proper interfaces.
- Comes with setup procedure which sets up backend storage. In case of Doctrine DBAL this means running SQL migrations
which create proper tables in configured database.
- Each backend storage connection can have master and slave configuration (master for writing, slave for reading)
- Has tracking functionality available which persist authentication data to backend storage. Currently, module can
track connected services and authentication events. Other trackers can be added by following proper interfaces.
- Tracking can run in two ways:
  - synchronously - authentication data persisted during authentication event typically with multiple
  queries / inserts / updates to backend storage.
  - asynchronously - only authentication event job is persisted during authentication event
  (one insert to backend storage). With this approach, authentication event jobs can be executed later in a separate
  process using SimpleSAMLphp cron module

## Installation
Version compatibility:

| profilepage | Tested SimpleSAMLphp |  PHP   | Note                        |
|:------------|:---------------------|:------:|-----------------------------|
| v2.\*       | v2.3.\*              | \>=8.1 | Recommended                 |
| v1.\*       | v2.0.\*              | \>=8.0 |                             |

Module is installable using Composer:

```shell
composer require cicnavi/simplesamlphp-module-profilepage
```

In config.php, search for the "module.enable" key and set 'profilepage' to true:

```php
// ...
'module.enable' => [
    'profilepage' => true,
    // ...
],
// ...
```

Depending on used features, module also requires:
- ext-redis: if PhpRedis is to be used as a store

## Configuration
As usual with SimpleSAMLphp modules, copy the module template configuration
to the SimpleSAMLphp config directory:

```shell
cp modules/profilepage/config-templates/module_profilepage.php config/
```

Next step is to configure available options in file config/module_profilepage.php. Each option has an explanation,
however, the description of the overall concept follows.

Module can be configured to only show current user data, with no accounting taking place. However, module can be
configured to track the following data:
* Connected organizations - by setting the class ModuleConfiguration::OPTION_PROVIDER_FOR_CONNECTED_SERVICES option. 
* Activity - by setting the class for ModuleConfiguration::OPTION_PROVIDER_FOR_ACTIVITY option.

Module comes with some Doctrine DBAL capable classes which can be used for those purposes. Here is an example config
excerpt which will enable storing current (latest) data for connected services and versioned data 
for authentication events, including versioned Idp and SP metadata, and versioned user attributes:

```php
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Data\Trackers;
use SimpleSAML\Module\profilepage\Data\Providers;

// ...
ModuleConfiguration::OPTION_PROVIDER_FOR_CONNECTED_SERVICES =>
    Providers\ConnectedServices\DoctrineDbal\CurrentDataProvider::class,
ModuleConfiguration::OPTION_PROVIDER_FOR_ACTIVITY =>
        Providers\Activity\DoctrineDbal\VersionedDataProvider::class,
// ...
```

### Processing type

The deployer can choose if the accounting processing will be performed during authentication event (synchronously),
or in a separate process (asynchronously), for example:

```php
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\ModuleConfiguration\AccountingProcessingType;

// ...
ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
    ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS,
// ...
```

If the processing type is asynchronous, then the deployer must also configure the job store related options:
- Jobs store class which will be used to store and fetch jobs from the backend store
- Accounting cron tag for job runner
- Cron module configuration (if the used tag is different from the ones available in cron module, which is the case
by default)

For each tracker or job store, the "connection key" must be set. Connection key determines which connection
parameters will be forwarded for tracker / job store initialization process.

Also review / edit all other configuration options, and set appropriate values. 

### Running Setup

After you have configured everything in config/module_profilepage.php, go to the SimpleSAMLphp Admin > Configuration
Page. There you will find a link "Profile Page configuration status", which will take you on the 
module configuration overview page.

If the configured trackers / jobs store require any setup, you will see a "Run Setup" button, so go ahead
and click it. In the case of default Doctrine DBAL tracker / jobs store, the setup will run all migration
classes used to create necessary tables in the database.

When the setup is finished, you'll be presented with the "Profile Page" link, which can be used by end
users to see their activity.

### Adding Authentication Processing Filter
Last step to start tracking user data using the configured tracker classes / jobs store is to add an [authentication
processing filter](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc.html) from the profilepage module
to the right place in SimpleSAMLphp configuration. Here is an example of setting it globally for all IdPs 
in config/config.php:

```php
// ...
'authproc.idp' => [
        // ... 
        1000 => 'profilepage:Accounting',
    ],
// ...
```
## Job Runner
If accounting processing is asynchronous, a job runner will have to be used in order to process jobs that have
been created during authentication events.

Job runner can be executed using [SimpleSAMLphp Cron module](https://github.com/simplesamlphp/simplesamlphp/blob/master/modules/cron/docs/cron.md).
As you can see in Cron documentation, a cron tag can be invoked using HTTP or CLI. When it comes to Job Runner, using
CLI is the preferred way, since the job runner can run in a long-running fashion, even indefinitely. However,
you are free to test execution using the HTTP version, in which case the maximum job runner execution time
will correspond to the 'max_execution_time' INI setting. 

Only one job runner instance can run at given point in time. By maintaining internal state, job runner can first check
if there is another job runner active. If yes, the latter will simply exit and let the active job runner do its work.
This way one is free to invoke the cron tag at any time, since only one job runner will ever be active.

## OpendID Connect integration
This module can also be used as an authentication processing filter for OIDC module 
https://github.com/simplesamlphp/simplesamlphp-module-oidc, meaning it can also track OIDC authentication events, 
Also, if connected services option is enabled, a user will be able to revoke any active access / refresh tokens 
for particular service in the user interface.

Accounting authentication processing filter can be added in the OIDC module configuration, as per OIDC module
documentation.

## Tests
To run phpcs, psalm and phpunit:

```shell
composer pre-commit
```