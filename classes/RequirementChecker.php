<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Classes;

use Adrenth\Raindrop;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use HydroCommunity\Raindrop\Models\Settings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

/**
 * Class RequirementChecker
 *
 * CAUTION: Must be PHP 7.0 compatible.
 *
 * @package HydroCommunity\Raindrop\Classes
 */
class RequirementChecker
{
    const /** @noinspection AccessModifierPresentedInspection */ REQUIREMENT_SSL = 'ssl';
    const /** @noinspection AccessModifierPresentedInspection */ REQUIREMENT_PHP_VERSION = 'php_version';
    const /** @noinspection AccessModifierPresentedInspection */ REQUIREMENT_CURL = 'curl';
    const /** @noinspection AccessModifierPresentedInspection */ REQUIREMENT_MFA_PAGE = 'mfa_page';
    const /** @noinspection AccessModifierPresentedInspection */ REQUIREMENT_MFA_SETUP_PAGE = 'mfa_setup_page';
    const /** @noinspection AccessModifierPresentedInspection */ REQUIREMENT_API_SETTINGS = 'mfa_api_settings';

    /**
     * Run all checks.
     *
     * @return bool
     */
    public function passes(): bool
    {
        foreach ($this->getRequirements() as $requirement) {
            if (!$this->check($requirement['test'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns TRUE if given requirement passes the requirement check.
     *
     * @param string $requirement Use class constants.
     *
     * @return bool
     */
    public function passesRequirement(string $requirement): bool
    {
        $requirements = $this->getRequirements();
        if (array_key_exists($requirement, $requirements)) {
            return $this->check($requirements[$requirement]['test']);
        }
    }

    /**
     * Get the requirements for Hydro Raindrop plugin.
     *
     * @return array
     */
    public function getRequirements(): array
    {
        return [
            self::REQUIREMENT_SSL => [
                'label' => 'SSL',
                'requirement' => 'SSL should be enabled.',
                'test' => function () {
                    /** @var Request $request */
                    $request = resolve(Request::class);
                    return $request->isSecure();
                },
            ],
            self::REQUIREMENT_PHP_VERSION => [
                'label' => 'PHP version',
                'requirement' => 'PHP version 7.1 or higher.',
                'test' => function () {
                    return version_compare(PHP_VERSION, '7.1.0') >= 0;
                },
            ],
            self::REQUIREMENT_CURL => [
                'label' => 'cURL extension',
                'requirement' => 'PHP cURL extension (ext-curl) installed and enabled.',
                'test' => function () {
                    return function_exists('curl_version');
                },
            ],
            self::REQUIREMENT_MFA_PAGE => [
                'label' => 'MFA Page',
                'requirement' => 'A CMS page must be present with the URL /hydro-raindrop/mfa',
                'test' => function () {
                    $pages = Theme::getActiveTheme()->listPages(true);

                    /** @var Page $page */
                    foreach ($pages as $page) {
                        /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
                        $url = isset($page->settings['url']) ? $page->settings['url'] : null;

                        if ($url === '/hydro-raindrop/mfa') {
                            return true;
                        }
                    }

                    return false;
                },
            ],
            self::REQUIREMENT_MFA_SETUP_PAGE => [
                'label' => 'MFA Setup Page',
                'requirement' => 'A CMS page must be present with the URL /hydro-raindrop/setup',
                'test' => function () {
                    $pages = Theme::getActiveTheme()->listPages(true);

                    /** @var Page $page */
                    foreach ($pages as $page) {
                        /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
                        $url = isset($page->settings['url']) ? $page->settings['url'] : null;

                        if ($url === '/hydro-raindrop/setup') {
                            return true;
                        }
                    }

                    return false;
                },
            ],
            self::REQUIREMENT_API_SETTINGS => [
                'label' => 'API Settings',
                'requirement' => 'API settings must be provided for this plugin to work.',
                'test' => function () {
                    /** @var Raindrop\ApiSettings $apiSettings */
                    $apiSettings = resolve(Raindrop\ApiSettings::class);
                    $applicationId = (string) Settings::get('application_id', '');

                    $possiblyCorrect = $applicationId !== ''
                        && strlen($applicationId) === 36
                        && $apiSettings->getClientId() !== ''
                        && strlen($apiSettings->getClientId()) === 26
                        && $apiSettings->getClientSecret() !== ''
                        && strlen($apiSettings->getClientSecret()) === 26;

                    if (!$possiblyCorrect) {
                        return false;
                    }

                    $cacheKey = 'hydro-community-raindrop_' . sha1(implode('-', [
                        $apiSettings->getClientId(),
                        $apiSettings->getClientSecret(),
                        $apiSettings->getEnvironment()->getApiUrl()
                    ]));

                    /** @var Repository $cache */
                    $cache = resolve(Repository::class);

                    if ($cache->has($cacheKey)) {
                        return $cache->get($cacheKey);
                    }

                    /** @var Raindrop\Client $client */
                    $client = resolve(Raindrop\Client::class);

                    /** @var Raindrop\TokenStorage\TokenStorage $tokenStorage */
                    $tokenStorage = resolve(Raindrop\TokenStorage\TokenStorage::class);

                    /*
                     * Try to fetch the Access Token in order to verify the API settings.
                     */
                    try {
                        $tokenStorage->unsetAccessToken();

                        $client->getAccessToken();
                        $cache->forever($cacheKey, true);
                    } catch (Raindrop\Exception\RefreshTokenFailed $e) {
                        $cache->forever($cacheKey, false);
                        return false;
                    }

                    return true;
                }
            ]
        ];
    }

    /**
     * Check requirement.
     *
     * @param callable $test The requirement test.
     *
     * @return bool
     */
    public function check(callable $test): bool
    {
        return $test();
    }
}
