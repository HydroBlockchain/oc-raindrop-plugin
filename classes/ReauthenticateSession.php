<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Session\Store;

/**
 * Class ReauthenticateSession
 *
 * @package HydroCommunity\Raindrop\Classes
 */
final class ReauthenticateSession
{
    /**
     * @var Store
     */
    private $store;

    /**
     * Construct the MFA Session.
     */
    public function __construct()
    {
        $this->store = resolve(Store::class);
    }

    /**
     * @param string $identifier
     */
    public function addPage(string $identifier): void
    {
        $lifetime = (int) Settings::get('mfa_lifetime_reauthentication', 3600);
        $hash = sha1($identifier);

        $this->store->put($this->getKey($hash), time() + $lifetime);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function checkPage(string $identifier): bool
    {
        $key = $this->getKey(sha1($identifier));

        if ($this->store->has($key)) {
            $expiresAt = $this->store->get($key, time() - 1);

            if ($expiresAt - time() < 0) {
                $this->store->forget($key);
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $hash
     * @return string
     */
    private function getKey(string $hash): string
    {
        return 'hydro_community_raindrop_reauthenticate_' . $hash;
    }
}
