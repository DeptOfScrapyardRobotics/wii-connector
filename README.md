# Wii Connector
### Drive Wii / NES / SNES Classic controllers and Nunchucks over I²C with PHP

PHP package for Nintendo Wii extension controllers that speak the classic I²C protocol (default slave **0x52**). It sits on the ScrapyardIO GPIO I²C stack, boots with the plaintext handshake used by CircuitPython / Adafruit drivers, and plugs digital buttons into BareMetal HumanInput (`BasicButton`, `DigitalButtonPad`). The Nunchuck also implements `AccelerationMeasurable` so it can wrap in BareMetal `Accelerometer`.

**Which device do I need?**

* **Wii Classic** (`WiiClassicController`) — dual sticks, analog shoulders, full face / Z / HOME set
* **SNES Classic** (`SNESClassicController`) — no sticks; digital pad is A/B/X/Y, L/R, START/SELECT, d-pad (no HOME / ZL / ZR)
* **NES Classic** (`NESClassicController`) — thin subclass of Classic (same I²C report; use Classic/SNES APIs as appropriate for your pad)
* **Nunchuck** (`WiiNunchuck`) — stick, C/Z buttons, 3-axis accel

Compatible I²C Interfaces
===============

These peripherals are 3.3 V I²C devices (often on a Wiimote-style breakout or adapter).

You can interface with them the following ways:

* A Linux single-board computer's I²C bus using the POSIX carrier (`posix`) — e.g. `/dev/i2c-1` on Raspberry Pi
* An MPSSE-enabled USB-to-serial device such as an FT232H using the USB carrier (`usb`)

Default address is `WiiConnectorI2CAddress::DEFAULT` (**0x52**).

Dependencies
=============

This package makes use of modules within:

* [The ScrapyardIO Framework](https://github.com/ScrapyardIO/framework)

For I²C you also need a carrier, for example:

* [POSI Extension](https://github.com/php-io-extensions/posi) + [Microscrap POSIX](https://github.com/microscrap/posix) / POSIX drivers, **or**
* [FTDI / MPSSE](https://github.com/microscrap/mpsse) path for USB

Confirm the bus sees the device (Pi example):

```bash
i2cdetect -y 1
# expect 0x52 when the controller is connected and powered
```

Installing from Composer
====================

```bash
composer require dept-of-scrapyard-robotics/wii-connector
```

Basic Usage
============

Build an `I2CAPI` with the GPIO facade, pass it into the controller, and call `boot()` (or `boot_now: true`) so the driver runs the init handshake.

### Wii Classic Controller

```php
<?php

use GPIO\Common\GPIO;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;

$i2c = GPIO::i2c('posix')
    ->device(1)                                          // /dev/i2c-1
    ->slave(WiiConnectorI2CAddress::DEFAULT->value)      // 0x52
    ->create();

$classic = new WiiClassicController($i2c, boot_now: true);

// Magic properties (each triggers an I²C read unless you use pollInput)
$classic->buttons;       // ['A' => bool, 'B' => bool, …]
$classic->d_pad;         // ['UP' => bool, …]
$classic->joystick_l;    // ['x' => int, 'y' => int]
$classic->joystick_r;
$classic->l_shoulder;    // analog int
$classic->r_shoulder;
$classic->values;        // full decoded snapshot

// One I²C poll → decoded + raw 6-byte buffer
$frame = $classic->pollInput();
// $frame['values'], $frame['raw']

$i2c->close();
```

### SNES Classic → DigitalButtonPad

```php
<?php

use GPIO\Common\GPIO;
use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;

$i2c = GPIO::i2c('posix')
    ->device(1)
    ->slave(WiiConnectorI2CAddress::DEFAULT->value)
    ->create();

$snes = new SNESClassicController($i2c, boot_now: true);

/** @var DigitalButtonPad $pad */
$pad = $snes->asDigitalButtonPad();

// One I²C read per pad->poll() — latches A/B/X/Y/L/R/START/SELECT/d-pad
$pad->poll();

if ($pad->isPressed('A')) {
    // rising edge this frame
}

if ($pad->isHolding('START')) {
    // held past BasicButton hold threshold (default 500 ms)
}

if ($pad->chord('L', 'R')) {
    // both shoulders down
}

$i2c->close();
```

SNES pad labels: `A`, `B`, `X`, `Y`, `START`, `SELECT`, `L`, `R`, `UP`, `DOWN`, `LEFT`, `RIGHT`.

### Full Classic → DigitalButtonPad

`WiiClassicController::asDigitalButtonPad()` uses the full `WiiClassicDigitalButton` set (includes `HOME`, `ZL`, `ZR` in addition to the SNES set). Same poll / latch pattern.

### Nunchuck (buttons + Accelerometer)

```php
<?php

use GPIO\Common\GPIO;
use BareMetal\Sensors\Accelerometry\Accelerometer;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;

$i2c = GPIO::i2c('posix')
    ->device(1)
    ->slave(WiiConnectorI2CAddress::DEFAULT->value)
    ->create();

$nunchuck = new WiiNunchuck($i2c, boot_now: true);

$nunchuck->joystick;      // ['x' => int, 'y' => int] 0–255
$nunchuck->buttons;       // ['C' => bool, 'Z' => bool]
$nunchuck->acceleration;  // raw 10-bit [x, y, z] ≈ 0–1023

// AccelerationMeasurable (approx g; ZERO_G=512, COUNTS_PER_G=256)
$nunchuck->x();
$nunchuck->y();
$nunchuck->z();

$accel = new Accelerometer($nunchuck);
$accel->getPitch();
$accel->getRoll();
$accel->getAcceleration(); // magnitude

$pad = $nunchuck->asDigitalButtonPad(); // C + Z only
$pad->poll();

$i2c->close();
```

Alternative Usage
============

### Poll loop with edges / holds

```php
<?php

$pad = $snes->asDigitalButtonPad();

while (true) {
    $pad->poll();

    foreach ($pad->pressedLabels() as $label) {
        echo "PRESSED  {$label}\n";
    }

    foreach ($pad->holdingLabels() as $label) {
        echo "HOLDING  {$label}\n";
    }

    foreach ($pad->labels() as $label) {
        if ($pad->wasReleased($label)) {
            echo "RELEASED {$label}\n";
        }
    }

    usleep(20_000);
}
```

Notes
=====

* **Boot / handshake** — `boot()` (via `boot_now` or explicit call) sends the plaintext init sequence (`0xF0`/`0xFB`) with settle delays; required before reliable reads.
* **Active-low buttons** — decoded as pressed when the report bit is clear (same convention as Adafruit / CircuitPython).
* **One poll per pad frame** — `asDigitalButtonPad()` shares a snapshot latch so `pad->poll()` does a single I²C read, then updates every `BasicButton`.
* **Analog vs digital** — sticks and analog shoulders stay on the Classic getter / `pollInput()` API; only digital bits feed `DigitalButtonPad`.
* **R shoulder mask** — Classic `getRShoulder()` uses the full 5-bit mask `0x1F` (not Adafruit’s older `0x1C`).
* **Nunchuck g-scale** — `x()`/`y()`/`z()` are approximate; calibration varies by unit. Raw samples remain on `getAcceleration()` / `$nunchuck->acceleration`.
* Always `close()` the I²C handle when finished.
