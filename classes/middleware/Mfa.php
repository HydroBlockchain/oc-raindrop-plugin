<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\Helpers\UrlHelper;
use HydroCommunity\Raindrop\Classes\MfaUser;
use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Http\Request;
use October\Rain\Auth\Models\User;
use October\Rain\Flash\FlashBag;
use RainLab\User\Classes\AuthManager as FrontEndAuthManager;
use Backend\Classes\AuthManager as BackendAuthManager;

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

        if ($this->mfaSession->isBackend()) {
            $authManager = BackendAuthManager::instance();
        } else {
            $authManager = FrontEndAuthManager::instance();
        }

        $isAuthenticated = $authManager->check();

        $mfaMethod = Settings::get('mfa_method', Settings::MFA_METHOD_PROMPTED);

        if ($path === 'hydro-raindrop/disable') {
            if (!$isAuthenticated || $mfaMethod === Settings::MFA_METHOD_ENFORCED) {
                $this->log->warning(
                    'Hydro Raindrop: Disabling request for Hydro Raindrop is not allowed. '
                    . 'User must be signed in and MFA method must be optional or prompted.'
                );
                return response()->make('Forbidden', 403);
            }

            $this->disable($authManager->getUser());
        }

        if ($path === 'hydro-raindrop/enable') {
            if (!$isAuthenticated || $mfaMethod === Settings::MFA_METHOD_ENFORCED) {
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

        $isBackend = $this->mfaSession->isBackend();

        if (!$this->mfaSession->isValid()) {
            $this->log->warning('Hydro Raindrop: MFA Session time-out detected, redirecting to sign on page.');

            $message = 'Session timed out, please sign in again.';

            if ($isBackend) {
                /** @var FlashBag $flashBag */
                $flashBag = resolve(FlashBag::class);
                $flashBag->info($message);
            } else {
                $this->mfaSession->setFlashMessage($message);
            }

            $this->mfaSession->destroy();

            $this->dispatcher->fire('hydrocommunity.raindrop.mfa.session-timed-out');

            if ($request->ajax()) {
                return response()->json([
                    'X_OCTOBER_REDIRECT' => $urlHelper->getSignOnResponse($isBackend)->getTargetUrl(),
                ]);
            }

            return $urlHelper->getSignOnResponse($isBackend);
        }

        $mfaUser = MfaUser::createFromSession();

        if ($mfaUser->requiresMfaSetup()
            && $urlHelper->getSetupUrl() !== $request->url()
        ) {
            $this->log->info('Hydro Raindrop: User must set up MFA. Redirecting to Setup page.');

            if ($request->ajax()) {
                return response()->json([
                    'X_OCTOBER_REDIRECT' => $urlHelper->getSetupResponse()->getTargetUrl(),
                ]);
            }

            return $urlHelper->getSetupResponse();
        }

        if ($mfaUser->requiresMfa()
            && $urlHelper->getMfaUrl() !== $request->url()
        ) {
            $this->log->info('Hydro Raindrop: User must perform MFA. Redirecting to MFA page.');

            if ($request->ajax()) {
                return response()->json([
                    'X_OCTOBER_REDIRECT' => $urlHelper->getMfaResponse()->getTargetUrl(),
                ]);
            }

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
        $mfaSession->start($this->mfaSession->isBackend(), $user->getKey())
            ->setAction(MfaSession::ACTION_ENABLE)
            ->setFlashMessage('Enter the security code into the Hydro app to enable Hydro Raindrop MFA.');
    }

    /**
     * @param User $user
     */
    private function disable(User $user): void
    {
        $mfaSession = new MfaSession();
        $mfaSession->start($this->mfaSession->isBackend(), $user->getKey())
            ->setAction(MfaSession::ACTION_DISABLE)
            ->setFlashMessage('Enter the security code into the Hydro app to disable Hydro Raindrop MFA.');
    }
}
