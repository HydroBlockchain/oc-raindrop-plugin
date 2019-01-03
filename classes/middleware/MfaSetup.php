<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Classes\UrlHelper;
use HydroCommunity\Raindrop\Classes\UserHelper;
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
        if (!$this->sessionHelper->hasUserId()) {
            return $next($request);
        }

        $userHelper = UserHelper::createFromSession();
        $urlHelper = new UrlHelper();

        if ($userHelper->requiresMfaSetup()
            && $urlHelper->getSetupUrl() !== $request->url()
        ) {
            return response()->redirectTo($urlHelper->getSetupUrl());
        }

        return $next($request);
    }
}
