<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Events;

use Backend\Widgets\Form;
use HydroCommunity\Raindrop\Classes\RequirementChecker;
use HydroCommunity\Raindrop\Models;
use RainLab\User\Controllers;
use RainLab\User\Models\User as FrontEndUser;

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
        if (get_class($form->model) === FrontEndUser::class
            || $form->getController() instanceof Controllers\Users
        ) {
            /*
             * Add the "blocked" form element to the User edit form.
            */
            $form->addFields([
                'meta[is_blocked]' => [
                    'tab' => 'rainlab.user::lang.user.account',
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
