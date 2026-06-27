<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

class Container
{
    /**
     * @var array<string, object>
     */
    private array $instances = [];

    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (!class_exists($class)) {
            throw new RuntimeException("Class {$class} does not exist.");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            $instance = new $class();

            $this->instances[$class] = $instance;

            return $instance;
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new RuntimeException(
                    "Unable to resolve {$parameter->getName()}."
                );
            }

            $dependencies[] = $this->get($type->getName());
        }

        $instance = $reflection->newInstanceArgs($dependencies);

        $this->instances[$class] = $instance;

        return $instance;
    }
}