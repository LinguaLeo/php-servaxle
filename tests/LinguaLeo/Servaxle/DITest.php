<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 LinguaLeo
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace LinguaLeo\Servaxle;

use LinguaLeo\Servaxle\MortalCombat\Battle;
use LinguaLeo\Servaxle\MortalCombat\Fighter;
use LinguaLeo\Servaxle\MortalCombat\DebugFighter;
use LinguaLeo\Servaxle\MortalCombat\Factory\FighterFactory;
use LinguaLeo\Servaxle\MortalCombat\ArenaInterface;
use LinguaLeo\Servaxle\MortalCombat\Arena\Portal;
use LinguaLeo\Servaxle\MortalCombat\Arena\LiveForest;

class DITest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "something" is undefined.
     */
    public function testUndefinedIdentifier()
    {
        $container = new DI();
        $container->something;
    }

    public function testSimpleIdentifier()
    {
        $container = new DI(['something' => 'foo']);
        $this->assertSame('foo', $container->something);
    }

    public function testCallableIdentifier()
    {
        $container = new DI([
            'something' => function () {
                return 'foo';
            }
        ]);
        $this->assertSame('foo', $container->something);
    }

    public function testCallableWithParameters()
    {
        $container = new DI([
            'foo' => 'something',
            'bar' => function (DI $container, $path) {
                return $container->foo.':'.$path;
            }
        ]);
        $this->assertSame('something:bar', $container->bar);
    }

    public function testNonCallableIdentifierAsAFunctionName()
    {
        $container = new DI(['funcName' => 'sort']);
        $this->assertSame('sort', $container->funcName);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "fighter.name" is undefined.
     */
    public function testFailedSharingWithoutValues()
    {
        $container = new DI(['fighter' => Fighter::class]);
        $container->fighter;
    }

    public function testSharingWithScalarParameterInConstructor()
    {
        $container = new DI(
            [
                'fighter' => Fighter::class,
                'fighter.name' => 'Baraka'
            ]
        );
        $fighter = $container->fighter;
        $this->assertInstanceOf(Fighter::class, $fighter);
        $this->assertSame('Baraka', $fighter->getName());
    }

    public function testSharingWithoutConstructor()
    {
        $container = new DI(['arena' => Portal::class]);
        $this->assertInstanceOf(Portal::class, $container->arena);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No implementation found for "LinguaLeo\Servaxle\MortalCombat\ArenaInterface" in the path "arena".
     */
    public function testFailedSharingNonInstantiableClass()
    {
        $container = new DI(['arena' => ArenaInterface::class]);
        $container->arena;
    }

    public function testSharingWithInterfaceImplementation()
    {
        $container = new DI(
            [
                'arena' => ArenaInterface::class,
                ArenaInterface::class => Portal::class
            ]
        );
        $this->assertInstanceOf(Portal::class, $container->arena);
    }

    public function testSharingWithClassRewritingForParameter()
    {
        $container = new DI(
            [
                'battle' => Battle::class,
                'battle.fighter1' => DebugFighter::class,
                'battle.fighter1.name' => 'Baraka',
                'battle.fighter2.name' => 'Kung Lao',
                'battle.arena.season' => 'Winter',
                ArenaInterface::class => LiveForest::class
            ]
        );

        $this->assertInstanceOf(DebugFighter::class, $container->battle->getFighter1());
        $this->assertInstanceOf(Fighter::class, $container->battle->getFighter2());
    }

    public function testSharingWithFactory()
    {
        $container = new DI(
            [
                'battle' => Battle::class,
                'battle.fighter1' => FighterFactory::class,
                'battle.fighter1.isDebug' => true,
                'battle.fighter1.name' => 'Scorpion',
                'battle.fighter2' => FighterFactory::class,
                'battle.fighter2.isDebug' => false,
                'battle.fighter2.name' => 'Kabal',
                ArenaInterface::class => Portal::class
            ]
        );

        $this->assertInstanceOf(DebugFighter::class, $container->battle->getFighter1());
        $this->assertSame('Scorpion', $container->battle->getFighter1()->getName());
        $this->assertInstanceOf(Fighter::class, $container->battle->getFighter2());
        $this->assertSame('Kabal', $container->battle->getFighter2()->getName());
    }
}
