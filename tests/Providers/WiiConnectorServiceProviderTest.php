<?php

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Providers\WiiConnectorServiceProvider;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Tests\Support\ConfigPathVessel;
use Voyager\Config\Repository;
use Voyager\NutsAndBolts\ServiceProvider;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;

it('registers the config under circuits.wii-connector, keeping anything the app already set', function (): void {
    $vessel = new ConfigPathVessel;
    $vessel->registerInstance('config', new Repository(['circuits' => ['wii-connector' => ['default_config' => 'bench']]]));

    (new WiiConnectorServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.wii-connector.default_config'))->toBe('bench')
        ->and($config->get('circuits.wii-connector.configs.i2c.slave'))->toBe(WiiConnectorI2CAddress::DEFAULT->value)
        ->and($config->get('circuits.wii-connector.configs.i2c.controller'))->toBe(\DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController::class)
        ->and($config->has('wii-connector'))->toBeFalse();
});

it('leaves other circuits config beside its own key untouched', function (): void {
    $vessel = new ConfigPathVessel;
    $vessel->registerInstance('config', new Repository(['circuits' => ['seesaw-mini-gamepad' => ['default_config' => 'i2c']]]));

    (new WiiConnectorServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.seesaw-mini-gamepad'))->toBe(['default_config' => 'i2c'])
        ->and($config->get('circuits.wii-connector.default_config'))->toBe('i2c');
});

it('publishes the config file into config/circuits under the wii-connector-config tag', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->registerInstance('config', new Repository);

    $provider = new WiiConnectorServiceProvider($app);
    $provider->register();
    $provider->boot();

    $root = dirname(__DIR__, 2);

    expect(ServiceProvider::pathsToPublish(WiiConnectorServiceProvider::class, 'wii-connector-config'))->toBe([
        "{$root}/config/wii-connector.php" => '/app/config/circuits/wii-connector.php',
    ]);
});

it('adds the configured controller to the circuit catalog when one is bound', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository);
    $app->registerInstance('circuit', $catalog = new CircuitRegistry);

    $provider = new WiiConnectorServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($catalog->listCircuits())->toBe(['wii-connector' => WiiExtension::class]);
});
