# simplesamlphp-module-accounting
SimpleSAMLphp module providing user accounting functionality.

## Features
- enables tracking of authentication events, synchronously (during authentication event) or
asynchronously (in a separate process using SimpleSAMLphp Cron feature)
- provides endpoints for end users to check their personal data, summary on connected
Service Providers, and list of authentication events

## Installation
Module is installable using Composer:
```shell
composer require cicnavi/simplesamlphp-module-accounting
```

## Configuration


## TODO
- [ ] Translation

## Tests
To run phpcs, psalm and phpunit:

```shell
composer pre-commit
```
