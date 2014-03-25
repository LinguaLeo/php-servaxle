<?php

namespace LinguaLeo\Servaxle;

use ReflectionClass;
use ReflectionParameter;

class ClassDependency
{
    protected $sharings;
    protected $values;
    protected $impls;
    protected $links;

    public function __construct(array $sharings, array $values, array $impls, array $links)
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
        return $this->$name = $this->newInstance($this->sharings[$name], $name);
    }

    public function newInstance($class, $path)
    {
        if (is_string($class)) {
            $class = new ReflectionClass($class);
        }

        if (!$class->isInstantiable()) {
            if (empty($this->impls[$class->name])) {
                throw new \RuntimeException(sprintf('Cannot to instantiate a non instantiable class "%s"', $class->name));
            }
            return $this->newInstance($this->impls[$class->name], $path);
        }

        return $class->newInstanceArgs($this->getArguments($class, $path));
    }

    protected function getArguments(ReflectionClass $class, $path)
    {
        $constructor = $class->getConstructor();
        if (!$constructor) {
            return;
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $args[] = $this->getArgument($class, $parameter, $path);
        }
        return $args;
    }

    private function getArgument(ReflectionClass $class, ReflectionParameter $parameter, $path)
    {
        $path .= '.'.$parameter->name;

        if (isset($this->links[$path])) {
            return $this->{$this->links[$path]};
        }

        $declaredId = $this->getDeclaredId($path.'$'.$class->getShortName(), $path);
        if ($declaredId) {
            $value = $this->values[$declaredId];
            if (is_string($value) && class_exists($value)) {
                return $this->newInstance($value, $declaredId);
            }
            if (!$parameter->isCallable() && is_callable($value)) {
                return $value($this);
            }
            return $value;
        }

        $parameterClass = $parameter->getClass();
        if (!$parameterClass) {
            throw new \RuntimeException(sprintf('Undefined type for parameter "%s"', $path));
        }
        return $this->newInstance($parameterClass, $path);
    }

    private function getDeclaredId()
    {
        foreach (func_get_args() as $id) {
            if (isset($this->values[$id])) {
                return $id;
            }
        }
        return null;
    }
}
