# Frontend API Reference

Last verified against code on `2026-03-27`.

This document is written for the Next.js/frontend team working with the Laravel backend in this repository.

## 1. Base Setup

- Base URL: `/api/v1`
- Auth: `Authorization: Bearer <access_token>`
- Auth driver: Sanctum personal access tokens
- Refresh endpoint: `POST /api/v1/public/auth/refresh`
- Common content types:
  - `application/json` for most endpoints
  - `multipart/form-data` for file uploads like `profile_picture`, property images, and blog thumbnail

## 2. Important Response Conventions

This API is not fully uniform yet. Frontend code should handle these patterns:

### Most new/admin endpoints

```json
{
  "success": true,
  "message": "Optional message",
  "data": {}
}
```

### Some legacy endpoints

```json
{
  "status": true,
  "message": "Optional message",
  "data": {}
}
```

### Validation errors

Usually:

```json
{
  "success": false,
  "errors": {
    "field": ["message"]
  }
}
```

Or, in older auth/register responses:

```json
{
  "success": false,
  "error": {
    "status": "error",
    "validationErrors": {
      "field": ["message"]
    }
  }
}
```

### Menu endpoint

`GET /api/v1/user/menu` now follows the standard wrapped format:

```json
{
  "success": true,
  "data": [],
  "message": "Menus retrieved successfully."
}
```

## 3. Recommended Frontend Boot Flow

1. Call `POST /api/v1/public/auth/login`
2. Store:
   - `data.access_token`
   - `data.refresh_token`
   - `data.access_token_expires_at`
   - `data.refresh_token_expires_at`
   - `data.user`
   - `data.menus`
3. Render sidebar from `data.menus`
4. Optionally call `GET /api/v1/user` for full current-user profile
5. Use `POST /api/v1/public/auth/refresh` when access token expires
6. Load bell dropdown from `GET /api/v1/notifications`

## 4. Auth and Session APIs

### Public auth

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| POST | `/public/auth/register` | Public self-registration | No | `name`, `email`, `phone`, `password` | Returns token, name, roles |
| POST | `/public/auth/admin/register` | Create admin through registration flow | Yes | Same as public register | Requires existing auth token |
| POST | `/public/auth/login` | Login and get menu with token pair | No | `email`, `password`, optional `device_name` | Returns access token, refresh token, user, menus |
| POST | `/public/auth/refresh` | Rotate token pair for current device/session | No | `refresh_token`, optional `device_name` | Returns new access token, refresh token, user, menus, and stable error codes on failure |
| POST | `/public/auth/forgot-password` | Send password reset email | No | `email` | Sends reset link if account exists and is active |
| GET | `/public/auth/reset-password/validate` | Validate reset token before showing form | No | Query: `email`, `token` | Returns `token_valid=true` when usable |
| POST | `/public/auth/reset-password` | Save a new password from email reset flow | No | `email`, `token`, `password`, `password_confirmation` | Resets password and revokes old tokens |

### Authenticated session

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| POST | `/logout` | Logout current session or all sessions | Yes | Optional `all_devices=true` | Deletes current token pair by default |

### Login response shape

`POST /api/v1/public/auth/login`

```json
{
  "success": true,
  "data": {
    "token": "same-as-access-token",
    "access_token": "token",
    "refresh_token": "refresh-token",
    "expires_in": 3600,
    "access_token_expires_at": "2026-03-27T12:00:00Z",
    "refresh_token_expires_at": "2026-04-03T12:00:00Z",
    "token_type": "Bearer",
    "device_name": "admin-web",
    "user": {
      "id": 1,
      "firstName": "Niraj",
      "lastName": "Sharma",
      "userName": "niraj",
      "email": "niraj@example.com",
      "roles": ["Admin"]
    },
    "menus": []
  },
  "message": "User logged in successfully."
}
```

### Frontend notes

- Login already returns menu data, so you do not need an extra menu request on initial login.
- Refresh is device/session scoped. Refreshing one device should not log out other devices.
- Inactive users and unverified users are blocked during login and refresh.

### Refresh error codes

`POST /api/v1/public/auth/refresh` now returns machine-readable codes in `error.code`:

