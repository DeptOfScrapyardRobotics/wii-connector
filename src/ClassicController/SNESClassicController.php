<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiSNESDigitalButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiGamepad;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use Fabricate\Contracts\Circuits\Attributes\IntegratedCircuit;

#[IntegratedCircuit('I2C')]
class SNESClassicController extends WiiGamepad implements ButtonPad
{
    use InterpretsClassicControllerReports;

    protected function buttonLabels(): array
    {
        return array_map(
            static fn (WiiSNESDigitalButton $button): string => $button->value,
            WiiSNESDigitalButton::cases(),
        );
    }
}
