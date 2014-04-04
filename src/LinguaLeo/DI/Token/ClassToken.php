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

namespace LinguaLeo\DI\Token;

use ReflectionClass;
use LinguaLeo\DI\TokenInterface;
use LinguaLeo\DI\Scope;

class ClassToken implements TokenInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \ReflectionClass
     */
    private $class;

    /**
     * @var array
     */
    private $arguments;

    public function __construct($id, ReflectionClass $class, array $arguments)
    {
        $this->id = $id;
        $this->class = $class;
        $this->arguments = $arguments;
    }

    public function getBinding()
    {
        return "function (\$scope) { return {$this->getScript()}; }";
    }

    public function getScript()
    {
        $script = 'new \\'.$this->class->name;
        if ($this->arguments) {
            $script .= '('.implode(', ', $this->arguments).')';
        }
        if ($this->isFactory()) {
            return "call_user_func($script, \$scope, '$this->id')";
        }
        return $script;
    }

    public function __toString()
    {
        return $this->getScript();
    }

    public function newInstance(Scope $scope)
    {
        $object = $this->createInstance($scope);
        if ($this->isFactory()) {
            return $object($scope, $this->id);
        }
        return $object;
    }

    private function newArguments(Scope $scope)
    {
        $arguments = [];
        foreach ($this->arguments as $token) {
            /* @var $token TokenInterface */
            $arguments[] = $token->newInstance($scope);
        }
        return $arguments;
    }

    private function createInstance(Scope $scope)
    {
        if (!$this->arguments) {
            return $this->class->newInstanceWithoutConstructor();
        }
        return $this->class->newInstanceArgs($this->newArguments($scope));
    }

    private function isFactory()
    {
        return $this->class->hasMethod('__invoke');
    }
}
