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
use LinguaLeo\DI\Token\ClassToken;
use LinguaLeo\DI\Token\ScalarToken;
use LinguaLeo\DI\Token\GotoToken;
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
     * @param string $id
     * @return mixed
     */
    public function __get($id)
    {
        return $this->$id = $this->getValue($id);
    }

    /**
     * Returns a value.
     *
     * @param string $id
     * @return mixed
     */
    public function getValue($id)
    {
        try {
            $token = $this->tokenize($id);
        } catch (ClosureSerializationException $ex) {
            return $this->values[$id]($this, $id);
        }
        return $token->newInstance($this);
    }

    /**
     * Tokenizes a value by an identifier.
     *
     * @param mixed $id
     * @return \LinguaLeo\DI\TokenInterface
     * @throws \InvalidArgumentException
     */
    public function tokenize($id)
    {
        if (!isset($this->values[$id])) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }
        return $this->parseToken($this->values[$id], $id);
    }

    /**
     * Returns a token for a class.
     *
     * @param ReflectionClass $class
     * @param string $id
     * @return \LinguaLeo\DI\Token\ClassToken
     * @throws \UnexpectedValueException
     */
    private function getClassToken(ReflectionClass $class, $id)
    {
        if (!$class->inNamespace()) {
            throw new \ReflectionException(sprintf('No namespace found for the class "%s"', $class->name));
        }
        if (!$class->isInstantiable()) {
            if (empty($this->values[$class->name])) {
                throw new \UnexpectedValueException(sprintf('No implementation found for "%s" in the path "%s".', $class->name, $id));
            }
            return $this->parseToken($this->values[$class->name], $id);
        }
        $args = [];
        $constructor = $class->getConstructor();
        if ($constructor) {
            $args = $this->getArguments($constructor, $id);
        }
        return new ClassToken($id, $class, $args);
    }

    /**
     * Parses a token.
     *
     * @param mixed $value
     * @param string $id
     * @return \LinguaLeo\DI\TokenInterface
     * @throws \LinguaLeo\DI\Exception\ClosureSerializationException
     */
    protected function parseToken($value, $id)
    {
        if (is_object($value) && $value instanceof \Closure) {
            throw new ClosureSerializationException(sprintf('Serialization of Closure "%s" is not allowed', $id));
        }
        if (is_string($value)) {
            switch ($value[0]) {
                case '@': return $this->getSymlinkToken($value);
                case '$': return $this->getVariableToken(substr($value, 1));
            }
            try {
                return $this->getClassToken(new ReflectionClass($value), $id);
            } catch (\ReflectionException $ex) {
                trigger_error($ex->getMessage(), E_USER_WARNING);
            }
        }
        return new ScalarToken($value);
    }

    /**
     * Returns arguments for a constructor.
     *
     * @param ReflectionMethod $constructor
     * @param string $id
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getArguments(ReflectionMethod $constructor, $id)
    {
        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $anchor = $id.'.'.$parameter->name;
            try {
                $token = $this->tokenize($anchor);
            } catch (\InvalidArgumentException $ex) {
                if (($parameterClass = $parameter->getClass())) {
                    $token = $this->getClassToken($parameterClass, $anchor);
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $token = new ScalarToken($parameter->getDefaultValue());
                } else {
                    throw $ex;
                }
            }
            $args[] = $token;
        }
        return $args;
    }

    /**
     * Returns a token for a symlink.
     *
     * @param string $id
     * @return \LinguaLeo\DI\Token\GotoToken
     * @throws \UnexpectedValueException
     */
    private function getSymlinkToken($id)
    {
        if (empty($this->values[$id])) {
            throw new \UnexpectedValueException(sprintf('Unknown %s symlink', $id));
        }
        return new GotoToken($this->values[$id]);
    }

    /**
     * Returns a token for a variable.
     *
     * @param string $id
     * @return \LinguaLeo\DI\Token\GotoToken
     * @throws \UnexpectedValueException
     */
    private function getVariableToken($id)
    {
        if (empty($this->values[$id])) {
            throw new \UnexpectedValueException(sprintf('Unknown "%s" variable', $id));
        }
        return new GotoToken($id);
    }
}
