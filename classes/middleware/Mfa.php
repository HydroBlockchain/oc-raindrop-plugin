<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Classes\UrlHelper;
use HydroCommunity\Raindrop\Classes\UserHelper;
use Illuminate\Http\Request;

/**
 * Class Mfa
 *
 * @package HydroCommunity\Raindrop\Classes\Middleware
 */
class Mfa extends BaseMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Exceptions\UserIdNotFoundInSessionStorage
     * @throws Exceptions\InvalidUserInSession
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->sessionHelper->hasUserId()) {
            return $next($request);
        }

        $user = $this->sessionHelper->getUser();

        $userHelper = new UserHelper($user);
        $urlHelper = new UrlHelper();

        if ($userHelper->requiresMfa()
            && $urlHelper->getMfaUrl() !== $request->url()
        ) {
            return response()->redirectTo($urlHelper->getMfaUrl());
        }

        return $next($request);
    }
}
