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

use LinguaLeo\Servaxle\MortalCombat\Fighter;
use LinguaLeo\Servaxle\MortalCombat\Battle;
use LinguaLeo\Servaxle\MortalCombat\Arena\Portal;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleProxy()
    {
        $container = new DI(
            [
                'foo' => Proxy::class,
                'foo.from' => 'unique',
                'unique' => function () {
                    return uniqid();
                },
            ]
        );

        $this->assertSame($container->foo, $container->unique);
    }

    public function testProxyOnTwoLevels()
    {
         $container = new DI(
            [
                'foo' => Proxy::class,
                'foo.from' => 'bar',
                'bar' => Proxy::class,
                'bar.from' => 'unique',
                'unique' => function () {
                    return uniqid();
                },
            ]
        );

        $this->assertSame($container->foo, $container->unique);
    }

    public function testRewriteProxyPath()
    {
        $container = new DI(
            [
                'fighter' => Fighter::class,
                'fighter.name' => 'Baraka',
                'battle' => Battle::class,
                'battle.fighter1' => Proxy::class,
                'battle.fighter1.from' => 'fighter',
                'battle.fighter2.name' => 'Kung Lao',
                'battle.arena' => Portal::class
            ]
        );

        $this->assertInstanceOf(Fighter::class, $container->battle->getFighter1());
        $this->assertSame($container->fighter, $container->battle->getFighter1());
    }

    public function testDiggerProxy()
    {
        $container = new DI(
            [
                'foo' => [
                    'bar' => [
                        'baz' => 'quux'
                    ]
                ],
                'unique' => Proxy::class,
                'unique.from' => ['foo', 'bar', 'baz']
            ]
        );

        $this->assertSame('quux', $container->unique);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The proxied fragment "baz" not found in the path "unique"
     */
    public function testFailedDiggerProxy()
    {
        $container = new DI(
            [
                'foo' => [
                    'bar' => 'quux'
                ],
                'unique' => Proxy::class,
                'unique.from' => ['foo', 'bar', 'baz']
            ]
        );

        $container->unique;
    }
}
