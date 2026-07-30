<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorI2CAddress;
use Fabricate\Actuation\HumanInput\BasicButton;
use Fabricate\Actuation\HumanInput\ButtonPad as FrameworkButtonPad;
use Fabricate\Contracts\Actuation\HumanInput\ButtonInput;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;
use Fabricate\Contracts\NutsAndBolts\BootScaffolding;
use Fabricate\Contracts\NutsAndBolts\BootSequence;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CSlave;

abstract class WiiGamepad extends FrameworkButtonPad implements ButtonPad, BootSequence
{
    use BootScaffolding;

    /** @var array<string, SnapshotButtonInput> */
    protected array $button_inputs = [];

    /** @var list<int> */
    protected array $last_snapshot = [0, 0, 0, 0, 0xFF, 0xFF];

    public function __construct(
        protected readonly WiiConnectorCarrierTransport $transport,
        bool $boot_now = false,
    ) {
        $layout = [];

        foreach ($this->buttonLabels() as $label) {
            $input = new SnapshotButtonInput;
            $this->button_inputs[$label] = $input;
            $layout[] = new BasicButton($label, $input);
        }

        parent::__construct($layout);

        if ($boot_now) {
            $this->boot();
        }
    }

    /** @return list<string> */
    abstract protected function buttonLabels(): array;

    /** @param list<int> $snapshot
     *  @return array<string, bool>
     */
    abstract protected function decodeButtons(array $snapshot): array;

    /** @param list<int> $snapshot */
    abstract protected function updateMeasurements(array $snapshot): void;

    public static function i2c(
        string|int $device,
        ?string $adapter = null,
        int|WiiConnectorI2CAddress $slave = WiiConnectorI2CAddress::DEFAULT,
        bool $boot_now = true,
    ): static {
        $address = $slave instanceof WiiConnectorI2CAddress ? $slave->value : $slave;
        $i2c = I2C::adapter($adapter)->device($device)->bus()->slave($address);

        return static::fromI2CBus($i2c, $boot_now);
    }

    public static function fromI2CBus(I2CSlave $i2c, bool $boot_now = true): static
    {
        return new static(new WiiConnectorCarrierTransport($i2c), $boot_now);
    }

    public function poll(): static
    {
        if (! $this->hasBooted()) {
            $this->boot();
        }

        $this->last_snapshot = $this->transport->snapshot();
        $state = $this->decodeButtons($this->last_snapshot);
        $this->updateMeasurements($this->last_snapshot);

        foreach ($this->button_inputs as $label => $input) {
            $input->latch($state[$label] ?? false);
        }

        foreach ($this->buttons as $button) {
            $button->poll();
        }

        return $this;
    }

    public function connected(): bool
    {
        return $this->hasBooted();
    }

    /** @return list<int> */
    public function rawSnapshot(): array
    {
        return $this->last_snapshot;
    }

    public function close(): void
    {
        parent::close();
        $this->transport->close();
    }

    protected function _boot(): void
    {
        usleep(100000);
        $this->transport->write(0xF0, 0x55);
        usleep(100000);
        $this->transport->write(0xFB, 0x00);
        usleep(100000);
    }

    protected function normalize(int $value, float $center, float $range, bool $invert = false): float
    {
        $normalized = ($value - $center) / $range;

        return max(-1.0, min(1.0, $invert ? -$normalized : $normalized));
    }
}

class SnapshotButtonInput implements ButtonInput
{
    protected bool $down = false;

    public function latch(bool $down): void
    {
        $this->down = $down;
    }

    public function isDown(): bool
    {
        return $this->down;
    }

    public function close(): void {}
}
