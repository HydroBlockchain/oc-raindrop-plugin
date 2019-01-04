<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use Cms\Classes\Page;
use Cms\Helpers\Cms;
use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Http\RedirectResponse;

/**
 * Class UrlHelper
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class UrlHelper
{
    public const URL_SETUP = '/hydro-raindrop/setup';
    public const URL_MFA = '/hydro-raindrop/mfa';

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
     * @return RedirectResponse
     */
    public function getSetupResponse(): RedirectResponse
    {
        return redirect()->to($this->getSetupUrl());
    }

    /**
     * @return string
     */
    public function getMfaUrl(): string
    {
        return $this->cmsHelper->url(self::URL_MFA);
    }

    /**
     * @return RedirectResponse
     */
    public function getMfaResponse(): RedirectResponse
    {
        return redirect()->to($this->getMfaUrl());
    }

    /**
     * @return RedirectResponse
     */
    public function getSignOnResponse(): RedirectResponse
    {
        $page = Settings::get('page_sign_on');
        $url = '/';

        if (!empty($page)) {
            $url = Page::url($page);
        }

        return redirect()->to($url);
    }

    /**
     * @return RedirectResponse
     */
    public function getRedirectResponse(): RedirectResponse
    {
        $page = Settings::get('page_redirect');

        if ($page === '') {
            return redirect()->refresh();
        }

        return redirect()->to(Page::url($page));
    }
}
