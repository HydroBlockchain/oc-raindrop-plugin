<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use HydroCommunity\Raindrop\Classes\MfaSession;
use Psr\Log\LoggerInterface;

/**
 * Class BaseMiddleware
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
abstract class BaseMiddleware
{
    public const SESSION_KEY_USER = 'HydroCommunity.Raindrop.UserId';

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var MfaSession
     */
    protected $mfaSession;

    /**
     * @param LoggerInterface $log
     */
    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
        $this->mfaSession = new MfaSession();
    }
}
