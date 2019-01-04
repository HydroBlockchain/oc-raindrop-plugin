<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Models;
use RainLab\User\Models\User;

/**
 * Class MfaUser
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class MfaUser
{
    /**
     * @var User
     */
    private $userModel;

    /**
     * @var MfaSession
     */
    private $sessionHelper;

    /**
     * @param User $userModel
     */
    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
        $this->sessionHelper = new MfaSession();
    }

    /**
     * @return MfaUser
     * @throws Exceptions\UserIdNotFoundInSessionStorage
     * @throws Exceptions\InvalidUserInSession
     */
    public static function createFromSession(): self
    {
        return new self((new MfaSession())->getUser());
    }

    /**
     * @return User
     */
    public function getUserModel(): User
    {
        return $this->userModel;
    }

    /**
     * @return string
     */
    public function getHydroId(): string
    {
        return (string) $this->userModel->meta->getAttribute('hydro_id');
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return (bool) $this->userModel->meta->getAttribute('is_blocked');
    }

    /**
     * @return bool
     */
    public function requiresMfa(): bool
    {
        /** @var Models\UserMeta $meta */
        $meta = $this->userModel->meta;

        $hydroId = $meta->getAttribute('hydro_id');
        $mfaEnabled = $meta->getAttribute('is_mfa_enabled');
        $mfaConfirmed = $meta->getAttribute('is_mfa_confirmed');

        return !empty($hydroId)
            && $mfaEnabled
            && ($mfaConfirmed || $this->sessionHelper->isActionVerify());
    }

    /**
     * @return bool
     */
    public function requiresMfaSetup(): bool
    {
        if ($this->sessionHelper->isActionEnable()) {
            return true;
        }

        $method = Models\Settings::get('mfa_method', Models\Settings::MFA_METHOD_PROMPTED);

        switch ($method) {
            case Models\Settings::MFA_METHOD_OPTIONAL:
                return false;
            case Models\Settings::MFA_METHOD_PROMPTED:
            case Models\Settings::MFA_METHOD_ENFORCED:
                return !$this->requiresMfa();
        }

        return false;
    }
}
