<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use BackedEnum;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiExtensionId;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;

class WiiConnectorException extends CircuitException
{
    public static function unexpectedExtension(WiiExtensionId $expected, int $actual): static
    {
        return new static(sprintf('Expected a %s extension (0x%08X), got identifier 0x%08X.', $expected->name, $expected->value, $actual));
    }

    public static function unsupportedButton(BackedEnum $button, string $class): static
    {
        return new static(sprintf('%s has no %s button.', $class, $button->name));
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property '{$name}' on {$class}.");
    }

    public static function writeFailed(int $register, int $wanted, int $wrote): static
    {
        return new static(sprintf('Wii extension register 0x%02X: wanted to write %d bytes, wrote %d.', $register, $wanted, $wrote));
    }

    public static function readFailed(int $register, int $length): static
    {
        return new static(sprintf('Wii extension register 0x%02X: the bus refused a %d byte read.', $register, $length));
    }

    public static function shortRead(int $register, int $wanted, int $got): static
    {
        return new static(sprintf('Wii extension register 0x%02X: wanted %d bytes, got %d.', $register, $wanted, $got));
    }

    public static function invalidHoldTime(int $hold_ms): static
    {
        return new static("hold_ms takes 0 or more; got {$hold_ms}.");
    }

    public static function notAController(string $class): static
    {
        return new static("[{$class}] is not a concrete Wii extension controller class.");
    }

    public static function notConnected(string $protocol, string $driver, string|int $device): static
    {
        return new static("Wii connector could not get a {$protocol} connection from driver [{$driver}] on device [{$device}].");
    }
}
