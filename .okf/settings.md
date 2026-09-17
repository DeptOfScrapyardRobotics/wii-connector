---
type: Reference
title: Settings and config
description: WiiConnectorConfiguration and WiiNunchuckConfiguration, magic properties, the circuits.wii-connector config file and its publish tag.
tags: [settings, configuration, provider, publish]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: configuration
    resource: src/WiiConnectorConfiguration.php
    title: WiiConnectorConfiguration
  - id: nunchuck-configuration
    resource: src/Nunchuck/WiiNunchuckConfiguration.php
    title: WiiNunchuckConfiguration
  - id: provider
    resource: src/Providers/WiiConnectorServiceProvider.php
    title: WiiConnectorServiceProvider
---

# Configuration

| Key | Default | Class |
|---|---|---|
| `hold_ms` | 500 | all |
| `invert_y` | true | all |
| `init_wait_ms` | 100 | all |
| `read_delay_us` | 3000 | all |
| `accel_zero` | 512 | `WiiNunchuckConfiguration` |
| `accel_counts_per_g` | 256 | `WiiNunchuckConfiguration` |

[^configuration] [^nunchuck-configuration] `get()` / `set()`; unknown key throws. `WiiNunchuck` constructor requires the Nunchuck configuration.

# Properties

All: `identifier`, `extension_id` (bus), `report`, `buttons`, `hold_ms` / `invert_y` (writable). Classic family: `d_pad`. Wii Classic: `left_stick`, `right_stick`, `left_trigger`, `right_trigger`, `axes`. Nunchuck: `stick`, `raw_acceleration`, `acceleration`, `x`, `y`, `z`. Subclass `__get` falls back to parent.

# Config file

Merged under `circuits.wii-connector`, published to `config/circuits/wii-connector.php`, tag `wii-connector-config`.[^provider] Keys: `default_config`; `configs.i2c.driver` / `device` / `slave` (0x52) / `controller` (class, default `WiiClassicController`). Package reads none of it.

[^configuration]: WiiConnectorConfiguration
[^nunchuck-configuration]: WiiNunchuckConfiguration
[^provider]: WiiConnectorServiceProvider
