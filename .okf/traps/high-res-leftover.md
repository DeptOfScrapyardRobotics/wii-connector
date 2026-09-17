---
type: Trap
title: High-resolution leftover
description: Register 0xFE keeps its value across host sessions; a controller left at format 3 reports a different layout.
tags: [trap, data-format, report]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: nes
    resource: src/ClassicController/NESClassicController.php
    title: NESClassicController::configureExtension()
---

# Trap

Bench: after `0xFE ← 0x03`, identifier byte 4 became 0x03 and report became `81 83 83 83 00 00 FF FF` — buttons moved to bytes 6–7. Standard decoder then reads garbage.

# Fix in place

Classic-family boot writes `0xFE ← 0x01`.[^nes] Nunchuck boot writes nothing there. Don't drop the write.

[^nes]: NESClassicController::configureExtension()
