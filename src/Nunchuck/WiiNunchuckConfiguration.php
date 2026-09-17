<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorConfiguration;

/** Shared settings plus the accelerometer's nominal calibration. */
class WiiNunchuckConfiguration extends WiiConnectorConfiguration
{
    /**
     * @param  int  $accel_zero  10-bit count at 0 g
     * @param  int  $accel_counts_per_g  counts per 1 g
     */
    public function __construct(
        int $hold_ms = 500,
        bool $invert_y = true,
        int $init_wait_ms = 100,
        int $read_delay_us = 3_000,
        protected int $accel_zero = 512,
        protected int $accel_counts_per_g = 256,
    ) {
        parent::__construct($hold_ms, $invert_y, $init_wait_ms, $read_delay_us);
    }
}
