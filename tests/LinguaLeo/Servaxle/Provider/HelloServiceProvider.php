<?php

namespace LinguaLeo\Servaxle\Provider;

use LinguaLeo\Servaxle\ServiceContainer;
use LinguaLeo\Servaxle\ServiceProviderInterface;

class HelloServiceProvider implements ServiceProviderInterface
{
    public function register(ServiceContainer $container)
    {
        $container->share('message', function () {
            return 'Hello';
        });
    }
}