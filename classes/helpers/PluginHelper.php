<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Helpers;

use Backend\Classes\AuthManager as BackendAuthManager;
use Backend\Controllers\Users as BackendUserController;
use Backend\Models\User as BackendUser;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\Middleware;
use HydroCommunity\Raindrop\Classes\RequirementChecker;
use HydroCommunity\Raindrop\Models;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Session\Middleware\StartSession;
use October\Rain\Foundation\Application;
use October\Rain\Support\Facades\Schema;
use RainLab\User\Models\User as FrontEndUser;

/**
 * Class PluginHelper
 *
 * @package HydroCommunity\Raindrop\Classes\Helpers
 */
final class PluginHelper
{
    /**
     * @var $application
     */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return bool
     */
    public function isPhpVersionSupported(): bool
    {
        return version_compare(PHP_VERSION, '7.1.0') >= 0;
    }

    /**
     * Add the middleware which is necessary to intercept requests.
     *
     * @return PluginHelper
     */
    public function addMiddleware(): self
    {
        $shouldAddMiddleware = !$this->application->runningInConsole()
            && !$this->application->runningUnitTests()
            && (new RequirementChecker())->passes();

        if ($shouldAddMiddleware) {
            /** @var \October\Rain\Foundation\Http\Kernel $kernel */
            $kernel = resolve(Kernel::class);
            $kernel->prependMiddleware(Middleware\Mfa::class)
                ->prependMiddleware(Middleware\BackendSignOn::class)
                ->prependMiddleware(Middleware\FrontendSignOn::class)
                ->prependMiddleware(StartSession::class); // Make sure the session is available.
        }

        return $this;
    }

    /**
     * Extend the Front-end user.
     *
     * - Add 'meta' relation to model
     * - Add meta-record to database if not exists.
     *
     * @return PluginHelper
     */
    public function extendFrontEndUser(): self
    {
        $shouldExtend = Schema::hasTable('hydrocommunity_raindrop_users_meta');

        if (!$shouldExtend) {
            return $this;
        }

        FrontEndUser::extend(function (FrontEndUser $model) {
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

        return $this;
    }

    /**
     * Extend the Back-end user.
     *
     * - Add 'meta' relation to model
     * - Add meta-record to database if not exists.
     *
     * @return PluginHelper
     */
    public function extendBackendUser(): self
    {
        $shouldExtend = Schema::hasTable('hydrocommunity_raindrop_backend_users_meta');

        if (!$shouldExtend) {
            return $this;
        }

        BackendUser::extend(function (BackendUser $model) {
            $model->hasOne['meta'] = [
                Models\BackendUserMeta::class,
                'key' => 'user_id',
                'delete' => true
            ];
            $model->bindEvent('model.afterFetch', function () use ($model) {
                $meta = $model->hasOne(Models\BackendUserMeta::class)->first();
                if ($meta === null) {
                    Models\BackendUserMeta::create([
                        'user_id' => $model->getKey(),
                    ]);
                }
            });
        });

        BackendUserController::extend(function (BackendUserController $controller) {
            $controller->addDynamicMethod('onEnableHydroRaindropMfa', function () {
                (new MfaSession())->start(true, BackendAuthManager::instance()->getUser()->getKey());

                return response()->redirectTo('/hydro-raindrop/enable');
            });

            $controller->addDynamicMethod('onDisableHydroRaindropMfa', function () {
                (new MfaSession())->start(true, BackendAuthManager::instance()->getUser()->getKey());

                return response()->redirectTo('/hydro-raindrop/disable');
            });
        });

        return $this;
    }
}
