<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck;

use BareMetal\Actuation\Actuator;
use BareMetal\Contracts\Circuits\BootSequence;
use BareMetal\Contracts\Sensors\Accelerometry\AccelerationMeasurable;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use GPIO\Contracts\I2C\I2CAPI;
use ScrapyardIO\NutsAndBolts\ScrapyardIOException;

/**
 * @property-read array{x: int, y: int} $joystick
 * @property-read array<string, bool> $buttons
 * @property-read list<int> $acceleration
 * @property-read float $x
 * @property-read float $y
 * @property-read float $z
 * @property-read array $values
 */
class WiiNunchuck extends Actuator implements BootSequence, AccelerationMeasurable
{
    use WiiNunchuckAPI;

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
            'joystick' => $this->getJoystick(),
            'buttons' => $this->getButtons(),
            'acceleration' => $this->getAcceleration(),
            'x' => $this->x(),
            'y' => $this->y(),
            'z' => $this->z(),
            'values' => $this->getValues(),
            default => throw WiiConnectorException::invalidProperty($name, static::class),
        };
    }

    public function x(): float
    {
        return $this->rawToGs($this->getAcceleration()[0]);
    }

    public function y(): float
    {
        return $this->rawToGs($this->getAcceleration()[1]);
    }

    public function z(): float
    {
        return $this->rawToGs($this->getAcceleration()[2]);
    }
}
