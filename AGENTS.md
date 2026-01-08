# MaiWPUI WordPress Plugin - Agent Context

This WordPress plugin provides REST API endpoints for mobile apps using the maiwpui React Native library.

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
