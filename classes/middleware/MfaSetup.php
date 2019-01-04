<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Classes\UrlHelper;
use HydroCommunity\Raindrop\Classes\MfaUser;
use Illuminate\Http\Request;

/**
 * Class MfaSetup
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
class MfaSetup extends BaseMiddleware
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
        if (!$this->mfaSession->isStarted()) {
            return $next($request);
        }

        $urlHelper = new UrlHelper();

        if (!$this->mfaSession->isValid()) {
            // TODO: Set Flash message ->withErrors
            $this->mfaSession->destroy();
            return $urlHelper->getSignOnResponse();
        }

        $userHelper = MfaUser::createFromSession();

        if ($userHelper->requiresMfaSetup()
            && $urlHelper->getSetupUrl() !== $request->url()
        ) {
            return $urlHelper->getSetupResponse();
        }

        return $next($request);
    }
}
