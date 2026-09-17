---
type: Guide
title: Polling a controller
description: What poll() reads, button state and queries, Wii Classic analog scaling, Nunchuck stick and acceleration, and dock polling.
tags: [poll, buttons, analog, accelerometer, io-pools]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/Concerns/WiiExtensionAPI.php
    title: WiiExtensionAPI
  - id: state
    resource: src/WiiButtonState.php
    title: WiiButtonState
  - id: classic
    resource: src/ClassicController/WiiClassicController.php
    title: WiiClassicController
  - id: nunchuck
    resource: src/Nunchuck/WiiNunchuck.php
    title: WiiNunchuck
---

# poll()

One 6-byte report read → button bits → each supported `WiiButtonState::update()` → `decodeAnalog()`.[^api] Pi 5 native: ~3.5 ms (3 ms read delay). `report()` = last bytes.

# Buttons

Queries take `WiiClassicButton|WiiNunchuckButton`; unsupported → throws.[^api] `isDown`, `isPressed`, `wasReleased`, `isHolding` (`hold_ms`), `heldMs`; lists `downButtons` / `pressedButtons` / `releasedButtons` / `holdingButtons` in report-bit order; `anyDown` / `allDown` / `chord` / `anyPressed`, no args = all supported. Classic family adds `dPad()`.

Edges true only on the poll that saw the change.[^state]

Bench SNES Mini: all 12 buttons, hold and START+SELECT chord confirmed.

# Wii Classic analog

Left stick 6-bit: `(raw − 31.5) / 31.5`. Right stick 5-bit: `(raw − 15.5) / 15.5`. Triggers `raw / 31.0`. Y flipped when `invert_y` (default) → up = −1.[^classic] `rawAnalog()` gives counts.

# Nunchuck

Stick 8-bit: `(raw − 128) / 127`, clamped, Y per `invert_y`. Acceleration 10-bit per axis; g = `(count − accel_zero) / accel_counts_per_g` (defaults 512 / 256, nominal).[^nunchuck]

# Dock

`every($gpio, $ticks = 1, $name = 'wii-extension')` → `Recurrence` running `poll()`.

[^api]: WiiExtensionAPI
[^state]: WiiButtonState
[^classic]: WiiClassicController
[^nunchuck]: WiiNunchuck
