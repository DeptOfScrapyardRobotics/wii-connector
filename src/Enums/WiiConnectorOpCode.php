<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums;

enum WiiConnectorOpCode: int
{
    case REPORT = 0x00;
    case INIT_UNENCRYPTED_1 = 0xF0;
    case IDENTIFIER = 0xFA;
    case INIT_UNENCRYPTED_2 = 0xFB;
    case DATA_FORMAT = 0xFE;
}
