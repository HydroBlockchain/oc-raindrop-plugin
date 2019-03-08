<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Console;

use Cms\Classes\Page;
use Illuminate\Console\Command;
use Throwable;

/**
 * Class InstallPages
 *
 * @package HydroCommunity\Raindrop\Console
 */
class InstallPages extends Command
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->name = 'hydro-community:raindrop:install-pages';
        $this->description = 'Installs required pages for the Hydro Raindrop plugin.';

        parent::__construct();
    }

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->createMfaPage();
        } catch (Throwable $e) {
            $this->output->error('Could not create MFA page: ' . $e->getMessage());
        }

        try {
            $this->createSetupPage();
        } catch (Throwable $e) {
            $this->output->error('Could not create Setup page: ' . $e->getMessage());
        }

        try {
            $this->createProfilePage();
        } catch (Throwable $e) {
            $this->output->error('Could not create Profile page: ' . $e->getMessage());
        }
    }

    private function createMfaPage()
    {
        Page::create([
            'fileName' => 'hydro/mfa',
            'url' => '/hydro-raindrop/mfa',
            'title' => 'Hydro Raindrop MFA',
            'description' => 'Hydro Raindrop MFA page.',
            'is_hidden' => 0,
            'settings' => [
                'hydroCommunityHydroMfa' => []
            ],
            'markup' => '{% component \'hydroCommunityHydroMfa\' %}'
        ]);
    }

    private function createSetupPage()
    {
        Page::create([
            'fileName' => 'hydro/setup',
            'url' => '/hydro-raindrop/setup',
            'title' => 'Hydro Raindrop Setup',
            'description' => 'Hydro Raindrop Setup page.',
            'is_hidden' => 0,
            'settings' => [
                'hydroCommunityHydroSetup' => []
            ],
            'markup' => '{% component \'hydroCommunityHydroSetup\' %}'
        ]);
    }

    private function createProfilePage()
    {
        Page::create([
            'fileName' => 'hydro/profile',
            'url' => '/hydro-raindrop/profile',
            'title' => 'Hydro Raindrop Setup',
            'description' => 'Hydro Raindrop Profile page.',
            'is_hidden' => 0,
            'settings' => [
                'account' => []
            ],
            'markup' => file_get_contents(plugins_path('hydrocommunity/raindrop/data/profile.htm'))
        ]);
    }
}
