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
use October\Rain\Auth\AuthException;
use October\Rain\Events\Dispatcher;
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
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @param Cms $cmsHelper
     * @param LoggerInterface $log
     * @param Dispatcher $dispatcher
     */
    public function __construct(CmsHelper $cmsHelper, LoggerInterface $log, Dispatcher $dispatcher)
    {
        $this->cmsHelper = $cmsHelper;
        $this->log = $log;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     * @throws AuthException
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->isSignOnRequest($request)) {
            return $next($request);
        }

        try {
            $authManager = AuthManager::instance();

            $loginName = $authManager->createUserModel()->getLoginName();

            /** @var User $user */
            $user = AuthManager::instance()->findUserByCredentials([
                $loginName => $request->get('login'),
                'password' => $request->get('password'),
            ]);
        } catch (Throwable $e) {
            $this->log->warning('Hydro Raindrop: Backend user could not be found with given credentials.');
            return $next($request);
        }

        $mfaSession = new MfaSession();

        $redirectUri = null;

        $userHelper = new MfaUser($user);

        if ($userHelper->isBlocked()) {
            $this->dispatcher->fire('hydrocommunity.raindrop.backend-user.blocked', [$user]);
            throw new AuthException(trans('Your account has been blocked.'));
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
