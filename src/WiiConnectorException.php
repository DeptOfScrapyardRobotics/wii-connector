<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use BareMetal\Contracts\Actuators\ActuationException;

class WiiConnectorException extends ActuationException
{
    public static function transportMissingProtocol(): static
    {
        return new static("All Wii Connectors require an I2C capable connection.");
    }

    public static function readFailed(): static
    {
        return new static('Wii peripheral data read failed.');
    }
}
