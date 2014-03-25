<?php

namespace LinguaLeo\Servaxle;

use LinguaLeo\Servaxle\MortalCombat\Battle;
use LinguaLeo\Servaxle\MortalCombat\Fighter;
use LinguaLeo\Servaxle\MortalCombat\DebugFighter;
use LinguaLeo\Servaxle\MortalCombat\Factory\FighterFactory;
use LinguaLeo\Servaxle\MortalCombat\ArenaInterface;
use LinguaLeo\Servaxle\MortalCombat\Arena\Portal;
use LinguaLeo\Servaxle\MortalCombat\Arena\LiveForest;

class ClassLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The sharing "something" is undefined.
     */
    public function testUndefinedSharing()
    {
        $locator = new ClassLocator();
        $locator->something;
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage Class SomethingClass does not exist
     */
    public function testSharingUnknownClass()
    {
        $locator = new ClassLocator(['something' => 'SomethingClass']);
        $locator->something;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined type for parameter "fighter.name"
     */
    public function testFailedSharingWithoutValues()
    {
        $locator = new ClassLocator(['fighter' => Fighter::class]);
        $locator->fighter;
    }

    public function testSharingWithScalarParameterInConstructor()
    {
        $locator = new ClassLocator(
            ['fighter' => Fighter::class],
            ['fighter.name' => 'Baraka']
        );
        $fighter = $locator->fighter;
        $this->assertInstanceOf(Fighter::class, $fighter);
        $this->assertSame('Baraka', $fighter->getName());
    }

    public function testSharingWithoutConstructor()
    {
        $locator = new ClassLocator(['arena' => Portal::class]);
        $this->assertInstanceOf(Portal::class, $locator->arena);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No implementation found for "LinguaLeo\Servaxle\MortalCombat\ArenaInterface" in the path "arena".
     */
    public function testFailedSharingNonInstantiableClass()
    {
        $locator = new ClassLocator(['arena' => ArenaInterface::class]);
        $locator->arena;
    }

    public function testSharingWithInterfaceImplementation()
    {
        $locator = new ClassLocator(
            ['arena' => ArenaInterface::class],
            [],
            [ArenaInterface::class => Portal::class]
        );
        $this->assertInstanceOf(Portal::class, $locator->arena);
    }

    public function testSharingWithLink()
    {
        $locator = new ClassLocator(
            [
                'battle' => Battle::class,
                'debug_fighter' => DebugFighter::class,
            ],
            [
                'battle.fighter2.name' => 'Kung Lao',
                'debug_fighter.name' => function () {
                    return uniqid();
                }
            ],
            [
                ArenaInterface::class => Portal::class
            ],
            [
                'battle.fighter1' => 'debug_fighter'
            ]
        );

        $this->assertInstanceOf(DebugFighter::class, $locator->battle->getFighter1());
        $this->assertSame($locator->battle->getFighter1(), $locator->debug_fighter);
    }

    public function testSharingWithClassRewritingForParameter()
    {
        $locator = new ClassLocator(
            [
                'battle' => Battle::class
            ],
            [
                'battle.fighter1' => DebugFighter::class,
                'battle.fighter1.name' => 'Baraka',
                'battle.fighter2.name' => 'Kung Lao',
                'battle.arena.season' => 'Winter',
            ],
            [
                ArenaInterface::class => LiveForest::class
            ]
        );

        $this->assertInstanceOf(DebugFighter::class, $locator->battle->getFighter1());
        $this->assertInstanceOf(Fighter::class, $locator->battle->getFighter2());
    }

    public function testSharingWithFactory()
    {
        $locator = new ClassLocator(
            [
                'battle' => Battle::class
            ],
            [
                'battle.fighter1' => FighterFactory::class,
                'battle.fighter1.isDebug' => true,
                'battle.fighter1.name' => 'Scorpion',
                'battle.fighter2' => FighterFactory::class,
                'battle.fighter2.isDebug' => false,
                'battle.fighter2.name' => 'Kabal',
            ],
            [
                ArenaInterface::class => Portal::class
            ]
        );

        $this->assertInstanceOf(DebugFighter::class, $locator->battle->getFighter1());
        $this->assertSame('Scorpion', $locator->battle->getFighter1()->getName());
        $this->assertInstanceOf(Fighter::class, $locator->battle->getFighter2());
        $this->assertSame('Kabal', $locator->battle->getFighter2()->getName());
    }
}