<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use Adrenth\Raindrop\ApiAccessToken;
use Adrenth\Raindrop\Exception\UnableToAcquireAccessToken;
use Adrenth\Raindrop\TokenStorage\TokenStorage;
use Illuminate\Cache\Repository;

/**
 * Class ApiTokenStorage
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class ApiTokenStorage implements TokenStorage
{
    const CACHE_KEY = 'HydroCommunity.Raindrop.Token';

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     * @throws UnableToAcquireAccessToken
     */
    public function getAccessToken(): ApiAccessToken
    {
        if (!$this->cache->has(self::CACHE_KEY)) {
            throw new UnableToAcquireAccessToken('Access Token is not found in the storage.');
        }

        $token = $this->cache->get(self::CACHE_KEY);

        if ($token instanceof ApiAccessToken) {
            return $token;
        }

        $this->unsetAccessToken();

        throw new UnableToAcquireAccessToken('Access Token is not found in the storage.');
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken(ApiAccessToken $token): void
    {
        $this->cache->forever(self::CACHE_KEY, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function unsetAccessToken(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
