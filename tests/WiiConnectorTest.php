<?php

use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\NESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton as Btn;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiExtensionId;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuckConfiguration;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Tests\Support\FakeGPIOResource;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiButtonState;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorConfiguration;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;
use GeneralPurposeIO\Contracts\Core\Recurrence;

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

// --- boot ---------------------------------------------------------------------

it('boots a Classic-family controller unencrypted and pins the standard report', function (string $class): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [SNES_MINI_ID];

    $chip = new $class(new WiiConnectorI2CTransport($bus), wiiConfig(), boot_now: true);

    expect($chip->connected())->toBeTrue()
        ->and($bus->log)->toBe([
            ['w', [0xF0, 0x55]],
            ['w', [0xFB, 0x00]],
            ['w', [0xFA]], ['r', 6],
            ['w', [0xFE, 0x01]],
        ]);
})->with([NESClassicController::class, SNESClassicController::class, WiiClassicController::class]);

it('boots a Nunchuck without touching the data format', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [NUNCHUCK_ID];

    new WiiNunchuck(new WiiConnectorI2CTransport($bus), new WiiNunchuckConfiguration(init_wait_ms: 0, read_delay_us: 0), boot_now: true);

    expect($bus->log)->toBe([['w', [0xF0, 0x55]], ['w', [0xFB, 0x00]], ['w', [0xFA]], ['r', 6]]);
});

it('reads the identifier', function (): void {
    [$chip] = wii(SNESClassicController::class, [WII_CLASSIC_ID, SNES_MINI_ID]);

    expect($chip->identifier)->toBe(WII_CLASSIC_ID)
        ->and($chip->extension_id)->toBe(WiiExtensionId::CLASSIC_CONTROLLER->value);
});

it('boots the full Wii Classic identifier as well as the mini one', function (): void {
    [$chip] = wii(WiiClassicController::class, id: WII_CLASSIC_ID);

    expect($chip->hasBooted())->toBeTrue();
});

it('boots a controller left in high-resolution mode and puts it back to standard', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [SNES_MINI_HIGH_RES_ID, [0x01]];

    $snes = new SNESClassicController(new WiiConnectorI2CTransport($bus), wiiConfig(), boot_now: true);

    expect(array_slice($bus->log, 4))->toBe([['w', [0xFE, 0x01]]])
        ->and($snes->data_format)->toBe(0x01);
});

it('refuses the wrong kind of extension before configuring it', function (string $class, array $id, string $message): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [$id];
    $config = $class === WiiNunchuck::class ? new WiiNunchuckConfiguration(init_wait_ms: 0, read_delay_us: 0) : wiiConfig();

    expect(fn () => new $class(new WiiConnectorI2CTransport($bus), $config, boot_now: true))
        ->toThrow(WiiConnectorException::class, $message)
        ->and($bus->log)->toHaveCount(4);
})->with([
    [SNESClassicController::class, NUNCHUCK_ID, 'Expected a CLASSIC_CONTROLLER extension (0xA4200001), got identifier 0xA4200000.'],
    [WiiNunchuck::class, SNES_MINI_ID, 'Expected a NUNCHUCK extension (0xA4200000), got identifier 0xA4200001.'],
]);

it('boots once, and only when asked', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [SNES_MINI_ID];
    $chip = new SNESClassicController(new WiiConnectorI2CTransport($bus), wiiConfig());

    expect($bus->log)->toBe([])->and($chip->connected())->toBeFalse();

    $chip->boot();
    $chip->boot();

    expect($bus->log)->toHaveCount(5);
});

// --- the Classic family ----------------------------------------------------------

it('builds SNES on NES and Wii Classic on SNES, each adding buttons', function (): void {
    [$nes] = wii(NESClassicController::class);
    [$snes] = wii(SNESClassicController::class);
    [$classic] = wii(WiiClassicController::class);

    expect($snes)->toBeInstanceOf(NESClassicController::class)
        ->and($classic)->toBeInstanceOf(SNESClassicController::class)
        ->and($nes->supportedButtons())->toBe([Btn::UP, Btn::LEFT, Btn::A, Btn::B, Btn::START, Btn::SELECT, Btn::DOWN, Btn::RIGHT])
        ->and($snes->supportedButtons())->toBe([Btn::UP, Btn::LEFT, Btn::X, Btn::A, Btn::Y, Btn::B, Btn::R, Btn::START, Btn::SELECT, Btn::L, Btn::DOWN, Btn::RIGHT])
        ->and($classic->supportedButtons())->toBe(Btn::cases())
        ->and(array_keys($nes->buttons()))->toBe(['UP', 'LEFT', 'A', 'B', 'START', 'SELECT', 'DOWN', 'RIGHT'])
        ->and($nes->buttons['A'])->toBeInstanceOf(WiiButtonState::class);
});

