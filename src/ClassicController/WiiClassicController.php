<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiGamepad;
use Fabricate\Contracts\Actuation\HumanInput\GameController;
use Fabricate\Contracts\Actuation\HumanInput\GameControllerAxis;
use Fabricate\Contracts\Circuits\Attributes\IntegratedCircuit;

#[IntegratedCircuit('I2C')]
class WiiClassicController extends WiiGamepad implements GameController
{
    use InterpretsClassicControllerReports;

    /** @var array<string, float> */
    protected array $axis_values = [
        'left_x' => 0.0,
        'left_y' => 0.0,
        'right_x' => 0.0,
        'right_y' => 0.0,
        'left_trigger' => 0.0,
        'right_trigger' => 0.0,
    ];

    protected function updateMeasurements(array $snapshot): void
    {
        $right_x = (($snapshot[0] & 0xC0) >> 3)
            | (($snapshot[1] & 0xC0) >> 5)
            | (($snapshot[2] & 0x80) >> 7);

        $this->axis_values = [
            'left_x' => $this->normalize($snapshot[0] & 0x3F, 31.5, 31.5),
            'left_y' => $this->normalize($snapshot[1] & 0x3F, 31.5, 31.5, true),
            'right_x' => $this->normalize($right_x, 15.5, 15.5),
            'right_y' => $this->normalize($snapshot[2] & 0x1F, 15.5, 15.5, true),
            'left_trigger' => ((($snapshot[2] & 0x60) >> 2) | (($snapshot[3] & 0xE0) >> 5)) / 31,
            'right_trigger' => ($snapshot[3] & 0x1F) / 31,
        ];
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
}
