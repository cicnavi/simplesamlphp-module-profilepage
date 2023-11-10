<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services;

use SimpleSAML\Module\profilepage\Helpers\Arr;
use SimpleSAML\Module\profilepage\Helpers\Attributes;
use SimpleSAML\Module\profilepage\Helpers\AuthenticationEventStateResolver;
use SimpleSAML\Module\profilepage\Helpers\DateTime;
use SimpleSAML\Module\profilepage\Helpers\Environment;
use SimpleSAML\Module\profilepage\Helpers\Filesystem;
use SimpleSAML\Module\profilepage\Helpers\Hash;
use SimpleSAML\Module\profilepage\Helpers\InstanceBuilderUsingModuleConfiguration;
use SimpleSAML\Module\profilepage\Helpers\Network;
use SimpleSAML\Module\profilepage\Helpers\ProviderResolver;
use SimpleSAML\Module\profilepage\Helpers\Random;
use SimpleSAML\Module\profilepage\Helpers\Routes;
use SimpleSAML\Module\profilepage\Helpers\SspModule;

class HelpersManager
{
    protected static ?DateTime $dateTime = null;
    protected static ?Environment $environment = null;
    protected static ?Random $random = null;
    protected static ?Routes $routes = null;
    protected static ?Arr $arr = null;
    protected static ?Hash $hash = null;
    protected static ?Attributes $attributes = null;
    protected static ?Filesystem $filesystem = null;
    protected static ?InstanceBuilderUsingModuleConfiguration $instanceBuilder = null;
    protected static ?Network $network = null;
    protected static ?AuthenticationEventStateResolver $authenticationEventStateResolver = null;
    protected static ?ProviderResolver $providerResolver = null;
    protected static ?SspModule $sspModule = null;


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

    public function getRoutes(): Routes
    {
        return self::$routes ??= new Routes();
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

    public function getSspModule(): SspModule
    {
        return self::$sspModule ??= new SspModule();
    }
}
