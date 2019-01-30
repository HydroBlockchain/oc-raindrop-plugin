<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use HydroCommunity\Raindrop\Classes\MfaSession;
use October\Rain\Events\Dispatcher;
use Psr\Log\LoggerInterface;

/**
 * Class BaseMiddleware
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
abstract class BaseMiddleware
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var MfaSession
     */
    protected $mfaSession;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @param LoggerInterface $log
     * @param Dispatcher $dispatcher
     */
    public function __construct(LoggerInterface $log, Dispatcher $dispatcher)
    {
        $this->log = $log;
        $this->mfaSession = new MfaSession();
        $this->dispatcher = $dispatcher;
    }
}
