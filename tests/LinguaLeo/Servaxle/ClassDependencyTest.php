<?php

namespace LinguaLeo\Servaxle;

use LinguaLeo\Servaxle\MortalCombat\Battle;
use LinguaLeo\Servaxle\MortalCombat\Fighter;
use LinguaLeo\Servaxle\MortalCombat\DebugFighter;
use LinguaLeo\Servaxle\MortalCombat\ArenaInterface;
use LinguaLeo\Servaxle\MortalCombat\Arena\Portal;
use LinguaLeo\Servaxle\MortalCombat\Arena\LiveForest;

class ClassDependencyTest extends \PHPUnit_Framework_TestCase
{
    public function testClassDependency()
    {
        $dependency = new ClassDependency(
            [
                'battle' => Battle::class,
                'debug_fighter' => DebugFighter::class,
            ],
            [
                'battle.fighter1' => DebugFighter::class,
                'battle.fighter1.name$DebugFighter' => 'Sub-Zero',
                'battle.fighter1.name' => function () { return 'Baraka'; },
                'battle.fighter2.name' => 'Kung Lao',
                'battle.arena.random$LiveForest' => function () {
                    var_dump('Not run');
                },
                'debug_fighter.name' => 'Debugger',
            ],
            [
//                ArenaInterface::class => Portal::class,
                ArenaInterface::class => LiveForest::class
            ],
            [
                'battle.fighter1' => 'debug_fighter'
            ]
        );

        $mt = microtime(true);
        var_dump($dependency->battle);
        var_dump(microtime(true) - $mt);
    }

}