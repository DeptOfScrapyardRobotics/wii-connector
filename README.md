# wii-connector

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dept-of-scrapyard-robotics/wii-connector.svg)](https://packagist.org/packages/dept-of-scrapyard-robotics/wii-connector)
[![License](https://img.shields.io/packagist/l/dept-of-scrapyard-robotics/wii-connector.svg)](LICENSE)

Read Wii extension controllers from PHP over I2C: the Nunchuck, the Wii Classic Controller, and the NES and SNES Classic Mini controllers.

`dept-of-scrapyard-robotics/wii-connector` starts the controller without encryption, checks what's plugged in, and turns each poll into button presses, releases, holds and analog values. Any breakout that exposes the Wii extension port's I2C pins works.

| Controller | Class | Buttons | Analog |
|---|---|---|---|
| NES Classic Mini | `ClassicController\NESClassicController` | D-pad, A, B, Start, Select | |
| SNES Classic Mini | `ClassicController\SNESClassicController` | NES buttons, plus X, Y, L, R | |
| Wii Classic Controller | `ClassicController\WiiClassicController` | SNES buttons, plus Home, ZL, ZR | two sticks, two triggers |
| Nunchuck | `Nunchuck\WiiNunchuck` | C, Z | stick, three-axis accelerometer |

Each Classic class extends the one above it, so an SNES controller is also an NES controller.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               i2c-dev, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, the circuit catalog
        → dept-of-scrapyard-robotics/wii-connector   ← this package
```

## Requirements

- PHP 8.4 or newer
- A Venusian 0.10 application with the `scrapyard-io/framework` 0.10 components (`gpio/i2c`, `gpio/integrated-circuits`)
- An I2C adapter:
  - `microscrap/scrapyard-linux` (driver `native`) for native `i2c-dev`, needs `ext-posi`
  - `microscrap/scrapyard-usb` (driver `usb`) for FTDI MPSSE boards such as the FT232H, needs `ext-ftdi`
- `venusian-voyager/io-pools` 0.10 if you poll on the event loop

## Installation

```bash
composer require dept-of-scrapyard-robotics/wii-connector
```

The service provider is discovered automatically. It merges the package's wiring config under `circuits.wii-connector` and registers it with the circuit catalog. To publish that config into your app, run:

```bash
php computer vendor:publish --tag=wii-connector-config
```

That writes `config/circuits/wii-connector.php`, with `driver => 'none'` until you fill in your bench.

## Quick start

An SNES Classic Mini controller on a Raspberry Pi's I2C bus 1:

```php
// config/circuits/wii-connector.php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;

return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'native',
            'device' => 1,
            'slave' => 0x52,
            'controller' => SNESClassicController::class,
        ],
    ],
];
```

```php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;

$snes = app('circuit')->conjure('wii-connector');   // connected and booted

while (true) {
    $snes->poll();

    if ($snes->isPressed(WiiClassicButton::A)) {
        echo "A!\n";
    }

    usleep(10_000);
}
```

Booting takes about 210 ms. It writes the two unencrypted-init registers, reads the identifier, and throws if the wrong kind of controller is plugged in. Classic-family controllers are then set to the standard six-byte report, in case something left them in high-resolution mode.

## Connecting

`conjure('wii-connector')` reads `circuits.wii-connector`, picks `default_config`, and builds the class `controller` names, through `WiiExtension::i2c()`. You can call the factory on a controller class directly; `controller` then defaults to that class:

```php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;

$snes = SNESClassicController::i2c('native', 1);            // 0x52 by default
$snes = SNESClassicController::i2c('usb', 'ft232h');
$snes = SNESClassicController::i2c('native', 1, boot_now: false);
```

A bus that isn't connected yet is connected by the factory. One your app already connected is shared as it is. A `controller` that isn't a concrete controller class throws before the bus is touched.

The NES and SNES Classic Mini controllers report the same identifier family as the Wii Classic Controller, so any Classic class boots with any of them. Pick the class for the buttons you want.

When your app's config sets `configs.i2c`, it replaces the package's whole entry, so name `controller` there too.

### Building the transport yourself

```php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;

$slave = app('gpio.i2c')->driver('native')->connectTo(1)->register()->device(1, 0x52);

$snes = new SNESClassicController(new WiiConnectorI2CTransport($slave), boot_now: true);
```

A read selects the register, waits `read_delay_us` (3 ms by default), then reads in a separate transaction.

## Buttons

Everything answers from the last `poll()`. One poll reads one six-byte report.

```php
$snes->isDown(WiiClassicButton::B);        // down right now
$snes->isPressed(WiiClassicButton::B);     // went down on this poll
$snes->wasReleased(WiiClassicButton::B);   // came up on this poll
$snes->isHolding(WiiClassicButton::B);     // down for at least hold_ms
$snes->heldMs(WiiClassicButton::B);

$snes->downButtons();         // [WiiClassicButton::UP, WiiClassicButton::B]
$snes->pressedButtons();
$snes->releasedButtons();
$snes->holdingButtons();

$snes->anyDown();                                             // any button it has
$snes->allDown(WiiClassicButton::X, WiiClassicButton::Y);
$snes->chord(WiiClassicButton::START, WiiClassicButton::SELECT);
$snes->anyPressed();