it('refuses buttons a controller does not have', function (string $class, Btn $button): void {
    [$chip] = wii($class);

    expect($chip->supports($button))->toBeFalse()
        ->and(fn () => $chip->isDown($button))->toThrow(WiiConnectorException::class, "has no {$button->name} button");
})->with([
    [NESClassicController::class, Btn::X],
    [NESClassicController::class, Btn::L],
    [SNESClassicController::class, Btn::HOME],
    [SNESClassicController::class, Btn::ZR],
]);

it('reads each Classic button from its own report bit', function (Btn $button, int $byte4, int $byte5): void {
    [$chip, $bus] = wii(WiiClassicController::class, [pressed($button)]);

    $chip->poll();

    expect($chip->report())->toBe([...SNES_MINI_ANALOG, $byte4, $byte5])
        ->and($chip->downButtons())->toBe([$button])
        ->and($chip->pressedButtons())->toBe([$button])
        ->and($bus->log)->toBe([['w', [0x00]], ['r', 6]]);
})->with([
    [Btn::RIGHT, 0x7F, 0xFF], [Btn::DOWN, 0xBF, 0xFF], [Btn::L, 0xDF, 0xFF], [Btn::SELECT, 0xEF, 0xFF],
    [Btn::HOME, 0xF7, 0xFF], [Btn::START, 0xFB, 0xFF], [Btn::R, 0xFD, 0xFF],
    [Btn::ZL, 0xFF, 0x7F], [Btn::B, 0xFF, 0xBF], [Btn::Y, 0xFF, 0xDF], [Btn::A, 0xFF, 0xEF],
    [Btn::X, 0xFF, 0xF7], [Btn::ZR, 0xFF, 0xFB], [Btn::LEFT, 0xFF, 0xFD], [Btn::UP, 0xFF, 0xFE],
]);

it('ignores report bits for buttons the controller does not have', function (): void {
    [$snes] = wii(SNESClassicController::class, [pressed(Btn::ZL, Btn::HOME, Btn::Y)]);

    $snes->poll();

    expect($snes->downButtons())->toBe([Btn::Y]);
});

it('tracks press, hold, release, chords and the D-pad across polls', function (): void {
    [$snes] = wii(SNESClassicController::class, [
        pressed(Btn::UP, Btn::B),
        pressed(Btn::UP, Btn::B, Btn::START, Btn::SELECT),
        pressed(Btn::START, Btn::SELECT),
        pressed(),
    ], config: new WiiConnectorConfiguration(hold_ms: 5, init_wait_ms: 0, read_delay_us: 0));

    $snes->poll();
    expect($snes->pressedButtons())->toBe([Btn::UP, Btn::B])
        ->and($snes->d_pad)->toBe(['up' => true, 'down' => false, 'left' => false, 'right' => false])
        ->and($snes->isHolding(Btn::B))->toBeFalse();

    usleep(10_000);
    $snes->poll();
    expect($snes->pressedButtons())->toBe([Btn::START, Btn::SELECT])
        ->and($snes->holdingButtons())->toBe([Btn::UP, Btn::B])
        ->and($snes->heldMs(Btn::B))->toBeGreaterThanOrEqual(5)
        ->and($snes->chord(Btn::START, Btn::SELECT))->toBeTrue()
        ->and($snes->anyDown(Btn::X, Btn::B))->toBeTrue()
        ->and($snes->allDown())->toBeFalse()
        ->and($snes->anyPressed(Btn::UP))->toBeFalse();

    $snes->poll();
    expect($snes->releasedButtons())->toBe([Btn::UP, Btn::B])
        ->and($snes->wasReleased(Btn::B))->toBeTrue()
        ->and($snes->isDown(Btn::B))->toBeFalse();

    $snes->poll();
    expect($snes->anyDown())->toBeFalse()
        ->and($snes->anyPressed())->toBeFalse()
        ->and($snes->releasedButtons())->toBe([Btn::START, Btn::SELECT]);
});

