<?php

namespace App\Exceptions;

use RuntimeException;

class Risk4SeaException extends RuntimeException
{
    public static function missingToken(): self
    {
        return new self('Risk4Sea token is missing. Set RISK4SEA_TOKEN in the environment.');
    }

    public static function authenticationFailed(): self
    {
        return new self('Risk4Sea authentication failed. Check the configured API token.');
    }

    public static function unexpectedPayload(): self
    {
        return new self('Unexpected Risk4Sea response payload.');
    }

    public static function requestFailed(string $message): self
    {
        return new self("Risk4Sea request failed: {$message}");
    }
}
