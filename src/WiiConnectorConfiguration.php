<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

/** Settings every Wii extension shares. Nothing here is read back from the controller. */
class WiiConnectorConfiguration
{
    /**
     * @param  int  $hold_ms  how long a button stays down before it counts as holding
     * @param  bool  $invert_y  on, up reads -1.0 on every stick
     * @param  int  $init_wait_ms  wait after each init write
     * @param  int  $read_delay_us  wait between selecting a register and reading it
     */
    public function __construct(
        protected int $hold_ms = 500,
        protected bool $invert_y = true,
        protected int $init_wait_ms = 100,
        protected int $read_delay_us = 3_000,
    ) {}

    public function get(string $var): mixed
    {
        if (isset($this->$var)) {
            return $this->$var;
        }

        throw WiiConnectorException::invalidProperty($var, static::class);
    }

    public function set(string $var, mixed $value): void
    {
        if (isset($this->$var)) {
            $this->$var = $value;

            return;
        }

        throw WiiConnectorException::invalidProperty($var, static::class);
    }
}
