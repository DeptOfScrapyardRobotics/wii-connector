<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;

/** SNES Classic Mini controller: the NES layout plus X, Y, L and R. */
class SNESClassicController extends NESClassicController
{
    public function supportedButtons(): array
    {
        return $this->onlyButtons(
            ...parent::supportedButtons(),
            ...[WiiClassicButton::X, WiiClassicButton::Y, WiiClassicButton::L, WiiClassicButton::R],
        );
    }
}
