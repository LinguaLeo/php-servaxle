<?php

namespace LinguaLeo\Servaxle;

class ServiceContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testLazeBuilding()
    {
        $this->parameters = [
            'battle.fighter1' => MortalCombat\DebugFighter::class,
            'battle.fighter1$DebugFighter.name' => 'Sub-Zero',
            'battle.fighter1.name' => function() { return 'Baraka'; },
            'battle.fighter2.name' => 'Kung Lao',
            'battle.arena$LiveForest.random' => function () {
                var_dump('Not run');
            }
        ];

        $this->sharing = [
            'battle' => MortalCombat\Battle::class
        ];

        $this->interfaces = [
//            MortalCombat\ArenaInterface::class => MortalCombat\Arena\Portal::class
            MortalCombat\ArenaInterface::class => MortalCombat\Arena\LiveForest::class
        ];

        var_dump($this->newInstance(MortalCombat\Battle::class, 'battle'));
    }

    private function newInstance($class, $path = '')
    {
        $args = [];

        // @todo check reverse sharing

        if (is_string($class)) {
            $class = new \ReflectionClass($class);
        }

        if (!$class->isInstantiable()) {
            if (isset($this->interfaces[$class->name])) {
                return $this->newInstance($this->interfaces[$class->name], $path);
            } else {
                throw new \RuntimeException('Cannot to instantiate non instantiable class');
            }
        }

        $constructor = $class->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $localPath = $path.'.'.$parameter->name;
                $concreteLocalPath = $path.'$'.$class->getShortName().'.'.$parameter->name;

                if (isset($this->parameters[$concreteLocalPath])) {
                    $localParameter = $this->parameters[$concreteLocalPath];
                } elseif (isset($this->parameters[$localPath])) {
                    $localParameter = $this->parameters[$localPath];
                } else {
                    $localParameter = null;
                }

                if ($localParameter) {
                    if (is_string($localParameter) && class_exists($localParameter)) {
                        $localParameter = $this->newInstance($localParameter, $localPath);
                    } elseif (!$parameter->isCallable() && is_callable($localParameter)) {
                        $localParameter = $localParameter($this);
                    }
                    $args[] = $localParameter;
                } else {
                    $parameterClass = $parameter->getClass();
                    if (!$parameterClass) {
                        throw new \RuntimeException('Undefined parameter: '.$localPath);
                    }
                    $args[] = $this->newInstance($parameterClass, $localPath);
                }
            }
        }

        // @todo save reverse sharing

        return $class->newInstanceArgs($args);
    }

    public function testGetter()
    {
        $container = new ServiceContainer();

        $container->attach('foo', Provider\HelloServiceProvider::class);
        $container->attach('foo', Provider\WorldServiceProvider::class);

        $this->assertSame('Hello World!', $container->foo->message);
    }
}