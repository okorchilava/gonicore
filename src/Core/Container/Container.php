<?php

declare(strict_types=1);

namespace GoniCore\Core\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * PSR-11 compatible dependency injection container.
 *
 * Supports:
 *   - bind()      — factory resolved fresh on every get()
 *   - singleton() — factory resolved once and cached
 *   - instance()  — register a pre-built object directly
 *   - Auto-wiring — concrete classes with type-hinted constructors
 *
 * Example:
 *   $c = new Container();
 *   $c->singleton(Connection::class, fn($c) => Connection::fromConfig([...]));
 *   $db = $c->get(Connection::class);
 */
final class Container implements ContainerInterface
{
    /** @var array<string, callable(self): mixed> */
    private array $bindings = [];

    /** @var array<string, callable(self): mixed> */
    private array $singletonBindings = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Bind an abstract identifier to a factory.
     * The factory is called on every get() call — no caching.
     *
     * @param callable(self): mixed $factory
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Bind an abstract identifier to a factory that is called only once.
     * Subsequent get() calls return the cached instance.
     *
     * @param callable(self): mixed $factory
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletonBindings[$abstract] = $factory;
    }

    /**
     * Register an already-constructed instance as a singleton.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->resolved[$abstract] = $instance;
    }

    // -------------------------------------------------------------------------
    // PSR-11
    // -------------------------------------------------------------------------

    /**
     * Resolve and return an entry from the container.
     *
     * Resolution order:
     *   1. Already-cached singleton / pre-registered instance
     *   2. Singleton factory (result is cached)
     *   3. Regular factory (result is NOT cached)
     *   4. Auto-wiring via Reflection
     *
     * @throws NotFoundException   if no binding exists and auto-wiring fails
     * @throws ContainerException  if resolution fails for any other reason
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * Return true if the container can provide an entry for $id.
     * (This includes classes that can be auto-wired even without explicit binding.)
     */
    public function has(string $id): bool
    {
        return isset($this->resolved[$id])
            || isset($this->singletonBindings[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    // -------------------------------------------------------------------------
    // Internal resolution
    // -------------------------------------------------------------------------

    private function resolve(string $abstract): mixed
    {
        // 1. Cached singleton or pre-registered instance.
        if (array_key_exists($abstract, $this->resolved)) {
            return $this->resolved[$abstract];
        }

        // 2. Singleton factory — resolve once and cache.
        if (isset($this->singletonBindings[$abstract])) {
            $this->resolved[$abstract] = ($this->singletonBindings[$abstract])($this);
            return $this->resolved[$abstract];
        }

        // 3. Regular factory — fresh resolution every time.
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // 4. Auto-wiring.
        if (class_exists($abstract)) {
            return $this->autowire($abstract);
        }

        throw new NotFoundException(
            "No binding or class found for identifier: \"{$abstract}\""
        );
    }

    /**
     * Instantiate $class by resolving its constructor dependencies recursively.
     *
     * @throws ContainerException  for non-instantiable classes or unresolvable parameters
     */
    private function autowire(string $class): object
    {
        try {
            $ref = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException(
                "Could not reflect class \"{$class}\": " . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!$ref->isInstantiable()) {
            throw new ContainerException(
                "Class \"{$class}\" is not instantiable (abstract, interface, or trait)."
            );
        }

        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            // Type-hinted class/interface — resolve recursively.
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
                continue;
            }

            // Use declared default value.
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // Nullable parameter — pass null.
            if ($param->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new ContainerException(
                "Cannot auto-wire parameter \"\${$param->getName()}\" "
                . "for class \"{$class}\": no type hint and no default value."
            );
        }

        return $ref->newInstanceArgs($args);
    }
}
