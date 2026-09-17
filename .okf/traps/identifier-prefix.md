---
type: Trap
title: Identifier prefix varies
description: Identifier bytes 0–1 differ between models and byte 4 follows the data format; checking them rejects real controllers.
tags: [trap, identifier, boot]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/Concerns/WiiExtensionAPI.php
    title: WiiExtensionAPI::getExtensionId()
---

# Trap

Bench SNES Mini answers `01 00 A4 20 01 01`; Wii Classic `00 00 A4 20 01 01`. Bench SNES left at format 3 answers `01 00 A4 20 03 01`. `getExtensionId()` uses bytes 2, 3, 5 only.[^api] Don't widen the check.

NES / SNES / Wii Classic share an identifier → package can't tell which Classic-family model is plugged in; caller picks class.

[^api]: WiiExtensionAPI::getExtensionId()
