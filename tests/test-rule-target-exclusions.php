<?php
/**
 * Focused smoke test for product/category exclusion filters.
 */

define('ABSPATH', dirname(__DIR__) . '/');

global $mock_product_categories;
$mock_product_categories = [];

class WC_Product {}

class Drw_Target_Test_Product extends WC_Product {
    private $id;
    private $parent_id;

    public function __construct($id, $parent_id = 0) {
        $this->id = (int)$id;
        $this->parent_id = (int)$parent_id;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_parent_id() {
        return $this->parent_id;
    }
}

function wc_get_product_term_ids($product_id, $taxonomy) {
    global $mock_product_categories;
    return isset($mock_product_categories[$product_id]) ? $mock_product_categories[$product_id] : [];
}

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/src/Controllers/RulesEngine.php';

$engine = Drw\App\Controllers\RulesEngine::instance();

$mock_product_categories[42] = [5];
$category_rule = [
    'apply_to' => 'specific_categories',
    'filters' => [
        'category_ids' => [5],
        'exclude_product_ids' => [42],
        'exclude_category_ids' => [],
    ],
];
assert_same(false, $engine->is_product_targeted_by_rule($category_rule, new Drw_Target_Test_Product(42)), 'Excluded product should not match an included category rule.');

$mock_product_categories[44] = [5, 9];
$category_exclusion_rule = [
    'apply_to' => 'specific_categories',
    'filters' => [
        'category_ids' => [5],
        'exclude_product_ids' => [],
        'exclude_category_ids' => [9],
    ],
];
assert_same(false, $engine->is_product_targeted_by_rule($category_exclusion_rule, new Drw_Target_Test_Product(44)), 'Excluded category should override an included category.');

$specific_rule = [
    'apply_to' => 'specific_products',
    'filters' => [
        'product_ids' => [50],
        'exclude_product_ids' => [50],
        'exclude_category_ids' => [],
    ],
];
assert_same(false, $engine->is_product_targeted_by_rule($specific_rule, new Drw_Target_Test_Product(50)), 'Product exclusion should override a specific product include.');

$all_rule = [
    'apply_to' => 'all_products',
    'filters' => [
        'exclude_product_ids' => [60],
        'exclude_category_ids' => [],
    ],
];
assert_same(false, $engine->is_product_targeted_by_rule($all_rule, new Drw_Target_Test_Product(60)), 'Product exclusion should work for all-products rules.');

$mock_product_categories[70] = [5];
assert_same(true, $engine->is_product_targeted_by_rule($category_rule, new Drw_Target_Test_Product(70)), 'Included category product should still match when it is not excluded.');

echo "Rule target exclusions OK\n";