it('reads every button down as allDown()', function (): void {
    [$nes] = wii(NESClassicController::class, [pressed(Btn::UP, Btn::LEFT, Btn::A, Btn::B, Btn::START, Btn::SELECT, Btn::DOWN, Btn::RIGHT)]);

    $nes->poll();

    expect($nes->allDown())->toBeTrue();
});

it('scales the Wii Classic sticks and triggers, up reading negative by default', function (): void {
    [$classic] = wii(WiiClassicController::class, [
        classicReport([0xFF, 0xFF, 0xFF, 0xFF]),
        classicReport([0x00, 0x00, 0x00, 0x00]),
        classicReport([0xFF, 0xFF, 0xFF, 0xFF]),
    ]);

    $classic->poll();
    expect($classic->rawAnalog())->toBe(['left_x' => 63, 'left_y' => 63, 'right_x' => 31, 'right_y' => 31, 'left_trigger' => 31, 'right_trigger' => 31])
        ->and($classic->axes())->toBe(['left_x' => 1.0, 'left_y' => -1.0, 'right_x' => 1.0, 'right_y' => -1.0, 'left_trigger' => 1.0, 'right_trigger' => 1.0]);

    $classic->poll();
    expect($classic->left_stick)->toBe(['x' => -1.0, 'y' => 1.0])
        ->and($classic->right_stick)->toBe(['x' => -1.0, 'y' => 1.0])
        ->and($classic->left_trigger)->toBe(0.0)
        ->and($classic->right_trigger)->toBe(0.0);

    $classic->invert_y = false;
    $classic->poll();
    expect($classic->leftStick())->toBe(['x' => 1.0, 'y' => 1.0])
        ->and($classic->rightStick())->toBe(['x' => 1.0, 'y' => 1.0])
        ->and($classic->invert_y)->toBeFalse();
});

it('reads the bench SNES idle report as centred sticks and released triggers', function (): void {
    [$classic] = wii(WiiClassicController::class, [pressed()]);

    $classic->poll();

    expect($classic->rawAnalog())->toBe(['left_x' => 32, 'left_y' => 32, 'right_x' => 16, 'right_y' => 16, 'left_trigger' => 0, 'right_trigger' => 0])
        ->and(abs($classic->leftStick()['x']))->toBeLessThan(0.05)
        ->and(abs($classic->rightStick()['y']))->toBeLessThan(0.05)
        ->and($classic->leftTrigger())->toBe(0.0);
});

// --- the Nunchuck ------------------------------------------------------------------

it('decodes the Nunchuck stick, buttons and 10-bit acceleration', function (): void {
    [$nunchuck] = wii(WiiNunchuck::class, [
        [0xFF, 0x00, 0x80, 0x40, 0xC0, 0b11_01_10_01],
        [0x80, 0x80, 0x80, 0x80, 0x80, 0b00_00_00_11],
    ], NUNCHUCK_ID);

    $nunchuck->poll();

    expect($nunchuck->supportedButtons())->toBe([WiiNunchuckButton::Z, WiiNunchuckButton::C])
        ->and($nunchuck->downButtons())->toBe([WiiNunchuckButton::C])
        ->and($nunchuck->rawStick())->toBe(['x' => 255, 'y' => 0])
        ->and($nunchuck->stick)->toBe(['x' => 1.0, 'y' => 1.0])
        ->and($nunchuck->raw_acceleration)->toBe(['x' => 514, 'y' => 257, 'z' => 771])
        ->and($nunchuck->acceleration)->toBe(['x' => 2 / 256, 'y' => -255 / 256, 'z' => 259 / 256])
        ->and($nunchuck->x)->toBe(2 / 256);

    $nunchuck->poll();

    expect($nunchuck->releasedButtons())->toBe([WiiNunchuckButton::C])
        ->and($nunchuck->raw_acceleration)->toBe(['x' => 512, 'y' => 512, 'z' => 512])
        ->and($nunchuck->acceleration())->toBe(['x' => 0.0, 'y' => 0.0, 'z' => 0.0])
        ->and([$nunchuck->x(), $nunchuck->y(), $nunchuck->z()])->toBe([0.0, 0.0, 0.0])
        ->and(abs($nunchuck->stick()['x']))->toBeLessThan(0.01);
});

