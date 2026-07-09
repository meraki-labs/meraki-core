<?php

declare(strict_types=1);

namespace Meraki\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ValidationException extends MerakiException implements HttpExceptionInterface
{
    public function __construct(
        private readonly array $errors,
        string $message = 'The given data was invalid',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 422, $previous);
    }

    public static function withErrors(array $errors, string $message = 'The given data was invalid'): static
    {
        return new static($errors, $message);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
