<?php

use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Providers\WiiConnectorServiceProvider;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;

/** The provider's config with the app's wiring over it, booted onto the bench's catalog, every slave answering $id. */
function wiiBench(array $i2c, array $id = SNES_MINI_ID): array
{
    $bench = fakeBench(['circuits' => ['wii-connector' => ['default_config' => 'i2c', 'configs' => ['i2c' => [
        'driver' => 'fake', 'device' => 1, ...$i2c,
    ]]]]]);
    $bench['i2c']->replies = [$id];
    $provider = new WiiConnectorServiceProvider($bench['app']);
    $provider->register();
    $provider->boot();

    return $bench;
}

it('conjures the controller the config names, booted, at 0x52', function (string $controller, array $id): void {
    $bench = wiiBench(['controller' => $controller], $id);

    $chip = $bench['app']->make('circuit')->conjure('wii-connector');

    expect($chip)->toBeInstanceOf($controller)
        ->and($chip->hasBooted())->toBeTrue()
        ->and($bench['i2c']->opened)->toBe([1])
        ->and($bench['i2c']->slaves)->toHaveKey('1:82');
})->with([
    'SNES Classic' => [SNESClassicController::class, SNES_MINI_ID],
    'Wii Classic (the default)' => [WiiClassicController::class, WII_CLASSIC_ID],
    'Nunchuck' => [WiiNunchuck::class, NUNCHUCK_ID],
]);

it('ships the Wii Classic Controller as the package config default', function (): void {
    $bench = fakeBench();
    (new WiiConnectorServiceProvider($bench['app']))->register();

    expect(config('circuits.wii-connector.configs.i2c.controller'))->toBe(WiiClassicController::class);
});

it('shares an I2C bus the app already connected', function (): void {
    $bench = wiiBench(['controller' => SNESClassicController::class]);
    $bench['app']->make('gpio.i2c')->driver('fake')->connectTo(1)->register();

    $bench['app']->make('circuit')->conjure('wii-connector');

    expect($bench['i2c']->opened)->toBe([1]);
});

it('builds a controller called directly on its class, without booting when asked', function (): void {
    $bench = wiiBench([]);

    $chip = SNESClassicController::i2c('fake', 1, boot_now: false);

    expect($chip)->toBeInstanceOf(SNESClassicController::class)
        ->and($chip->hasBooted())->toBeFalse()
        ->and($bench['i2c']->slaves['1:82']->log)->toBe([]);
});

it('refuses a controller that is not a concrete Wii extension', function (string $controller): void {
    $bench = wiiBench(['controller' => $controller]);

    expect(fn () => $bench['app']->make('circuit')->conjure('wii-connector'))->toThrow(WiiConnectorException::class, 'is not a concrete Wii extension controller class')
        ->and($bench['i2c']->opened)->toBe([]);
})->with([WiiExtension::class, stdClass::class]);
