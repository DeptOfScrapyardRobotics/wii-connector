<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use GPIO\Contracts\I2C\I2CAPI;

/**
 * Wii Classic test double — inject a 6-byte report, skip I2C / handshake.
 */
class FakeWiiClassicController extends WiiClassicController
{
    /** @var list<int> */
    public array $buffer = [0, 0, 0, 0, 0xFF, 0xFF];

    public int $read_count = 0;

    public function __construct(?I2CAPI $i2c = null)
    {
        if (is_null($i2c)) {
            $i2c = new class implements I2CAPI
            {
                public function read(int $len): array|false
                {
                    return array_fill(0, $len, 0);
                }

                public function write(string|array $data): int
                {
                    return is_array($data) ? count($data) : strlen($data);
                }

                public function bulkWrite(string|array $messages): array|false
                {
                    return [];
                }

                public function writeRead(string|array $bytes_to_write, int $bytes_to_read): array|false
                {
                    return array_fill(0, $bytes_to_read, 0);
                }
            };
        }

        parent::__construct($i2c, boot_now: false);
        $this->booted = true;
    }

    /**
     * @param  list<int>  $buffer
     */
    public function setBuffer(array $buffer): void
    {
        $this->buffer = $buffer;
    }

    protected function readData(): array
    {
        $this->read_count++;

        return $this->buffer;
    }

    protected function initialize(): void
    {
        // no-op for unit tests
    }
}
