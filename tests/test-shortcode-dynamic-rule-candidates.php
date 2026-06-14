<?php
/**
 * Focused smoke test to ensure shortcode finds products targeted by dynamic rules.
 */

namespace Drw\App\Controllers {
    class RulesEngine {
        public static function instance() {
            return new self();
        }

        public function get_active_rules() {
            return [
                [
                    'apply_to' => 'specific_products',
                    'filters' => [
                        'product_ids' => [150],
                        'exclude_product_ids' => [],
                        'exclude_category_ids' => [],
                    ],
                    'adjustments' => [
                        'type' => 'percentage',
                        'value' => 12,
                    ],
                ],
            ];
        }

        public function calculate_catalog_discount($product, $regular_price) {
            return $product->get_id() === 150 ? 88.0 : null;
        }
    }
}

namespace {
    define('ABSPATH', dirname(__DIR__) . '/');
    define('DRW_PLUGIN_URL', 'https://example.test/wp-content/plugins/discount-rules-woo/');
    define('DRW_VERSION', '1.2.0');

    global $mock_products, $last_get_posts_args;
    $mock_products = [];
    $last_get_posts_args = [];

    class Drw_Test_Product {
        private $id;

        public function __construct($id) {
            $this->id = (int)$id;
        }

        public function get_id() {
            return $this->id;
        }

        public function get_name() {
            return 'Dynamic Product ' . $this->id;
        }

        public function get_regular_price() {
            return 100;
        }

        public function get_sale_price() {
            return '';
        }

        public function is_type($type) {
            return false;
        }
    }

    function shortcode_atts($pairs, $atts, $shortcode = '') {
        return array_merge($pairs, $atts);
    }

    function wp_enqueue_style($handle, $src, $deps = [], $ver = false) {}

    function get_posts($args) {
        global $last_get_posts_args;
        $last_get_posts_args[] = $args;
        if (!empty($args['post__in'])) {
            return array_values($args['post__in']);
        }
        return range(1, 80);
    }

    function wc_get_product($id) {
        global $mock_products;
        if (!isset($mock_products[$id])) {
            $mock_products[$id] = new Drw_Test_Product($id);
        }
        return $mock_products[$id];
    }

    function get_permalink($id) {
        return 'https://example.test/product/' . $id;
    }

    function get_the_post_thumbnail($id, $size = 'woocommerce_thumbnail', $attr = []) {
        return '<img src="https://example.test/product-' . (int)$id . '.jpg" alt="">';
    }

    function wc_price($price) {
        return '$' . number_format((float)$price, 2);
    }

    function esc_attr($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    function esc_html($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    function esc_html__($value, $domain = null) {
        return esc_html($value);
    }

    function esc_url($value) {
        return (string)$value;
    }

    function sanitize_text_field($value) {
        return trim(strip_tags((string)$value));
    }

    function absint($value) {
        return max(0, abs((int)$value));
    }

    function assert_true($condition, $message) {
        if (!$condition) {
            fwrite(STDERR, "FAIL: {$message}\n");
            exit(1);
        }
    }

    require_once dirname(__DIR__) . '/src/Controllers/ShortcodeController.php';

    $controller = Drw\App\Controllers\ShortcodeController::instance();
    $html = $controller->render_sale_items_list(['limit' => 4, 'scan_limit' => 20]);

    assert_true(strpos($html, 'Dynamic Product 150') !== false, 'Shortcode should include products explicitly targeted by active dynamic rules.');
    assert_true(strpos($html, '<div class="sale-perc">-12 %</div>') !== false, 'Shortcode should render the dynamic rule percentage badge.');

    echo "Shortcode dynamic rule candidates OK\n";
}
