---
type: Trap
title: SNES shoulders drive trigger bits
description: On the SNES Classic Mini, pressing L also sets the left trigger analog field to full in the report.
tags: [trap, snes, triggers, report]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: classic
    resource: src/ClassicController/WiiClassicController.php
    title: WiiClassicController::rawAnalog()
---

# Trap

Bench, L held: `A0 20 70 E0 DF FF` → L bit clear and left-trigger field = 31.[^classic] `SNESClassicController` exposes no analog, so no effect there; driving an SNES Mini through `WiiClassicController` shows trigger 1.0 whenever L is down.

[^classic]: WiiClassicController::rawAnalog()
