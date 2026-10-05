# Security Policy

## Supported versions

wii-connector is pre-1.0. No 0.x release receives security fixes or advisories; fixes land in the
next release line. Security support starts with 1.0.

| Version | Security fixes |
|---------|----------------|
| < 1.0   | No             |

## Reporting a vulnerability

Please don't open a public issue for a security problem.

Report it privately through GitHub: the **Report a vulnerability** button on this repository's
**Security** tab. If that isn't available, email **info@projectsaturnstudios.com**.

Include what you found, the affected version, the adapter and hardware in use, and steps to
reproduce. Reports are read and weighed for the release line in development; before 1.0 there is
no response-time commitment.

## Security model

wii-connector is plain PHP. It reads and writes Wii extension registers through the bus
transport `scrapyard-io/framework` hands it, and holds no native code of its own. What a PHP
process may touch is decided below it: the adapter (`microscrap/scrapyard-linux` over ext-posi,
`microscrap/scrapyard-usb` over ext-ftdi) and the operating system's permissions on the I2C or
USB device. Grant those through device groups or udev rules scoped to the hardware, not by
running PHP as root.

- **Configuration is trusted input.** `conjure()` connects whatever bus and address the
  `circuits.wii-connector` config names and instantiates the `controller` class it names; only
  concrete Wii extension classes are accepted. Keep that config under the app's control.
- **Bus results are checked.** A short write, a refused read or a short read throws instead of
  returning partial data.
- **Input is data.** Button and analog values come from the controller; an app that maps them to
  actions decides what each may do.

A report is in scope when this package writes a register it was not asked to, or instantiates a
class other than a concrete Wii extension controller. Weaknesses in an adapter or extension
belong to that package's own policy.
