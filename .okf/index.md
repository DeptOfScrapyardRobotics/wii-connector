---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/wii-connector

Wii extension controller drivers for `scrapyard-io/framework` 0.10: NES → SNES → Wii Classic hierarchy, Nunchuck. Conjured from config, unencrypted init, 6-byte reports, poll-based buttons + analog, polling on the event loop.

Read this index first, open only concepts task needs. Every concept `status: draft` until human verifies.

# Concepts

* [overview.md](/overview.md) - package identity, requires, class hierarchy, boot, errors
* [protocol.md](/protocol.md) - registers, init, identifier, data format, report layout
* [polling.md](/polling.md) - poll(), button state, Classic analog, Nunchuck stick + accelerometer, event-loop timer
* [settings.md](/settings.md) - configuration, properties, config file, publish tag


# Log

* [log.md](/log.md)
