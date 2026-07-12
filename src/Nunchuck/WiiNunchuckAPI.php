<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck;

use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\BuildsDigitalButtonPad;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckAcceleration;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckDigitalButton;

trait WiiNunchuckAPI
{
    use WiiNunchuckInternalAPI;
    use BuildsDigitalButtonPad;

    public function getJoystick(): array
    {
        $buffer = $this->readData();

        return [
            'x' => $buffer[0],
            'y' => $buffer[1],
        ];
    }

    public function getButtons(): array
    {
        $buffer = $this->readData();

        return $this->decodeButtons($buffer);
    }

    /**
     * C / Z digital bits from one I2C read.
     *
     * @return array<string, bool>
     */
    public function getDigitalButtons(): array
    {
        return $this->getButtons();
    }

    /**
     * DigitalButtonPad over Nunchuck C/Z. One I2C poll per pad->poll().
     */
    public function asDigitalButtonPad(): DigitalButtonPad
    {
        return $this->digitalButtonPadFromSnapshot(
            WiiNunchuckDigitalButton::cases(),
            fn (): array => $this->getDigitalButtons(),
        );
    }

    /**
     * Raw 10-bit accelerometer sample [x, y, z] (≈0–1023).
     *
     * @return list<int>
     */
    public function getAcceleration(): array
    {
        $buffer = $this->readData();

        return $this->decodeAcceleration($buffer);
    }

    public function getValues(): array
    {
        $buffer = $this->readData();

        return [
            'joystick' => [
                'x' => $buffer[0],
                'y' => $buffer[1],
            ],
            'buttons' => $this->decodeButtons($buffer),
            'acceleration' => $this->decodeAcceleration($buffer),
        ];
    }

    /**
     * Single I2C poll returning both the decoded state and the raw 6-byte buffer.
     *
     * @return array{values: array, raw: list<int>}
     */
    public function pollInput(): array
    {
        $buffer = $this->readData();

        return [
            'values' => [
                'joystick' => [
                    'x' => $buffer[0],
                    'y' => $buffer[1],
                ],
                'buttons' => $this->decodeButtons($buffer),
                'acceleration' => $this->decodeAcceleration($buffer),
            ],
            'raw' => $buffer,
        ];
    }

    /**
     * @param  list<int>  $buffer
     * @return array<string, bool>
     */
    protected function decodeButtons(array $buffer): array
    {
        return [
            WiiNunchuckDigitalButton::C->value => ! ($buffer[5] & 0x02),
            WiiNunchuckDigitalButton::Z->value => ! ($buffer[5] & 0x01),
        ];
    }

    /**
     * @param  list<int>  $buffer
     * @return list<int>
     */
    protected function decodeAcceleration(array $buffer): array
    {
        return [
            ($buffer[2] << 2) | (($buffer[5] & 0xC0) >> 6),
            ($buffer[3] << 2) | (($buffer[5] & 0x30) >> 4),
            ($buffer[4] << 2) | (($buffer[5] & 0x0C) >> 2),
        ];
    }

    protected function rawToGs(int $raw): float
    {
        return ($raw - WiiNunchuckAcceleration::ZERO_G->value)
            / WiiNunchuckAcceleration::COUNTS_PER_G->value;
    }
}
