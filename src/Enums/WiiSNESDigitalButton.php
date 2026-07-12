<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

/**
 * Digital buttons present on a Nintendo SNES Classic Controller
 * (no HOME / ZL / ZR; shoulders are L + R only).
 */
enum WiiSNESDigitalButton: string
{
    case A = 'A';
    case B = 'B';
    case X = 'X';
    case Y = 'Y';
    case START = 'START';
    case SELECT = 'SELECT';
    case L = 'L';
    case R = 'R';
    case UP = 'UP';
    case DOWN = 'DOWN';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
}
