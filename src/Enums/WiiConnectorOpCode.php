<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

enum WiiConnectorOpCode: int
{
    case DATA = 0x00;

    case HANDSHAKE_1 = 0xF0;
    case HANDSHAKE_2 = 0xFB;
}
