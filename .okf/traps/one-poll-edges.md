---
type: Trap
title: Edges last one poll
description: isPressed / wasReleased are true only on the poll that saw the change, and a tap between two polls is never seen.
tags: [trap, buttons, poll]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: state
    resource: src/WiiButtonState.php
    title: WiiButtonState::update()
---

# Trap

Check edges right after each `poll()`; next poll clears them.[^state] Poll every ~10 ms; report has no latch.

[^state]: WiiButtonState::update()
