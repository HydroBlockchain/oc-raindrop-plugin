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
 * Class SessionHelper
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class SessionHelper
{
    private const SESSION_KEY_USER = 'HydroCommunity.Raindrop.User';
    private const SESSION_KEY_ACTION = 'HydroCommunity.Raindrop.Action';
    private const SESSION_KEY_MESSAGE = 'HydroCommunity.Raindrop.Message';

    public const ACTION_ENABLE = 'enable';
    public const ACTION_VERIFY = 'verify';
    public const ACTION_DISABLE = 'disable';

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
     * @return User
     * @throws InvalidUserInSession
     * @throws UserIdNotFoundInSessionStorage
     */
    public function getUser(): User
    {
        $user = null;

        $sessionHelper = new SessionHelper();

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
     * @return void
     */
    public function setUserId(int $userId): void
    {
        $this->store->put(self::SESSION_KEY_USER, $userId);
    }

    /**
     * @return bool
     */
    public function hasUserId(): bool
    {
        return $this->store->has(self::SESSION_KEY_USER);
    }

    /**
     * @return void
     */
    public function forgetUserId(): void
    {
        $this->store->forget(self::SESSION_KEY_USER);
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
     * @return void
     */
    public function setAction(string $action): void
    {
        $this->store->put(self::SESSION_KEY_ACTION, $action);
    }

    /**
     * @return bool
     */
    public function hasAction(): bool
    {
        return $this->store->has(self::SESSION_KEY_ACTION);
    }

    /**
     * @return void
     */
    public function forgetAction(): void
    {
        $this->store->forget(self::SESSION_KEY_ACTION);
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
     * @return void
     */
    public function setMessage(int $message): void
    {
        $this->store->put(self::SESSION_KEY_MESSAGE, $message);
    }

    /**
     * @return bool
     */
    public function hasMessage(): bool
    {
        return $this->store->has(self::SESSION_KEY_MESSAGE);
    }

    /**
     * @return void
     */
    public function forgetMessage(): void
    {
        $this->store->forget(self::SESSION_KEY_MESSAGE);
    }

    /**
     * @return void
     */
    public function forgetAll(): void
    {
        $this->forgetMessage();
        $this->forgetUserId();
        $this->forgetAction();
    }
}
