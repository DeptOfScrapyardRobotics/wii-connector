# Agent guidelines — dept-of-scrapyard-robotics/wii-connector

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Catalog, transport, loop and adapter semantics belong to `scrapyard-io/framework`'s and Venusian's bundles; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports, the circuit catalog) → **`dept-of-scrapyard-robotics/wii-connector`** (controller drivers) → apps.

## Package rules (quick) — 0.10.x

- Composer: `dept-of-scrapyard-robotics/wii-connector` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Actuators\WiiConnector\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/contracts`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Never `scrapyard-io/framework`, `venusian/surface` or `venusian/framework`. Protocol components, io-pools and adapters are `suggest`.
- **Hierarchy**: `WiiExtension` (abstract `Bootable` + `Actuator`) → `NESClassicController` → `SNESClassicController` → `WiiClassicController`; `WiiNunchuck` beside them. Subclasses supply `extensionId()`, `supportedButtons()`, `buttonBits()`, `decodeAnalog()`, optional `configureExtension()`.
- **Boot**: unencrypted init (0xF0 ← 0x55, 0xFB ← 0x00), identifier check on bytes 2, 3, 5 only (byte 4 is the data format), Classic family pins the standard report (0xFE ← 0x01). Keep that write: a controller left in high-res mode moves its buttons.
- **The factory is the config shape.** `ConjuresWiiExtension::i2c()` parameters are exactly a `circuits.wii-connector.configs.*` entry's keys; the provider catalogs `wii-connector` against `WiiExtension`, and `i2c()` builds the concrete class `controller` names (or `static::class`). A new config key = a new factory parameter, and the reverse.
- **Polling**: `poll()` reads one six-byte report; edges last one poll. `every(Loop, interval, name)` / `stop(Loop, name)` put it on a loop timer.
- **Reach the framework through the container.** 0.10 has no protocol aliases, facades or dock. The factory resolves `gpio.i2c` from `ControlPanel::getInstance()`; apps call `app('circuit')->conjure()`.
- **Surface**: 0.10 has no HumanInput contracts; the chip's button enums and analog methods are the only vocabulary until it does.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fake bus, a real EventLoop; no hardware
```

Run it under NTS and ZTS PHP before every commit. Suites stay hardware-free: controllers are for scratch smoke scripts, never committed and never in `tests/`.

Hardware truth: an SNES Classic Mini controller at `0x52` on a Raspberry Pi 5's I2C bus 1, proven by someone pressing every button.
