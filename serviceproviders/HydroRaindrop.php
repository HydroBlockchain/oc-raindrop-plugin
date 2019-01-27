<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\ServiceProviders;

use Adrenth\Raindrop;
use HydroCommunity\Raindrop\Classes\ApiTokenStorage;
use HydroCommunity\Raindrop\Models\Settings;
use October\Rain\Support\ServiceProvider;

/**
 * Class HydroRaindrop
 *
 * @package HydroCommunity\ServiceProviders
 */
class HydroRaindrop extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Raindrop\ApiSettings::class, function () {
            return new Raindrop\ApiSettings(
                Settings::get('client_id', ''),
                Settings::get('client_secret', ''),
                Settings::get('environment', 'sandbox') === 'sandbox'
                    ? new Raindrop\Environment\SandboxEnvironment()
                    : new Raindrop\Environment\ProductionEnvironment()
            );
        });

        $this->app->bind(ApiTokenStorage::class, function () {
            return new ApiTokenStorage($this->app->get('cache.store'));
        });

        $this->app->alias(
            ApiTokenStorage::class,
            Raindrop\TokenStorage\TokenStorage::class
        );

        $this->app->bind(Raindrop\Client::class, function () {
            return new Raindrop\Client(
                app(Raindrop\ApiSettings::class),
                app(Raindrop\TokenStorage\TokenStorage::class),
                Settings::get('application_id', '')
            );
        });
    }
}
