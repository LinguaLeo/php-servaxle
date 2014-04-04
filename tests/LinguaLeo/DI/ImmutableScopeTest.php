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

use LinguaLeo\DI\MortalCombat\Fighter;
use LinguaLeo\DI\MortalCombat\ArenaInterface;
use LinguaLeo\DI\MortalCombat\Arena\Portal;
use LinguaLeo\DI\MortalCombat\Factory\FighterFactory;

class ImmutableScopeTest extends \PHPUnit_Framework_TestCase
{
    private function inBrackets()
    {
        return '['.PHP_EOL.implode(PHP_EOL, func_get_args()).PHP_EOL.']';
    }

    public function provideValuesForCompilation()
    {
        return [
            [
                ['foo' => 'bar'],
                $this->inBrackets("'foo' => 'bar', // bar")
            ],
            [
                [
                    'fighter' => Fighter::class,
                    'fighter.name' => 'Baraka'
                ],
                $this->inBrackets(
                    "'fighter' => function (\$scope) { return new \LinguaLeo\DI\MortalCombat\Fighter('Baraka'); }, // LinguaLeo\DI\MortalCombat\Fighter",
                    "'fighter.name' => 'Baraka', // Baraka"
                )
            ],
            [
                [
                    'arena' => ArenaInterface::class,
                    ArenaInterface::class => Portal::class
                ],
                $this->inBrackets(
                    "'arena' => function (\$scope) { return new \LinguaLeo\DI\MortalCombat\Arena\Portal; }, // LinguaLeo\DI\MortalCombat\ArenaInterface"
                )
            ],
            [
                [
                    'fighter' => FighterFactory::class,
                    'fighter.isDebug' => true,
                    'fighter.name' => 'Scorpion',
                ],
                $this->inBrackets(
                    "'fighter' => function (\$scope) { return call_user_func(new \LinguaLeo\DI\MortalCombat\Factory\FighterFactory(true, 'Scorpion'), \$scope, 'fighter'); }, // LinguaLeo\DI\MortalCombat\Factory\FighterFactory",
                    "'fighter.isDebug' => true, // 1",
                    "'fighter.name' => 'Scorpion', // Scorpion"
                )
            ],
            [
                [
                    'something' => 'foo',
                    'bar' => '$something'
                ],
                $this->inBrackets(
                    "'something' => 'foo', // foo",
                    "'bar' => function (\$scope) { return \$scope->something; }, // \$something"
                )
            ]
        ];
    }

    /**
     * @dataProvider provideValuesForCompilation
     */
    public function testCompileSimple($values, $compiledScript)
    {
        $scope = new ImmutableScope($values);
        $this->assertSame($compiledScript, $scope->compile());
    }

    public function testCallableValue()
    {
        $scope = new ImmutableScope([
            'something' => 'foo',
            'bar' => function ($scope, $id) {
                return $scope->something.$id;
            }
        ]);
        $this->assertSame('foobar', $scope->bar);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "something" is not defined.
     */
    public function testUndefinedIdentifier()
    {
        $scope = new ImmutableScope(['foo' => 'bar']);
        $scope->something;
    }
}