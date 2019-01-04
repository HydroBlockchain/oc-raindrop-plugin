<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes\Middleware;

use Closure;
use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Classes\UrlHelper;
use HydroCommunity\Raindrop\Classes\MfaUser;
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
        if (!$this->mfaSession->isStarted()) {
            return $next($request);
        }

        $urlHelper = new UrlHelper();

        if (!$this->mfaSession->isValid()) {
            $this->mfaSession->destroy();
            return $urlHelper->getSignOnResponse();
        }

        $user = $this->mfaSession->getUser();

        $userHelper = new MfaUser($user);

        if ($userHelper->requiresMfa()
            && $urlHelper->getMfaUrl() !== $request->url()
        ) {
            return $urlHelper->getMfaResponse();
        }

        return $next($request);
    }
}
