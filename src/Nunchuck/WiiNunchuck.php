<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckAcceleration;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckDigitalButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiGamepad;
use Fabricate\Contracts\Actuation\HumanInput\GameController;
use Fabricate\Contracts\Actuation\HumanInput\GameControllerAxis;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use Fabricate\Contracts\Circuits\Attributes\IntegratedCircuit;
use Fabricate\Contracts\Sensors\Interfaces\Accelerometer;

#[IntegratedCircuit('I2C')]
class WiiNunchuck extends WiiGamepad implements GameController, Accelerometer
{
    /** @var array<string, float> */
    protected array $axis_values = [
        'left_x' => 0.0,
        'left_y' => 0.0,
        'right_x' => 0.0,
        'right_y' => 0.0,
        'left_trigger' => 0.0,
        'right_trigger' => 0.0,
    ];

    /** @var list<int> */
    protected array $raw_acceleration = [512, 512, 512];

    /** @var list<float> */
    protected array $acceleration = [0.0, 0.0, 0.0];

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
            default => throw new \OutOfBoundsException("Unknown Wii Nunchuck property [$name]."),
        };
    }

    protected function buttonLabels(): array
    {
        return array_map(
            static fn (WiiNunchuckDigitalButton $button): string => $button->value,
            WiiNunchuckDigitalButton::cases(),
        );
    }

    protected function decodeButtons(array $snapshot): array
    {
        return [
            'C' => ! (bool) ($snapshot[5] & 0x02),
            'Z' => ! (bool) ($snapshot[5] & 0x01),
        ];
    }

    protected function updateMeasurements(array $snapshot): void
    {
        $this->axis_values['left_x'] = $this->normalize($snapshot[0], 128, 127);
        $this->axis_values['left_y'] = $this->normalize($snapshot[1], 128, 127, true);
        $this->raw_acceleration = [
            ($snapshot[2] << 2) | (($snapshot[5] & 0xC0) >> 6),
            ($snapshot[3] << 2) | (($snapshot[5] & 0x30) >> 4),
            ($snapshot[4] << 2) | (($snapshot[5] & 0x0C) >> 2),
        ];
        $this->acceleration = array_map(
            static fn (int $value): float => ($value - WiiNunchuckAcceleration::ZERO_G->value)
                / WiiNunchuckAcceleration::COUNTS_PER_G->value,
            $this->raw_acceleration,
        );
    }

    public function axis(GameControllerAxis $axis): float
    {
        return $this->axis_values[$axis->value];
    }

    /** @return array<string, float> */
    public function axes(): array
    {
        return $this->axis_values;
    }

    /** @return list<int> */
    public function rawAcceleration(): array
    {
        return $this->raw_acceleration;
    }

    /** @return array{x: int, y: int} */
    public function getJoystick(): array
    {
        return ['x' => $this->last_snapshot[0], 'y' => $this->last_snapshot[1]];
    }

    /** @return array<string, bool> */
    public function getButtons(): array
    {
        return $this->decodeButtons($this->last_snapshot);
    }

    /** @return array<string, bool> */
    public function getDigitalButtons(): array
    {
        return $this->getButtons();
    }

    /** @return list<int> */
    public function getAcceleration(): array
    {
        return $this->raw_acceleration;
    }

    public function asDigitalButtonPad(): ButtonPad
    {
        return $this;
    }

    /** @return array<string, mixed> */
    public function getValues(): array
    {
        return [
            'joystick' => $this->getJoystick(),
            'buttons' => $this->getButtons(),
            'acceleration' => $this->getAcceleration(),
        ];
    }

    /** @return array{values: array<string, mixed>, raw: list<int>} */
    public function pollInput(): array
    {
        $this->poll();

        return ['values' => $this->getValues(), 'raw' => $this->rawSnapshot()];
    }

    public function x(): float
    {
        return $this->acceleration[0];
    }

    public function y(): float
    {
        return $this->acceleration[1];
    }

    public function z(): float
    {
        return $this->acceleration[2];
    }
}
