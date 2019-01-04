<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop;

use Backend\Widgets\Form;
use HydroCommunity\Raindrop\Classes\Middleware;
use HydroCommunity\Raindrop\Components\HydroMfa;
use HydroCommunity\Raindrop\Components\HydroSetup;
use HydroCommunity\Raindrop\Models\Settings;
use HydroCommunity\Raindrop\Models\UserMeta;
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

    /**
     * {@inheritdoc}
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'Hydro Raindrop',
            'description' => 'Integrates Hydro Raindrop MFA to OctoberCMS.',
            'author' => 'Hydro Community',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerComponents(): array
    {
        return array_merge(
            (array) parent::registerComponents(),
            [
                HydroMfa::class => 'hydroCommunityHydroMfa',
                HydroSetup::class => 'hydroCommunityHydroSetup',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function registerPermissions(): array
    {
        return [
            'hydrocommunity.raindrop.access_settings' => [
                'tab' => 'Hydro Raindrop',
                'label' => 'Manage Hydro Raindrop settings.',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'Hydro Raindrop',
                'description' => 'Manage Hydro Raindrop settings.',
                'category' => SettingsManager::CATEGORY_USERS,
                'icon' => 'icon-tint',
                'class' => Settings::class,
                'order' => 500,
                'permissions' => ['hydrocommunity.raindrop.access_settings'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();

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

        User::extend(function (User $model) {
            $model->hasOne['meta'] = [
                UserMeta::class,
                'key' => 'user_id',
                'delete' => true,
            ];
            $model->bindEvent('model.afterFetch', function () use ($model) {
                $meta = $model->hasOne(UserMeta::class)->first();
                if ($meta === null) {
                    UserMeta::create([
                        'user_id' => $model->getKey(),
                    ]);
                }
            });
        });

        Event::listen('backend.form.extendFields', function (Form $form) {
            if (!($form->model instanceof User) || !($form->getController() instanceof Users)) {
                return;
            }
            $form->addFields([
                'meta[is_mfa_enabled]' => [
                    'tab' => 'rainlab.user::lang.user.account',
                    'type' => 'switch',
                    'label' => 'Enable Multi Factor Authentication',
                ],
            ]);
        });
    }

    public function register(): void
    {
        parent::register();

        $this->app->register(HydroRaindrop::class);
    }
}
