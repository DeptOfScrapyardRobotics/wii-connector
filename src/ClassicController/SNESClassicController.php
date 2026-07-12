<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiSNESDigitalButton;

class SNESClassicController extends WiiClassicController
{
    /**
     * SNES subset only — no HOME / ZL / ZR.
     *
     * @return array<string, bool>
     */
    public function getDigitalButtons(): array
    {
        $buffer = $this->readData();

        return $this->decodeSNESDigitalButtons($buffer);
    }

    /**
     * DigitalButtonPad over SNES buttons. One I2C poll per pad->poll().
     */
    public function asDigitalButtonPad(): DigitalButtonPad
    {
        return $this->digitalButtonPadFromSnapshot(
            WiiSNESDigitalButton::cases(),
            fn (): array => $this->getDigitalButtons(),
        );
    }

    /**
     * @param  list<int>  $buffer
     * @return array<string, bool>
     */
    protected function decodeSNESDigitalButtons(array $buffer): array
    {
        $all = $this->decodeDigitalButtons($buffer);
        $buttons = [];

        foreach (WiiSNESDigitalButton::cases() as $button) {
            $buttons[$button->value] = (bool) ($all[$button->value] ?? false);
        }

        return $buttons;
    }
}
