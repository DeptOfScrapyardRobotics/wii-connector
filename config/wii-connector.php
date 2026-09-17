<?php

use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;

return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'none',
            'device' => '',
            'slave' => WiiConnectorI2CAddress::DEFAULT->value,
            'controller' => WiiClassicController::class,
        ],
    ],
];
