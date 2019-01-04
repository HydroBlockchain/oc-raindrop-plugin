<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use HydroCommunity\Raindrop\Classes\Exceptions\InvalidUserInSession;
use HydroCommunity\Raindrop\Classes\Exceptions\MessageNotFoundInSessionStorage;
use HydroCommunity\Raindrop\Classes\Exceptions\UserIdNotFoundInSessionStorage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store;
use RainLab\User\Models\User;

/**
 * Class MfaSession
 *
 * @package HydroCommunity\Raindrop\Classes
 */
final class MfaSession
{
    private const SESSION_KEY_USER = 'HydroCommunity.Raindrop.User';
    private const SESSION_KEY_ACTION = 'HydroCommunity.Raindrop.Action';
    private const SESSION_KEY_MESSAGE = 'HydroCommunity.Raindrop.Message';
    private const SESSION_KEY_TIME = 'HydroCommunity.Raindrop.Time';

    public const ACTION_ENABLE = 'enable';
    public const ACTION_VERIFY = 'verify';
    public const ACTION_DISABLE = 'disable';

    /**
     * Session lifetime in seconds.
     *
     * @var int
     */
    private const SESSION_LIFETIME = 10;

    /**
     * @var Store
     */
    private $store;

    /**
     */
    public function __construct()
    {
        $this->store = resolve(Store::class);
    }

    /**
     * @return MfaSession
     */
    public function start(): self
    {
        $this->destroy();

        $this->store->put(
            self::SESSION_KEY_TIME,
            time() + self::SESSION_LIFETIME
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->store->has(self::SESSION_KEY_TIME);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->store->has(self::SESSION_KEY_TIME)) {
            return false;
        }

        $time = $this->store->get(self::SESSION_KEY_TIME);

        return time() <= $time;
    }

    /**
     * @return User
     * @throws InvalidUserInSession
     * @throws UserIdNotFoundInSessionStorage
     */
    public function getUser(): User
    {
        $user = null;

        $sessionHelper = new MfaSession();

        $userId = $sessionHelper->getUserId();

        try {
            /** @var User $user */
            $user = User::query()->findOrFail($userId);
        } catch (ModelNotFoundException $e) {
            $sessionHelper->forgetUserId();
            throw InvalidUserInSession::withIdentifier($userId);
        }

        return $user;
    }

    /**
     * @return int
     * @throws UserIdNotFoundInSessionStorage
     */
    public function getUserId(): int
    {
        if (!$this->hasUserId()) {
            throw new UserIdNotFoundInSessionStorage('User ID not found in session storage.');
        }

        return (int) $this->store->get(self::SESSION_KEY_USER);
    }

    /**
     * @param int $userId
     * @return MfaSession
     */
    public function setUserId(int $userId): MfaSession
    {
        $this->store->put(self::SESSION_KEY_USER, $userId);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasUserId(): bool
    {
        return $this->store->has(self::SESSION_KEY_USER);
    }

    /**
     * @return MfaSession
     */
    public function forgetUserId(): MfaSession
    {
        $this->store->forget(self::SESSION_KEY_USER);
        return $this;
    }

    /**
     * @return bool
     */
    public function isActionEnable(): bool
    {
        return $this->store->get(self::SESSION_KEY_ACTION) === self::ACTION_ENABLE;
    }

    /**
     * @return bool
     */
    public function isActionDisable(): bool
    {
        return $this->store->get(self::SESSION_KEY_ACTION) === self::ACTION_DISABLE;
    }

    /**
     * @return bool
     */
    public function isActionVerify(): bool
    {
        return $this->store->get(self::SESSION_KEY_ACTION) === self::ACTION_VERIFY;
    }

    /**
     * @param string $action
     * @return MfaSession
     */
    public function setAction(string $action): MfaSession
    {
        $this->store->put(self::SESSION_KEY_ACTION, $action);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAction(): bool
    {
        return $this->store->has(self::SESSION_KEY_ACTION);
    }

    /**
     * @return MfaSession
     */
    public function forgetAction(): MfaSession
    {
        $this->store->forget(self::SESSION_KEY_ACTION);
        return $this;
    }

    /**
     * @return int
     * @throws MessageNotFoundInSessionStorage
     */
    public function getMessage(): int
    {
        if (!$this->store->has(self::SESSION_KEY_MESSAGE)) {
            throw new MessageNotFoundInSessionStorage('No message found in session storage. Generate a message first.');
        }

        return $this->store->get(self::SESSION_KEY_MESSAGE);
    }

    /**
     * @param int $message
     * @return MfaSession
     */
    public function setMessage(int $message): MfaSession
    {
        $this->store->put(self::SESSION_KEY_MESSAGE, $message);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasMessage(): bool
    {
        return $this->store->has(self::SESSION_KEY_MESSAGE);
    }

    /**
     * @return MfaSession
     */
    public function forgetMessage(): MfaSession
    {
        $this->store->forget(self::SESSION_KEY_MESSAGE);
        return $this;
    }

    /**
     * @return MfaSession
     */
    public function destroy(): MfaSession
    {
        $this->forgetMessage();
        $this->forgetUserId();
        $this->forgetAction();
        $this->store->forget(self::SESSION_KEY_TIME);
        return $this;
    }
}
