<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Updates;

use Illuminate\Support\Facades\Artisan;
use October\Rain\Database\Updates\Migration;
use Throwable;

/**
 * Class InstallDefaultPages
 *
 * @package HydroCommunity\Raindrop\Updates
 */
class InstallDefaultPages extends Migration
{
    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function up()
    {
        try {
            Artisan::call('hydro-community:raindrop:install-pages');
        } catch (Throwable $e) {

        }
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function down()
    {
    }
}
