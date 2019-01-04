<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Components;

use Adrenth\Raindrop;
use Cms\Classes\CodeBase;
use Cms\Classes\ComponentBase;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\UrlHelper;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Http\Request;
use October\Rain\Flash\FlashBag;

/**
 * Class HydroComponentBase
 *
 * @package HydroCommunity\Raindrop\Components
 */
abstract class HydroComponentBase extends ComponentBase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * @var MfaSession
     */
    protected $mfaSession;

    /**
     * @var FlashBag
     */
    protected $flash;

    /**
     * @var Raindrop\Client
     */
    protected $client;

    /**
     * @param CodeBase|null $cmsObject
     * @param array $properties
     */
    public function __construct(
        ?CodeBase $cmsObject = null,
        array $properties = []
    ) {
        parent::__construct($cmsObject, $properties);

        $this->request = resolve(Request::class);
        $this->log = resolve(Log::class);
        $this->urlHelper = new UrlHelper();
        $this->mfaSession = new MfaSession();
        $this->flash = resolve(FlashBag::class);
        $this->client = resolve(Raindrop\Client::class);
    }

    public function onRun()
    {
        parent::onRun();

        $this->page['app_tagline'] = \Backend\Models\BrandSetting::get('app_tagline');
        $this->page['app_name'] = \Backend\Models\BrandSetting::get('app_name');
        $this->page['app_logo'] = \Backend\Models\BrandSetting::getLogo();
    }
}
