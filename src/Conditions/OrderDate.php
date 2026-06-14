<?php

namespace Drw\App\Conditions;

if (!defined('ABSPATH')) {
    exit;
}

class OrderDate implements ConditionInterface
{
    /**
     * Check if the current date, time, and weekday fall within the configured schedule ranges.
     *
     * @param array $data The condition configuration.
     * @param \WC_Cart|null $cart WooCommerce Cart object.
     * @param \WC_Product|null $product WooCommerce Product object.
     * @return bool
     */
    public function check(array $data, $cart = null, $product = null)
    {
        $operator = !empty($data['operator']) ? $data['operator'] : 'in_list'; // in_list (within range), not_in_list (outside range)
        
        $current_time = current_time('timestamp');

        $in_range = true;

        // 1. Date/Time Range Check
        $date_from = self::first_value($data, ['date_from', 'start_date']);
        $date_to = self::first_value($data, ['date_to', 'end_date']);
        $time_from = self::normalize_time(self::first_value($data, ['time_from', 'order_time_from', 'start_time']));
        $time_to = self::normalize_time(self::first_value($data, ['time_to', 'order_time_to', 'end_time']));
        $duration_minutes = !empty($data['duration_minutes']) ? max(0, (int)$data['duration_minutes']) : 0;

        $from_ts = self::build_boundary_timestamp($date_from, $time_from, false);
        $to_ts = $duration_minutes > 0 && $from_ts
            ? $from_ts + ($duration_minutes * 60)
            : self::build_boundary_timestamp($date_to, $time_to, true);

        if ($from_ts) {
            if ($current_time < $from_ts) {
                $in_range = false;
            }
        }
        if ($in_range && $to_ts) {
            if ($current_time > $to_ts) {
                $in_range = false;
            }
        }

        // 2. Recurring daily Time Range Check when no date boundary is set.
        if ($in_range && !$from_ts && !$to_ts && ($time_from !== '' || $time_to !== '')) {
            $in_range = self::is_current_time_inside_daily_window($current_time, $time_from, $time_to);
        }

        // 3. Allowed Weekdays Check
        if ($in_range) {
            $weekdays = !empty($data['weekdays']) ? (array)$data['weekdays'] : (!empty($data['allowed_weekdays']) ? (array)$data['allowed_weekdays'] : []);
            
            if (!empty($weekdays)) {
                $current_w_numeric = (int)date('w', $current_time); // 0 (Sun) - 6 (Sat)
                $current_N_numeric = (int)date('N', $current_time); // 1 (Mon) - 7 (Sun)
                $current_name = strtolower(date('l', $current_time)); // e.g. 'monday'
                
                $weekday_matched = false;
                foreach ($weekdays as $day) {
                    $day_clean = strtolower(trim($day));
                    if (is_numeric($day_clean)) {
                        $day_val = (int)$day_clean;
                        if ($day_val === $current_w_numeric || $day_val === $current_N_numeric) {
                            $weekday_matched = true;
                            break;
                        }
                    } else {
                        // Match full name or abbreviation (e.g. 'mon' matches 'monday')
                        if ($day_clean === $current_name || strpos($current_name, $day_clean) === 0) {
                            $weekday_matched = true;
                            break;
                        }
                    }
                }
                if (!$weekday_matched) {
                    $in_range = false;
                }
            }
        }

        // 4. Operator Evaluation
        if ($operator === 'in_list' || $operator === 'in_range') {
            return $in_range;
        } elseif ($operator === 'not_in_list' || $operator === 'not_in_range') {
            return !$in_range;
        }

        return $in_range;
    }

    /**
     * Return the first non-empty value from possible field names.
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
     * Normalize HH:MM strings.
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
        if (is_numeric($date)) {
            return (int)$date;
        }

        $date = trim((string)$date);
        if ($date === '') {
            return 0;
        }

        $time = self::normalize_time($time);
        $time = $time !== '' ? $time : ($is_end ? '23:59' : '00:00');
        $timestamp = strtotime($date . ' ' . $time);

        return $timestamp ? (int)$timestamp : 0;
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
