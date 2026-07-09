<?php

declare(strict_types=1);

namespace Meraki\Core\Support;

/**
 * Base class for Data Transfer Objects (DTOs).
 *
 * Usage — declare a readonly class extending Data:
 *
 *   final readonly class CreateUserData extends Data
 *   {
 *       public function __construct(
 *           public string $name,
 *           public string $email,
 *           public string $password,
 *       ) {}
 *   }
 *
 *   // In controller:
 *   $data = CreateUserData::from($request->validated());
 */
abstract class Data
{
    /**
     * Instantiate DTO from an associative array (e.g. $request->validated()).
     * Maps array keys to constructor parameter names — extra keys are ignored.
     */
    public static function from(array $data): static
    {
        $reflection  = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new static();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name         = $param->getName();
            $args[$name]  = array_key_exists($name, $data)
                ? $data[$name]
                : ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
        }

        return new static(...$args);
    }

    /**
     * Serialize DTO to a plain array (property name → value).
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $result     = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $result[$property->getName()] = $property->getValue($this);
        }

        return $result;
    }

    /**
     * Return a new instance with specific properties overridden.
     * Useful for immutable transformations.
     */
    public function with(array $overrides): static
    {
        return static::from(array_merge($this->toArray(), $overrides));
    }
}
