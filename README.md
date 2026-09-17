# wii-connector

Read Wii extension controllers from PHP over I2C: the Nunchuck, the Wii Classic Controller, and the NES and SNES Classic Mini controllers.

`dept-of-scrapyard-robotics/wii-connector` starts the controller without encryption, checks what's plugged in, and turns each poll into button presses, releases, holds and analog values. Any breakout that exposes the Wii extension port's I2C pins works.

| Controller | Class | Buttons | Analog |
|---|---|---|---|
| NES Classic Mini | `ClassicController\NESClassicController` | D-pad, A, B, Start, Select | |
| SNES Classic Mini | `ClassicController\SNESClassicController` | NES buttons, plus X, Y, L, R | |
| Wii Classic Controller | `ClassicController\WiiClassicController` | SNES buttons, plus Home, ZL, ZR | two sticks, two triggers |
| Nunchuck | `Nunchuck\WiiNunchuck` | C, Z | stick, three-axis accelerometer |

Each Classic class extends the one above it, so an SNES controller is also an NES controller.

## Requirements

- PHP 8.4 or newer
- A Venusian application with `scrapyard-io/framework` 0.8
- An I2C adapter:
  - `microscrap/scrapyard-linux` for native `i2c-dev` (needs `ext-posi`)
  - `microscrap/scrapyard-usb` for FTDI MPSSE boards such as the FT232H (needs `ext-ftdi`)

## Installation

```bash
composer require dept-of-scrapyard-robotics/wii-connector
```

The service provider is discovered automatically. It merges the package's wiring config under `circuits.wii-connector`. To publish that config into your app, run:

```bash
php computer vendor:publish --tag=wii-connector-config
```

That writes `config/circuits/wii-connector.php`.

## Quick start

An SNES Classic Mini controller on a Raspberry Pi's `i2c-1`:

```php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;
use GeneralPurposeIO\I2C\I2C;

$slave = I2C::driver('native')
    ->connectTo(1)
    ->register()
    ->device(1, WiiConnectorI2CAddress::DEFAULT->value);

$snes = new SNESClassicController(new WiiConnectorI2CTransport($slave), boot_now: true);

while (true) {
    $snes->poll();

    if ($snes->isPressed(WiiClassicButton::A)) {
        echo "A!\n";
    }

    usleep(10_000);
}
```

Booting takes about 200 ms. It writes the two unencrypted-init registers, reads the identifier, and throws if the wrong kind of controller is plugged in. Classic-family controllers are then set to the standard six-byte report, in case something left them in high-resolution mode.

## Connecting

Every controller takes a `WiiConnectorI2CTransport` wrapping an I2C connection at `0x52` (`WiiConnectorI2CAddress::DEFAULT`):

```php
// Linux i2c-dev
$slave = I2C::driver('native')->connectTo(1)->register()->device(1, 0x52);

// FTDI MPSSE
$slave = I2C::driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x52);

$transport = new WiiConnectorI2CTransport($slave);
```

A read selects the register, waits `read_delay_us` (3 ms by default), then reads in a separate transaction.

The NES and SNES Classic Mini controllers report the same identifier family as the Wii Classic Controller, so any Classic class boots with any of them. Pick the class for the buttons you want.

### From the published config

The config file holds your wiring and the class to build. The package merges it but does not open connections from it:

```php
$name = config('circuits.wii-connector.default_config');      // 'i2c'
$wiring = config("circuits.wii-connector.configs.{$name}");

$slave = I2C::driver($wiring['driver'])
    ->connectTo($wiring['device'])
    ->register()
    ->device($wiring['device'], $wiring['slave']);

$controller = new $wiring['controller'](new WiiConnectorI2CTransport($slave), boot_now: true);
```

## Buttons

Everything answers from the last `poll()`. One poll reads one six-byte report, in about 3.5 ms on a Raspberry Pi 5.

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

A press or release shows for exactly one poll. A tap that starts and ends between two polls is not seen, so poll often.

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

With the defaults, up reads -1.0, the way screen coordinates run. Set `invert_y` to `false` to make up read +1.0.

## Nunchuck

```php
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;

$nunchuck = new WiiNunchuck(new WiiConnectorI2CTransport($slave), boot_now: true);
$nunchuck->poll();

$nunchuck->isDown(WiiNunchuckButton::Z);
$nunchuck->stick();              // ['x' => …, 'y' => …]
$nunchuck->rawStick();           // 0–255 each
$nunchuck->rawAcceleration();    // ['x' => 0–1023, 'y' => …, 'z' => …]
$nunchuck->acceleration();       // in g
$nunchuck->x();                  // one axis, in g
```

Acceleration in g comes from `accel_zero` and `accel_counts_per_g` in `WiiNunchuckConfiguration`, which default to 512 and 256. Calibrate them for your Nunchuck if you need accurate values.

## Polling on the dock

```php
use Voyager\IOPools\MagicAliases\IOPool;

$recurrence = $snes->every(IOPool::gpio());    // poll() on every gpio tick
// …
$recurrence->stop();
```

`every($gpio, $ticks, $name)` puts `poll()` on the gpio dock. Pass `$name` when several controllers share a dock.

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

Failures throw `DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException`, which extends the framework's `GPIOLevelException`:

- The plugged-in controller is a different kind than the class expects.
- Your code asks about a button the controller doesn't have.
- The bus writes fewer bytes than asked, refuses a read, or returns fewer bytes than asked.
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
| `default_config` | `'i2c'` | which entry under `configs` to use |
| `configs.i2c.driver` | `'none'` | I2C adapter: `native` or `usb` |
| `configs.i2c.device` | `''` | a bus number, or `ft232h` |
| `configs.i2c.slave` | `0x52` | extension address |
| `configs.i2c.controller` | `WiiClassicController::class` | the class to build |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against a recording fake of the I2C bus, so it needs no hardware. Each controller's boot bytes and every button's report bit are checked.

## License

MIT. See [LICENSE](LICENSE).
