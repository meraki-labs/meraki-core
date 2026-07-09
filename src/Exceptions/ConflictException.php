<?php

declare(strict_types=1);

namespace Meraki\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ConflictException extends MerakiException implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'A conflict occurred with the current state of the resource',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 409, $previous);
    }

    public static function duplicate(string $resource, string $field, string $value): static
    {
        return new static("{$resource} with {$field} [{$value}] already exists.");
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