- `refresh_token_invalid`
- `refresh_token_expired`
- `account_inactive`
- `email_unverified`
- `user_not_found`
- `refresh_failed`

### Password reset flow

1. Frontend submits `POST /public/auth/forgot-password` with user email.
2. Backend sends a reset email using a frontend URL.
3. The email link opens:
   - `FRONTEND_RESET_PASSWORD_URL?token=...&email=...`
   - or, if not set: `FRONTEND_URL/reset-password?token=...&email=...`
4. Frontend calls `GET /public/auth/reset-password/validate`.
5. If valid, frontend shows the new-password form.
6. Frontend submits `POST /public/auth/reset-password`.

### Password reset error codes

- `email_not_found`
- `account_inactive`
- `reset_link_throttled`
- `reset_link_send_failed`
- `reset_token_invalid`
- `password_reset_failed`

## 5. Current User APIs

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/user` | Get current user profile | Yes | None | Returns user + detail + roles |
| PUT/PATCH | `/user` | Update current user profile | Yes | JSON body | Updates user and `user_details` fields |
| POST | `/user/profile-picture` | Upload current user profile picture | Yes | `multipart/form-data` with `profile_picture` | Max 2 MB, jpg/jpeg/png/webp |
| DELETE | `/user/profile-picture` | Remove current user profile picture | Yes | None | Clears stored picture |
| GET | `/user/menu` | Load sidebar menu for current user | Yes | None | Returns wrapped menu payload in `data` |

### `GET /user` response shape

```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_code": "Opsh-2026-1",
    "first_name": "Niraj",
    "middle_name": null,
    "last_name": "Sharma",
    "name": "Niraj Sharma",
    "username": "niraj",
    "email": "niraj@example.com",
    "phone": "9800000000",
    "status": 1,
    "roles": ["Admin"],
    "detail": {
      "user_id": 1,
      "date_of_birth": "1998-01-10",
      "bio": "Sales staff",
      "profile_picture": "profile-pictures/abc.jpg",
      "profile_picture_url": "http://127.0.0.1:8000/storage/profile-pictures/abc.jpg",
      "gender": "male",
      "country": "Nepal",
      "state": "Bagmati",
      "district": "Kathmandu",
      "local_bodies": "Kathmandu Metropolitan",
      "street_name": "Baneshwor"
    }
  }
}
```

### `PUT /user` accepted fields

- `first_name`
- `middle_name`
- `last_name`
- `username`
- `email`
- `phone`
- `date_of_birth`
- `bio`
- `gender`
- `country`
- `state`
- `district`
- `local_bodies`
- `street_name`

## 6. Menu APIs

There are two menu use cases:

- `GET /user/menu` for current-user sidebar rendering
- `/menus/*` for admin CRUD and role-permission mapping

### User menu

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/user/menu` | Sidebar menu for logged-in user | Yes | None | Returns filtered menu tree inside `data` |

Menu response shape:

```json
{
  "success": true,
  "data": [
    {
      "id": "dashboard",
      "menu_id": 1,
      "name": "Dashboard",
      "icon": "FaTachometerAlt",
      "path": "/admin/dashboard",
      "route": "dashboard",
      "url": "/admin/dashboard",
      "permission_name": "dashboard",
      "permissions": ["view"],
      "children": []
    }
  ],
  "message": "Menus retrieved successfully."
}
```

Menu item shape:

```json
{
  "id": "dashboard",
  "menu_id": 1,
  "name": "Dashboard",
  "icon": "FaTachometerAlt",
  "path": "/admin/dashboard",
  "route": "dashboard",
  "url": "/admin/dashboard",
  "permission_name": "dashboard",
  "permissions": ["view"],
  "children": []
}
```

### Admin menu management

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/menus` | List menus for admin | Yes | Query: optional `parent_id` | Returns menu data plus permission contract metadata |
| POST | `/menus` | Create a menu | Yes | JSON body | Auto-generates permissions from `permission_name` |
| GET | `/menus/accessible` | Preview accessible menus for current user | Yes | None | Returns active filtered tree |
| POST | `/menus/reorder` | Reorder menus | Yes | `{ "menus": [{ "id": 1, "order": 1 }] }` | Updates order only |
| GET | `/menus/{menu}` | Get one menu | Yes | None | Loads parent, children, creator, and generated permissions metadata |
| PUT/PATCH | `/menus/{menu}` | Update menu | Yes | JSON body | Can regenerate permissions if base changes |
| DELETE | `/menus/{menu}` | Delete menu | Yes | None | Fails if menu has children |
| PUT | `/menus/{menu}/role-permissions` | Assign generated menu permissions to roles | Yes | `role_permissions` array | Uses actions like `view`, `create`, `edit` |

### Menu create/update payload

```json
{
  "name": "Customer Support",
  "icon": "FaHeadset",
  "route": "customer-support",
  "url": "/admin/customer-support",
  "parent_id": null,
  "order": 8,
  "is_status": true,
  "permission_name": "customer_support",
  "is_public": false,
  "role_permissions": [
    {
      "role_id": 1,
      "actions": ["view", "create", "edit", "delete", "manage"]
    },
    {
      "role_id": 2,
      "actions": ["view", "edit"]
    }
  ]
}
```

### Permission generation

When a menu is created/updated, the backend can generate:

- `view_{permission_name}`
- `create_{permission_name}`
- `edit_{permission_name}`
- `delete_{permission_name}`
- `approve_{permission_name}`
- `export_{permission_name}`
- `upload_{permission_name}`
- `manage_{permission_name}`

### Menu contract metadata

Menu responses now expose the supported permission actions and request-field contract via `meta` or `data.supported_actions` so the frontend can build permission-management forms without hardcoding action names.

## 7. RBAC APIs

Preferred admin flow:

1. Load permissions
2. Load roles
3. Create/update roles
4. Load users
5. Get user access detail
6. Sync roles and direct permissions

### Roles

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/rbac/roles` | List roles | Yes | Query: `per_page` | Includes permissions and `users_count` |
| POST | `/rbac/roles` | Create role | Yes | `name`, optional `permissions` | Permissions can be ids or names |
| GET | `/rbac/roles/{role}` | Role detail | Yes | None | Includes full permission list |
| PUT/PATCH | `/rbac/roles/{role}` | Update role | Yes | Optional `name`, optional `permissions` | `Super Admin` name cannot be changed |
| DELETE | `/rbac/roles/{role}` | Delete role | Yes | None | Fails for `Super Admin` or assigned roles |

### Permissions

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/rbac/permissions` | List all Spatie permissions | Yes | None | Returns flat list and grouped list |

Permission item shape:

```json
{
  "id": 1,
  "name": "view_property",
  "guard_name": "web",
  "action": "view",
  "resource": "property"
}
```

### RBAC users

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/rbac/users` | List users for access management | Yes | Query: `search`, `status`, `per_page` | Returns summary list |
| POST | `/rbac/users` | Admin creates user and assigns roles | Yes | JSON body | Creates `users` + `user_details` + roles |
| GET | `/rbac/users/{user}/access` | Get full user access profile | Yes | None | Includes roles, direct permissions, all permissions, detail, menus |
| PUT | `/rbac/users/{user}/roles` | Replace all roles | Yes | `{ "roles": [...] }` | Roles can be ids or names |
| PUT | `/rbac/users/{user}/permissions` | Replace all direct permissions | Yes | `{ "permissions": [...] }` | Permissions can be ids or names |
| PATCH | `/rbac/users/{user}/status` | Activate/deactivate user | Yes | `{ "status": true|false }` | Deactivation revokes tokens |

### Managed user create payload

```json
{
  "first_name": "Ram",
  "middle_name": "",
  "last_name": "Sharma",
  "username": "ramsharma",
  "email": "ram@example.com",
  "phone": "9800000000",
  "password": "Password@123",
  "password_confirmation": "Password@123",
  "roles": ["Employee"],
  "status": true,
  "email_verified": true,
  "date_of_birth": "1998-01-10",
  "bio": "Sales staff",
  "gender": "male",
  "country": "Nepal",
  "state": "Bagmati",
  "district": "Kathmandu",
  "local_bodies": "Kathmandu Metropolitan",
  "street_name": "Baneshwor"
}
```

### RBAC access payload

`GET /api/v1/rbac/users/{user}/access`

Returns:

- `user`
- `roles`
- `direct_permissions`
- `all_permissions`
- `detail`
- `menus`

### RBAC restrictions

- Non-`Super Admin` users cannot assign `Super Admin`
- Non-`Super Admin` users cannot change a `Super Admin` user
- A user cannot deactivate their own account
- The last active `Super Admin` cannot be deactivated

## 8. Permission Matrix APIs

These endpoints overlap with RBAC and menu permissions. Use them only if the frontend needs matrix-style permission editing screens.

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/permission-matrix` | List permission matrix rows | Yes | Query: `role_id`, `feature_name`, `per_page` | Returns resource collection |
| GET | `/permission-matrix/grouped` | Group matrix by feature | Yes | None | Best for matrix UI |
| POST | `/permission-matrix` | Create matrix row | Yes | `feature_name`, `role_id`, boolean flags | Syncs Spatie permissions |
| PUT | `/permission-matrix/{permissionMatrix}` | Update matrix row | Yes | Boolean flags | Syncs Spatie permissions |
| DELETE | `/permission-matrix/{permissionMatrix}` | Delete matrix row | Yes | None | Removes row only |
| GET | `/permission-matrix/employee/{roleId}` | Get matrix + menu accessibility for one role | Yes | None | Returns feature permissions + menu permissions |
| POST | `/permission-matrix/employee/bulk-update` | Bulk update matrix rows for one role | Yes | `role_id`, `permissions[]` | Best for bulk save UI |

### Permission flags

- `can_view`
- `can_create`
- `can_edit`
- `can_delete`
- `can_approve`
- `can_export`
- `can_upload`
- `can_all`

## 9. Employee Permission APIs

These are older helper endpoints around employee-role permission screens.

| Method | Path | Use | Auth | Request body | Response notes |
|---|---|---|---|---|---|
| GET | `/employee-permissions/roles` | List roles with employee counts | Yes | None | Excludes `Super Admin` |
| GET | `/employee-permissions/matrix` | Get static matrix view | Yes | None | Uses a fixed feature list |
| POST | `/employee-permissions/assign/{roleId}` | Assign permissions to one role for one feature | Yes | `feature`, `permissions[]` | Updates permission matrix row |
| GET | `/employee-permissions/role/{roleId}/employees` | List employees by role | Yes | None | Returns paginated users |

### Notes

- Prefer `/rbac/*` for core user/role management.
- Use this group only if the frontend has a dedicated employee permission matrix page already built around it.

## 10. Dashboard APIs

Dashboard data is already shaped for the current admin UI cards/widgets.

| Method | Path | Use | Auth | Query params | Response notes |
|---|---|---|---|---|---|
| GET | `/dashboard/summary` | Full dashboard payload | Yes | `period=week|month|year`, `limit` | Best one-call dashboard load |
| GET | `/dashboard/recent-properties` | Recent property cards only | Yes | `period`, `limit` | Returns `items[]` and `view_all_url` |
| GET | `/dashboard/recent-activity` | Activity feed only | Yes | `limit` | Inquiry/showing/property events |
| GET | `/dashboard/performance` | KPI cards only | Yes | `period` | Response rate, listings viewed, conversion rate, revenue |
| GET | `/dashboard/report` | JSON report export payload | Yes | `period`, `limit` | Wraps summary in `dashboard` |

### `GET /dashboard/summary` sections

- `period`
- `available_periods`
- `generated_at`
- `current_user`
- `stats`
  - `total_properties`
  - `active_listings`
  - `pending_deals`
  - `monthly_views`
- `quick_actions`
- `recent_properties`
- `recent_activity`
- `performance`
- `quick_links`

### Frontend notes

- `pending_deals` is derived from inquiries/followups, not from a dedicated deals table.
- `total_revenue_ytd` is estimated from sold/leased property prices and includes:
  - `is_estimated`
  - `calculation_basis`

## 11. Notification APIs

These are the in-app inbox APIs. The backend is ready for realtime broadcasting, but actual websocket push still depends on enabling a real broadcast driver such as Reverb or Pusher.

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/notifications` | Notification list | Yes | Query: `status=all|read|unread`, `per_page` | Returns `data`, `meta`, `pagination` |
| GET | `/notifications/unread-count` | Bell count | Yes | None | Returns unread count only |
| PATCH | `/notifications/{notificationId}/read` | Mark one as read | Yes | None | Returns formatted notification |
| PATCH | `/notifications/read-all` | Mark all as read | Yes | None | Returns updated count |

Notification item shape:

```json
{
  "id": "db-notification-id",
  "type": "property.created",
  "title": "New property added",
  "message": "Modern Luxury Villa was created",
  "severity": "info",
  "action_url": "/admin/property/3098",
  "action_label": "View property",
  "entity": {},
  "actor": {},
  "meta": {},
  "read_at": null,
  "created_at": "2026-03-27T12:00:00Z"
}
```

### Current notification events

- New user registration
- New property created
- New inquiry followup created

Recipients:

- `Super Admin`
- `Admin`
- The creator user for the event

## 12. Public Website APIs

### Public properties

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/public/properties/summary` | Basic paginated property list | No | Query: `limit`, `page`, `order_by` | Lightweight listing |
| GET | `/public/properties/list` | Full searchable property listing | No | Many filter params | Main public search endpoint |
| GET | `/public/properties/{slug}/details` | Property details page | No | None | Loads property with rich relations |

### Main public listing filters

Common useful query params for `GET /public/properties/list`:

- Pagination:
  - `page`
  - `limit`
- Sort:
  - `sort=latest`
  - `sort=oldest`
  - `sort=price_low_to_high`
  - `sort=price_high_to_low`
  - `sort=title_asc`
  - `sort=title_desc`
  - or legacy `order_by_field` + `order_by`
- Search:
  - `title`
  - `tags`
  - `property_code`
  - `description`
- Exact filters:
  - `status`
  - `currency`
- Foreign keys:
  - `property_face_id`
  - `property_type_id`
  - `listing_type_id`
  - `property_status_id`
  - `road_type_id`
  - `road_condition_id`
  - `water_source_id`
  - `sewage_type_id`
  - `land_unit_id`
  - `measure_unit_id`
- Slug filters:
  - `listing_type_slug`
  - `property_type_slug`
  - `property_status_slug`
  - `property_face_slug`
  - `property_category_slug`
- Booleans:
  - `is_featured`
  - `is_negotiable`
  - `banking_available`
  - `has_electricity`
  - `is_road_accessible`
  - `is_verified`
  - `has_images`
  - `has_address`
- Ranges:
  - `min_price`, `max_price`
  - `min_land_area`, `max_land_area`
  - `min_road_width`, `max_road_width`
  - `min_length`, `max_length`
  - `min_height`, `max_height`
- Address:
  - `city`
  - `state`
  - `country`
  - `zip_code`
  - `address_line`
- House details:
  - `bedrooms`
  - `bathrooms`
  - `kitchens`
  - `floors`
  - `parking`
  - `furnished`
- Geo:
  - `latitude`
  - `longitude`
  - `distance`
- Id lists:
  - `ids`
  - `exclude_ids`

### Public blogs

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/public/blog` | Blog listing | No | Query: `category_id`, `category_slug`, `limit`, `page`, `order_by` | Returns paginator inside `data` |
| GET | `/public/blog/list/{id}` | Blogs by category id | No | None | Category filter style endpoint |
| GET | `/public/blog/{slug}` | Blog detail page | No | None | Loads comments and user |

### Public inquiries and tours

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| POST | `/public/inquiry` | Submit property inquiry from public site | No | `property_id`, `name`, `phone`, `from`, optional email/message | Main public inquiry endpoint |
| POST | `/public/tour` | Book showing/tour from public site | No | `property_id`, `date`, `time`, `name`, `phone`, optional email/message | Creates field visit record |

### Public options

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/public/options/all` | Load all option datasets at once | No | None | Useful for initial public filter boot if payload size is acceptable |

## 13. Option Management APIs

These endpoints are dynamic. The frontend must send `dropdownfor` to tell the backend which option model to use.

### Main endpoints

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/options` | List option items for a dropdown type | Yes | Query includes `dropdownfor` | Paginated result |
| POST | `/options` | Create option item | Yes | Body includes `dropdownfor` + data fields | Field names vary by type |
| GET | `/options/types` | List stable option types metadata | Yes | None | Best entry point for frontend option screens |
| GET | `/options/catalog/{type}` | Load normalized typed option catalog | Yes | Query: `page`, `limit`, `search`, optional `parent_id`, `is_status` | Best stable options endpoint |
| GET | `/options/dropdown/{slug}/{module?}` | Load simple dropdown options | Yes | `slug` path param | Usually returns `id` + `label` |
| GET | `/options/menu` | List available option types | Yes | None | Returns type names |
| GET | `/options/show` | Intended option metadata route | Yes | None | Legacy helper |
| GET | `/options/{id}` | Get one option item | Yes | Query requires `dropdownfor` | Returns one record |
| PUT | `/options/{id}` | Update one option item | Yes | Query/body requires `dropdownfor` | Dynamic update |
| DELETE | `/options/{id}/{type}` | Delete one option item | Yes | Type in path | Dynamic delete |
| PATCH | `/options/status/{id}` | Update option status | Yes | Legacy route | Treat as unstable until normalized |

### Common `dropdownfor` values

Examples from current backend map:

- `province`
- `district`
- `municipality`
- `ward`
- `roadtype`
- `roadcondition`
- `unit`
- `propertytype`
- `propertystatus`
- `listingtype`
- `housetype`
- `rooftype`
- `constructionstatus`
- `watersource`
- `sewagetype`
- `propertyface`
- `measureUnit`
- `propertyCategory`
- `furnishing`
- `parkingType`
- `amenities`
- `contact_method`
- `status`
- `request_type`
- `category`

### Frontend notes

- This controller is highly dynamic and item shape depends on the selected model.
- For new frontend work, prefer:
  - `GET /options/types`
  - `GET /options/catalog/{type}`
  - `GET /options/dropdown/{slug}`
- Most create calls need at least:
  - `dropdownfor`
  - `label` or `name`
- For category-like records, `parentId` is accepted and mapped to `parent_id`.

## 14. Property Management APIs

These are authenticated admin property endpoints.

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/properties` | Admin property list | Yes | Query: `page`, `limit`, `order_by`, `order_column`, filters | Returns paginated items |
| POST | `/properties` | Create property | Yes | JSON or multipart payload | Large nested payload supported |
| GET | `/properties/{property}` | Property detail by id | Yes | None | Route binds by `id`, not slug |
| PUT/PATCH | `/properties/{property}` | Update property | Yes | JSON or multipart payload | Partial update supported |
| DELETE | `/properties/{property}` | Delete property | Yes | None | Hard delete |
| PATCH | `/properties/{property}/status` | Update active status | Yes | `{ "is_status": true }` or `{ "isStatus": true }` | Both keys accepted |

### Minimal property create payload

```json
{
  "title": "Modern Luxury Villa",
  "property_type_id": 1,
  "listing_type_id": 1,
  "property_status_id": 1,
  "advertise_price": 1250000,
  "currency": "NPR",
  "land_area": 3200,
  "land_unit_id": 1,
  "is_status": true
}
```

### Property payload sections supported by backend

Core property fields:

- `property_code`
- `title`
- `slug`
- `tags`
- `description`
- `land_area`
- `land_unit_id`
- `property_face_id`
- `property_type_id`
- `property_category_id`
- `listing_type_id`
- `length`
- `height`
- `measure_unit_id`
- `is_road_accessible`
- `road_type_id`
- `road_condition_id`
- `road_width`
- `base_price`
- `advertise_price`
- `currency`
- `is_featured`
- `is_negotiable`
- `banking_available`
- `has_electricity`
- `water_source_id`
- `sewage_type_id`
- `views_count`
- `likes_count`
- `seo_title`
- `seo_description`
- `property_status_id`
- `status`
- `video_url`

Address fields:

- `province_id`
- `district_id`
- `municipality_id`
- `ward_id`
- `area`
- `postal_code`
- `full_address`
- `latitude`
- `longitude`

House detail fields:

- `furnishing_id`
- `house_type_id`
- `built_area`
- `built_area_unit_id`
- `total_floors`
- `floor_details`
- `year_built`
- `year_renovated`
- `construction_status`
- `construction_status_details`
- `roof_type_id`
- `reserved_tank`
- `tank_area`
- `parking_cars`
- `parking_bikes`
- `parking_type_id`
- `parking_area`
- `parking_area_unit_id`
- `amenities`
- `building_face_id`

Related arrays:

- `images[]`
- `image_types[]`
- `featured_image_index`
- `features[]`
- `nearby_places[]`
- `delete_images[]` for update

### `nearby_places[]` item shape

```json
{
  "name": "School",
  "type": "education",
  "distance": 1.5,
  "distance_unit": "km",
  "description": "5 minutes away"
}
```

## 15. Inquiry APIs

### Inquiry CRUD

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/inquiries` | Admin inquiry list | Yes | Filters + pagination | Returns inquiry resource collection |
| POST | `/inquiries` | Create inquiry from admin | Yes | JSON body | Similar to public inquiry but without required `from` |
| GET | `/inquiries/{inquiry}` | Get one inquiry | Yes | None | Returns one inquiry |
| PUT/PATCH | `/inquiries/{inquiry}` | Update inquiry | Yes | JSON body | Full update style |
| DELETE | `/inquiries/{inquiry}` | Delete inquiry | Yes | None | Hard delete |

### Inquiry create/update fields

- `property_id`
- `inquiry_type_id`
- `property_type_id`
- `name`
- `email`
- `phone`
- `preferred_location`
- `min_price`
- `max_price`
- `message`
- `from` for public inquiry

### Inquiry followups

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/inquiries/{inquiryId}/followups` | List followups for inquiry | Yes | Filters + pagination | Returns followups for one inquiry |
| POST | `/inquiries/{inquiryId}/followups` | Create followup | Yes | `contact_method_id`, `followup_status_id`, `message`, `next_followup_date` | Also triggers notifications |
| GET | `/inquiries/{inquiryId}/followups/{inquiryFollowup}` | Get one followup | Yes | None | Returns followup |
| PUT | `/inquiries/{inquiryId}/followups/{inquiryFollowup}` | Update followup | Yes | Same fields as create | Full update style |
| DELETE | `/inquiries/{inquiryId}/followups/{id}` | Delete followup | Yes | None | Hard delete |

### Followup create payload

```json
{
  "contact_method_id": 1,
  "followup_status_id": 2,
  "message": "Customer asked for a second call.",
  "next_followup_date": "2026-03-30"
}
```

## 16. Field Visit APIs

Authenticated field visit endpoints are nested under a property.

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/field-visits` | Global admin field-visit list | Yes | Filters + pagination | Best for all-visits admin page |
| GET | `/properties/{propertyId}/field-visits` | List visits for property | Yes | Filters + pagination | Returns visits |
| POST | `/properties/{propertyId}/field-visits` | Create visit | Yes | JSON body | Requires `accept_term` boolean |
| GET | `/properties/{propertyId}/field-visits/{fieldVisit}` | Get one visit | Yes | None | Returns record directly |
| PUT | `/properties/{propertyId}/field-visits/{fieldVisit}` | Update visit | Yes | JSON body | Uses mass update |
| DELETE | `/properties/{propertyId}/field-visits/{fieldVisit}` | Delete visit | Yes | None | Deletes visit |
| PATCH | `/properties/{propertyId}/field-visits/status/{fieldVisit}` | Update visit status | Yes | `{ "status": "pending|confirmed|cancelled|completed" }` | Scoped to the property route param |

### Field visit create payload

```json
{
  "property_id": 3098,
  "date": "2026-03-29",
  "time": "14:30",
  "name": "Sita Sharma",
  "phone": "9800000000",
  "email": "sita@example.com",
  "message": "Please confirm before arrival.",
  "remarks": "VIP client",
  "accept_term": true,
  "status": "pending"
}
```

### Public tour payload

`POST /api/v1/public/tour`

```json
{
  "property_id": 3098,
  "date": "2026-03-29",
  "time": "14:30",
  "name": "Sita Sharma",
  "phone": "9800000000",
  "email": "sita@example.com",
  "message": "Please confirm before arrival.",
  "accept_term": true
}
```

## 17. Blog APIs

### Admin blog CRUD

| Method | Path | Use | Auth | Query/body | Response notes |
|---|---|---|---|---|---|
| GET | `/blog` | Admin blog list | Yes | Query: `page`, `limit`, `categoryId`, `isStatus`, `searchTerm`, `createdAt` | Returns paginated blog posts |
| POST | `/blog` | Create blog post | Yes | JSON or multipart body | Adds creator automatically |
| GET | `/blog/{blogPost}` | Get one blog post | Yes | None | Loads category and user |
| PUT/PATCH | `/blog/{blogPost}` | Update blog post | Yes | JSON or multipart body | Partial update supported |
| PATCH | `/blog/status/{blogPost}` | Update published/active status | Yes | `isStatus` | Uses `StatusUpdateRequest` |
| DELETE | `/blog/{blogPost}` | Delete blog post | Yes | None | Hard delete |

### Blog create/update fields

- `title`
- `entry`
- `author`
- `categoryId`
- `tags`
- `content`
- `status`
- `isStatus`
- `scheduledPublishDate`
- `thumbnail` file upload

### Public blog endpoints

See section 12 for public blog listing/detail endpoints.

## 18. Frontend Implementation Tips

### Auth client

- Keep both `access_token` and `refresh_token`
- Use `access_token_expires_at` to refresh before 401s where possible
- On refresh success, replace both tokens because refresh rotates the pair
- On `403` inactive account, force logout and show disabled-account state

### Menu rendering

- Prefer menu from login response on first page load after auth
- Use `GET /user/menu` to refresh menus after role/permission changes
- Use `permissions` array on each menu item for button-level UI rules

### RBAC screens

- Role page:
  - load `/rbac/roles`
  - load `/rbac/permissions`
- User access page:
  - load `/rbac/users`
  - load `/rbac/users/{id}/access`
- Menu permission page:
  - load `/menus`
  - use `/menus/{menu}/role-permissions` for assignments

### Dashboard page

- If you want a single initial request, use `/dashboard/summary`
- If widgets refresh independently, use the split endpoints:
  - `/dashboard/recent-properties`
  - `/dashboard/recent-activity`
  - `/dashboard/performance`

## 19. Known Gaps and Quirks

- Some legacy endpoints use `status` instead of `success`.
- `OptionController` is dynamic and not yet fully normalized; expect model-specific shapes.
- Some older CRUD controllers return raw models directly on show/update routes instead of a consistent wrapper.

## 20. Suggested Frontend Abstractions

To keep the UI code clean, create separate frontend clients/modules:

- `authApi`
  - login
  - refresh
  - logout
- `profileApi`
  - getCurrentUser
  - updateProfile
  - uploadProfilePicture
  - deleteProfilePicture
- `menuApi`
  - getCurrentMenu
  - listMenus
  - createMenu
  - updateMenu
  - syncMenuRolePermissions
- `rbacApi`
  - listRoles
  - createRole
  - updateRole
  - deleteRole
  - listPermissions
  - listUsers
  - getUserAccess
  - createManagedUser
  - syncUserRoles
  - syncUserPermissions
  - updateUserStatus
- `dashboardApi`
  - getSummary
  - getRecentProperties
  - getRecentActivity
  - getPerformance
- `notificationApi`
  - listNotifications
  - getUnreadCount
  - markRead
  - markAllRead
- `propertyApi`
  - listPublic
  - getPublicDetail
  - listAdmin
  - createProperty
  - updateProperty
  - updatePropertyStatus
- `inquiryApi`
  - createPublicInquiry
  - listAdminInquiries
  - createFollowup
- `fieldVisitApi`
  - createPublicTour
  - listPropertyVisits
  - createPropertyVisit
- `blogApi`
  - listPublicBlogs
  - getPublicBlog
  - listAdminBlogs
  - createAdminBlog
