<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Contracts;

use October\Rain\Events\Dispatcher;

/**
 * Interface EventSubscriber
 *
 * @package HydroCommunity\Raindrop\Classes\Contracts
 */
interface EventSubscriber
{
    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function subscribe(Dispatcher $dispatcher);
}
