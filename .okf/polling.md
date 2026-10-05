---
type: Guide
title: Polling a controller
description: What poll() reads, button state and queries, Wii Classic analog scaling, Nunchuck stick and acceleration, and polling on the event loop.
tags: [poll, buttons, analog, accelerometer, io-pools]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T00:30:00Z }
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

Edges true only on the poll that saw the change, cleared by the next: check them after each `poll()`. The report has no latch, so poll every ~10 ms.[^state]

Bench SNES Mini, 0.10 on a 10 ms loop timer: all 12 buttons pressed and released.

# Wii Classic analog

Left stick 6-bit: `(raw − 31.5) / 31.5`. Right stick 5-bit: `(raw − 15.5) / 15.5`. Triggers `raw / 31.0`. Y flipped when `invert_y` (default) → up = −1.[^classic] `rawAnalog()` gives counts. An SNES Mini read through `WiiClassicController` reports the left trigger at 31 while L is held; `SNESClassicController` exposes no analog.

# Nunchuck

Stick 8-bit: `(raw − 128) / 127`, clamped, Y per `invert_y`. Acceleration 10-bit per axis; g = `(count − accel_zero) / accel_counts_per_g` (defaults 512 / 256, nominal).[^nunchuck]

# Event loop

`every(Loop $loop, float $interval_s = 0.01, string $name = 'wii-extension')` → loop `Timer` running `poll()`; `stop($loop, $name)` forgets it. A name per controller lets several poll side by side.

[^api]: WiiExtensionAPI
[^state]: WiiButtonState
[^classic]: WiiClassicController
[^nunchuck]: WiiNunchuck
