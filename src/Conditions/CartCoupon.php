<?php

namespace Drw\App\Conditions;

if (!defined('ABSPATH')) {
    exit;
}

class CartCoupon implements ConditionInterface
{
    /**
     * Check if specific WooCommerce coupons are applied in the cart.
     *
     * @param array $data The condition configuration.
     * @param \WC_Cart|null $cart WooCommerce Cart object.
     * @param \WC_Product|null $product WooCommerce Product object.
     * @return bool
     */
    public function check(array $data, $cart = null, $product = null)
    {
        if (!$cart) {
            $cart = WC()->cart;
        }
        if (!$cart) {
            return false;
        }

        $operator = !empty($data['operator']) ? $data['operator'] : 'in_list'; // in_list, not_in_list, applied, not_applied
        if ($operator === 'applied') {
            $operator = 'in_list';
        } elseif ($operator === 'not_applied') {
            $operator = 'not_in_list';
        }
        $target_coupons = !empty($data['value']) ? (array)$data['value'] : [];

        // WooCommerce applied coupons are usually lowercase
        $applied_coupons = array_map('strtolower', (array)$cart->get_applied_coupons());
        $target_coupons = array_map('strtolower', array_map('trim', $target_coupons));

        $has_match = false;
        foreach ($target_coupons as $coupon) {
            if (in_array($coupon, $applied_coupons, true)) {
                $has_match = true;
                break;
            }
        }

        if ($has_match && !self::is_schedule_matched($data)) {
            $has_match = false;
        }

        if ($operator === 'in_list') {
            return $has_match;
        } elseif ($operator === 'not_in_list') {
            return !$has_match;
        }

        return false;
    }

    /**
     * Check optional coupon schedule fields against the current site time.
     *
     * Supports same-day windows:
     * start_date=2026-06-14 start_time=07:00 end_date=2026-06-14 end_time=10:00
     *
     * Supports duration windows:
     * start_date=2026-06-14 start_time=09:00 duration_minutes=30
     */
    public static function is_schedule_matched(array $data, $current_time = null)
    {
        $current_time = $current_time !== null ? (int)$current_time : current_time('timestamp');

        $start_date = self::first_value($data, ['start_date', 'date_from', 'coupon_start_date']);
        $end_date = self::first_value($data, ['end_date', 'date_to', 'coupon_end_date']);
        $start_time = self::normalize_time(self::first_value($data, ['start_time', 'time_from', 'coupon_start_time']));
        $end_time = self::normalize_time(self::first_value($data, ['end_time', 'time_to', 'coupon_end_time']));
        $duration_minutes = self::get_duration_minutes($data);

        $start_ts = self::build_boundary_timestamp($start_date, $start_time, false);
        $end_ts = $duration_minutes > 0 && $start_ts
            ? $start_ts + ($duration_minutes * 60)
            : self::build_boundary_timestamp($end_date, $end_time, true);

        if ($start_ts && $current_time < $start_ts) {
            return false;
        }

        if ($end_ts && $current_time > $end_ts) {
            return false;
        }

        if (!$start_ts && !$end_ts && ($start_time !== '' || $end_time !== '')) {
            return self::is_current_time_inside_daily_window($current_time, $start_time, $end_time);
        }

        return true;
    }

    /**
     * Return the first non-empty value from a list of possible keys.
     */
    private static function first_value(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }

        return '';
    }

    /**
     * Normalize HH:MM time strings.
     */
    private static function normalize_time($time)
    {
        $time = trim((string)$time);
        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '';
    }

    /**
     * Build a timestamp from date and optional time.
     */
    private static function build_boundary_timestamp($date, $time, $is_end)
    {
        $date = trim((string)$date);
        $time = self::normalize_time($time);

        if ($date === '') {
            return 0;
        }

        $time = $time !== '' ? $time : ($is_end ? '23:59' : '00:00');
        $timestamp = strtotime($date . ' ' . $time);

        return $timestamp ? (int)$timestamp : 0;
    }

    /**
     * Convert duration fields into minutes.
     */
    private static function get_duration_minutes(array $data)
    {
        if (!empty($data['duration_minutes'])) {
            return max(0, (int)$data['duration_minutes']);
        }

        if (empty($data['duration_value'])) {
            return 0;
        }

        $value = max(0, (int)$data['duration_value']);
        $unit = !empty($data['duration_unit']) ? strtolower(trim($data['duration_unit'])) : 'minutes';

        if (in_array($unit, ['hour', 'hours', 'h'], true)) {
            return $value * 60;
        }

        return $value;
    }

    /**
     * Evaluate recurring daily time windows, including overnight ranges.
     */
    private static function is_current_time_inside_daily_window($current_time, $start_time, $end_time)
    {
        $current = date('H:i', $current_time);
        $start_time = $start_time !== '' ? $start_time : '00:00';
        $end_time = $end_time !== '' ? $end_time : '23:59';

        if ($start_time <= $end_time) {
            return $current >= $start_time && $current <= $end_time;
        }

        return $current >= $start_time || $current <= $end_time;
    }
}
