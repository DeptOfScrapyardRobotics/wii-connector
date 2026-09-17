<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\ReadWriter;

/**
 * One-byte registers. A read selects the register, waits for the extension to
 * prepare the bytes, then reads in a separate transaction.
 */
class WiiConnectorI2CTransport implements ReadWriter
{
    public function __construct(
        protected I2CTransport $transport,
    ) {}

    public function read(int $register, int $length, int $delay_us = 3_000): array
    {
        $this->write($register, []);

        if ($delay_us > 0) {
            usleep($delay_us);
        }

        $bytes = $this->transport->read($length);

        if ($bytes === false) {
            throw WiiConnectorException::readFailed($register, $length);
        }

        if (count($bytes) < $length) {
            throw WiiConnectorException::shortRead($register, $length, count($bytes));
        }

        return array_values($bytes);
    }

    public function write(int $register, array $data): int
    {
        $payload = [$register & 0xFF, ...$data];
        $written = $this->transport->write($payload);

        if ($written !== count($payload)) {
            throw WiiConnectorException::writeFailed($register, count($payload), $written);
        }

        return $written;
    }

    /** Nothing to release: the slave transport is a view on a connection the I2C driver owns. */
    public function close(): void
    {
        //
    }
}
