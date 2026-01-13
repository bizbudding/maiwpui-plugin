# MaiWPUI WordPress Plugin - Agent Context

This WordPress plugin provides REST API endpoints for mobile apps using the maiwpui React Native library.

## CRITICAL: App-Agnostic Rule

**This plugin MUST remain app-agnostic.** It is a shared library used by multiple apps (QwikCoach, etc.).

**NEVER add:**
- App-specific business logic (e.g., `is_peopletak`, `qc_*` anything)
- Hardcoded IDs specific to one app
- App-specific user flags or checks

**ALWAYS use filters** to allow apps to extend functionality in their own mu-plugins files:
- `maiwpui_user_profile_data` - extend profile response
- `maiwpui_user_membership_data` - add custom membership flags
- `maiwpui_allowed_user_meta_keys` - allow app-specific meta keys

**Example - WRONG (in maiwpui-plugin):**
```php
$is_peopletak = in_array(7176, $plan_ids); // NO! App-specific
```

**Example - CORRECT (in app's mu-plugins):**
```php
add_filter('maiwpui_user_membership_data', function($data, $user_id, $plan_ids) {
    $data['is_peopletak'] = in_array(7176, $plan_ids);
    return $data;
}, 10, 3);
```

## REST API Namespace

All endpoints are under: `/wp-json/maiwpui/v1/`

## Authentication

Uses custom Bearer token authentication (not JWT). Tokens are stored in user meta with selector/validator pattern.

**Important:** The `determine_current_user` filter in `Auth::init()` sets up WordPress user context from Bearer tokens. This makes `get_current_user_id()` work correctly for all REST requests.

## Key Endpoints

### POST /login
Returns token + user data.

### POST /register
Creates user, sets meta/terms, returns token.

### GET /user/profile
Returns user profile with optional meta fields.

**Query Parameters:**
- `meta_keys` - Array of meta keys to include. Accepts both formats:
  - `?meta_keys[]=key1&meta_keys[]=key2` (array notation)
  - `?meta_keys=key1,key2` (comma-separated)

### POST /user/meta (custom endpoint)
Update user meta via custom endpoint.

**Body:** `{ "meta": { "key": "value" } }`

**Note:** Only keys in `maiwpui_allowed_user_meta_keys` filter are accepted.

### WordPress Core Alternative
User meta can also be updated via WordPress core:
`POST /wp/v2/users/{id}` with `{ "meta": { "key": "value" } }`

Requires `register_meta()` with `show_in_rest => true` for each meta key.

## Filters

### maiwpui_allowed_user_meta_keys
Whitelist of meta keys that can be read/written via the API.

```php
add_filter('maiwpui_allowed_user_meta_keys', function($keys) {
    return array_merge($keys, ['qc_persona', 'custom_field']);
});
```

### maiwpui_allowed_user_taxonomies
Whitelist of taxonomies for user term assignment. Default: `['user-group']`

### maiwpui_user_profile_data
Extend the user profile response with app-specific data.

```php
add_filter('maiwpui_user_profile_data', function($data, $user_id) {
    $data['access'] = [
        'status' => get_user_meta($user_id, 'qc_access_status', true),
        'can_access' => my_app_check_access($user_id),
    ];
    return $data;
}, 10, 2);
```

### maiwpui_user_membership_data
Add custom flags to membership data based on plan IDs.

```php
add_filter('maiwpui_user_membership_data', function($data, $user_id, $plan_ids) {
    $data['is_premium'] = in_array(123, $plan_ids);
    return $data;
}, 10, 3);
```

## Common Issues

### "Not allowed to edit this user" (401)
The `determine_current_user` filter must be registered to set up user context from Bearer token. Check that `Auth::init()` is called in plugin initialization.

### Meta not returned in profile
1. Check `meta_keys` parameter is being sent correctly
2. Verify `sanitize_string_array` handles both string and array formats
3. Ensure meta key is in `maiwpui_allowed_user_meta_keys` filter

### Token format
`{user_id}.{selector}.{validator}` - The user_id prefix allows direct DB lookup without scanning all users.

## File Structure

- `class-auth.php` - Token generation, verification, `determine_current_user` filter
- `class-rest-api.php` - All REST endpoint handlers
- `class-plugin.php` - Activation, meta registration
- `class-membership-manager.php` - WooCommerce Memberships integration
