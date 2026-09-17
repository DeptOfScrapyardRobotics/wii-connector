<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;

/**
 * Wii Classic Controller: the SNES layout plus Home, ZL and ZR, two analog
 * sticks and two analog triggers.
 */
class WiiClassicController extends SNESClassicController
{
    /** @var array{left_x: float, left_y: float, right_x: float, right_y: float, left_trigger: float, right_trigger: float} */
    protected array $analog = [
        'left_x' => 0.0, 'left_y' => 0.0,
        'right_x' => 0.0, 'right_y' => 0.0,
        'left_trigger' => 0.0, 'right_trigger' => 0.0,
    ];

    public function supportedButtons(): array
    {
        return $this->onlyButtons(
            ...parent::supportedButtons(),
            ...[WiiClassicButton::HOME, WiiClassicButton::ZL, WiiClassicButton::ZR],
        );
    }

    /**
     * @throws \DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'left_stick' => $this->leftStick(),
            'right_stick' => $this->rightStick(),
            'left_trigger' => $this->leftTrigger(),
            'right_trigger' => $this->rightTrigger(),
            'axes' => $this->axes(),
            default => parent::__get($name),
        };
    }

    /** @return array{x: float, y: float} −1 … 1 */
    public function leftStick(): array
    {
        return ['x' => $this->analog['left_x'], 'y' => $this->analog['left_y']];
    }

    /** @return array{x: float, y: float} −1 … 1 */
    public function rightStick(): array
    {
        return ['x' => $this->analog['right_x'], 'y' => $this->analog['right_y']];
    }

    /** 0 … 1 */
    public function leftTrigger(): float
    {
        return $this->analog['left_trigger'];
    }

    /** 0 … 1 */
    public function rightTrigger(): float
    {
        return $this->analog['right_trigger'];
    }

    /** @return array{left_x: float, left_y: float, right_x: float, right_y: float, left_trigger: float, right_trigger: float} */
    public function axes(): array
    {
        return $this->analog;
    }

    /**
     * Raw counts from the last report: sticks left 6-bit, right 5-bit; triggers 5-bit.
     *
     * @return array{left_x: int, left_y: int, right_x: int, right_y: int, left_trigger: int, right_trigger: int}
     */
    public function rawAnalog(): array
    {
        [$b0, $b1, $b2, $b3] = $this->report;

        return [
            'left_x' => $b0 & 0x3F,
            'left_y' => $b1 & 0x3F,
            'right_x' => (($b0 & 0xC0) >> 3) | (($b1 & 0xC0) >> 5) | (($b2 & 0x80) >> 7),
            'right_y' => $b2 & 0x1F,
            'left_trigger' => (($b2 & 0x60) >> 2) | (($b3 & 0xE0) >> 5),
            'right_trigger' => $b3 & 0x1F,
        ];
    }

    protected function decodeAnalog(array $report): void
    {
        $raw = $this->rawAnalog();
        $invert = $this->config()->get('invert_y');

        $this->analog = [
            'left_x' => $this->normalize($raw['left_x'], 31.5, 31.5),
            'left_y' => $this->normalize($raw['left_y'], 31.5, 31.5, $invert),
            'right_x' => $this->normalize($raw['right_x'], 15.5, 15.5),
            'right_y' => $this->normalize($raw['right_y'], 15.5, 15.5, $invert),
            'left_trigger' => $raw['left_trigger'] / 31.0,
            'right_trigger' => $raw['right_trigger'] / 31.0,
        ];
    }
}
