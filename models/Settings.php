<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Models;

use Cms\Classes\Page;
use October\Rain\Database\Model;
use System\Behaviors\SettingsModel;

/**
 * Class Settings
 *
 * @package HydroCommunity\Raindrop\Models
 * @mixin SettingsModel
 */
class Settings extends Model
{
    const /** @noinspection AccessModifierPresentedInspection */ MFA_METHOD_OPTIONAL = 'optional';
    const /** @noinspection AccessModifierPresentedInspection */ MFA_METHOD_PROMPTED = 'prompted';
    const /** @noinspection AccessModifierPresentedInspection */ MFA_METHOD_ENFORCED = 'enforced';

    const /** @noinspection AccessModifierPresentedInspection */ MFA_TIMEOUT = 90;

    public $implement = [SettingsModel::class];

    public $settingsCode = 'hydrocommunity_raindrop_settings';

    public $settingsFields = 'fields.yaml';

    /**
     * @return array
     */
    public function getPageSignOnOptions(): array
    {
        return Page::sortBy('baseFileName')
                ->lists('baseFileName', 'baseFileName');
    }

    /**
     * @return array
     */
    public function getPageRedirectOptions(): array
    {
        return ['' => '- refresh page -']
            + Page::sortBy('baseFileName')
                ->lists('baseFileName', 'baseFileName');
    }
}
