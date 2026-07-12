<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use BareMetal\Actuation\Actuator;
use BareMetal\Contracts\Circuits\BootSequence;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use GPIO\Contracts\I2C\I2CAPI;
use ScrapyardIO\NutsAndBolts\ScrapyardIOException;

class WiiClassicController extends Actuator implements BootSequence
{
    use WiiClassicAPI;

    /**
     * @throws ScrapyardIOException
     */
    public function __construct(
        protected I2CAPI $i2c,
        bool $boot_now = false,
    ) {
        if ($boot_now) {
            $this->boot();
        }
    }

    /**
     * @throws WiiConnectorException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'joystick_l' => $this->getJoystickL(),
            'joystick_r' => $this->getJoystickR(),
            'l_shoulder' => $this->getLShoulder(),
            'r_shoulder' => $this->getRShoulder(),
            'buttons' => $this->getButtons(),
            'd_pad' => $this->getDPad(),
            'values' => $this->getValues(),
            default => throw WiiConnectorException::invalidProperty($name, static::class),
        };
    }
}
