<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Console;

use Cms\Classes\ThemeManager;
use Illuminate\Console\Command;
use System\Classes\UpdateManager;
use Throwable;

/**
 * Class InstallDemoTheme
 *
 * @package HydroCommunity\Raindrop\Console
 */
class InstallDemoTheme extends Command
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->name = 'hydro-community:raindrop:install-demo-theme';
        $this->description = 'Installs Hydro Raindrop Demo theme.';

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
            @set_time_limit(3600);

            $theme = 'HydroCommunity.hydro_raindrop_demo';

            $installedThemes = ThemeManager::instance()->getInstalled();

            if (isset($installedThemes[$theme])) {
                $this->output->warning('Theme already installed.');
                return;
            }

            $manager = UpdateManager::instance();

            $details = $manager->requestThemeDetails($theme);

            $manager->downloadTheme($theme, $details['hash']);
            $manager->extractTheme($theme, $details['hash']);
            $manager->update();
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
        }
    }
}
