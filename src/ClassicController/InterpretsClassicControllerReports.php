<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicDigitalButton;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;

trait InterpretsClassicControllerReports
{
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
            default => throw new \OutOfBoundsException("Unknown Classic Controller property [$name]."),
        };
    }

    protected function buttonLabels(): array
    {
        return array_map(
            static fn (WiiClassicDigitalButton $button): string => $button->value,
            WiiClassicDigitalButton::cases(),
        );
    }

    protected function decodeButtons(array $snapshot): array
    {
        return [
            'A' => ! (bool) ($snapshot[5] & 0x10),
            'B' => ! (bool) ($snapshot[5] & 0x40),
            'X' => ! (bool) ($snapshot[5] & 0x08),
            'Y' => ! (bool) ($snapshot[5] & 0x20),
            'START' => ! (bool) ($snapshot[4] & 0x04),
            'SELECT' => ! (bool) ($snapshot[4] & 0x10),
            'HOME' => ! (bool) ($snapshot[4] & 0x08),
            'ZL' => ! (bool) ($snapshot[5] & 0x80),
            'ZR' => ! (bool) ($snapshot[5] & 0x04),
            'L' => ! (bool) ($snapshot[4] & 0x20),
            'R' => ! (bool) ($snapshot[4] & 0x02),
            'UP' => ! (bool) ($snapshot[5] & 0x01),
            'DOWN' => ! (bool) ($snapshot[4] & 0x40),
            'LEFT' => ! (bool) ($snapshot[5] & 0x02),
            'RIGHT' => ! (bool) ($snapshot[4] & 0x80),
        ];
    }

    protected function updateMeasurements(array $snapshot): void {}

    /** @return array{x: int, y: int} */
    public function getJoystickL(): array
    {
        return ['x' => $this->last_snapshot[0] & 0x3F, 'y' => $this->last_snapshot[1] & 0x3F];
    }

    /** @return array{x: int, y: int} */
    public function getJoystickR(): array
    {
        return [
            'x' => (($this->last_snapshot[0] & 0xC0) >> 3)
                | (($this->last_snapshot[1] & 0xC0) >> 5)
                | (($this->last_snapshot[2] & 0x80) >> 7),
            'y' => $this->last_snapshot[2] & 0x1F,
        ];
    }

    public function getLShoulder(): int
    {
        return (($this->last_snapshot[2] & 0x60) >> 2) | (($this->last_snapshot[3] & 0xE0) >> 5);
    }

    public function getRShoulder(): int
    {
        return $this->last_snapshot[3] & 0x1F;
    }

    /** @return array<string, bool> */
    public function getDigitalButtons(): array
    {
        return array_intersect_key(
            $this->decodeButtons($this->last_snapshot),
            array_flip($this->labels()),
        );
    }

    /** @return array<string, bool> */
    public function getButtons(): array
    {
        return array_filter(
            $this->getDigitalButtons(),
            static fn (string $label): bool => ! in_array($label, ['UP', 'DOWN', 'LEFT', 'RIGHT'], true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /** @return array<string, bool> */
    public function getDPad(): array
    {
        return array_intersect_key($this->getDigitalButtons(), array_flip(['UP', 'DOWN', 'LEFT', 'RIGHT']));
    }

    public function asDigitalButtonPad(): ButtonPad
    {
        return $this;
    }

    /** @return array<string, mixed> */
    public function getValues(): array
    {
        return [
            'joystick_l' => $this->getJoystickL(),
            'joystick_r' => $this->getJoystickR(),
            'l_shoulder' => $this->getLShoulder(),
            'r_shoulder' => $this->getRShoulder(),
            'buttons' => $this->getButtons(),
            'd_pad' => $this->getDPad(),
        ];
    }

    /** @return array{values: array<string, mixed>, raw: list<int>} */
    public function pollInput(): array
    {
        $this->poll();

        return ['values' => $this->getValues(), 'raw' => $this->rawSnapshot()];
    }
}
