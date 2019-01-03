<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Exceptions;

use RuntimeException;

/**
 * Class InvalidUserInSession
 *
 * @package HydroCommunity\Raindrop\Classes\Exceptions
 */
class InvalidUserInSession extends RuntimeException
{
    /**
     * @param int $identifier
     * @return InvalidUserInSession
     */
    public static function withIdentifier(int $identifier): InvalidUserInSession
    {
        return new self(sprintf('User with ID %d not found.', $identifier));
    }
}
