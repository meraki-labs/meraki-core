<?php

namespace Meraki\Core\Template;

class ConflictedFile
{
    public function __construct(
        public readonly string $relativePath,
        public readonly string $manifestChecksum,
        public readonly string $currentChecksum,
    ) {}
}
