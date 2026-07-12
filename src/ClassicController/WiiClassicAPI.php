<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\BuildsDigitalButtonPad;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicDigitalButton;

trait WiiClassicAPI
{
    use WiiClassicInternalAPI;
    use BuildsDigitalButtonPad;

    public function getJoystickL(): array
    {
        $buffer = $this->readData();

        return [
            'x' => $buffer[0] & 0x3F,
            'y' => $buffer[1] & 0x3F,
        ];
    }

    public function getJoystickR(): array
    {
        $buffer = $this->readData();

        return [
            'x' => (($buffer[0] & 0xC0) >> 3) | (($buffer[1] & 0xC0) >> 5) | (($buffer[2] & 0x80) >> 7),
            'y' => $buffer[2] & 0x1F,
        ];
    }

    public function getLShoulder(): int
    {
        $buffer = $this->readData();

        return (($buffer[2] & 0x60) >> 2) | (($buffer[3] & 0xE0) >> 5);
    }

    public function getRShoulder(): int
    {
        $buffer = $this->readData();

        // Adafruit CircuitPython uses 0x1C; the correct 5-bit mask is 0x1F.
        return $buffer[3] & 0x1F;
    }

    public function getButtons(): array
    {
        $buffer = $this->readData();

        return $this->decodeButtons($buffer);
    }

    public function getDPad(): array
    {
        $buffer = $this->readData();

        return $this->decodeDPad($buffer);
    }

    /**
     * Face / shoulder / menu / d-pad digital bits from one I2C read.
     *
     * @return array<string, bool>
     */
    public function getDigitalButtons(): array
    {
        $buffer = $this->readData();

        return $this->decodeDigitalButtons($buffer);
    }

    /**
     * DigitalButtonPad over Classic digital inputs. One I2C poll per pad->poll().
     */
    public function asDigitalButtonPad(): DigitalButtonPad
    {
        return $this->digitalButtonPadFromSnapshot(
            WiiClassicDigitalButton::cases(),
            fn (): array => $this->getDigitalButtons(),
        );
    }

    public function getValues(): array
    {
        return $this->decodeBuffer($this->readData());
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
            'values' => $this->decodeBuffer($buffer),
            'raw' => $buffer,
        ];
    }

    /**
     * @param  list<int>  $buffer
     */
    protected function decodeBuffer(array $buffer): array
    {
        return [
            'joystick_l' => [
                'x' => $buffer[0] & 0x3F,
                'y' => $buffer[1] & 0x3F,
            ],
            'joystick_r' => [
                'x' => (($buffer[0] & 0xC0) >> 3) | (($buffer[1] & 0xC0) >> 5) | (($buffer[2] & 0x80) >> 7),
                'y' => $buffer[2] & 0x1F,
            ],
            'l_shoulder' => (($buffer[2] & 0x60) >> 2) | (($buffer[3] & 0xE0) >> 5),
            'r_shoulder' => $buffer[3] & 0x1F,
            'buttons' => $this->decodeButtons($buffer),
            'd_pad' => $this->decodeDPad($buffer),
        ];
    }

    /**
     * @param  list<int>  $buffer
     * @return array<string, bool>
     */
    protected function decodeDigitalButtons(array $buffer): array
    {
        return [
            ...$this->decodeButtons($buffer),
            ...$this->decodeDPad($buffer),
        ];
    }

    /**
     * @param  list<int>  $buffer
     * @return array<string, bool>
     */
    protected function decodeButtons(array $buffer): array
    {
        return [
            WiiClassicDigitalButton::A->value => ! ($buffer[5] & 0x10),
            WiiClassicDigitalButton::B->value => ! ($buffer[5] & 0x40),
            WiiClassicDigitalButton::X->value => ! ($buffer[5] & 0x08),
            WiiClassicDigitalButton::Y->value => ! ($buffer[5] & 0x20),
            WiiClassicDigitalButton::START->value => ! ($buffer[4] & 0x04),
            WiiClassicDigitalButton::SELECT->value => ! ($buffer[4] & 0x10),
            WiiClassicDigitalButton::HOME->value => ! ($buffer[4] & 0x08),
            WiiClassicDigitalButton::ZL->value => ! ($buffer[5] & 0x80),
            WiiClassicDigitalButton::ZR->value => ! ($buffer[5] & 0x04),
            WiiClassicDigitalButton::L->value => ! ($buffer[4] & 0x20),
            WiiClassicDigitalButton::R->value => ! ($buffer[4] & 0x02),
        ];
    }

    /**
     * @param  list<int>  $buffer
     * @return array<string, bool>
     */
    protected function decodeDPad(array $buffer): array
    {
        return [
            WiiClassicDigitalButton::UP->value => ! ($buffer[5] & 0x01),
            WiiClassicDigitalButton::DOWN->value => ! ($buffer[4] & 0x40),
            WiiClassicDigitalButton::LEFT->value => ! ($buffer[5] & 0x02),
            WiiClassicDigitalButton::RIGHT->value => ! ($buffer[4] & 0x80),
        ];
    }
}
