<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Events;

use Cms\Classes\Controller;

/**
 * Class CmsPageInitComponents
 *
 * @package HydroCommunity\Raindrop\Classes\Events
 */
class CmsPageInitComponents
{
    /**
     * @param Controller $controller
     * @throws \Cms\Classes\CmsException
     */
    public function handle(Controller $controller): void
    {
        // TODO: Only add this component if Hydro Raindrop is enabled and properly configured.
        $controller->addComponent('hydroCommunityHydroFlash', 'hydroCommunityHydroFlash', [], true);
    }
}
