<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

/**
 * Class CreateBackendUsersMetaTable
 *
 * @package HydroCommunity\Raindrop\Updates
 */
class CreateBackendUsersMetaTable extends Migration
{
    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function up()
    {
        Schema::create('hydrocommunity_raindrop_backend_users_meta', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('hydro_id')->nullable();
            $table->boolean('is_mfa_enabled')->default(false);
            $table->boolean('is_mfa_confirmed')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->boolean('mfa_failed_attempts')->default(false);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('backend_users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function down()
    {
        Schema::dropIfExists('hydrocommunity_raindrop_backend_users_meta');
    }
}
