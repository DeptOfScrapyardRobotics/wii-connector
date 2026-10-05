# dept-of-scrapyard-robotics/wii-connector Update Log

## 2026-10-04
* **Update**: 0.10 port. [overview](/overview.md): 0.10 split requires, `conjure('wii-connector')` through `WiiExtension::i2c()` building the configured controller, catalog slug; Surface HumanInput gone in 0.10, chip enums only.
* **Update**: [polling](/polling.md): `every(Loop)` timer and `stop()` replace the dock; live reference from the Pi. [settings](/settings.md): catalog, `boot_now`, whole-entry config replacement.
* **Removal**: the four 0.8 warning notes (high-res leftover, identifier prefix, one-poll edges, SNES shoulders on triggers), folded into [overview](/overview.md) and [polling](/polling.md).

## 2026-09-17
* **Update**: [overview](/overview.md) — every extension implements Surface `Circuits\ButtonPad`; `WiiClassicController`/`WiiNunchuck` also `Circuits\GameController`. Chip API untouched, widened params only. New require: `surface/contracts`.

## 2026-09-16
* **Update**: [protocol](/protocol.md) identifier byte 4 = data format; family check uses bytes 2, 3, 5 (bench SNES stuck at format 3 answered `A4 20 03 01`).
* **Creation**: bundle seeded for 0.8.0 — [overview](/overview.md), [protocol](/protocol.md), [polling](/polling.md), [settings](/settings.md), four [traps](/traps/index.md).
