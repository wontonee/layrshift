---
name: woocommerce
description: Manage WooCommerce products, variations, orders, attributes, categories, and store settings on a LayrShift site. Use for ecommerce tasks, product catalogs, variable products, shipping classes, or Woo blocks.
enable_prompt: true
enable_agentic: true
---

# WooCommerce via LayrShift

Prefer **`layrshift/woocommerce-*` abilities** for store status and product reads. Use `execute-php` for writes (product creation, orders).

## Start here

1. `layrshift/skill-get` → `woocommerce`
2. `layrshift/woocommerce-get-status` — version, currency, counts
3. `layrshift/woocommerce-list-products` — browse catalog
4. `layrshift/woocommerce-get-product` — single product detail

## Abilities

| Step | Ability |
|------|---------|
| Store status | `layrshift/woocommerce-get-status` |
| List products | `layrshift/woocommerce-list-products` |
| Product detail | `layrshift/woocommerce-get-product` |

## Probe

```php
return array(
    'woocommerce' => defined( 'WC_VERSION' ) ? WC_VERSION : null,
    'currency'    => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : null,
);
```

## Products

```php
$product = new WC_Product_Variable();
$product->set_name( 'T-Shirt' );
$product->set_status( 'draft' );
$product->set_regular_price( '19.99' );
$id = $product->save();
return $id;
```

For variations, use `WC_Product_Variable` + `WC_Product_Variation`. Set attributes on the parent before creating variations.

## Taxonomies & attributes

- Product categories/tags: `wp_insert_term` / `wp_set_object_terms` with `product_cat` and `product_tag`.
- Global attributes: `wc_create_attribute()` then assign to products via `WC_Product_Attribute`.

## Orders & settings

- Read-only order inspection unless the user explicitly requests order changes.
- Store settings: `get_option( 'woocommerce_*' )` — document changes before `update_option`.

## Blocks & templates

- Prefer WooCommerce blocks (`woocommerce/*`) in Gutenberg via `gutenberg-edit-content` skill when building shop pages.
- Template overrides live in the theme `woocommerce/` folder — use `layrshift/read-file` / `edit-file`.

## Verification

```php
$p = wc_get_product( $product_id );
return array(
    'name'   => $p->get_name(),
    'status' => $p->get_status(),
    'price'  => $p->get_price(),
    'type'   => $p->get_type(),
);
```
