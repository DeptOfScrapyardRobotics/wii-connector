<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

/** Report layout selected in register 0xFE. */
enum WiiDataFormat: int
{
    case STANDARD = 0x01;
    case HIGH_RESOLUTION = 0x03;
}
