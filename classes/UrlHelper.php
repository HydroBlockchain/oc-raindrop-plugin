<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use Cms\Helpers\Cms;

/**
 * Class UrlHelper
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class UrlHelper
{
    private const URL_SETUP = '/hydro-raindrop/setup';
    private const URL_MFA = '/hydro-raindrop/mfa';

    /**
     * @var Cms
     */
    private $cmsHelper;

    public function __construct()
    {
        $this->cmsHelper = resolve(Cms::class);
    }

    /**
     * @return string
     */
    public function getSetupUrl(): string
    {
        return $this->cmsHelper->url(self::URL_SETUP);
    }

    /**
     * @return string
     */
    public function getMfaUrl(): string
    {
        return $this->cmsHelper->url(self::URL_MFA);
    }
}
