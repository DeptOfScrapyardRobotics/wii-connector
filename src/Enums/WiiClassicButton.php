<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

/**
 * A Classic-family button, backed by its bit in report bytes 4–5 read as
 * (byte4 << 8) | byte5. Pressed reads 0.
 */
enum WiiClassicButton: int
{
    case UP = 0;
    case LEFT = 1;
    case ZR = 2;
    case X = 3;
    case A = 4;
    case Y = 5;
    case B = 6;
    case ZL = 7;
    case R = 9;
    case START = 10;
    case HOME = 11;
    case SELECT = 12;
    case L = 13;
    case DOWN = 14;
    case RIGHT = 15;
}
