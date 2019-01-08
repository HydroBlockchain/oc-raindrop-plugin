<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop;

use Backend\Widgets\Form;
use Cms\Classes\Controller;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\Middleware;
use HydroCommunity\Raindrop\Classes\RequirementChecker;
use HydroCommunity\Raindrop\Components;
use HydroCommunity\Raindrop\Models;
use HydroCommunity\Raindrop\ServiceProviders\HydroRaindrop;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use October\Rain\Foundation\Application;
use RainLab\User\Controllers\Users;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;

/**
 * Class Plugin
 *
 * @package HydroCommunity\Raindrop
 */
class Plugin extends PluginBase
{
    /**
     * {@inheritdoc}
     */
    public $require = [
        'Rainlab.User',
        'Rainlab.Translate',
    ];

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @noinspection PhpMissingDocCommentInspection */
    public function pluginDetails(): array
    {
        return [
            'name' => 'Hydro Raindrop',
            'description' => 'Integrates Hydro Raindrop MFA to OctoberCMS.',
            'author' => 'Hydro Community',
            'icon' => 'icon-leaf',
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @noinspection PhpMissingDocCommentInspection */
    public function registerComponents(): array
    {
        return [
            Components\HydroMfa::class => 'hydroCommunityHydroMfa',
            Components\HydroSetup::class => 'hydroCommunityHydroSetup',
            Components\HydroFlash::class => 'hydroCommunityHydroFlash',
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @noinspection PhpMissingDocCommentInspection */
    public function registerPermissions(): array
    {
        return [
            'hydrocommunity.raindrop.access_settings' => [
                'tab' => 'Hydro Raindrop',
                'label' => 'Manage Hydro Raindrop settings.',
            ],
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @noinspection PhpMissingDocCommentInspection */
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'Hydro Raindrop',
                'description' => 'Manage Hydro Raindrop settings.',
                'category' => SettingsManager::CATEGORY_USERS,
                'icon' => 'icon-tint',
                'class' => Models\Settings::class,
                'order' => 500,
                'permissions' => ['hydrocommunity.raindrop.access_settings'],
            ],
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @noinspection PhpMissingDocCommentInspection */
    public function boot()
    {
        /*
         * Do not boot the plugin when PHP version is too low.
         */
        if (version_compare(PHP_VERSION, '7.1.0') <= 0) {
            return;
        }

        /** @var Application $application */
        $application = resolve(Application::class);

        $shouldAddMiddleware = !$application->runningInBackend()
            && !$application->runningInConsole()
            && !$application->runningUnitTests();

        if ($shouldAddMiddleware) {
            /** @var \October\Rain\Foundation\Http\Kernel $kernel */
            $kernel = resolve(Kernel::class);
            $kernel->prependMiddleware(Middleware\Mfa::class)
                ->prependMiddleware(Middleware\SignOn::class)
                ->prependMiddleware(StartSession::class); // Make sure the session is available.
        }

        /*
         * Add database relation to User model.
         */
        User::extend(function (User $model) {
            $model->hasOne['meta'] = [
                Models\UserMeta::class,
                'key' => 'user_id',
                'delete' => true,
            ];
            $model->bindEvent('model.afterFetch', function () use ($model) {
                $meta = $model->hasOne(Models\UserMeta::class)->first();
                if ($meta === null) {
                    Models\UserMeta::create([
                        'user_id' => $model->getKey(),
                    ]);
                }
            });
        });

        /*
         * Add the "blocked" form element to the User edit form.
         */
        Event::listen('backend.form.extendFields', function (Form $form) {
            if ($form->model instanceof User || $form->getController() instanceof Users) {
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
        });

        /*
         * Add the HydroFlash component to each and every page.
         */
        Event::listen(
            'cms.page.initComponents',
            function (Controller $controller) {
                $controller->addComponent('hydroCommunityHydroFlash', 'hydroCommunityHydroFlash', [], true);
            }
        );

        /*
         * Add the Hydro Flash Component to the RainLab.User SignIn component so that
         * Hydro MFA messages will be rendered at the top of the Sign In form.
         */
        Event::listen(
            'cms.page.renderPartial',
            function (Controller $controller, string $partialName, string &$partialContent) {
                if ($partialName === 'account::signin') {
                    $controller->addCss('/plugins/hydrocommunity/raindrop/assets/css/hydro-raindrop.css');
                    $result = (string) $controller->renderComponent('hydroCommunityHydroFlash', ['type' => 'error']);
                    $partialContent = $result . $partialContent;
                }
            }
        );
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function register()
    {
        if (version_compare(PHP_VERSION, '7.1.0') <= 0) {
            return;
        }

        $this->app->register(HydroRaindrop::class);
    }
}
