<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

/**
 * Approximate Nunchuck accelerometer calibration.
 * 10-bit reports rest near ZERO_G; scale is device-dependent.
 */
enum WiiNunchuckAcceleration: int
{
    case ZERO_G = 512;
    case COUNTS_PER_G = 256;
}