it('scales Nunchuck acceleration from its configuration', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = [NUNCHUCK_ID, [0x80, 0x80, 0x90, 0x80, 0x80, 0x03]];
    $nunchuck = new WiiNunchuck(
        new WiiConnectorI2CTransport($bus),
        new WiiNunchuckConfiguration(init_wait_ms: 0, read_delay_us: 0, accel_zero: 500, accel_counts_per_g: 200),
        boot_now: true,
    );

    $nunchuck->poll();

    expect($nunchuck->x())->toBe((576 - 500) / 200)
        ->and($nunchuck->config()->get('accel_counts_per_g'))->toBe(200);
});

it('refuses Classic buttons on the Nunchuck and Nunchuck buttons on a Classic controller', function (): void {
    [$nunchuck] = wii(WiiNunchuck::class, id: NUNCHUCK_ID);
    [$classic] = wii(WiiClassicController::class);

    expect(fn () => $nunchuck->isDown(Btn::A))->toThrow(WiiConnectorException::class, 'WiiNunchuck has no A button')
        ->and(fn () => $classic->isDown(WiiNunchuckButton::Z))->toThrow(WiiConnectorException::class, 'WiiClassicController has no Z button');
});

// --- dock, settings, errors -----------------------------------------------------------

it('puts poll() on the gpio dock', function (): void {
    [$snes, $bus] = wii(SNESClassicController::class, [pressed(Btn::A)]);
    $gpio = new FakeGPIOResource;

    $recurrence = $snes->every($gpio, 3);

    expect($recurrence)->toBeInstanceOf(Recurrence::class)
        ->and($bus->log)->toBe([])
        ->and($gpio->runRecurrence('wii-extension'))->toBe($snes)
        ->and($snes->pressedButtons())->toBe([Btn::A]);
});

it('waits the configured delay between selecting a register and reading it', function (): void {
    [$snes] = wii(SNESClassicController::class, [pressed()], config: new WiiConnectorConfiguration(init_wait_ms: 0, read_delay_us: 20_000));

    $start = hrtime(true);
    $snes->poll();

    expect((hrtime(true) - $start) / 1e6)->toBeGreaterThanOrEqual(19.0);
});

it('refuses a negative hold time and unknown names', function (): void {
    [$snes] = wii(SNESClassicController::class);

    expect(fn () => $snes->hold_ms = -1)->toThrow(WiiConnectorException::class, 'hold_ms takes 0 or more; got -1')
        ->and(fn () => $snes->report = [])->toThrow(WiiConnectorException::class, "Invalid property 'report'")
        ->and(fn () => $snes->left_stick)->toThrow(WiiConnectorException::class, "Invalid property 'left_stick'")
        ->and(fn () => $snes->config()->get('accel_zero'))->toThrow(WiiConnectorException::class, "Invalid property 'accel_zero'");

    $snes->hold_ms = 250;

    expect($snes->hold_ms)->toBe(250)->and($snes->report)->toBe([0, 0, 0, 0, 0xFF, 0xFF]);
});

it('throws on a short write, a refused read or a short read', function (): void {
    [$snes, $bus] = wii(SNESClassicController::class, [false, [0xA0, 0x20]]);

    expect(fn () => $snes->poll())->toThrow(WiiConnectorException::class, 'register 0x00: the bus refused a 6 byte read')
        ->and(fn () => $snes->poll())->toThrow(WiiConnectorException::class, 'register 0x00: wanted 6 bytes, got 2');

    $bus->short_by = 1;

    expect(fn () => $snes->initialize())->toThrow(WiiConnectorException::class, 'register 0xF0: wanted to write 2 bytes, wrote 1');
});

it('forgets button state on close and leaves the bus to its driver', function (): void {
    [$snes, $bus] = wii(SNESClassicController::class, [pressed(Btn::A)]);

    $snes->poll();
    $snes->close();

    expect($snes->downButtons())->toBe([])
        ->and($bus->closed)->toBeFalse();
});
