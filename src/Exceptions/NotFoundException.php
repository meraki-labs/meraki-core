<?php

declare(strict_types=1);

namespace Meraki\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class NotFoundException extends MerakiException implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'Resource not found',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 404, $previous);
    }

    public static function forModel(string $model, int|string $id): static
    {
        return new static("{$model} [{$id}] not found.");
    }

    public function getStatusCode(): int
    {
        return 404;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
