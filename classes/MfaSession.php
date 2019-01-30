<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use Backend\Models\User as BackendUser;
use HydroCommunity\Raindrop\Classes\Exceptions\InvalidUserInSession;
use HydroCommunity\Raindrop\Classes\Exceptions\MessageNotFoundInSessionStorage;
use HydroCommunity\Raindrop\Classes\Exceptions\UserIdNotFoundInSessionStorage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store;
use October\Rain\Auth\Models\User;
use RainLab\User\Models\User as FrontEndUser;

/**
 * Class MfaSession
 *
 * @package HydroCommunity\Raindrop\Classes
 */
final class MfaSession
{
    private const KEY_BACKEND = 'hydro_community_raindrop_backend';
    private const KEY_USER = 'hydro_community_raindrop_user';
    private const KEY_ACTION = 'hydro_community_raindrop_action';
    private const KEY_ACTION_PARAMETERS = 'hydro_community_raindrop_action_parameters';
    private const KEY_MESSAGE = 'hydro_community_raindrop_message';
    private const KEY_TIME = 'hydro_community_raindrop_time';
    private const KEY_FLASH_MESSAGE = 'hydro_community_raindrop_flash_message';

    public const ACTION_ENABLE = 'enable';
    public const ACTION_VERIFY = 'verify';
    public const ACTION_DISABLE = 'disable';
    public const ACTION_REAUTHENTICATE = 'reauthenticate';

    /**
     * Session lifetime in seconds.
     *
     * @var int
     */
    private const SESSION_LIFETIME = 90;

    /**
     * @var Store
     */
    private $store;

    /**
     * Construct the MFA Session.
     */
    public function __construct()
    {
        $this->store = resolve(Store::class);
    }

    /**
     * @param bool $backend
     * @param int $userId
     * @return MfaSession
     */
    public function start(bool $backend, int $userId): MfaSession
    {
        $this->destroy();

        $this->store->put(self::KEY_BACKEND, $backend);
        $this->store->put(self::KEY_TIME, time() + self::SESSION_LIFETIME);
        $this->store->put(self::KEY_USER, $userId);

        return $this;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->store->has(self::KEY_TIME);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->store->has(self::KEY_TIME)) {
            return false;
        }

        $time = $this->store->get(self::KEY_TIME);

        return time() <= $time;
    }

    /**
     * @return bool
     */
    public function isBackend(): bool
    {
        return $this->store->get(self::KEY_BACKEND, false);
    }

    /**
     * @return User
     * @throws InvalidUserInSession
     * @throws UserIdNotFoundInSessionStorage
     */
    public function getUser(): User
    {
        /** @var User $user */
        $user = null;

        if (!$this->store->has(self::KEY_USER)) {
            throw new UserIdNotFoundInSessionStorage('User ID not found in session storage.');
        }

        $userId = (int) $this->store->get(self::KEY_USER);

        try {
            if ($this->isBackend()) {
                $user = BackendUser::query()->findOrFail($userId);
            } else {
                $user = FrontEndUser::query()->findOrFail($userId);
            }
        } catch (ModelNotFoundException $e) {
            $this->store->forget(self::KEY_USER);
            throw InvalidUserInSession::withIdentifier($userId);
        }

        return $user;
    }

    /**
     * @return bool
     */
    public function isActionEnable(): bool
    {
        return $this->store->get(self::KEY_ACTION) === self::ACTION_ENABLE;
    }

    /**
     * @return bool
     */
    public function isActionDisable(): bool
    {
        return $this->store->get(self::KEY_ACTION) === self::ACTION_DISABLE;
    }

    /**
     * @return bool
     */
    public function isActionVerify(): bool
    {
        return $this->store->get(self::KEY_ACTION) === self::ACTION_VERIFY;
    }

    /**
     * @return bool
     */
    public function isActionReauthenticate(): bool
    {
        return $this->store->get(self::KEY_ACTION) === self::ACTION_REAUTHENTICATE;
    }

    /**
     * @return array
     */
    public function getActionParameters(): array
    {
        return $this->store->get(self::KEY_ACTION_PARAMETERS, []);
    }

    /**
     * @param string $action
     * @param array $parameters
     * @return MfaSession
     */
    public function setAction(string $action, array $parameters = []): MfaSession
    {
        $this->store->put(self::KEY_ACTION, $action);
        $this->store->put(self::KEY_ACTION_PARAMETERS, $parameters);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAction(): bool
    {
        return $this->store->has(self::KEY_ACTION);
    }

    /**
     * @return MfaSession
     */
    public function forgetAction(): MfaSession
    {
        $this->store->forget(self::KEY_ACTION);
        $this->store->forget(self::KEY_ACTION_PARAMETERS);
        return $this;
    }

    /**
     * @return int
     * @throws MessageNotFoundInSessionStorage
     */
    public function getMessage(): int
    {
        if (!$this->store->has(self::KEY_MESSAGE)) {
            throw new MessageNotFoundInSessionStorage(
                'No message found in session storage. Generate a message first.'
            );
        }

        return $this->store->get(self::KEY_MESSAGE);
    }

    /**
     * @param int $message
     * @return MfaSession
     */
    public function setMessage(int $message): MfaSession
    {
        $this->store->put(self::KEY_MESSAGE, $message);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasMessage(): bool
    {
        return $this->store->has(self::KEY_MESSAGE);
    }

    /**
     * @return MfaSession
     */
    public function forgetMessage(): MfaSession
    {
        $this->store->forget(self::KEY_MESSAGE);
        return $this;
    }

    /**
     * @param string $message
     * @return MfaSession
     */
    public function setFlashMessage(string $message): MfaSession
    {
        $this->store->put(self::KEY_FLASH_MESSAGE, $message);
        return $this;
    }

    /**
     * @return string
     */
    public function getFlashMessage(): string
    {
        return (string) $this->store->pull(self::KEY_FLASH_MESSAGE, '');
    }

    /**
     * @return MfaSession
     */
    public function destroy(): MfaSession
    {
        $this->forgetMessage();
        $this->forgetAction();
        $this->store->forget(self::KEY_USER);
        $this->store->forget(self::KEY_TIME);

        return $this;
    }
}
