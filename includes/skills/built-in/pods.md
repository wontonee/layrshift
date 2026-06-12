---
name: pods
description: Manage Pods custom post types, fields, taxonomies, and pod items on a LayrShift site. Use for Pods CMS structure, pod fields, or Advanced Content Types.
enable_prompt: true
enable_agentic: true
---

# Pods via LayrShift

Use Pods API functions (`pods()`, `pods_api()`) — never raw SQL for pod data.

## Probe

```php
return array(
    'pods' => defined( 'PODS_VERSION' ) ? PODS_VERSION : null,
);
```

## List pods

```php
$api = pods_api();
$pods = $api->load_pods( array( 'fields' => false ) );
return array_map( fn( $p ) => array( 'name' => $p['name'], 'type' => $p['type'] ), $pods );
```

## Read / write items

```php
$pod = pods( 'product', $item_id );
$pod->field( 'price' );
$pod->save( 'price', '29.99' );
```

## Register structure

- Prefer Pods UI on staging for exploration; export PHP via Pods Migrate or register programmatically in sandbox for repeatable agent work.
- Flush rewrite rules after CPT pod changes.

## Verification

Load pod item, list fields, confirm save round-trip on a draft item.
