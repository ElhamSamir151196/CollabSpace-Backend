<?php

use App\Enums\Priority;
use App\Enums\TaskStatus;

it('stores pending but shows it as To-do', function () {
    expect(TaskStatus::Pending->value)->toBe('pending')
        ->and(TaskStatus::Pending->label())->toBe('To-do')
        ->and(TaskStatus::Pending->toArray())->toBe(['value' => 'pending', 'label' => 'To-do']);
});

it('exposes values for validation and options for dropdowns', function () {
    expect(Priority::values())->toBe(['low', 'medium', 'high', 'critical'])
        ->and(Priority::options()[3])->toBe(['value' => 'critical', 'label' => 'Critical']);
});
