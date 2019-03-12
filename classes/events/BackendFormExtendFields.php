<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Events;

use Backend\Classes\FormTabs;
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
                    'comment' => 'Blockedw users are not able to sign in.',
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

        /*
         * Add "Blocked" form field to Backend User form.
         */
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

        /*
         * Add form element for enabling/disabling Hydro Raindrop MFA
         */
        if (get_class($form->model) === BackendUser::class
            && $form->getController() instanceof BackendUserController
            && $form->getContext() === 'myaccount'
        ) {
            $form->addTabFields([
                '_backend_user_hydro_raindrop@myaccount' => [
                    'tab' => 'backend::lang.user.account',
                    'label' => 'Hydro Raindrop MFA',
                    'span' => 'left',
                    'type' => 'partial',
                    'path' => '$/hydrocommunity/raindrop/views/_backend_user_hydro_raindrop.htm'
                ]
            ]);
        }

        if ($form->model instanceof Models\Settings) {
            $requirementChecker = new RequirementChecker();

            if (!$requirementChecker->passesRequirement(RequirementChecker::REQUIREMENT_API_SETTINGS)) {
                /** @var FormTabs $formTabs */
                $formTabs = $form->getTabs()->primary;
                $formTabs->icons = [
                    'API Settings' => 'text-danger icon-warning',
                ];
            }

            $form->addTabFields([
                '_reload' => [
                    'tab' => 'API Settings',
                    'type' => 'partial',
                    'path' => '$/hydrocommunity/raindrop/views/_backend_settings_reload.htm'
                ]
            ]);
        }
    }
}
