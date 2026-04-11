<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class FitParseException extends RuntimeException
{
    public static function invalidHeader(): self
    {
        return new self('Invalid FIT file header.');
    }

    public static function invalidSignature(): self
    {
        return new self('Invalid FIT file signature: expected ".FIT".');
    }

    public static function invalidCrc(): self
    {
        return new self('FIT file CRC check failed.');
    }

    public static function truncatedFile(): self
    {
        return new self('FIT file is truncated or corrupt.');
    }

    public static function notAnActivity(): self
    {
        return new self('FIT file is not an activity file (file_id type must be 4).');
    }

    public static function missingSession(): self
    {
        return new self('FIT activity file contains no session data.');
    }
}
