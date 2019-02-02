<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use HydroCommunity\Raindrop\Classes\Exceptions;
use HydroCommunity\Raindrop\Models;
use October\Rain\Auth\Models\User;
use October\Rain\Database\Model;

/**
 * Class MfaUser
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class MfaUser
{
    /**
     * The Front-End or Back-End user model.
     *
     * @var User
     */
    private $userModel;

    /**
     * @var MfaSession
     */
    private $mfaSession;

    /**
     * @param User $userModel
     */
    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
        $this->mfaSession = new MfaSession();
    }

    /**
     * @return MfaUser
     * @throws Exceptions\InvalidUserInSession
     * @throws Exceptions\UserIdNotFoundInSessionStorage
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
     * Whether MFA is required for this user.
     *
     * @return bool
     */
    public function requiresMfa(): bool
    {
        /** @var Model $meta */
        $meta = $this->userModel->meta;

        $hydroId = $meta->getAttribute('hydro_id');
        $mfaEnabled = $meta->getAttribute('is_mfa_enabled');
        $mfaConfirmed = $meta->getAttribute('is_mfa_confirmed');

        return !empty($hydroId)
            && $mfaEnabled
            && ($mfaConfirmed || $this->mfaSession->isActionVerify() || $this->mfaSession->isActionDisable());
    }

    /**
     * Whether MFA Setup is required for this user.
     *
     * @return bool
     */
    public function requiresMfaSetup(): bool
    {
        if ($this->mfaSession->isActionEnable()) {
            return true;
        }

        if ($this->mfaSession->isActionDisable()) {
            return false;
        }

        if ($this->mfaSession->isBackend()) {
            $method = Models\Settings::get(
                'mfa_method_backend',
                Models\Settings::MFA_METHOD_PROMPTED
            );
        } else {
            $method = Models\Settings::get(
                'mfa_method',
                Models\Settings::MFA_METHOD_PROMPTED
            );
        }

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
