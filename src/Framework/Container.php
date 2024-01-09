<?php

declare(strict_types=1);

namespace Framework;

use ReflectionClass, ReflectionNamedType;
use Framework\Exceptions\ContainerException;

class Container
{
    private array $definitions = [];
    private array $resolved = [];

    public function addDefinitions(array $newDefinitions)
    {
        /* 
        $this->definitions = array_merge($this->definitions, $newDefinitions); 
        */
        $this->definitions = [...$this->definitions, ...$newDefinitions];
    }

    public function resolve(string $className)
    {
        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException("Class {$className} is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return new $className();
        }
        $params = $constructor->getParameters();

        if (count($params) === 0) {
            return new $className();
        }
        $dependencies = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (!$type) {
                throw new ContainerException("Failed to resolve class {$className} because param{$name} is missing a type hint.");
            }

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new ContainerException("Failed to resolve class {$className} because invalid param name.");
            }

            $dependencies[] = $this->get($type->getName());
        }
        return $reflectionClass->newInstanceArgs($dependencies);
    }

    public function get(string $id)
    {
        // Check if the specified identifier ($id) is not present in the definitions array of the container
        if (!array_key_exists($id, $this->definitions)) {
            // If not found, throw an exception indicating that there is no definition for the given identifier in the container
            throw new ContainerException("No definition for {$id} found in container");
        }

        // Check if the specified identifier ($id) is already present in the resolved array
        if (array_key_exists($id, $this->resolved)) {
            // If found, return the previously resolved dependencies for the given identifier
            return $this->resolved[$id];
        }

        // Retrieve the factory (callable) associated with the specified identifier ($id) from the definitions array
        $factory = $this->definitions[$id];

        // Call the factory to obtain the dependencies associated with the specified identifier
        $dependencies = $factory($this);

        // Cache the resolved dependencies in the resolved array to avoid redundant processing for the same identifier
        $this->resolved[$id] = $dependencies;

        // Return the resolved dependencies for the specified identifier
        return $dependencies;
    }
}
