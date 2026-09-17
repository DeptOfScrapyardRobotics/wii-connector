<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

/** A Nunchuck button, backed by its bit in report byte 5. Pressed reads 0. */
enum WiiNunchuckButton: int
{
    case Z = 0;
    case C = 1;
}
