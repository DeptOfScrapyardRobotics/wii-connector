<?php

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton as Btn;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuckConfiguration;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorConfiguration;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Tests\Support\ConfigPathVessel;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Tests\Support\FakeI2CConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionManager;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use Voyager\Config\Repository;
use Voyager\IOPools\EventLoop;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\PromiseEngines\GuzzlePromiseEngine;
use Voyager\IOPools\ResourceRegistry;
use Voyager\IOPools\Waiter\StreamSelectWaiterBackend;
use Voyager\Vessel\ControlPanel;

/*
| Proven against a recording fake I2C transport: every byte a Wii extension
| would see, every byte it would answer, in order. Nothing here touches a bus.
| The live check is an SNES Classic Mini controller at 0x52 on the Pi 5.
*/

/*
| A Venusian app's core defines config() over the container's config repository;
| CircuitRegistry::conjure() calls it. A package suite has no core, so this
| stands in for it the same way.
*/
if (! function_exists('config')) {
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = ControlPanel::getInstance()->make('config');

        return match (true) {
            is_null($key) => $config,
            is_array($key) => $config->set($key),
            default => $config->get($key, $default),
        };
    }
}

/** A loop on the select backend, the way IOPools builds one. */
function testLoop(int $pace_ms = 16): EventLoop
{
    $registry = new ResourceRegistry;

    return new EventLoop($registry, new LoopWaiter($registry, new StreamSelectWaiterBackend, $pace_ms * 1_000_000), new GuzzlePromiseEngine);
}

/**
 * The shared container as an app sets it up: config, the circuit catalog, and the I2C manager with a 'fake' driver.
 *
 * @return array{i2c: FakeI2CConnectionDriver, app: ConfigPathVessel}
 */
function fakeBench(array $config = []): array
{
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository($config));
    $app->registerInstance('circuit', new CircuitRegistry);
    ControlPanel::setInstance($app);

    $bench = ['i2c' => new FakeI2CConnectionDriver, 'app' => $app];
    $app->registerInstance('gpio.i2c', (new I2CConnectionManager($app))->extend('fake', fn () => $bench['i2c']));

    return $bench;
}

pest()->afterEach(function (): void {
    ControlPanel::setInstance(null);
})->in(__DIR__);

/** Identifier the bench SNES Classic Mini controller answers. */
const SNES_MINI_ID = [0x01, 0x00, 0xA4, 0x20, 0x01, 0x01];

const WII_CLASSIC_ID = [0x00, 0x00, 0xA4, 0x20, 0x01, 0x01];

const NUNCHUCK_ID = [0x00, 0x00, 0xA4, 0x20, 0x00, 0x00];

/** The bench SNES controller's identifier while left in high-resolution mode. */
const SNES_MINI_HIGH_RES_ID = [0x01, 0x00, 0xA4, 0x20, 0x03, 0x01];

/** Analog bytes the bench SNES controller reports: sticks centred, triggers released. */
const SNES_MINI_ANALOG = [0xA0, 0x20, 0x10, 0x00];

/** A Classic-family report with these buttons down. */
function classicReport(array $analog = SNES_MINI_ANALOG, Btn ...$pressed): array
{
    $word = 0xFFFF;

    foreach ($pressed as $button) {
        $word &= ~(1 << $button->value);
    }

    return [...$analog, $word >> 8, $word & 0xFF];
}

function pressed(Btn ...$buttons): array
{
    return classicReport(SNES_MINI_ANALOG, ...$buttons);
}

function wiiConfig(): WiiConnectorConfiguration
{
    return new WiiConnectorConfiguration(init_wait_ms: 0, read_delay_us: 0);
}

/**
 * @param  class-string<WiiExtension>  $class
 * @return array{0: WiiExtension, 1: FakeI2CTransport} booted, boot traffic cleared
 */
function wii(string $class, array $reports = [], array $id = SNES_MINI_ID, ?WiiConnectorConfiguration $config = null): array
{
    $bus = new FakeI2CTransport;
    $bus->replies = [$id, ...$reports];
    $config ??= $class === WiiNunchuck::class
        ? new WiiNunchuckConfiguration(init_wait_ms: 0, read_delay_us: 0)
        : wiiConfig();
    $chip = new $class(new WiiConnectorI2CTransport($bus), $config, boot_now: true);
    $bus->log = [];

    return [$chip, $bus];
}
