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

namespace LinguaLeo\DI;

use LinguaLeo\DI\MortalCombat\Battle;
use LinguaLeo\DI\MortalCombat\Fighter;
use LinguaLeo\DI\MortalCombat\DebugFighter;
use LinguaLeo\DI\MortalCombat\Factory\FighterFactory;
use LinguaLeo\DI\MortalCombat\ArenaInterface;
use LinguaLeo\DI\MortalCombat\Arena\Portal;
use LinguaLeo\DI\MortalCombat\Arena\LiveForest;

class ScopeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "something" is undefined.
     */
    public function testUndefinedIdentifier()
    {
        $scope = new Scope();
        $scope->something;
    }

    public function testSimpleIdentifier()
    {
        $scope = new Scope(['something' => 'foo']);
        $this->assertSame('foo', $scope->something);
    }

    public function testCallableIdentifier()
    {
        $scope = new Scope([
            'something' => function () {
                return 'foo';
            }
        ]);
        $this->assertSame('foo', $scope->something);
    }

    public function testCallableWithParameters()
    {
        $scope = new Scope([
            'foo' => 'something',
            'bar' => function (Scope $scope, $path) {
                return $scope->foo.':'.$path;
            }
        ]);
        $this->assertSame('something:bar', $scope->bar);
    }

    public function testNonCallableIdentifierAsAFunctionName()
    {
        $scope = new Scope(['funcName' => 'sort']);
        $this->assertSame('sort', $scope->funcName);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "fighter.name" is undefined.
     */
    public function testFailedSharingWithoutValues()
    {
        $scope = new Scope(['fighter' => Fighter::class]);
        $scope->fighter;
    }

    public function testSharingWithScalarParameterInConstructor()
    {
        $scope = new Scope(
            [
                'fighter' => Fighter::class,
                'fighter.name' => 'Baraka'
            ]
        );
        $fighter = $scope->fighter;
        $this->assertInstanceOf(Fighter::class, $fighter);
        $this->assertSame('Baraka', $fighter->getName());
    }

    public function testSharingWithoutConstructor()
    {
        $scope = new Scope(['arena' => Portal::class]);
        $this->assertInstanceOf(Portal::class, $scope->arena);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No implementation found for "LinguaLeo\DI\MortalCombat\ArenaInterface" in the path "arena".
     */
    public function testFailedSharingNonInstantiableClass()
    {
        $scope = new Scope(['arena' => ArenaInterface::class]);
        $scope->arena;
    }

    public function testSharingWithInterfaceImplementation()
    {
        $scope = new Scope(
            [
                'arena' => ArenaInterface::class,
                ArenaInterface::class => Portal::class
            ]
        );
        $this->assertInstanceOf(Portal::class, $scope->arena);
    }

    public function testSharingWithClassRewritingForParameter()
    {
        $scope = new Scope(
            [
                'battle' => Battle::class,
                'battle.fighter1' => DebugFighter::class,
                'battle.fighter1.name' => 'Baraka',
                'battle.fighter2.name' => 'Kung Lao',
                'battle.arena.season' => 'Winter',
                ArenaInterface::class => LiveForest::class
            ]
        );

        $this->assertInstanceOf(DebugFighter::class, $scope->battle->getFighter1());
        $this->assertInstanceOf(Fighter::class, $scope->battle->getFighter2());
    }

    public function testSharingWithFactory()
    {
        $scope = new Scope(
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

        $this->assertInstanceOf(DebugFighter::class, $scope->battle->getFighter1());
        $this->assertSame('Scorpion', $scope->battle->getFighter1()->getName());
        $this->assertInstanceOf(Fighter::class, $scope->battle->getFighter2());
        $this->assertSame('Kabal', $scope->battle->getFighter2()->getName());
    }

    public function testSymlink()
    {
        $scope = new Scope(
            [
                'foo' => function () {
                    return uniqid();
                },
                'bar' => '@something',
                '@something' => 'foo'
            ]
        );

        $this->assertSame($scope->foo, $scope->bar);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown @something symlink
     */
    public function testUnknownSymlink()
    {
        $scope = new Scope(['foo' => '@something']);
        $scope->foo;
    }
}
