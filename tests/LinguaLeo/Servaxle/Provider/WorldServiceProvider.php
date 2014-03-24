<?php

namespace LinguaLeo\Servaxle\Provider;

use LinguaLeo\Servaxle\ServiceContainer;
use LinguaLeo\Servaxle\ServiceProviderInterface;

class WorldServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container)
    {
        $container->extend('message', function ($message) {
            return $message.' World!';
        });
    }
}