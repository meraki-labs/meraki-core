<?php

declare(strict_types=1);

namespace Meraki\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthorizationException extends MerakiException implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'This action is unauthorized',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $previous);
    }

    public static function forAction(string $action, string $resource): static
    {
        return new static("You are not authorized to [{$action}] this [{$resource}].");
    }

    public function getStatusCode(): int
    {
        return 403;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
