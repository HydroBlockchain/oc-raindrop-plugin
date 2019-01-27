<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Models;

use October\Rain\Database\Model;
use RainLab\User\Models\User;

/**
 * Class UserMeta
 *
 * @package HydroCommunity\Raindrop\Models
 */
class UserMeta extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'hydrocommunity_raindrop_users_meta';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'user_id'
    ];

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'user' => User::class
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'is_mfa_enabled' => 'bool',
        'is_mfa_confirmed' => 'bool',
        'is_blocked' => 'bool',
    ];
}
