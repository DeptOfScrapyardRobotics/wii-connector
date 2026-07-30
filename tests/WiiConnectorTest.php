<?php

use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\NESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorCarrierTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiGamepad;
use Fabricate\Contracts\Actuation\HumanInput\GameController;
use Fabricate\Contracts\Actuation\HumanInput\GameControllerAxis;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use Fabricate\Contracts\Sensors\Interfaces\Accelerometer;
use GeneralPurposeIO\Contracts\I2C\I2CDriver;
use GeneralPurposeIO\I2C\I2CSlave;

function fakeWiiBus(array $snapshots): array
{
    $driver = new class($snapshots) implements I2CDriver
    {
        public array $writes = [];
        public array $reads = [];
        public array $write_reads = [];
        public bool $closed = false;

        public function __construct(public array $snapshots) {}

        public function read(int $address, int $length): array|false
        {
            $this->reads[] = [$address, $length];

            return array_shift($this->snapshots) ?: false;
        }

        public function write(int $address, array|string $data): int
        {
            $this->writes[] = [$address, $data];

            return is_array($data) ? count($data) : strlen($data);
        }

        public function writeRead(int $address, array|string $data, int $length): array|false
        {
            $this->write_reads[] = [$address, $data, $length];

            return array_shift($this->snapshots) ?: false;
        }

        public function bulkWrite(int $address, array|string $messages): array|false
        {
            return false;
        }

        public function close(): void
        {
            $this->closed = true;
        }
    };

    return [new I2CSlave(0x52, $driver), $driver];
}

it('polls one Classic snapshot and decodes active-low buttons', function (): void {
    [$bus, $driver] = fakeWiiBus([[0x20, 0x20, 0x10, 0x10, 0xFF, 0xEF]]);
    $controller = WiiClassicController::fromI2CBus($bus, false)->poll();

    expect($driver->writes)->toBe([
        [0x52, [0xF0, 0x55]],
        [0x52, [0xFB, 0x00]],
        [0x52, [0x00]],
    ])->and($driver->reads)->toBe([[0x52, 6]])
        ->and($driver->write_reads)->toBe([])
        ->and($controller->isDown('A'))->toBeTrue()
        ->and($controller->isDown('B'))->toBeFalse()
        ->and($controller->axis(GameControllerAxis::LEFT_X))->toBeGreaterThan(0.0);
});

it('waits for the extension to prepare its report between write and read', function (): void {
    [$bus, $driver] = fakeWiiBus([[0x20, 0x20, 0x10, 0x10, 0xFF, 0xEF]]);
    $delays = [];
    $transport = new WiiConnectorCarrierTransport(
        $bus,
        static function (int $microseconds) use (&$delays): void {
            $delays[] = $microseconds;
        },
    );

    expect($transport->snapshot())->toBe([0x20, 0x20, 0x10, 0x10, 0xFF, 0xEF])
        ->and($driver->writes)->toBe([[0x52, [0x00]]])
        ->and($delays)->toBe([3_000])
        ->and($driver->reads)->toBe([[0x52, 6]])
        ->and($driver->write_reads)->toBe([]);
});

it('rejects an incomplete extension report', function (): void {
    [$bus] = fakeWiiBus([[0x20, 0x20, 0x10, 0x10, 0xFF]]);
    $transport = new WiiConnectorCarrierTransport(
        $bus,
        static function (int $microseconds): void {},
    );

    expect(fn (): array => $transport->snapshot())
        ->toThrow(RuntimeException::class, 'The Wii extension did not return a six-byte snapshot.');
});

it('exposes reduced SNES and NES button layouts', function (): void {
    [$snes_bus, $snes_driver] = fakeWiiBus([[0x20, 0x20, 0x10, 0x10, 0xFF, 0xEF]]);
    [$nes_bus, $nes_driver] = fakeWiiBus([[0x20, 0x20, 0x10, 0x10, 0xFF, 0xBF]]);

    $snes = SNESClassicController::fromI2CBus($snes_bus, false)->poll();
    $nes = NESClassicController::fromI2CBus($nes_bus, false)->poll();

    expect($snes)->toBeInstanceOf(ButtonPad::class)
        ->and($snes instanceof GameController)->toBeFalse()
        ->and($nes)->toBeInstanceOf(ButtonPad::class)
        ->and($nes instanceof GameController)->toBeFalse()
        ->and(get_parent_class($snes))->toBe(WiiGamepad::class)
        ->and(get_parent_class($nes))->toBe(WiiGamepad::class)
        ->and($snes->labels())->toBe(['A', 'B', 'X', 'Y', 'START', 'SELECT', 'L', 'R', 'UP', 'DOWN', 'LEFT', 'RIGHT'])
        ->and($nes->labels())->toBe(['A', 'B', 'START', 'SELECT', 'UP', 'DOWN', 'LEFT', 'RIGHT'])
        ->and($snes->isDown('A'))->toBeTrue()
        ->and($nes->isDown('B'))->toBeTrue()
        ->and($snes_driver->reads)->toHaveCount(1)
        ->and($nes_driver->reads)->toHaveCount(1)
        ->and($snes_driver->write_reads)->toBe([])
        ->and($nes_driver->write_reads)->toBe([]);
});

it('keeps full Classic axes on the direct WiiGamepad subclass only', function (): void {
    [$bus] = fakeWiiBus([]);
    $classic = WiiClassicController::fromI2CBus($bus, false);

    expect(get_parent_class($classic))->toBe(WiiGamepad::class)
        ->and($classic)->toBeInstanceOf(ButtonPad::class)
        ->and($classic)->toBeInstanceOf(GameController::class);
});

it('decodes Nunchuck controls and approximate acceleration from one snapshot', function (): void {
    [$bus, $driver] = fakeWiiBus([[0xFF, 0x00, 0x80, 0x80, 0x80, 0x00]]);
    $nunchuck = WiiNunchuck::fromI2CBus($bus, false)->poll();

    expect($nunchuck)->toBeInstanceOf(GameController::class)
        ->and($nunchuck)->toBeInstanceOf(Accelerometer::class)
        ->and($driver->reads)->toHaveCount(1)
        ->and($driver->write_reads)->toBe([])
        ->and($nunchuck->isDown('C'))->toBeTrue()
        ->and($nunchuck->isDown('Z'))->toBeTrue()
        ->and($nunchuck->axis(GameControllerAxis::LEFT_X))->toBe(1.0)
        ->and($nunchuck->axis(GameControllerAxis::LEFT_Y))->toBe(1.0)
        ->and([$nunchuck->x(), $nunchuck->y(), $nunchuck->z()])->toBe([0.0, 0.0, 0.0]);
});

it('advertises discovery and all four circuit registrations', function (): void {
    $root = dirname(__DIR__);
    $composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $provider = file_get_contents($root.'/src/WiiConnectorServiceProvider.php');

    expect($composer['version'])->toBe('0.6.0')
        ->and($composer['extra']['scrapyard-io']['providers'])
        ->toContain('DeptOfScrapyardRobotics\\Actuators\\WiiConnector\\WiiConnectorServiceProvider');

    foreach (['wii-classic', 'snes-classic', 'nes-classic', 'wii-nunchuck'] as $alias) {
        expect($provider)->toContain("Circuit::addCircuit('{$alias}'");
    }
});
