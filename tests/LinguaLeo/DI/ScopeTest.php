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
use LinguaLeo\DI\MortalCombat\Arena\Portal;
use LinguaLeo\DI\MortalCombat\ArenaInterface;
use LinguaLeo\DI\MortalCombat\DebugFighter;
use LinguaLeo\DI\MortalCombat\Arena\LiveForest;
use LinguaLeo\DI\MortalCombat\Battle;
use LinguaLeo\DI\MortalCombat\Factory\FighterFactory;

use LinguaLeo\DI\Token\ScalarToken;
use LinguaLeo\DI\Token\ClassToken;

class ScopeTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleIdentifier()
    {
        $scope = new Scope(['something' => 'foo']);
        $token = $scope->tokenize('something');
        $this->assertInstanceOf(ScalarToken::class, $token);
        $this->assertSame("'foo'", $token->getScript());
        $this->assertSame("'foo'", $token->getBinding());
    }

    public function testScalarValueAsArray()
    {
        $scope = new Scope(
            [
                'something' => [
                    'foo' => 'hello',
                    'bar' => 'world'
                ]
            ]
        );
        $token = $scope->tokenize('something');
        $this->assertInstanceOf(ScalarToken::class, $token);
        $this->assertSame("function () { return array (
  'foo' => 'hello',
  'bar' => 'world',
); }", $token->getBinding());
    }

    /**
     * @expectedException \LinguaLeo\DI\Exception\ClosureSerializationException
     * @expectedExceptionMessage Serialization of Closure "something" is not allowed
     */
    public function testClosureIdentifier()
    {
        $scope = new Scope([
            'something' => function () {
                return 'foo';
            }
        ]);
        $scope->tokenize('something');
    }

    public function testNonCallableIdentifierAsAFunctionName()
    {
        $scope = new Scope(['funcName' => 'sort']);
        $token = $scope->tokenize('funcName');
        $this->assertInstanceOf(ScalarToken::class, $token);
    }

    public function testClassTokenization()
    {
        $scope = new Scope(
            [
                'fighter' => Fighter::class,
                'fighter.name' => 'Baraka'
            ]
        );

        $token = $scope->tokenize('fighter');
        $this->assertInstanceOf(ClassToken::class, $token);
        $this->assertSame("new \LinguaLeo\DI\MortalCombat\Fighter('Baraka')", $token->getScript());
    }

    public function testClassNotInNamespaceTokenization()
    {
        $scope = new Scope(['std' => \stdClass::class]);
        $token = $scope->tokenize('std');
        $this->assertInstanceOf(ScalarToken::class, $token);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "fighter.name" is not defined.
     */
    public function testFailedSharingWithoutValues()
    {
        $scope = new Scope(['fighter' => Fighter::class]);
        $scope->tokenize('fighter');
    }

    public function testClassTokenizationWithoutConstructor()
    {
        $scope = new Scope(['arena' => Portal::class]);
        $token = $scope->tokenize('arena');
        $this->assertSame('new \LinguaLeo\DI\MortalCombat\Arena\Portal', $token->getScript());
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage No implementation found for "LinguaLeo\DI\MortalCombat\ArenaInterface" in the path "arena".
     */
    public function testFailedTokenizationNonInstantiableClass()
    {
        $scope = new Scope(['arena' => ArenaInterface::class]);
        $scope->tokenize('arena');
    }

    public function testTokenizationWithInterfaceImplementation()
    {
        $scope = new Scope(
            [
                'arena' => ArenaInterface::class,
                ArenaInterface::class => Portal::class
            ]
        );
        $token = $scope->tokenize('arena');
        $this->assertSame('new \LinguaLeo\DI\MortalCombat\Arena\Portal', $token->getScript());
    }

    public function testTokenizationClassWithParameterRewriting()
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
        $token = $scope->tokenize('battle');
        $this->assertSame("new \LinguaLeo\DI\MortalCombat\Battle(new \LinguaLeo\DI\MortalCombat\DebugFighter('Baraka', 0), new \LinguaLeo\DI\MortalCombat\Fighter('Kung Lao'), new \LinguaLeo\DI\MortalCombat\Arena\LiveForest('Winter'))", $token->getScript());
    }


    public function testTokenizationWithFactory()
    {
        $expectedScript = "new \LinguaLeo\DI\MortalCombat\Battle(call_user_func(new \LinguaLeo\DI\MortalCombat\Factory\FighterFactory(true, 'Scorpion'), \$scope, 'battle.fighter1'), call_user_func(new \LinguaLeo\DI\MortalCombat\Factory\FighterFactory(false, 'Kabal'), \$scope, 'battle.fighter2'), new \LinguaLeo\DI\MortalCombat\Arena\Portal)";

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

        $token = $scope->tokenize('battle');

        $this->assertSame($expectedScript, $token->getScript());
        $this->assertSame("function (\$scope) { return $expectedScript; }", $token->getBinding());
    }

    public function testTokenizeSymlink()
    {
        $scope = new Scope(
            [
                '@foo' => 'something',
                'bar' => '@foo'
            ]
        );
        $token = $scope->tokenize('bar');
        $this->assertSame('$scope->something', $token->getScript());
        $this->assertSame('function ($scope) { return $scope->something; }', $token->getBinding());
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Unknown @foo symlink
     */
    public function testTokenizeUndefinedSymlink()
    {
        $scope = new Scope(['bar' => '@foo']);
        $scope->tokenize('bar');
    }

    public function testTokenizeVariable()
    {
        $scope = new Scope(
            [
                'foo' => 'something',
                'bar' => '$foo'
            ]
        );
        $token = $scope->tokenize('bar');
        $this->assertSame('$scope->foo', $token->getScript());
        $this->assertSame('function ($scope) { return $scope->foo; }', $token->getBinding());
    }

    public function testGotoTokenFromRecursiveArguments()
    {
        $expectedScript = 'new \LinguaLeo\DI\MortalCombat\Arena\LiveForest($scope->season)';
        $scope = new Scope(
            [
                'season' => 'Summer',
                'portal' => LiveForest::class,
                'portal.season' => '$season'
            ]
        );
        $token = $scope->tokenize('portal');
        $this->assertSame($expectedScript, $token->getScript());
        $this->assertSame("function (\$scope) { return $expectedScript; }", $token->getBinding());
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Unknown "foo" variable
     */
    public function testTokenizeUndefinedVariable()
    {
        $scope = new Scope(['bar' => '$foo']);
        $scope->tokenize('bar');
    }

    public function testTokenizeVariableByInterface()
    {
        $scope = new Scope(
            [
                'portal' => Portal::class,
                'arena' => ArenaInterface::class,
                ArenaInterface::class => '$portal'
            ]
        );
        $token = $scope->tokenize('arena');
        $this->assertSame('$scope->portal', $token->getScript());
    }

    public function testGetValueFromScalarToken()
    {
        $scope = new Scope(['foo' => 'bar']);
        $this->assertSame('bar', $scope->getValue('foo'));
    }

    public function testGetValueAsGotoToken()
    {
        $scope = new Scope(
            [
                'something' => 'foo',
                'bar' => '$something'
            ]
        );
        $this->assertSame('foo', $scope->getValue('bar'));
    }

    public function testGetValueAsCallback()
    {
        $scope = new Scope(
            [
                'foo' => function () {
                    return uniqid();
                }
            ]
        );
        $this->assertSame($scope->foo, $scope->foo);
    }

    public function testGetValueFromClassToken()
    {
        $scope = new Scope(
            [
                'fighter' => Fighter::class,
                'fighter.name' => 'Baraka'
            ]
        );
        $this->assertInstanceOf(Fighter::class, $scope->fighter);
        $this->assertSame('Baraka', $scope->fighter->getName());
    }

    public function testGetValueFromClassTokenWithoutConstructor()
    {
        $scope = new Scope(
            [
                'portal' => Portal::class
            ]
        );
        $this->assertInstanceOf(Portal::class, $scope->portal);
    }

    public function testGetValueFromClassFactory()
    {
        $scope = new Scope(
            [
                'fighter' => FighterFactory::class,
                'fighter.isDebug' => true,
                'fighter.name' => 'Scorpion',
            ]
        );
        $this->assertInstanceOf(Fighter::class, $scope->fighter);
        $this->assertSame('Scorpion', $scope->fighter->getName());
    }

    public function provideConstants()
    {
        return [
            ['PHP_INT_MAX', PHP_INT_MAX],
            ['\DateTimeZone::AFRICA', \DateTimeZone::AFRICA]
        ];
    }

    /**
     * @dataProvider provideConstants
     */
    public function testGetConstant($name, $value)
    {
        $scope = new Scope(['someconst' => $name]);
        $this->assertSame($value, $scope->someconst);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value for identifier "foo" is empty
     */
    public function testTokenizeEmptyVariable()
    {
        $scope = new Scope(['foo' => ''] );
        $scope->tokenize('foo');
    }
}
