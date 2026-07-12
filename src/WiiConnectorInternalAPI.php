<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use BareMetal\Contracts\Circuits\BootScaffolding;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorOpCode;
use ScrapyardIO\NutsAndBolts\Concerns\Splices16Bits;

trait WiiConnectorInternalAPI
{
    use BootScaffolding, Splices16Bits;

    protected function initialize(): void
    {
        // Let a freshly powered extension settle, then disable encryption with the
        // plaintext handshake. Mirrors the CircuitPython driver's 100 ms init delays.
        usleep(100000);
        $this->write(WiiConnectorOpCode::HANDSHAKE_1, [0x55]);
        usleep(100000);
        $this->write(WiiConnectorOpCode::HANDSHAKE_2, [0x00]);
        usleep(100000);
    }

    /**
     * @throws WiiConnectorException
     */
    protected function readData(): array
    {
        $data = $this->read(WiiConnectorOpCode::DATA, 6);

        if (count($data) !== 6) {
            throw WiiConnectorException::readFailed();
        }

        return $data;
    }

    protected function write(WiiConnectorOpCode $register_hex, array $command_data = []): int
    {
        $payload = [$this->getLowByte($register_hex->value), ...$command_data];

        $results = $this->i2c->write($payload);
        usleep(2000);

        return $results;
    }

    /**
     * @return list<int>
     */
    protected function read(WiiConnectorOpCode $register_hex, int $length): array
    {
        $this->i2c->write([$this->getLowByte($register_hex->value)]);
        usleep(3000);

        return $this->i2c->read($length);
    }
}
