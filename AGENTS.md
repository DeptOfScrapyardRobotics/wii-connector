# Agent guidelines — dept-of-scrapyard-robotics/wii-connector

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s) and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Transport and dock semantics belong to `scrapyard-io/framework`; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports) → **`dept-of-scrapyard-robotics/wii-connector`** (Wii extension controllers) → Surface human input, once that component exists.

## Package rules (quick) — 0.8.x

- Composer: `dept-of-scrapyard-robotics/wii-connector` **0.8.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Actuators\WiiConnector\` → `src/` (DOSR files input devices under Actuators).
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/nuts-and-bolts`. Never `scrapyard-io/framework` or `venusian/framework`. Protocol components and adapters are `suggest`.
- **Hierarchy is fixed.** `WiiExtension` (abstract, `Bootable` + `Actuator`) ← `NESClassicController` ← `SNESClassicController` ← `WiiClassicController`; `WiiNunchuck` ← `WiiExtension` directly. Each Classic subclass only adds buttons (and, for the Wii Classic, analog). Start from NES, the fewest inputs.
- **Boot**: `0xF0 ← 0x55`, wait, `0xFB ← 0x00`, wait, identifier at `0xFA` (6 bytes) — bytes 2, 3 and 5 must match `extensionId()` — then `configureExtension()` (Classic family: `0xFE ← 0x01`, standard report). Bytes 0–1 differ by model (SNES Mini `01 00`, Wii Classic `00 00`) and byte 4 is the current data format; don't check them.
- **Buttons** are enums backed by their report bit: `WiiClassicButton` → bit in `(byte4 << 8) | byte5`; `WiiNunchuckButton` → bit in byte 5. Active low. One `WiiButtonState` per supported button; asking about an unsupported one throws.
- **Poll model.** `poll()` = one 6-byte read at `0x00` (select, wait `read_delay_us`, separate read). Buttons and analog answer from the last report.
- **Configuration is the state** for settings the controller can't report (`hold_ms`, `invert_y`, `init_wait_ms`, `read_delay_us`; Nunchuck adds `accel_zero`, `accel_counts_per_g`).
- **Reach the framework through MagicAliases** (`I2C::`, `IOPool::`), never `app('gpio.*')`.
- **Config** merges under `circuits.wii-connector`; publish tag `wii-connector-config` → `config/circuits/wii-connector.php`. The package reads none of it.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int- or string-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fakes; no hardware
```

Hardware truth is an SNES Classic Mini controller at `0x52` on the Pi 5's `i2c-1` (`fnk`). No Nunchuck, NES or Wii Classic controller is on the bench; those classes are proven on fakes only. Announce with `say` before any run that needs someone to press buttons, and start capture before the spoken cue.
