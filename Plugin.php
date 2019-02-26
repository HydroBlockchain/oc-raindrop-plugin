<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop;

use HydroCommunity\Raindrop\Classes\EventListener;
use HydroCommunity\Raindrop\Classes\Helpers\PluginHelper;
use HydroCommunity\Raindrop\Components;
use HydroCommunity\Raindrop\Models;
use HydroCommunity\Raindrop\ServiceProviders\HydroRaindrop;
use Illuminate\Contracts\Events\Dispatcher;
use October\Rain\Foundation\Application;
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
     * This plugin should have elevated privileges.
     *
     * @var bool
     */
    public $elevated = true;

    /**
     * {@inheritdoc}
     */
    public $require = [
        'Rainlab.User',
        'Rainlab.Translate',
    ];

    /**
     * @var PluginHelper
     */
    private $helper;

    /**
     * {@inheritdoc}
     * @param Application $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->helper = new PluginHelper($app);
    }

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
            Components\HydroReauthenticate::class => 'hydroCommunityHydroReauthenticate',
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
    public function registerMarkupTags(): array
    {
        return [
            'functions' => [
                'isHydroRaindropMfaMethodEnforced' => function () {
                    $mfaMethod =  Models\Settings::get('mfa_method', Models\Settings::MFA_METHOD_PROMPTED);
                    return $mfaMethod === Models\Settings::MFA_METHOD_ENFORCED;
                }
            ]
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @noinspection PhpMissingDocCommentInspection */
    public function boot()
    {
        /*
         * Do not boot the plugin when PHP version is too low.
         */
        if (!$this->helper->isPhpVersionSupported()) {
            return;
        }

        $this->helper->addMiddleware()
            ->extendBackendUser()
            ->extendFrontEndUser();

        /** @var Dispatcher $eventDispatcher */
        $eventDispatcher = resolve(Dispatcher::class);
        $eventDispatcher->subscribe(EventListener::class);
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function register()
    {
        if (!$this->helper->isPhpVersionSupported()) {
            return;
        }

        $this->app->register(HydroRaindrop::class);
    }
}
