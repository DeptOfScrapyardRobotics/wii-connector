<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

/**
 * Identifier bytes 2, 3 and 5 as (b2 << 24) | (b3 << 16) | b5. Bytes 0–1 vary
 * between models of one family, and byte 4 carries the current data format.
 */
enum WiiExtensionId: int
{
    case NUNCHUCK = 0xA4200000;
    case CLASSIC_CONTROLLER = 0xA4200001;
}
