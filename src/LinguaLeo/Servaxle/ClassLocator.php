<?php

namespace LinguaLeo\Servaxle;

use ReflectionClass;
use ReflectionMethod;

class ClassLocator
{
    private $sharings;
    private $values;
    private $impls;
    private $links;

    public function __construct(array $sharings = [], array $values = [], array $impls = [], array $links = [])
    {
        $this->sharings = $sharings;
        $this->values = $values;
        $this->impls = $impls;
        $this->links = $links;
    }

    public function __get($name)
    {
        if (empty($this->sharings[$name])) {
            throw new \InvalidArgumentException(sprintf('The sharing "%s" is undefined.', $name));
        }
        return $this->$name = $this->newInstance(new ReflectionClass($this->sharings[$name]), $name);
    }

    private function newInstance(ReflectionClass $class, $path)
    {
        if (!$class->isInstantiable()) {
            if (empty($this->impls[$class->name])) {
                throw new \RuntimeException(sprintf('No implementation found for "%s" in the path "%s".', $class->name, $path));
            }
            return $this->newInstance(new ReflectionClass($this->impls[$class->name]), $path);
        }

        $constructor = $class->getConstructor();
        if (!$constructor) {
            return $class->newInstanceWithoutConstructor();
        }

        return $class->newInstanceArgs($this->getArguments($constructor, $path));
    }

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

    private function reflectValue($value, $path)
    {
        if (is_string($value) && class_exists($value)) {
            $value = $this->newInstance(new ReflectionClass($value), $path);
        }
        if (is_callable($value)) {
            $value = $value($this);
        }
        return $value;
    }
}
