<?php

use BareMetal\Actuation\HumanInput\DigitalButtonPad;
use BareMetal\Contracts\Sensors\Accelerometry\AccelerationMeasurable;
use BareMetal\Sensors\Accelerometry\Accelerometer;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiClassicDigitalButton;
use DeptOfScrapyardRobotics\Actuators\WiiConnector\Enums\WiiNunchuckDigitalButton;
use DeptOfScrapyardRobotics\Tests\Fixtures\FakeWiiClassicController;
use DeptOfScrapyardRobotics\Tests\Fixtures\FakeWiiNunchuck;

test('Wii Classic decode reports active-low face buttons and d-pad', function () {
    $classic = new FakeWiiClassicController;

    // Idle: button bytes all high → nothing pressed.
    expect($classic->getDigitalButtons())->toMatchArray([
        'A' => false,
        'B' => false,
        'UP' => false,
        'DOWN' => false,
    ]);

    // Clear A (0x10) and UP (0x01) in byte 5.
    $classic->setBuffer([0, 0, 0, 0, 0xFF, 0xFF & ~0x10 & ~0x01]);
    $buttons = $classic->getDigitalButtons();

    expect($buttons['A'])->toBeTrue()
        ->and($buttons['UP'])->toBeTrue()
        ->and($buttons['B'])->toBeFalse()
        ->and($buttons['DOWN'])->toBeFalse();
});

test('Wii Classic asDigitalButtonPad does one I2C read per pad poll', function () {
    $classic = new FakeWiiClassicController;
    $pad = $classic->asDigitalButtonPad();

    expect($pad)->toBeInstanceOf(DigitalButtonPad::class)
        ->and($pad->labels())->toHaveCount(count(WiiClassicDigitalButton::cases()));

    $classic->setBuffer([0, 0, 0, 0, 0xFF & ~0x04, 0xFF]); // START pressed (byte4 bit 0x04)
    $before = $classic->read_count;
    $pad->poll();

    expect($classic->read_count)->toBe($before + 1)
        ->and($pad->isDown('START'))->toBeTrue()
        ->and($pad->isPressed('START'))->toBeTrue()
        ->and($pad->isDown('A'))->toBeFalse();
});

test('Wii Classic right shoulder uses the full 5-bit mask', function () {
    $classic = new FakeWiiClassicController;
    $classic->setBuffer([0, 0, 0, 0x1F, 0xFF, 0xFF]);

    expect($classic->getRShoulder())->toBe(0x1F);
});

test('Wii Nunchuck asDigitalButtonPad exposes C and Z', function () {
    $nunchuck = new FakeWiiNunchuck;
    $pad = $nunchuck->asDigitalButtonPad();

    expect($pad->labels())->toBe([
        WiiNunchuckDigitalButton::C->value,
        WiiNunchuckDigitalButton::Z->value,
    ]);

    // Clear C (0x02) and Z (0x01) in byte 5.
    $nunchuck->setBuffer([128, 128, 0, 0, 0, 0xFF & ~0x02 & ~0x01]);
    $before = $nunchuck->read_count;
    $pad->poll();

    expect($nunchuck->read_count)->toBe($before + 1)
        ->and($pad->chord('C', 'Z'))->toBeTrue()
        ->and($pad->isPressed('C'))->toBeTrue()
        ->and($pad->isPressed('Z'))->toBeTrue();
});

test('Wii Nunchuck acceleration decode packs low bits from byte 5', function () {
    $nunchuck = new FakeWiiNunchuck;
    $nunchuck->setBuffer([0, 0, 0x10, 0x20, 0x30, 0b11001100]);

    // x low = bits 7-6 of byte5, y low = bits 5-4, z low = bits 3-2
    expect($nunchuck->getAcceleration())->toBe([
        (0x10 << 2) | 0b11,
        (0x20 << 2) | 0b00,
        (0x30 << 2) | 0b11,
    ]);
});

test('Wii Nunchuck implements AccelerationMeasurable for Accelerometer wrapping', function () {
    $nunchuck = new FakeWiiNunchuck;

    // Encode raw x=512, y=512, z=768 (≈ +1g on Z with ZERO_G=512, COUNTS_PER_G=256)
    // x: 512 = 0x80 << 2 | 0b00 → buf[2]=0x80, buf[5] bits76=00
    // y: 512 = 0x80 << 2 | 0b00 → buf[3]=0x80, buf[5] bits54=00
    // z: 768 = 0xC0 << 2 | 0b00 → buf[4]=0xC0, buf[5] bits32=00
    $nunchuck->setBuffer([0, 0, 0x80, 0x80, 0xC0, 0x00]);

    expect($nunchuck)->toBeInstanceOf(AccelerationMeasurable::class)
        ->and($nunchuck->x())->toBe(0.0)
        ->and($nunchuck->y())->toBe(0.0)
        ->and($nunchuck->z())->toBe(1.0);

    $accel = new Accelerometer($nunchuck);

    expect($accel->getX())->toBe(0.0)
        ->and($accel->getY())->toBe(0.0)
        ->and($accel->getZ())->toBe(1.0)
        ->and($accel->getAcceleration())->toBe(1.0);
});
