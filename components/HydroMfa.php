<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Components;

use Adrenth\Raindrop\Exception\UnregisterUserFailed;
use Adrenth\Raindrop\Exception\VerifySignatureFailed;
use Exception;
use HydroCommunity\Raindrop\Classes\Exceptions\InvalidUserInSession;
use HydroCommunity\Raindrop\Classes\Exceptions\MessageNotFoundInSessionStorage;
use HydroCommunity\Raindrop\Classes\Exceptions\UserIdNotFoundInSessionStorage;
use HydroCommunity\Raindrop\Classes\MfaUser;
use HydroCommunity\Raindrop\Classes\Helpers\UrlHelper;
use HydroCommunity\Raindrop\Classes\ReauthenticateSession;
use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Http\RedirectResponse;
use RainLab\User\Classes\AuthManager as FrontEndAuthManager;
use Backend\Classes\AuthManager as BackendAuthManager;

/**
 * Class HydroMfa
 *
 * @package HydroCommunity\Raindrop\Components
 */
class HydroMfa extends HydroComponentBase
{
    /**
     * @var MfaUser
     */
    private $userHelper;

    /**
     * @var string
     */
    public $message;

    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Hydro MFA',
            'description' => 'Renders the MFA form.'
        ];
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function onRun()
    {
        parent::onRun();

        try {
            $this->prepareVars();
        } catch (UserIdNotFoundInSessionStorage | InvalidUserInSession $e) {
            $this->log->error($e);
            return redirect()->to('/');
        }

        $this->addCss('assets/css/hydro-raindrop.css');
    }

    /**
     * @throws InvalidUserInSession
     * @throws UserIdNotFoundInSessionStorage
     * @throws Exception
     */
    protected function prepareVars(): void
    {
        $this->userHelper = MfaUser::createFromSession();

        if (!$this->mfaSession->hasMessage()) {
            $this->mfaSession->setMessage($this->client->generateMessage());
        }

        $this->message = $this->mfaSession->getMessage();
    }

    /**
     * @return RedirectResponse|array
     * @throws Exception
     */
    public function onAuthenticate()
    {
        try {
            $this->prepareVars();
        } catch (UserIdNotFoundInSessionStorage | InvalidUserInSession $e) {
            $this->log->error($e);
            return redirect()->to('/');
        }

        $signatureVerified = $this->verifySignatureLogin();

        if ($signatureVerified) {
            return $this->handleMfaSuccess();
        }

        return $this->handleMfaFailure();
    }

    /**
     * @return RedirectResponse
     * @throws Exception
     */
    public function onCancel(): RedirectResponse
    {
        try {
            $this->prepareVars();
        } catch (UserIdNotFoundInSessionStorage | InvalidUserInSession $e) {
            $this->log->error($e);
        }

        $isBackend = $this->mfaSession->isBackend();

        $this->mfaSession->destroy();

        return (new UrlHelper())->getSignOnResponse($isBackend);
    }

    /**
     * @return bool
     */
    private function verifySignatureLogin(): bool
    {
        $user = $this->userHelper->getUserModel();

        try {
            $message = $this->mfaSession->getMessage();
        } catch (MessageNotFoundInSessionStorage $e) {
            $this->log->error('Hydro Raindrop: ' . $e->getMessage());
            return false;
        }

        try {
            $this->client->verifySignature(
                $this->userHelper->getHydroId(),
                $message
            );

            $this->mfaSession->forgetMessage();

            if ($this->mfaSession->isActionVerify()) {
                $user->meta()->update([
                    'is_mfa_confirmed' => true,
                ]);
            }

            return true;
        } catch (VerifySignatureFailed $e) {
            $this->log->warning('Hydro Raindrop: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return RedirectResponse
     * @throws \October\Rain\Auth\AuthException
     */
    private function handleMfaSuccess(): RedirectResponse
    {
        if ($this->mfaSession->isBackend()) {
            $authManager = BackendAuthManager::instance();
        } else {
            $authManager = FrontEndAuthManager::instance();
        }

        $user = $this->userHelper->getUserModel();

        if (!$authManager->check()) {
            $authManager->login($user, false);
        }

        /*
         * Unregister User
         */
        if (Settings::get('mfa_method', Settings::MFA_METHOD_PROMPTED) !== Settings::MFA_METHOD_ENFORCED
            && $this->mfaSession->isActionDisable()
        ) {
            $hydroId = $this->userHelper->getHydroId();

            try {
                $this->client->unregisterUser($hydroId);

                $user->meta()->update([
                    'hydro_id' => null,
                    'is_mfa_enabled' => false,
                    'is_mfa_confirmed' => false,
                    'is_blocked' => false,
                    'mfa_failed_attempts' => 0,
                ]);
            } catch (UnregisterUserFailed $e) {
                $this->log->error('Hydro Raindrop: ' . $e->getMessage());
            }
        }

        /*
         * Reauthenticate
         */
        if ($this->mfaSession->isActionReauthenticate()) {
            (new ReauthenticateSession())->addPage(
                $this->mfaSession->getActionParameters()['identifier']
            );

            $redirect = $this->mfaSession->getActionParameters()['redirect'];

            $this->mfaSession->destroy();

            return redirect()->to($redirect);
        }

        $isBackend = $this->mfaSession->isBackend();
        $isActionVerify = $this->mfaSession->isActionVerify();
        $isActionDisable = $this->mfaSession->isActionDisable();

        if ($isBackend && $isActionVerify) {
            $this->flash->success('Hydro Raindrop MFA successfully enabled!');
        }

        if ($isBackend && $isActionDisable) {
            $this->flash->success('Hydro Raindrop MFA successfully disabled!');
        }

        $this->mfaSession->destroy();

        return $this->urlHelper->getRedirectResponse(
            $isBackend,
            $isActionVerify || $isActionDisable
        );
    }

    /**
     * @return RedirectResponse|array
     * @throws InvalidUserInSession
     * @throws UserIdNotFoundInSessionStorage
     */
    private function handleMfaFailure()
    {
        $this->mfaSession->setFlashMessage('Authentication failed, please try again.');
        $this->mfaSession->forgetMessage();

        $user = $this->userHelper->getUserModel();

        $failedAttempts = $user->meta->getAttribute('mfa_failed_attempts');

        $user->meta()->update([
            'mfa_failed_attempts' => ++$failedAttempts,
        ]);

        $maximumAttempts = (int) Settings::get('mfa_maximum_attempts', 0);

        if ($maximumAttempts > 0 && $failedAttempts > $maximumAttempts) {
            $user->meta()->update([
                'is_blocked' => true,
                'mfa_failed_attempts' => 0
            ]);

            $isBackend = $this->mfaSession->isBackend();

            $this->mfaSession->setFlashMessage('Your account has been blocked.');
            $this->mfaSession->destroy();

            return (new UrlHelper())->getSignOnResponse($isBackend);
        }

        $this->prepareVars();

        return [
            '#hydroDigits' => $this->renderPartial($this->alias . '::_message'),
            '#hydroFlash' => $this->controller->renderComponent('hydroCommunityHydroFlash', ['type' => 'error']),
        ];
    }
}
