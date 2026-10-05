<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Concerns;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;
use Voyager\Vessel\ControlPanel;

/**
 * The i2c() protocol factory the circuit catalog calls. Its parameters are the keys of a
 * config/circuits/wii-connector.php entry, so app('circuit')->conjure('wii-connector') builds the controller the
 * config names, wired and booted. Called on a concrete controller class, $controller defaults to that class. A bus
 * that is not connected yet is connected here; one the app already connected is shared as it is.
 */
trait ConjuresWiiExtension
{
    /** @param  class-string<WiiExtension>|null  $controller */
    public static function i2c(
        string $driver,
        string|int $device,
        int $slave = WiiConnectorI2CAddress::DEFAULT->value,
        ?string $controller = null,
        bool $boot_now = true,
    ): static {
        $controller ??= static::class;

        if (! is_subclass_of($controller, WiiExtension::class) || (new \ReflectionClass($controller))->isAbstract()) {
            throw WiiConnectorException::notAController($controller);
        }

        $bus = ControlPanel::getInstance()->make('gpio.i2c')->driver($driver);
        $i2c = $bus->device($device, $slave) ?? $bus->connectTo($device)->register()->device($device, $slave);

        if (is_null($i2c)) {
            throw WiiConnectorException::notConnected('I2C', $driver, $device);
        }

        return new $controller(new WiiConnectorI2CTransport($i2c), boot_now: $boot_now);
    }
}
