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

use ReflectionClass;
use ReflectionMethod;

class ClassLocator
{
    private $sharings;
    private $values;
    private $impls;
    private $links;

    /**
     * Instantiates the locator.
     *
     * @param array $sharings
     * @param array $values
     * @param array $impls
     * @param array $links
     */
    public function __construct(array $sharings = [], array $values = [], array $impls = [], array $links = [])
    {
        $this->sharings = $sharings;
        $this->values = $values;
        $this->impls = $impls;
        $this->links = $links;
    }

    /**
     * Gets an object.
     *
     * @param string $name
     * @return object
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (empty($this->sharings[$name])) {
            throw new \InvalidArgumentException(sprintf('The sharing "%s" is undefined.', $name));
        }
        return $this->$name = $this->createInstance($this->sharings[$name], $name);
    }

    /**
     * Creates a new instance from class name.
     *
     * @param string $className
     * @param string $path
     * @return object
     */
    public function createInstance($className, $path = '')
    {
        return $this->newInstance(new ReflectionClass($className), $path);
    }

    /**
     * Creates a new instance from reflection.
     *
     * @param ReflectionClass $class
     * @param string $path
     * @return object
     * @throws \RuntimeException
     */
    private function newInstance(ReflectionClass $class, $path)
    {
        if (!$class->isInstantiable()) {
            if (empty($this->impls[$class->name])) {
                throw new \RuntimeException(sprintf('No implementation found for "%s" in the path "%s".', $class->name, $path));
            }
            return $this->createInstance($this->impls[$class->name], $path);
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
     * @throws \RuntimeException
     */
    private function getArguments(ReflectionMethod $constructor, $path)
    {
        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $anchor = $path.'.'.$parameter->name;
            if (isset($this->links[$anchor])) {
                $args[] = $this->{$this->links[$anchor]};
            } elseif (isset($this->values[$anchor])) {
                $args[] = $this->reflectValue($this->values[$anchor], $anchor);
            } elseif (($parameterClass = $parameter->getClass())) {
                $args[] = $this->newInstance($parameterClass, $anchor);
            } else {
                throw new \RuntimeException(sprintf('Undefined type for parameter "%s"', $anchor));
            }
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
        if (is_string($value) && class_exists($value)) {
            $value = $this->createInstance($value, $path);
        }
        if (is_callable($value)) {
            $value = $value($this);
        }
        return $value;
    }
}
