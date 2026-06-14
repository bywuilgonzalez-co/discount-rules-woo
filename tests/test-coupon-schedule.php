<?php
/**
 * Focused smoke test for scheduled coupon conditions.
 */

define('ABSPATH', dirname(__DIR__) . '/');

global $mock_current_time;
$mock_current_time = strtotime('2026-06-14 08:30:00');

class Drw_Test_Cart {
    private $coupons;

    public function __construct($coupons) {
        $this->coupons = $coupons;
    }

    public function get_applied_coupons() {
        return $this->coupons;
    }
}

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
require_once dirname(__DIR__) . '/src/Conditions/CartCoupon.php';

$condition = new Drw\App\Conditions\CartCoupon();
$cart = new Drw_Test_Cart(['flash-am']);

$scheduled_coupon = [
    'type' => 'cart_coupon',
    'operator' => 'in_list',
    'value' => ['flash-am'],
    'start_date' => '2026-06-14',
    'end_date' => '2026-06-14',
    'start_time' => '07:00',
    'end_time' => '10:00',
];

assert_same(true, $condition->check($scheduled_coupon, $cart), 'Coupon should be valid inside same-day 7am-10am window.');

$mock_current_time = strtotime('2026-06-14 10:30:00');
assert_same(false, $condition->check($scheduled_coupon, $cart), 'Coupon should be invalid after the configured end time.');

$mock_current_time = strtotime('2026-06-14 09:15:00');
$duration_coupon = [
    'type' => 'cart_coupon',
    'operator' => 'in_list',
    'value' => ['flash-am'],
    'start_date' => '2026-06-14',
    'start_time' => '09:00',
    'duration_minutes' => 30,
];

assert_same(true, $condition->check($duration_coupon, $cart), 'Coupon should be valid inside a duration window.');

$mock_current_time = strtotime('2026-06-14 09:45:00');
assert_same(false, $condition->check($duration_coupon, $cart), 'Coupon should expire after duration_minutes.');

echo "Coupon schedule OK\n";
