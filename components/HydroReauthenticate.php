<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Components;

use Cms\Classes\ComponentBase;
use HydroCommunity\Raindrop\Classes\Helpers\UrlHelper;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\ReauthenticateSession;
use RainLab\User\Classes\AuthManager as FrontendAuthManager;

/**
 * Class HydroReauthenticate
 *
 * @package HydroCommunity\Raindrop\Components
 */
class HydroReauthenticate extends ComponentBase
{
    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Hydro Reauthenticate',
            'description' => 'Let users reauthenticate when this component is added to a page.'
        ];
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function onRun()
    {
        if (!FrontendAuthManager::instance()->check()) {
            return null;
        }

        $identifier = $this->controller->getPage()->getId();
        $user = FrontendAuthManager::instance()->getUser();

        $reauthenticate = new ReauthenticateSession();

        if ($reauthenticate->checkPage($identifier)) {
            return null;
        }

        $mfaSession = new MfaSession();
        $mfaSession->start(false, $user->getKey());
        $mfaSession->setAction(
            MfaSession::ACTION_REAUTHENTICATE,
            [
                'identifier' => $identifier,
                'redirect' => $this->controller->pageUrl(
                    $this->controller->getPage()->getFileName()
                )
            ]
        );

        return redirect()->to((new UrlHelper())->getMfaUrl());
    }
}
