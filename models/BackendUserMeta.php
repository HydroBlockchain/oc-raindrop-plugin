<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Models;

use Backend\Models\User;
use October\Rain\Database\Model;

/**
 * Class BackendUserMeta
 *
 * @package HydroCommunity\Raindrop\Models
 */
class BackendUserMeta extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'hydrocommunity_raindrop_backend_users_meta';

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
