<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\NESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\SNESClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\ClassicController\WiiClassicController;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Nunchuck\WiiNunchuck;
use Fabricate\NutsAndBolts\MagicAliases\Circuit;
use Fabricate\NutsAndBolts\ServiceProvider;

class WiiConnectorServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Circuit::addCircuit('wii-classic', WiiClassicController::class);
        Circuit::addCircuit('snes-classic', SNESClassicController::class);
        Circuit::addCircuit('nes-classic', NESClassicController::class);
        Circuit::addCircuit('wii-nunchuck', WiiNunchuck::class);
    }
}
