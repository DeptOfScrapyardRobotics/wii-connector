<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Concerns;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;

trait WiiExtensionBootstrap
{
    use WiiExtensionAPI;

    /**
     * @throws WiiConnectorException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'identifier' => $this->getIdentifier(),
            'extension_id' => $this->getExtensionId(),
            'data_format' => $this->getDataFormat(),
            'hold_ms' => $this->getHoldMs(),
            'invert_y' => $this->getInvertY(),
            'report' => $this->report(),
            'buttons' => $this->buttons(),
            default => throw WiiConnectorException::invalidProperty($name, static::class),
        };
    }

    /**
     * @throws WiiConnectorException
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'hold_ms' => $this->setHoldMs($value),
            'invert_y' => $this->setInvertY($value),
            default => throw WiiConnectorException::invalidProperty($name, static::class),
        };
    }

    /** Unencrypted init, identifier check, extension setup, clean button state. */
    protected function _boot(): void
    {
        $this->initialize();
        $this->confirmExtension();
        $this->configureExtension();

        foreach ($this->states as $state) {
            $state->reset();
        }
    }

    protected function confirmExtension(): void
    {
        $id = $this->getExtensionId();

        if ($id !== $this->extensionId()->value) {
            throw WiiConnectorException::unexpectedExtension($this->extensionId(), $id);
        }
    }
}
