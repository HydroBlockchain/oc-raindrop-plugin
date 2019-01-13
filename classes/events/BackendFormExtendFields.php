<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Events;

use Backend\Controllers\Users as BackendUserController;
use RainLab\User\Controllers\Users as FrontendUserController;
use Backend\Widgets\Form;
use HydroCommunity\Raindrop\Classes\RequirementChecker;
use HydroCommunity\Raindrop\Models;
use RainLab\User\Models\User as FrontendUser;
use Backend\Models\User as BackendUser;

/**
 * Class BackendFormExtendFields
 *
 * @package HydroCommunity\Raindrop\Classes\Events
 */
class BackendFormExtendFields
{
    /**
     * @param Form $form
     */
    public function handle(Form $form): void
    {
        if (get_class($form->model) === FrontendUser::class
            && $form->getController() instanceof FrontendUserController
        ) {
            $form->addTabFields([
                'meta[is_blocked]@update' => [
                    'tab' => 'rainlab.user::lang.user.account',
                    'comment' => 'Blocked users are not able to sign in.',
                    'type' => 'checkbox',
                    'label' => 'Blocked',
                ],
                'meta[is_blocked]@preview' => [
                    'tab' => 'rainlab.user::lang.user.account',
                    'comment' => 'Blocked users are not able to sign in.',
                    'type' => 'checkbox',
                    'label' => 'Blocked',
                ],
            ]);

            $form->addTabFields([
                'meta[hydro_id]@preview' => [
                    'tab' => 'Hydro Raindrop',
                    'type' => 'text',
                    'label' => 'HydroID',
                    'span' => 'left'
                ],
                'meta[is_mfa_enabled]@preview' => [
                    'tab' => 'Hydro Raindrop',
                    'type' => 'checkbox',
                    'label' => 'MFA enabled',
                    'span' => 'left'
                ],
                'meta[is_mfa_confirmed]@preview' => [
                    'tab' => 'Hydro Raindrop',
                    'type' => 'checkbox',
                    'label' => 'MFA confirmed',
                    'span' => 'left'
                ],
                'meta[mfa_failed_attempts]@preview' => [
                    'tab' => 'Hydro Raindrop',
                    'type' => 'text',
                    'label' => 'MFA failed attempts',
                    'span' => 'left'
                ],
            ]);
        }

        if (get_class($form->model) === BackendUser::class
            && $form->getController() instanceof BackendUserController
        ) {
            $form->addTabFields([
                'meta[is_blocked]@update' => [
                    'tab' => 'backend::lang.user.account',
                    'comment' => 'Blocked users are not able to sign in.',
                    'type' => 'switch',
                    'label' => 'Blocked',
                ],
            ]);
        }

        if ($form->model instanceof Models\Settings) {
            $requirementChecker = new RequirementChecker();
            if (!$requirementChecker->passes()) {
                $form->removeTab('General');
                $form->removeTab('API Settings');
                $form->removeTab('Customization');
            }
        }
    }
}
