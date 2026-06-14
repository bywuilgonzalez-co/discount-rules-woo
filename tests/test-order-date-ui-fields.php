<?php
/**
 * Focused smoke test for admin scheduling fields used by order_date conditions.
 */

define('ABSPATH', dirname(__DIR__) . '/');

global $mock_current_time;
$mock_current_time = strtotime('2026-06-14 08:30:00');

function current_time($type) {
    global $mock_current_time;
    return $mock_current_time;
}

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/src/Conditions/ConditionInterface.php';
require_once dirname(__DIR__) . '/src/Conditions/OrderDate.php';

$condition = new Drw\App\Conditions\OrderDate();

$same_day_window = [
    'type' => 'order_date',
    'operator' => 'in_range',
    'start_date' => '2026-06-14',
    'end_date' => '2026-06-14',
    'start_time' => '07:00',
    'end_time' => '10:00',
];

assert_same(true, $condition->check($same_day_window), 'OrderDate should accept admin start/end date and time fields inside the window.');

$mock_current_time = strtotime('2026-06-14 10:30:00');
assert_same(false, $condition->check($same_day_window), 'OrderDate should reject times after the admin end_time.');

$mock_current_time = strtotime('2026-06-14 09:15:00');
$duration_window = [
    'type' => 'order_date',
    'operator' => 'in_range',
    'start_date' => '2026-06-14',
    'start_time' => '09:00',
    'duration_minutes' => 30,
];

assert_same(true, $condition->check($duration_window), 'OrderDate should accept duration_minutes while inside the duration window.');

$mock_current_time = strtotime('2026-06-14 09:45:00');
assert_same(false, $condition->check($duration_window), 'OrderDate should reject after duration_minutes has elapsed.');

echo "Order date UI fields OK\n";
