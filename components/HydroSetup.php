<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Components;

use Adrenth\Raindrop\Exception\RegisterUserFailed;
use Adrenth\Raindrop\Exception\UnregisterUserFailed;
use Adrenth\Raindrop\Exception\UserAlreadyMappedToApplication;
use HydroCommunity\Raindrop\Classes\Exceptions\InvalidUserInSession;
use HydroCommunity\Raindrop\Classes\Exceptions\UserIdNotFoundInSessionStorage;
use HydroCommunity\Raindrop\Classes\MfaSession;
use HydroCommunity\Raindrop\Classes\MfaUser;
use HydroCommunity\Raindrop\Classes\Helpers\UrlHelper;
use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;
use InvalidArgumentException;
use October\Rain\Exception\ValidationException;
use RainLab\User\Classes\AuthManager as FrontendAuthManager;
use Backend\Classes\AuthManager as BackendAuthManager;

/**
 * Class SetupHydroID
 *
 * @package HydroCommunity\Raindrop\Components
 */
class HydroSetup extends HydroComponentBase
{
    /**
     * Whether Hydro Setup can be skipped.
     *
     * @var bool
     */
    public $skipAllowed = false;

    /**
     * @var MfaUser
     */
    private $userHelper;

    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Hydro Setup',
            'description' => 'Renders the form for setting up the HydroID.'
        ];
    }

    /**
     * {@inheritdoc}
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
     */
    protected function prepareVars(): void
    {
        $this->userHelper = MfaUser::createFromSession();
        $this->skipAllowed = Settings::get('mfa_method') !== Settings::MFA_METHOD_ENFORCED;
    }

    /**
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function onSubmit(): RedirectResponse
    {
        $this->validateSubmitRequest();

        try {
            $this->prepareVars();
        } catch (UserIdNotFoundInSessionStorage | InvalidUserInSession $e) {
            $this->log->error($e);
            return redirect()->to('/');
        }

        return $this->registerUser((string) $this->request->get('hydro_id'));
    }

    /**
     * @return RedirectResponse
     * @throws \October\Rain\Auth\AuthException
     */
    public function onSkip(): RedirectResponse
    {
        try {
            $this->prepareVars();
        } catch (UserIdNotFoundInSessionStorage | InvalidUserInSession $e) {
            $this->log->error($e);
            return redirect()->to('/');
        }

        $user = $this->userHelper->getUserModel();

        $isBackend = $this->mfaSession->isBackend();

        $this->mfaSession->destroy();

        $mfaMethod = Settings::get('mfa_method');

        if ($mfaMethod === Settings::MFA_METHOD_OPTIONAL
            || $mfaMethod === Settings::MFA_METHOD_PROMPTED
        ) {
            if ($isBackend) {
                BackendAuthManager::instance()->login($user, false);
            } else {
                FrontendAuthManager::instance()->login($user, false);
            }

            return (new UrlHelper())->getRedirectResponse($isBackend);
        }

        return (new UrlHelper())->getSignOnResponse($isBackend);
    }

    /**
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    private function validateSubmitRequest(): void
    {
        $message = trans('Please provide a valid HydroID.');

        /** @var Request $request */
        $request = resolve(Request::class);

        /** @var Factory $factory */
        $factory = resolve(Factory::class);

        $validator = $factory->make(
            $request->request->all(),
            [
                'hydro_id' => 'required|min:3|max:32'
            ],
            [
                'required' => $message,
                'min' => $message,
                'max' => $message,
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @param string $hydroId
     * @return RedirectResponse
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    private function registerUser(string $hydroId): RedirectResponse
    {
        $redirectTo = $this->urlHelper->getMfaUrl();

        $user = $this->userHelper->getUserModel();

        try {
            $this->client->registerUser($hydroId);
        } catch (UserAlreadyMappedToApplication $e) {
            /*
             * User is already mapped to this application.
             *
             * Edge case: A user tries to re-register with HydroID. If the user meta has been deleted, the
             *            user can re-use his HydroID but needs to verify it again.
             */

            $this->log->warning(
                'Hydro Raindrop: User is already mapped to this application: ' . $e->getMessage()
            );

            try {
                $this->client->unregisterUser($hydroId);

                throw new ValidationException([
                    'hydro_id' => trans(
                        'Your HydroID was already mapped to this site. '
                        . 'Mapping is removed. Please re-enter your HydroID to proceed.'
                    )
                ]);
            } catch (UnregisterUserFailed $e) {
                $this->log->error('Hydro Raindrop: Unregistering user failed: ' . $e->getMessage());
            }

        } catch (RegisterUserFailed $e) {
            $user->meta()->update([
                'hydro_id' => null,
                'is_mfa_enabled' => false,
                'is_mfa_confirmed' => false,
                'mfa_failed_attempts' => 0,
            ]);

            throw new ValidationException([
                'hydro_id' => e(trans($e->getMessage()))
            ]);
        }

        $user->meta()->update([
            'hydro_id' => $hydroId,
            'is_mfa_enabled' => true,
            'is_mfa_confirmed' => false,
            'mfa_failed_attempts' => 0,
        ]);

        $this->mfaSession->setAction(MfaSession::ACTION_VERIFY);

        return redirect()->to($redirectTo);
    }
}
