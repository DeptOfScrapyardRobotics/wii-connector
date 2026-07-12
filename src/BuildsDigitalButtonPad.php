<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector;

use BareMetal\Actuation\HumanInput\BasicButton;
use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use BareMetal\Actuation\HumanInput\LatchedButton;
use BackedEnum;

/**
 * Builds a DigitalButtonPad from a one-poll I2C button snapshot.
 *
 * @phpstan-type ButtonSnapshot array<string, bool>
 */
trait BuildsDigitalButtonPad
{
    /**
     * @param  list<BackedEnum|string>  $labels
     * @param  callable(): array<string, bool>  $snapshot
     */
    protected function digitalButtonPadFromSnapshot(array $labels, callable $snapshot): DigitalButtonPad
    {
        /** @var array<string, LatchedButton> $latches */
        $latches = [];
        $buttons = [];

        foreach ($labels as $label) {
            $key = $label instanceof BackedEnum ? (string) $label->value : $label;
            $latch = new LatchedButton;
            $latches[$key] = $latch;
            $buttons[] = new BasicButton($key, $latch);
        }

        return new DigitalButtonPad(
            $buttons,
            function () use ($latches, $snapshot): void {
                $state = $snapshot();

                foreach ($latches as $key => $latch) {
                    $latch->latch((bool) ($state[$key] ?? false));
                }
            },
        );
    }
}
