<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Module\accounting\Helpers\Arr;
use SimpleSAML\Module\accounting\Helpers\Attributes;
use SimpleSAML\Module\accounting\Helpers\AuthenticationEventStateResolver;
use SimpleSAML\Module\accounting\Helpers\DateTime;
use SimpleSAML\Module\accounting\Helpers\Environment;
use SimpleSAML\Module\accounting\Helpers\Filesystem;
use SimpleSAML\Module\accounting\Helpers\Hash;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfiguration;
use SimpleSAML\Module\accounting\Helpers\Network;
use SimpleSAML\Module\accounting\Helpers\ProviderResolver;
use SimpleSAML\Module\accounting\Helpers\Random;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutes;

class HelpersManager
{
    protected static ?DateTime $dateTime;
    protected static ?Environment $environment;
    protected static ?Random $random;
    protected static ?ModuleRoutes $routes;
    protected static ?Arr $arr;
    protected static ?Hash $hash;
    protected static ?Attributes $attributes;
    protected static ?Filesystem $filesystem;
    protected static ?InstanceBuilderUsingModuleConfiguration $instanceBuilder;
    protected static ?Network $network;
    protected static ?AuthenticationEventStateResolver $authenticationEventStateResolver;
    protected static ?ProviderResolver $providerResolver;


    public function getDateTime(): DateTime
    {
        return self::$dateTime ??= new DateTime();
    }

    public function getEnvironment(): Environment
    {
        return self::$environment ??= new Environment();
    }

    public function getRandom(): Random
    {
        return self::$random ??= new Random();
    }

    public function getModuleRoutes(): ModuleRoutes
    {
        return self::$routes ??= new ModuleRoutes();
    }

    public function getArr(): Arr
    {
        return self::$arr ??= new Arr();
    }

    public function getHash(): Hash
    {
        return self::$hash ??= new Hash($this->getArr());
    }

    public function getAttributes(): Attributes
    {
        return self::$attributes ??= new Attributes();
    }

    public function getFilesystem(): Filesystem
    {
        return self::$filesystem ??= new Filesystem();
    }

    public function getInstanceBuilderUsingModuleConfiguration(): InstanceBuilderUsingModuleConfiguration
    {
        return self::$instanceBuilder ??= new InstanceBuilderUsingModuleConfiguration();
    }

    public function getNetwork(): Network
    {
        return self::$network ??= new Network();
    }

    public function getAuthenticationEventStateResolver(): AuthenticationEventStateResolver
    {
        return self::$authenticationEventStateResolver ??= new AuthenticationEventStateResolver();
    }

    public function getProviderResolver(): ProviderResolver
    {
        return self::$providerResolver ??= new ProviderResolver();
    }
}
