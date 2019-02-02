<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Backend\Classes\AuthManager;
use Backend\Helpers\Backend as BackendHelper;
use Backend\Models\User;
use Closure;
use Cms\Helpers\Cms;
use Cms\Helpers\Cms as CmsHelper;
use HydroCommunity\Raindrop\Classes\Helpers\UrlHelper;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\MfaUser;
use Illuminate\Http\Request;
use October\Rain\Events\Dispatcher;
use October\Rain\Flash\FlashBag;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class BackendSignOn
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
class BackendSignOn
{
    /**
     * @var CmsHelper
     */
    private $cmsHelper;

    /**
     * @var BackendHelper
     */
    private $backendHelper;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @param Cms $cmsHelper
     * @param BackendHelper $backendHelper
     * @param LoggerInterface $log
     * @param Dispatcher $dispatcher
     * @param FlashBag $flashBag
     */
    public function __construct(
        CmsHelper $cmsHelper,
        BackendHelper $backendHelper,
        LoggerInterface $log,
        Dispatcher $dispatcher,
        FlashBag $flashBag
    ) {
        $this->cmsHelper = $cmsHelper;
        $this->backendHelper = $backendHelper;
        $this->log = $log;
        $this->dispatcher = $dispatcher;
        $this->flashBag = $flashBag;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->isSignOnBlockedRequest($request)) {
            $this->flashBag->error(e(trans('hydrocommunity.raindrop::lang.account.blocked')));
            return $next($request);
        }

        if (!$this->isSignOnRequest($request)) {
            return $next($request);
        }

        try {
            $authManager = AuthManager::instance();

            $loginName = $authManager->createUserModel()->getLoginName();

            /** @var User $user */
            $user = AuthManager::instance()->authenticate([
                $loginName => $request->get('login'),
                'password' => $request->get('password'),
            ]);

            // Immediately logout, authentication was successful.
            AuthManager::instance()->logout();
        } catch (Throwable $e) {
            $this->log->warning('Hydro Raindrop: Backend user could not be found with given credentials.');
            return $next($request);
        }

        $mfaSession = new MfaSession();

        $redirectUri = null;

        $userHelper = new MfaUser($user);

        if ($userHelper->isBlocked()) {
            $this->dispatcher->fire('hydrocommunity.raindrop.backend-user.blocked', [$user]);
            return redirect()->to($this->backendHelper->url('backend/auth/signin') . '?blocked=1');
        }

        $mfaSession->start(true, $user->getKey());

        /*
         * Set up of Hydro Raindrop MFA is required.
         */
        if ($userHelper->requiresMfaSetup()) {
            $this->dispatcher->fire('hydrocommunity.raindrop.backend-user.requires-mfa-setup', [$user]);
            $this->log->info('Backend user authenticates and requires Hydro Raindrop MFA Setup.');
            $redirectUri = UrlHelper::URL_SETUP;
        }

        /*
         * Hydro Raindrop MFA is required to proceed.
         */
        if ($userHelper->requiresMfa()) {
            $this->dispatcher->fire('hydrocommunity.raindrop.backend-user.requires-mfa', [$user]);
            $this->log->info('Backend user authenticates and requires Hydro Raindrop MFA.');
            $redirectUri = UrlHelper::URL_MFA;
        }

        return redirect()->to($this->cmsHelper->url($redirectUri));
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isSignOnBlockedRequest(Request $request): bool
    {
        return $request->url() === $this->backendHelper->url('backend/auth/signin')
            && $request->method() === 'GET'
            && (int) $request->get('blocked') === 1;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isSignOnRequest(Request $request): bool
    {
        /** @var BackendHelper $backendHelper */
        $backendHelper = resolve(BackendHelper::class);

        return $request->url() === $backendHelper->url('backend/auth/signin')
            && $request->method() === 'POST'
            && $request->has('login')
            && $request->has('password');
    }
}
