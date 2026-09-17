---
type: Reference
title: Extension protocol
description: Registers, unencrypted init, identifier, data format and the six-byte report layouts the package decodes.
tags: [protocol, registers, report, i2c]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: transport
    resource: src/Transports/WiiConnectorI2CTransport.php
    title: WiiConnectorI2CTransport
  - id: opcodes
    resource: src/Enums/WiiConnectorOpCode.php
    title: WiiConnectorOpCode
  - id: ids
    resource: src/Enums/WiiExtensionId.php
    title: WiiExtensionId
  - id: classic
    resource: src/ClassicController/WiiClassicController.php
    title: WiiClassicController
  - id: nunchuck
    resource: src/Nunchuck/WiiNunchuck.php
    title: WiiNunchuck
---

# Framing

Address 0x52. One-byte register. Write = `[reg, ...data]`. Read = write `[reg]` → wait `read_delay_us` → separate `read(n)`.[^transport]

# Registers

From `WiiConnectorOpCode`.[^opcodes]

| Reg | Use |
|---|---|
| 0x00 | report, 6 bytes |
| 0xF0 ← 0x55, 0xFB ← 0x00 | unencrypted init |
| 0xFA | identifier, 6 bytes |
| 0xFE | data format: 0x01 standard, 0x03 high-res |

Identifier: bytes 0–1 vary by model, byte 4 = current data format, bytes 2, 3, 5 = family → `WiiExtensionId` packs them as `(b2 << 24) | (b3 << 16) | b5`: Classic `0xA4200001`, Nunchuck `0xA4200000`.[^ids] Bench SNES Mini: `01 00 A4 20 01 01`, and `01 00 A4 20 03 01` while in high-res.

# Classic report (standard)

| Byte | Bits |
|---|---|
| 0 | 7:6 RX hi, 5:0 LX |
| 1 | 7:6 RX mid, 5:0 LY |
| 2 | 7 RX lo, 6:5 LT hi, 4:0 RY |
| 3 | 7:5 LT lo, 4:0 RT |
| 4 | RIGHT DOWN L SELECT HOME START R — (bit 7→0), active low |
| 5 | ZL B Y A X ZR LEFT UP, active low |

[^classic] Bench SNES idle: `A0 20 10 00 FF FF` (LX 32, LY 32, RX 16, RY 16).

High-res (0xFE = 3), seen on bench: 8 bytes, analog one byte each, buttons in 6–7. Not decoded.

# Nunchuck report

SX, SY, AX hi, AY hi, AZ hi, then byte 5: AZ lo 7:6, AY lo 5:4, AX lo 3:2, C bit 1, Z bit 0 (active low).[^nunchuck]

[^transport]: WiiConnectorI2CTransport
[^opcodes]: WiiConnectorOpCode
[^ids]: WiiExtensionId
[^classic]: WiiClassicController
[^nunchuck]: WiiNunchuck
