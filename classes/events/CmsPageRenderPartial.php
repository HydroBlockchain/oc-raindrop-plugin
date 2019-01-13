<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Events;

use Cms\Classes\Controller;

/**
 * Class CmsPageRenderPartial
 *
 * @package HydroCommunity\Raindrop\Classes\Events
 */
class CmsPageRenderPartial
{
    /**
     * @param Controller $controller
     * @param string $partialName
     * @param string $partialContent
     * @return string
     */
    public function handle(Controller $controller, string $partialName, string $partialContent): string
    {
        // TODO: Only add this component if Hydro Raindrop is enabled and properly configured.
        /*
         * Add the Hydro Flash Component to the RainLab.User SignIn component so that
         * Hydro MFA messages will be rendered at the top of the Sign In form.
         */
        if ($partialName === 'account::signin') {
            $controller->addCss('/plugins/hydrocommunity/raindrop/assets/css/hydro-raindrop.css');
            $result = (string) $controller->renderComponent('hydroCommunityHydroFlash', ['type' => 'error']);
            $partialContent = $result . $partialContent;
        }

        return $partialContent;
    }
}
