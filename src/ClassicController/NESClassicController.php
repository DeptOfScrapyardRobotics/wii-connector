<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNESDigitalButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiGamepad;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use Fabricate\Contracts\Circuits\Attributes\IntegratedCircuit;

#[IntegratedCircuit('I2C')]
class NESClassicController extends WiiGamepad implements ButtonPad
{
    use InterpretsClassicControllerReports;

    protected function buttonLabels(): array
    {
        return array_map(
            static fn (WiiNESDigitalButton $button): string => $button->value,
            WiiNESDigitalButton::cases(),
        );
    }
}
