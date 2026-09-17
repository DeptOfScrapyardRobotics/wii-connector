<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Providers;

use Voyager\NutsAndBolts\ServiceProvider;

/**
 * The extension wiring config lives under the circuits tree: config('circuits.wii-connector'),
 * published to config/circuits/wii-connector.php, which the config loader keys the same way.
 */
class WiiConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/wii-connector.php', 'circuits.wii-connector');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/wii-connector.php' => $this->app->configPath('circuits/wii-connector.php'),
        ], 'wii-connector-config');
    }
}
