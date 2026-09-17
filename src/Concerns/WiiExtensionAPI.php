<?php

namespace DeptOfScrapyardRobotics\Actuators\WiiConnector\Concerns;

use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiConnectorOpCode;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiButtonState;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\WiiConnectorException;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver;
use GeneralPurposeIO\Contracts\Core\Recurrence;

trait WiiExtensionAPI
{
    protected function sendCommand(WiiConnectorOpCode $register, array $command_data = []): int
    {
        return $this->transport()->write($register->value, $command_data);
    }

    protected function readData(WiiConnectorOpCode $register, int $length): array
    {
        return $this->transport()->read($register->value, $length, $this->config()->get('read_delay_us'));
    }

    // --- identity and setup ---------------------------------------------------

    /** Start the extension without encryption: 0xF0 ← 0x55, then 0xFB ← 0x00. */
    public function initialize(): void
    {
        $wait_us = $this->config()->get('init_wait_ms') * 1_000;

        $this->sendCommand(WiiConnectorOpCode::INIT_UNENCRYPTED_1, [0x55]);
        usleep($wait_us);
        $this->sendCommand(WiiConnectorOpCode::INIT_UNENCRYPTED_2, [0x00]);
        usleep($wait_us);
    }

    /** @return list<int> the six identifier bytes at 0xFA */
    public function getIdentifier(): array
    {
        return $this->readData(WiiConnectorOpCode::IDENTIFIER, 6);
    }

    /** The family part of the identifier (bytes 2, 3 and 5); compare with WiiExtensionId. */
    public function getExtensionId(): int
    {
        [, , $b2, $b3, , $b5] = $this->getIdentifier();

        return ($b2 << 24) | ($b3 << 16) | $b5;
    }

    /** The data format the extension reports in right now, from 0xFE. */
    public function getDataFormat(): int
    {
        return $this->readData(WiiConnectorOpCode::DATA_FORMAT, 1)[0];
    }

    // --- settings --------------------------------------------------------------

    public function getHoldMs(): int
    {
        return $this->config()->get('hold_ms');
    }

    public function setHoldMs(int $hold_ms): void
    {
        if ($hold_ms < 0) {
            throw WiiConnectorException::invalidHoldTime($hold_ms);
        }

        $this->config()->set('hold_ms', $hold_ms);
    }

    public function getInvertY(): bool
    {
        return $this->config()->get('invert_y');
    }

    public function setInvertY(bool $invert): void
    {
        $this->config()->set('invert_y', $invert);
    }

    // --- polling --------------------------------------------------------------

    /** The six report bytes, read now. */
    public function readReport(): array
    {
        return $this->readData(WiiConnectorOpCode::REPORT, 6);
    }

    /** @return list<int> the report the last poll decoded */
    public function report(): array
    {
        return $this->report;
    }

    /** Read one report; buttons and analog values answer from it until the next poll. */
    public function poll(): static
    {
        $this->report = $this->readReport();
        $at_ns = hrtime(true);
        $bits = $this->buttonBits($this->report);

        foreach ($this->states as $state) {
            $state->update(($bits & (1 << $state->button->value)) === 0, $at_ns);
        }

        $this->decodeAnalog($this->report);

        return $this;
    }

    /** Put poll() on the gpio dock. */
    public function every(GPIOResourceDriver $gpio, int $ticks = 1, string $name = 'wii-extension'): Recurrence
    {
        return $gpio->every($name, fn (): static => $this->poll(), $ticks);
    }

    /** (raw − center) / range, clamped to −1 … 1. */
    protected function normalize(int $raw, float $center, float $range, bool $invert = false): float
    {
        $value = max(-1.0, min(1.0, ($raw - $center) / $range));

        return $invert ? -$value : $value;
    }

    // --- buttons from the last poll -------------------------------------------

    public function supports(WiiClassicButton|WiiNunchuckButton $button): bool
    {
        return in_array($button, $this->supportedButtons(), true);
    }

    public function button(WiiClassicButton|WiiNunchuckButton $button): WiiButtonState
    {
        if (! $this->supports($button)) {
            throw WiiConnectorException::unsupportedButton($button, static::class);
        }

        return $this->states[$button->value];
    }

    /** @return array<string, WiiButtonState> keyed by button name */
    public function buttons(): array
    {
        $out = [];

        foreach ($this->states as $state) {
            $out[$state->button->name] = $state;
        }

        return $out;
    }

    public function isDown(WiiClassicButton|WiiNunchuckButton $button): bool
    {
        return $this->button($button)->isDown();
    }

    public function isPressed(WiiClassicButton|WiiNunchuckButton $button): bool
    {
        return $this->button($button)->isPressed();
    }

    public function wasReleased(WiiClassicButton|WiiNunchuckButton $button): bool
    {
        return $this->button($button)->wasReleased();
    }

    public function isHolding(WiiClassicButton|WiiNunchuckButton $button): bool
    {
        return $this->button($button)->isHolding($this->config()->get('hold_ms'));
    }

    public function heldMs(WiiClassicButton|WiiNunchuckButton $button): int
    {
        return $this->button($button)->heldMs();
    }

    /** @return list<WiiClassicButton|WiiNunchuckButton> */
    public function downButtons(): array
    {
        return $this->filterButtons(fn (WiiButtonState $s): bool => $s->isDown());
    }

    /** @return list<WiiClassicButton|WiiNunchuckButton> */
    public function pressedButtons(): array
    {
        return $this->filterButtons(fn (WiiButtonState $s): bool => $s->isPressed());
    }

    /** @return list<WiiClassicButton|WiiNunchuckButton> */
    public function releasedButtons(): array
    {
        return $this->filterButtons(fn (WiiButtonState $s): bool => $s->wasReleased());
    }

    /** @return list<WiiClassicButton|WiiNunchuckButton> */
    public function holdingButtons(): array
    {
        $hold_ms = $this->config()->get('hold_ms');

        return $this->filterButtons(fn (WiiButtonState $s): bool => $s->isHolding($hold_ms));
    }

    /** Any of these down; no buttons means any supported button. */
    public function anyDown(WiiClassicButton|WiiNunchuckButton ...$buttons): bool
    {
        return array_any($buttons ?: $this->supportedButtons(), fn ($b): bool => $this->isDown($b));
    }

    /** All of these down; no buttons means every supported button. */
    public function allDown(WiiClassicButton|WiiNunchuckButton ...$buttons): bool
    {
        return array_all($buttons ?: $this->supportedButtons(), fn ($b): bool => $this->isDown($b));
    }

    public function chord(WiiClassicButton|WiiNunchuckButton ...$buttons): bool
    {
        return $this->allDown(...$buttons);
    }

    /** Any of these pressed this poll; no buttons means any supported button. */
    public function anyPressed(WiiClassicButton|WiiNunchuckButton ...$buttons): bool
    {
        return array_any($buttons ?: $this->supportedButtons(), fn ($b): bool => $this->isPressed($b));
    }

    /** @return list<WiiClassicButton|WiiNunchuckButton> */
    protected function filterButtons(callable $keep): array
    {
        return array_values(array_map(
            fn (WiiButtonState $s) => $s->button,
            array_filter($this->states, $keep),
        ));
    }
}
