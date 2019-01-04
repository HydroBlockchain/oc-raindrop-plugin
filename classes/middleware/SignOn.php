<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\MfaUser;
use Illuminate\Http\Request;
use October\Rain\Auth\AuthException;
use RainLab\User\Classes\AuthManager;
use RainLab\User\Models\User;
use Throwable;

/**
 * Class SignOnMiddleware
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
class SignOn extends BaseMiddleware
{
    /**
     * Intercept the Sign-on request (if applicable).
     *
     * @param $request
     * @param Closure $next
     * @return mixed
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
                $loginName => $request->request->get('login'),
                'password' => $request->request->get('password'),
            ]);
        } catch (Throwable $e) {
            return $next($request);
        }

        $redirectUri = null;

        $userHelper = new MfaUser($user);

        if ($userHelper->isBlocked()) {
            throw new AuthException(trans('Your account has been blocked.'));
        }

        $this->mfaSession
            ->start()
            ->setUserId($user->getKey());

        /*
         * Set up of Hydro Raindrop MFA is required.
         */
        if ($userHelper->requiresMfaSetup()) {
            $this->log->info('User authenticates and requires Hydro Raindrop MFA Setup.');
            $redirectUri = '/hydro-raindrop/setup';
        }

        /*
         * Hydro Raindrop MFA is required to proceed.
         */
        if ($userHelper->requiresMfa()) {
            $this->log->info('User authenticates and requires Hydro Raindrop MFA.');
            $redirectUri = '/hydro-raindrop/mfa';
        }

        return response()->json([
            'X_OCTOBER_REDIRECT' => $redirectUri,
        ]);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isSignOnRequest(Request $request): bool
    {
        return $request->ajax()
            && $request->hasHeader('X-OCTOBER-REQUEST-HANDLER')
            && $request->header('X-OCTOBER-REQUEST-HANDLER') === 'onSignin'
            && $request->request->has('login')
            && $request->request->has('password');
    }
}