$snes->d_pad;     // ['up' => true, 'down' => false, 'left' => false, 'right' => false]
```

Classic-family buttons are `WiiClassicButton` cases, and Nunchuck buttons are `WiiNunchuckButton::C` and `::Z`. Asking a controller about a button it doesn't have throws. `supports($button)` checks first, and `supportedButtons()` lists them all.

A press or release shows for exactly one poll. A tap that starts and ends between two polls is not seen, so poll every 10 ms or so.

`button($button)` returns that button's `WiiButtonState`, and `buttons()` returns them all keyed by name.

## Wii Classic Controller analog

```php
$classic->leftStick();       // ['x' => -1.0 … 1.0, 'y' => -1.0 … 1.0]
$classic->rightStick();
$classic->leftTrigger();     // 0.0 … 1.0
$classic->rightTrigger();
$classic->axes();            // all six
$classic->rawAnalog();       // left stick 0–63, right stick 0–31, triggers 0–31
```

With the defaults, up reads -1.0, the way screen coordinates run. Set `invert_y` to `false` to make up read +1.0. An SNES Classic Mini read through `WiiClassicController` reports a full left trigger while L is held.

## Nunchuck

```php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;

$nunchuck = WiiNunchuck::i2c('native', 1);
$nunchuck->poll();

$nunchuck->isDown(WiiNunchuckButton::Z);
$nunchuck->stick();              // ['x' => …, 'y' => …]
$nunchuck->rawStick();           // 0–255 each
$nunchuck->rawAcceleration();    // ['x' => 0–1023, 'y' => …, 'z' => …]
$nunchuck->acceleration();       // in g
$nunchuck->x();                  // one axis, in g
```

Acceleration in g comes from `accel_zero` and `accel_counts_per_g` in `WiiNunchuckConfiguration`, which default to 512 and 256. Calibrate them for your Nunchuck if you need accurate values.

## Polling on the event loop

```php
use Voyager\Contracts\IOPools\Loop;

$loop = app(Loop::class);

$snes->every($loop);             // poll() every 10 ms
// …
$snes->stop($loop);
```

`every($loop, $interval_s, $name)` returns the loop `Timer` that runs `poll()`. Give each controller its own `$name` (default `wii-extension`) to poll several side by side, and pass the same name to `stop()`.

## Configuration object

`WiiConnectorConfiguration` holds settings every controller shares. `WiiNunchuckConfiguration` adds the accelerometer calibration.

| Argument | Default | Meaning |
|---|---|---|
| `hold_ms` | `500` | how long a button stays down before it counts as holding |
| `invert_y` | `true` | up reads -1.0 on every stick |
| `init_wait_ms` | `100` | wait after each init write |
| `read_delay_us` | `3000` | wait between selecting a register and reading it |
| `accel_zero` | `512` | Nunchuck only: count at 0 g |
| `accel_counts_per_g` | `256` | Nunchuck only: counts per g |

## Properties

| Property | Controllers | Type |
|---|---|---|
| `identifier` | all | the six identifier bytes, read now |
| `extension_id` | all | the family part (bytes 2, 3, 5) as one `int`, read now |
| `data_format` | all | register 0xFE, read now: 1 standard, 3 high resolution |
| `report` | all | the last six-byte report |
| `buttons` | all | `array<string, WiiButtonState>` |
| `hold_ms` | all, writable | `int` |
| `invert_y` | all, writable | `bool` |
| `d_pad` | Classic family | `array` |
| `left_stick`, `right_stick`, `left_trigger`, `right_trigger`, `axes` | Wii Classic | analog values |
| `stick`, `raw_acceleration`, `acceleration`, `x`, `y`, `z` | Nunchuck | analog values |

## Errors

Failures throw `WiiConnectorException`, which descends from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException`:

- The plugged-in controller is a different kind than the class expects.
- Your code asks about a button the controller doesn't have.
- The bus writes fewer bytes than asked, refuses a read, or returns fewer bytes than asked.
- The protocol driver hands back no bus (`notConnected`), or `controller` isn't a concrete controller class (`notAController`).
- `hold_ms` is negative.
- Your code reads or writes a property or configuration key that doesn't exist.

## Closing

```php
$snes->close();
```

`close()` clears the button state. The I2C connection belongs to the protocol driver and stays open.

## Configuration file

`config/circuits/wii-connector.php`:

| Key | Default | Meaning |
|---|---|---|
| `default_config` | `'i2c'` | which entry under `configs` `conjure()` uses |
| `configs.i2c.driver` | `'none'` | I2C adapter: `native` or `usb` |
| `configs.i2c.device` | `''` | a bus number, or `ft232h` |
| `configs.i2c.slave` | `0x52` | extension address |
| `configs.i2c.controller` | `WiiClassicController::class` | the class to build |
| `configs.i2c.boot_now` | `true` | boot during `conjure()` |

## Upgrading from 0.8

| 0.8 | 0.10 |
|---|---|
| `scrapyard-io/framework` 0.8 components, `surface/contracts` | the 0.10 components; no Surface requirement |
| `I2C::driver(...)` and `new $wiring['controller'](...)` | `app('circuit')->conjure('wii-connector')`, `{Controller}::i2c()`, or `app('gpio.i2c')->driver(...)` |
| `$pad->every(IOPool::gpio(), $ticks)` → `Recurrence` | `$pad->every($loop, $interval_s)` → loop `Timer`; `$pad->stop($loop)` |
| extensions implemented Surface's `ButtonPad` / `GameController`; methods took Surface's `GamepadButton` / `GamepadAxis` too | Surface 0.10 has no HumanInput contracts; the chip's `WiiClassicButton` / `WiiNunchuckButton` and stick, trigger and accelerometer methods only |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against a recording fake of the I2C bus, so it needs no hardware. Every boot sequence is checked byte for byte. An SNES Classic Mini controller on a Raspberry Pi 5's I2C bus was also exercised for this release: it booted in 209 ms and all 12 buttons registered pressed and released on a 10 ms event-loop timer.

## Security

The driver reads and writes registers on hardware the PHP process can open. See [SECURITY.md](SECURITY.md) for the support policy and how to report a vulnerability.

## License

MIT. See [LICENSE](LICENSE).
