<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorInternalAPI;

trait WiiNunchuckInternalAPI
{
    use WiiConnectorInternalAPI;

    protected function _boot(): void
    {
        $this->initialize();
    }
}
