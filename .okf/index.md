---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/wii-connector — knowledge bundle

Wii extension controller drivers for `scrapyard-io/framework` 0.8: NES → SNES → Wii Classic hierarchy, Nunchuck. Unencrypted init, 6-byte reports, poll-based buttons + analog, dock polling.

Read this index first, open only concepts task needs. Every concept `status: draft` until human verifies.

# Concepts

* [overview.md](/overview.md) - package identity, requires, class hierarchy, boot, errors
* [protocol.md](/protocol.md) - registers, init, identifier, data format, report layout
* [polling.md](/polling.md) - poll(), button state, Classic analog, Nunchuck stick + accelerometer, dock
* [settings.md](/settings.md) - configuration, properties, config file, publish tag

# Traps

* [traps/](/traps/index.md) - identifier prefix, high-res leftovers, SNES shoulders drive triggers, edges last one poll

# Log

* [log.md](/log.md)
