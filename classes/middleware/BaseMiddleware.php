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
    public const SESSION_KEY_USER = 'hydro_community_raindrop_user_id';

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
