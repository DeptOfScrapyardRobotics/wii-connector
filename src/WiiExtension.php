<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Concerns\WiiExtensionBootstrap;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiExtensionId;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Transports\WiiConnectorI2CTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Actuator;
use GeneralPurposeIO\IntegratedCircuits\Bootable;

/** Anything plugged into a Wii Remote's extension port, at 0x52. */
abstract class WiiExtension extends Bootable implements Actuator
{
    use WiiExtensionBootstrap;

    /** @var array<int, WiiButtonState> keyed by button value */
    protected array $states = [];

    /** @var list<int> the last six-byte report */
    protected array $report = [0, 0, 0, 0, 0xFF, 0xFF];

    public function __construct(
        protected readonly WiiConnectorI2CTransport $transport,
        protected readonly WiiConnectorConfiguration $config = new WiiConnectorConfiguration,
        bool $boot_now = false,
    ) {
        foreach ($this->supportedButtons() as $button) {
            $this->states[$button->value] = new WiiButtonState($button);
        }

        parent::__construct($boot_now);
    }

    /** The identifier family this class drives. */
    abstract public function extensionId(): WiiExtensionId;

    /** @return list<WiiClassicButton|WiiNunchuckButton> in report bit order */
    abstract public function supportedButtons(): array;

    /** The report's button bits, active low, indexed the way the button enum is backed. */
    abstract protected function buttonBits(array $report): int;

    /** Update analog values from a new report. */
    abstract protected function decodeAnalog(array $report): void;

    /** Extension-specific setup after the identifier check. */
    protected function configureExtension(): void {}

    public function transport(): WiiConnectorI2CTransport
    {
        return $this->transport;
    }

    public function config(): WiiConnectorConfiguration
    {
        return $this->config;
    }

    public function connected(): bool
    {
        return $this->hasBooted();
    }

    /** Forget button state. The bus connection stays with its driver. */
    public function close(): void
    {
        foreach ($this->states as $state) {
            $state->reset();
        }

        $this->transport->close();
    }
}
