<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use Closure;
use GeneralPurposeIO\I2C\I2CSlave;
use RuntimeException;

class WiiConnectorCarrierTransport
{
    protected readonly Closure $sleep;

    public function __construct(
        protected readonly I2CSlave $i2c,
        ?Closure $sleep = null,
    ) {
        $this->sleep = $sleep ?? static function (int $microseconds): void {
            usleep($microseconds);
        };
    }

    public function write(int $register, int ...$data): int
    {
        return $this->i2c->write([$register, ...$data]);
    }

    /** @return list<int> */
    public function snapshot(): array
    {
        $this->i2c->write([0x00]);
        ($this->sleep)(3_000);
        $data = $this->i2c->read(6);

        if ($data === false || count($data) !== 6) {
            throw new RuntimeException('The Wii extension did not return a six-byte snapshot.');
        }

        return array_values($data);
    }

    public function close(): void
    {
        $this->i2c->close();
    }
}
