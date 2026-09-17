---
type: Package
title: dept-of-scrapyard-robotics/wii-connector
description: Wii extension controller drivers for scrapyard-io/framework 0.8 — identity, requires, class hierarchy, boot, errors.
resource: composer.json
tags: [wii, nunchuck, classic-controller, snes, nes, i2c, package]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
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
---

# Identity

`dept-of-scrapyard-robotics/wii-connector` 0.8.0, namespace `DeptOfScrapyardRobotics\Actuators\WiiConnector\`.[^composer] Split requires: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-voyager/nuts-and-bolts`.

# Hierarchy

```
WiiExtension (abstract, Bootable + Actuator)
├── ClassicController\NESClassicController      UP DOWN LEFT RIGHT A B START SELECT
│   └── SNESClassicController                   + X Y L R
│       └── WiiClassicController                + HOME ZL ZR, 2 sticks, 2 triggers
└── Nunchuck\WiiNunchuck                         C Z, stick, accelerometer
```

Base supplies transport, config, boot, poll, button state, dock.[^base] Subclasses supply `extensionId()`, `supportedButtons()`, `buttonBits()`, `decodeAnalog()`, optional `configureExtension()`.

# Boot

Init unencrypted → identifier check → `configureExtension()` → button state cleared.[^bootstrap] SNES Mini on Pi 5: 206 ms (two 100 ms init waits).

# Errors

`WiiConnectorException` → `CircuitException` → `GPIOLevelException`.[^exception] Wrong extension, unsupported button, short write, refused / short read, negative `hold_ms`, unknown property or config key.

[^composer]: Package manifest
[^base]: WiiExtension
[^bootstrap]: WiiExtensionBootstrap
[^exception]: WiiConnectorException
