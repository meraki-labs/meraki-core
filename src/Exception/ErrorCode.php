<?php

namespace Meraki\Core\Exception;

/**
 * @Author DatPA
 */
enum ErrorCode: string
{
    case NOT_FOUND = 'NOT_FOUND';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
}