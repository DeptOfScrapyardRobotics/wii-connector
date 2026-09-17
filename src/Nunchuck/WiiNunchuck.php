<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiExtensionId;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiExtension;

/** Wii Nunchuck: C and Z, an 8-bit analog stick and a three-axis 10-bit accelerometer. */
class WiiNunchuck extends WiiExtension
{
    /** @var array{x: float, y: float} */
    protected array $stick = ['x' => 0.0, 'y' => 0.0];

    /** @var array{x: int, y: int, z: int} */
    protected array $raw_acceleration = ['x' => 512, 'y' => 512, 'z' => 512];

    public function __construct(
        WiiConnectorI2CTransport $transport,
        WiiNunchuckConfiguration $config = new WiiNunchuckConfiguration,
        bool $boot_now = false,
    ) {
        parent::__construct($transport, $config, $boot_now);
    }

    public function extensionId(): WiiExtensionId
    {
        return WiiExtensionId::NUNCHUCK;
    }

    public function supportedButtons(): array
    {
        return WiiNunchuckButton::cases();
    }

    /**
     * @throws \DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'stick' => $this->stick(),
            'raw_acceleration' => $this->rawAcceleration(),
            'acceleration' => $this->acceleration(),
            'x' => $this->x(),
            'y' => $this->y(),
            'z' => $this->z(),
            default => parent::__get($name),
        };
    }

    /** @return array{x: float, y: float} −1 … 1 */
    public function stick(): array
    {
        return $this->stick;
    }

    /** @return array{x: int, y: int} 0 … 255 from the last report */
    public function rawStick(): array
    {
        return ['x' => $this->report[0], 'y' => $this->report[1]];
    }

    /** @return array{x: int, y: int, z: int} 10-bit counts from the last report */
    public function rawAcceleration(): array
    {
        return $this->raw_acceleration;
    }

    /** @return array{x: float, y: float, z: float} in g, from the configured zero and scale */
    public function acceleration(): array
    {
        $zero = $this->config()->get('accel_zero');
        $per_g = $this->config()->get('accel_counts_per_g');

        return array_map(fn (int $count): float => (float) (($count - $zero) / $per_g), $this->raw_acceleration);
    }

    public function x(): float
    {
        return $this->acceleration()['x'];
    }

    public function y(): float
    {
        return $this->acceleration()['y'];
    }

    public function z(): float
    {
        return $this->acceleration()['z'];
    }

    protected function buttonBits(array $report): int
    {
        return $report[5];
    }

    /** Byte 5 carries each axis's low two bits: X in 3:2, Y in 5:4, Z in 7:6. */
    protected function decodeAnalog(array $report): void
    {
        $this->stick = [
            'x' => $this->normalize($report[0], 128, 127),
            'y' => $this->normalize($report[1], 128, 127, $this->config()->get('invert_y')),
        ];

        $this->raw_acceleration = [
            'x' => ($report[2] << 2) | (($report[5] >> 2) & 0x03),
            'y' => ($report[3] << 2) | (($report[5] >> 4) & 0x03),
            'z' => ($report[4] << 2) | (($report[5] >> 6) & 0x03),
        ];
    }
}
