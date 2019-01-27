<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use HydroCommunity\Raindrop\Classes\Events;
use October\Rain\Events\Dispatcher;
use October\Rain\Foundation\Application;

/**
 * Class EventListener
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class EventListener
{
    /**
     * @param Dispatcher $dispatcher
     */
    public function subscribe(Dispatcher $dispatcher): void
    {
        /** @var Application $application */
        $application = resolve(Application::class);

        if ($application->runningInBackend()
            && !$application->runningInConsole()
        ) {
            $dispatcher->listen('backend.form.extendFields', Events\BackendFormExtendFields::class);
        }

        if (!$application->runningInBackend()
            && !$application->runningInConsole()
        ) {
            $dispatcher->listen('cms.page.initComponents', Events\CmsPageInitComponents::class);
            $dispatcher->listen('cms.page.renderPartial', Events\CmsPageRenderPartial::class);
        }
    }
}
