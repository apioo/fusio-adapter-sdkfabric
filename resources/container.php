<?php

use Fusio\Adapter\SdkFabric\Connection\Airtable;
use Fusio\Adapter\SdkFabric\Connection\Discord;
use Fusio\Adapter\SdkFabric\Connection\Notion;
use Fusio\Adapter\SdkFabric\Connection\Starwars;
use Fusio\Adapter\SdkFabric\Connection\Twitter;
use Fusio\Engine\Adapter\ServiceBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = ServiceBuilder::build($container);
    $services->set(Airtable::class);
    $services->set(Discord::class);
    $services->set(Notion::class);
    $services->set(Starwars::class);
    $services->set(Twitter::class);
};
