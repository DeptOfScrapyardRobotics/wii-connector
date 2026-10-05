---
type: Package
title: dept-of-scrapyard-robotics/wii-connector
description: Wii extension controller drivers for scrapyard-io/framework 0.10 — identity, requires, conjure, class hierarchy, boot, errors.
resource: composer.json
tags: [wii, nunchuck, classic-controller, snes, nes, i2c, package]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T00:30:00Z }
sources:
  - id: composer
    resource: composer.json
    title: Package manifest
  - id: base
    resource: src/WiiExtension.php
    title: WiiExtension
  - id: bootstrap
    resource: src/Concerns/WiiExtensionBootstrap.php
    title: WiiExtensionBootstrap
  - id: exception
    resource: src/WiiConnectorException.php
    title: WiiConnectorException
  - id: factory
    resource: src/Concerns/ConjuresWiiExtension.php
    title: ConjuresWiiExtension
---

# Identity

`dept-of-scrapyard-robotics/wii-connector` 0.10.0, alias `dev-main` → `0.10.x-dev`, namespace `DeptOfScrapyardRobotics\Actuators\WiiConnector\`.[^composer] Split requires: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/contracts`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`.

# Surface

0.8 implemented Surface HumanInput's `ButtonPad` / `GameController` and took Surface's button and axis vocabulary beside the chip's. Surface 0.10 has no HumanInput contracts, so 0.10 takes the chip enums and shapes only.

# Conjure

Catalog slug `wii-connector` → `WiiExtension`. `conjure('wii-connector')` → `WiiExtension::i2c(driver, device, slave = 0x52, controller, boot_now = true)` builds the class `controller` names (must be a concrete `WiiExtension`, else `notAController`). Called on a concrete class (`SNESClassicController::i2c(...)`), `controller` defaults to that class.[^factory] Bus from `gpio.i2c`, shared if the app connected it. NES / SNES / Wii Classic share one identifier, so the config's class picks the model.

# Hierarchy

```
WiiExtension (abstract, Bootable + Actuator)
├── ClassicController\NESClassicController      UP DOWN LEFT RIGHT A B START SELECT
│   └── SNESClassicController                   + X Y L R
│       └── WiiClassicController                + HOME ZL ZR, 2 sticks, 2 triggers
└── Nunchuck\WiiNunchuck                         C Z, stick, accelerometer
```

Base supplies transport, config, `i2c()`, boot, poll, button state, loop timer.[^base] Subclasses supply `extensionId()`, `supportedButtons()`, `buttonBits()`, `decodeAnalog()`, optional `configureExtension()`.

# Boot

Init unencrypted → identifier check (bytes 2, 3, 5 only: byte 4 is the data format) → `configureExtension()` → button state cleared.[^bootstrap] Classic family writes `0xFE ← 0x01`: a controller left in high-res mode (format 3) moves its buttons to bytes 6–7, and the standard decoder needs format 1. SNES Mini on Pi 5, 0.10: 209 ms (two 100 ms init waits).

# Errors

`WiiConnectorException` → `CircuitException` → `GPIOLevelException`.[^exception] Wrong extension, unsupported button, short write, refused / short read, no bus from the driver (`notConnected`), `controller` not a concrete extension (`notAController`), negative `hold_ms`, unknown property or config key.

[^composer]: Package manifest
[^base]: WiiExtension
[^bootstrap]: WiiExtensionBootstrap
[^exception]: WiiConnectorException
[^factory]: ConjuresWiiExtension
