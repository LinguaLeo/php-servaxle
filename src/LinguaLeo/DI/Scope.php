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

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use LinguaLeo\DI\Token\ClassToken;
use LinguaLeo\DI\Token\ScalarToken;
use LinguaLeo\DI\Token\GotoToken;
use LinguaLeo\DI\Token\ConstantToken;
use LinguaLeo\DI\Exception\ClosureSerializationException;

class Scope
{
    protected $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Returns a value as a property.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->$key = $this->getValue($key);
    }

    /**
     * Returns a value.
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        try {
            $token = $this->tokenize($key);
        } catch (ClosureSerializationException $ex) {
            return $this->values[$key]($this, $key);
        }
        return $token->newInstance($this);
    }

    /**
     * Tokenizes a value by an identifier.
     *
     * @param mixed $key
     * @return \LinguaLeo\DI\TokenInterface
     * @throws \InvalidArgumentException
     */
    public function tokenize($key)
    {
        if (!isset($this->values[$key])) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $key));
        }
        return $this->parseToken($this->values[$key], $key);
    }

    /**
     * Returns a token for a class.
     *
     * @param ReflectionClass $class
     * @param string $key
     * @return \LinguaLeo\DI\Token\ClassToken
     * @throws \UnexpectedValueException
     */
    private function getClassToken(ReflectionClass $class, $key)
    {
        if (!$class->inNamespace()) {
            throw new \ReflectionException(sprintf('No namespace found for the class "%s"', $class->name));
        }
        if (!$class->isInstantiable()) {
            if (empty($this->values[$class->name])) {
                throw new \UnexpectedValueException(
                    sprintf('No implementation found for "%s" in the path "%s".', $class->name, $key)
                );
            }
            return $this->parseToken($this->values[$class->name], $key);
        }
        $args = [];
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = $this->getArguments($constructor, $key);
        }
        return new ClassToken($key, $class, $args);
    }

    /**
     * Parses a token.
     *
     * @param mixed $value
     * @param string $key
     * @return \LinguaLeo\DI\TokenInterface
     * @throws \LinguaLeo\DI\Exception\ClosureSerializationException
     * @throws \InvalidArgumentException
     */
    protected function parseToken($value, $key)
    {
        if (is_object($value) && $value instanceof \Closure) {
            throw new ClosureSerializationException(sprintf('Serialization of Closure "%s" is not allowed', $key));
        }
        if (is_string($value) && !empty($value)) {
            switch ($value[0]) {
                case '@':
                    return $this->getSymlinkToken($value);
                case '$':
                    return $this->getVariableToken(substr($value, 1));
            }
            if (defined($value)) {
                return new ConstantToken($value);
            }
            try {
                return $this->getClassToken(new ReflectionClass($value), $key);
            } catch (\ReflectionException $ex) {
                error_log($ex->getMessage());
            }
        }
        return new ScalarToken($value);
    }

    /**
     * Returns arguments for a constructor.
     *
     * @param ReflectionMethod $constructor
     * @param string $key
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getArguments(ReflectionMethod $constructor, $key)
    {
        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $args[] = $this->getArgument($parameter, $key);
        }
        return $args;
    }

    /**
     * Finds a token for a parameter
     *
     * @param ReflectionParameter $parameter
     * @param string $key
     * @return \LinguaLeo\DI\TokenInterface
     * @throws \InvalidArgumentException
     */
    private function getArgument(ReflectionParameter $parameter, $key)
    {
        $key .= '.'.$parameter->name;
        try {
            return $this->tokenize($key);
        } catch (\InvalidArgumentException $ex) {
            $class = $parameter->getClass();
            if ($class) {
                return $this->getClassToken($class, $key);
            }
            if ($parameter->isDefaultValueAvailable()) {
                return new ScalarToken($parameter->getDefaultValue());
            }
            throw $ex;
        }
    }

    /**
     * Returns a token for a symlink.
     *
     * @param string $key
     * @return \LinguaLeo\DI\Token\GotoToken
     * @throws \UnexpectedValueException
     */
    private function getSymlinkToken($key)
    {
        if (empty($this->values[$key])) {
            throw new \UnexpectedValueException(sprintf('Unknown %s symlink', $key));
        }
        return new GotoToken($this->values[$key]);
    }

    /**
     * Returns a token for a variable.
     *
     * @param string $key
     * @return \LinguaLeo\DI\Token\GotoToken
     * @throws \UnexpectedValueException
     */
    private function getVariableToken($key)
    {
        if (empty($this->values[$key])) {
            throw new \UnexpectedValueException(sprintf('Unknown "%s" variable', $key));
        }
        return new GotoToken($key);
    }

    /**
     * Compiles values into php script.
     *
     * @param array $values
     * @return string
     */
    public static function compile(array $values)
    {
        $scope = new self($values);
        $script = '['.PHP_EOL;
        foreach ($scope->values as $id => $value) {
            if (interface_exists($id)) {
                continue;
            }
            $script .= "'$id' => ".$scope->parseToken($value, $id)->getBinding().','.PHP_EOL;
        }
        return $script.']';
    }
}
