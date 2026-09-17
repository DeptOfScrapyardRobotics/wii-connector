<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorOpCode;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiDataFormat;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiExtensionId;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;

/**
 * NES Classic Mini controller: D-pad, A, B, Start, Select. The base of the
 * Classic family; SNES and Wii Classic add to it.
 */
class NESClassicController extends WiiExtension
{
    public function extensionId(): WiiExtensionId
    {
        return WiiExtensionId::CLASSIC_CONTROLLER;
    }

    public function supportedButtons(): array
    {
        return $this->onlyButtons(
            WiiClassicButton::UP, WiiClassicButton::DOWN, WiiClassicButton::LEFT, WiiClassicButton::RIGHT,
            WiiClassicButton::A, WiiClassicButton::B, WiiClassicButton::START, WiiClassicButton::SELECT,
        );
    }

    /**
     * @throws \DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'd_pad' => $this->dPad(),
            default => parent::__get($name),
        };
    }

    /** @return array{up: bool, down: bool, left: bool, right: bool} */
    public function dPad(): array
    {
        return [
            'up' => $this->isDown(WiiClassicButton::UP),
            'down' => $this->isDown(WiiClassicButton::DOWN),
            'left' => $this->isDown(WiiClassicButton::LEFT),
            'right' => $this->isDown(WiiClassicButton::RIGHT),
        ];
    }

    /** Pin the standard six-byte report; a controller can be left in high-resolution mode. */
    protected function configureExtension(): void
    {
        $this->sendCommand(WiiConnectorOpCode::DATA_FORMAT, [WiiDataFormat::STANDARD->value]);
    }

    protected function buttonBits(array $report): int
    {
        return ($report[4] << 8) | $report[5];
    }

    protected function decodeAnalog(array $report): void {}

    /** @return list<WiiClassicButton> the given buttons in report bit order */
    protected function onlyButtons(WiiClassicButton ...$buttons): array
    {
        return array_values(array_filter(
            WiiClassicButton::cases(),
            fn (WiiClassicButton $b): bool => in_array($b, $buttons, true),
        ));
    }
}
