<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorInternalAPI;

trait WiiClassicInternalAPI
{
    use WiiConnectorInternalAPI;

    protected function _boot(): void
    {
        $this->initialize();
    }
}
