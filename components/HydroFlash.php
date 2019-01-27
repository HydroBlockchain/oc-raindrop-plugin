<?php

declare(strict_types=1);

namespace HydroCommunity\Raindrop\Components;

/**
 * Class HydroFlash
 *
 * @package HydroCommunity\Raindrop\Components
 */
class HydroFlash extends HydroComponentBase
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $type;

    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Hydro Flash',
            'description' => 'Renders Hydro Raindrop specific Flash messages.'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function defineProperties(): array
    {
        return [
            'type' => [
                'title' => 'Type',
                'description' => 'The Hydro flash message type',
                'type' => 'dropdown',
                'default' => 'info',
                'options' => [
                    'info' => 'Information',
                    'warning' => 'Warning',
                    'error' => 'Error',
                    'success' => 'Success'
                ]
            ]
        ];
    }

    /**
     * @return void
     */
    public function onRender(): void
    {
        parent::onRender();

        $this->type = $this->property('type', 'info');
        $this->message = $this->mfaSession->getFlashMessage();
    }
}
