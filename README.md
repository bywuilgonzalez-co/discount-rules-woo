# Dynamic Pricing & Discount Rules for WooCommerce

A 100% complete, high-performance, and unified dynamic pricing and discount rules plugin for WooCommerce. Developed and maintained by [Bywuilgonzalez.com](https://bywuilgonzalez.com).

## Features

- **Storewide Sales**: Apply flat or percentage discounts across all products.
- **Product-Specific Discounts**: Target specific products or categories with customized discounts.
- **Quantity-Based Bulk Tiers**: Incentivize large orders using bulk quantity price breaks (e.g. Buy 1-5, get 5%; Buy 6+, get 10%).
- **Cart Conditions engine**: Ensure discounts only trigger when cart parameters are met:
  - Cart subtotal threshold.
  - Cart line item quantities.
  - Selected user roles (including guest checks).
  - Specific user emails or wildcard domains (e.g. `*@domain.com`).
  - Shipping address check (country, state, city, zip code with wildcards).
- **Strikeout Prices**: Beautifully crossed-out original prices (`$10.00 $8.00`) on shop catalog loops and product pages.
- **Order Fees**: Cart-level subtotal discounts automatically applied as native order fees for accurate tax processing.
- **HPOS Compatibility**: Declarative support for WooCommerce High-Performance Order Storage (HPOS).
- **React Admin UI**: Seamless, modern administrator interface built native to the WordPress dashboard using `@wordpress/components`.
- **Sale Items Shortcodes**: Display discounted products with `[drw_sale_items_list]`, `[awdr_sale_items_list]`, or `[on_sale]`.
- **Advanced Target Exclusions**: Apply a rule to a category while excluding specific products or categories.
- **Scheduled Coupon Windows**: Restrict coupon rules to date/time ranges or short durations such as 7:00 AM to 10:00 AM.

## Shortcodes

Use this on an offers page to render products that have a native WooCommerce sale price or an active dynamic discount rule:

```text
[awdr_sale_items_list limit="12" columns="4"]
```

Optional category filtering:

```text
[awdr_sale_items_list category="combos" limit="12" columns="4"]
```

Discounted products render a percentage badge using this markup:

```html
<div class="sale-perc">-12 %</div>
```

## Advanced Rule Targeting

Rules can target all products, specific products, or product categories. Use the **Exclusions** section in the rule editor to exclude products or categories from that rule, even if they match the selected target category.

For coupon rules, use the schedule fields under **Cart Coupon Applied**:

- Start Date / End Date
- Start Time / End Time
- Duration in minutes, useful for flash coupons such as `07:00` to `10:00` or a 30-minute launch window.

The scheduling fields used by the admin rule builder are supported directly, including `Start Date`, `End Date`, `Start Time`, `End Time`, and `Duration (minutes)`.

## Installation

1. Copy the `discount-rules-woo` folder to your WordPress plugins directory: `/wp-content/plugins/`.
2. Go to your WordPress Dashboard > **Plugins** and activate the plugin.
3. Configure your rules under **WooCommerce > Discount Rules**.

## License

GPLv3 or later.
