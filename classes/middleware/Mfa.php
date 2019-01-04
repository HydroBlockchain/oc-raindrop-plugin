<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\UrlHelper;
use HydroCommunity\Raindrop\Classes\MfaUser;
use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RainLab\User\Classes\AuthManager;
use RainLab\User\Models\User;

/**
 * Class MfaSetup
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
class Mfa extends BaseMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Exceptions\InvalidUserInSession
     * @throws Exceptions\UserIdNotFoundInSessionStorage
     */
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();

        /*
         * Request for enable or disable Hydro Raindrop MFA:
         *
         * - User must be authenticated.
         * - 'Enable' will start the Setup process.
         * - 'Disable' will start the MFA process (in the Middleware/Mfa class).
         *   When MFA method is enforced. Disabling/Enabling MFA is not allowed.
         */
        $authManager = AuthManager::instance();
        $isAuthenticated = $authManager->check();

        if ($path === 'hydro-raindrop/disable') {
            if (!$isAuthenticated || Settings::get('mfa_method') === Settings::MFA_METHOD_ENFORCED) {
                $this->log->warning(
                    'Hydro Raindrop: Disabling request for Hydro Raindrop is not allowed. '
                    . 'User must be signed in and MFA method must be optional or prompted.'
                );
                return response()->make('Forbidden', 403);
            }

            $this->disable($authManager->getUser());
        }

        if ($path === 'hydro-raindrop/enable') {
            if (!$isAuthenticated || Settings::get('mfa_method') === Settings::MFA_METHOD_ENFORCED) {
                $this->log->warning(
                    'Hydro Raindrop: Enabling request for Hydro Raindrop is not allowed. '
                    . 'User must be signed in and MFA method must be optional or prompted.'
                );
            }

            $this->enable($authManager->getUser());
        }

        if (!$this->mfaSession->isStarted()) {
            return $next($request);
        }

        $urlHelper = new UrlHelper();

        if (!$this->mfaSession->isValid()) {
            $this->log->warning('Hydro Raindrop: MFA Session time-out detected, redirecting to sign on page.');
            // TODO: Set Flash message ->withErrors
            $this->mfaSession->destroy();
            return $urlHelper->getSignOnResponse();
        }

        $mfaUser = MfaUser::createFromSession();

        if ($mfaUser->requiresMfaSetup()
            && $urlHelper->getSetupUrl() !== $request->url()
        ) {
            $this->log->info('Hydro Raindrop: User must set up MFA. Redirecting to Setup page.');
            return $urlHelper->getSetupResponse();
        }

        if ($mfaUser->requiresMfa()
            && $urlHelper->getMfaUrl() !== $request->url()
        ) {
            $this->log->info('Hydro Raindrop: User must perform MFA. Redirecting to MFA page.');
            return $urlHelper->getMfaResponse();
        }

        return $next($request);
    }

    /**
     * @param User $user
     */
    private function enable(User $user): void
    {
        $mfaSession = new MfaSession();
        $mfaSession->start()
            ->setUserId((int) $user->getKey())
            ->setAction(MfaSession::ACTION_ENABLE);
    }

    /**
     * @param User $user
     */
    private function disable(User $user): void
    {
        $mfaSession = new MfaSession();
        $mfaSession->start()
            ->setUserId((int) $user->getKey())
            ->setAction(MfaSession::ACTION_DISABLE);
    }
}
