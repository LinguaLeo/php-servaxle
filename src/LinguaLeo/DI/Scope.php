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

class Scope
{
    private $values;

    /**
     * Instantiates the container.
     *
     * @param array $values
     */
    public function __construct(array $values = [])
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
     * @throws \InvalidArgumentException
     */
    public function getValue($id)
    {
        if (!isset($this->values[$id])) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is undefined.', $id));
        }
        return $this->reflectValue($this->values[$id], $id);
    }

    /**
     * Creates a new instance from class name.
     *
     * @param string $className
     * @param string $path
     * @return object
     * @throws \ReflectionException
     */
    public function createInstance($className, $path = '')
    {
        if (!is_string($className)) {
            throw new \ReflectionException(sprintf('The parameter "className" must be a string. Given %s.', gettype($className)));
        }
        return $this->newInstance(new ReflectionClass($className), $path);
    }

    /**
     * Creates a new instance from reflection.
     *
     * @param ReflectionClass $class
     * @param string $path
     * @return object
     * @throws \UnexpectedValueException
     */
    private function newInstance(ReflectionClass $class, $path)
    {
        if (!$class->isInstantiable()) {
            if (empty($this->values[$class->name])) {
                throw new \UnexpectedValueException(sprintf('No implementation found for "%s" in the path "%s".', $class->name, $path));
            }
            return $this->reflectValue($this->values[$class->name], $path);
        }

        $constructor = $class->getConstructor();
        if (!$constructor) {
            return $class->newInstanceWithoutConstructor();
        }

        return $class->newInstanceArgs($this->getArguments($constructor, $path));
    }

    /**
     * Returns arguments for the constructor.
     *
     * @param ReflectionMethod $constructor
     * @param string $path
     * @return array
     */
    private function getArguments(ReflectionMethod $constructor, $path)
    {
        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $anchor = $path.'.'.$parameter->name;
            try {
                $value = $this->getValue($anchor);
            } catch (\InvalidArgumentException $e) {
                if (($parameterClass = $parameter->getClass())) {
                    $value = $this->newInstance($parameterClass, $anchor);
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $value = $parameter->getDefaultValue();
                } else {
                    throw $e;
                }
            }
            $args[] = $value;
        }
        return $args;
    }

    /**
     * Reflects and returns a value from a path.
     *
     * @param mixed $value
     * @param string $path
     * @return mixed
     */
    private function reflectValue($value, $path)
    {
        try {
            $value = $this->createInstance($value, $path);
        } catch (\ReflectionException $e) {
        }
        if (is_object($value) && method_exists($value, '__invoke')) {
            return $value($this, $path);
        }
        if (is_string($value) && '@' === $value[0]) {
            return $this->resolveSymlink($value);
        }
        return $value;
    }

    /**
     * Resolves a symlink.
     *
     * @param string $value
     * @return mixed
     * @throws \UnexpectedValueException
     */
    private function resolveSymlink($value)
    {
        if (empty($this->values[$value])) {
            throw new \UnexpectedValueException(sprintf('Unknown %s symlink', $value));
        }
        return $this->{$this->values[$value]};
    }
}
