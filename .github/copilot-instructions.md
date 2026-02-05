# Copilot Instructions for matsu-f.com

Real estate management system (松永不動産) with dual-interface architecture: admin control panel + public-facing website.

## Architecture Overview

**Two-tier structure:**
- **`control/`** - Admin panel with database management, property CRUD, user authentication
- **`inc/`, `api/`** - Public website displaying rental/sale properties, likes tracking, analytics
- **`image/`, `material/`** - Static assets

**Key database:** MySQL (`_matsunagafu`) with tables for users, `sale_properties`, `rent_properties`, areas (`エリアs`), property images, and `property_likes`.

## Core Patterns

### Database Access
- Central PDO factory in [`control/lib/db.php`](control/lib/db.php#L1): `getPDO()` returns singleton connection
- MySQL config in [`control/config/database.php`](control/config/database.php) with charset `utf8mb4`
- All queries use prepared statements with named parameters to prevent SQL injection
- Set error mode to `ERRMODE_EXCEPTION`, fetch as associative arrays
- Example: `$pdo->prepare('SELECT ... WHERE status = :status')->execute(['status' => 1])`

### Authentication & Sessions
- Functions in [`control/lib/auth.php`](control/lib/auth.php#L1):
  - `is_logged_in()` - checks `$_SESSION['user_id']`
  - `require_login()` - redirects to `login.php` if not authenticated
  - `login(int $userId)` / `logout()` - manage session
- All admin pages call `require_login()` at the start
- Session started with `session_start()` once per request

### Admin Panel Routing
- Single entry point: [`control/public/index.php`](control/public/index.php#L1)
- Page routing via `?page=` query parameter mapped to view files
- Title map synced with routes array (both in same file)
- Pages: `dashboard`, `sale_list/create/edit`, `rent_list/create/edit`, `area_list/edit`, `news_list`, `settings`

### Form Processing & HTML Safety
- Helper function `h()` in view files: `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`
- Always escape output when displaying user data
- `post_value()` for string form fields, `post_bool()` for checkboxes (converts to '0'/'1')
- `filter_input(INPUT_GET/POST, $key, FILTER_VALIDATE_INT)` for integer params

### Property Lists (Rent/Sale)
- Views use POST for operations, GET for filtering:
  - Search by name/location: `LIKE` queries
  - Filter by category (`cate`), status field
  - Reorder with `?action=reorder` (AJAX POST with `order[]` IDs) - updates `sort` column
- Join with `property_images` table for thumbnail URLs
- Always paginate/limit queries (typically `LIMIT 100`)

### API Endpoints
- Location: [`api/`](api/)
- `like.php` - POST to record like, GET to count (tracks by `content_type`, `content_id`, `page_path`)
- `notices.php`, `page-view.php` - tracking/analytics
- All return JSON with `JSON_UNESCAPED_UNICODE` flag
- Response pattern: `['ok' => true, 'data' => ...]` with HTTP status codes

### Public Website
- Includes in [`inc/`](inc/) load property data and render HTML pages
- Uses same `getPDO()` from control panel
- Google Maps API key in [`control/config/google.php`](control/config/google.php)
- Queries filter by `status = 1` (published only)
- CSS variables: `--card-radius: 12px`, `--card-gap: 16px`, `--card-shadow: 0 12px 24px rgba(0,0,0,0.18)`

## Development Conventions

- **Language**: Japanese property/field names (賃料, エリア, 間取り) mixed with English; mix is acceptable
- **Date/Time**: Timezone `Asia/Tokyo` for new records, stored as `Y-m-d` for daily tracking
- **Strict types**: Use `declare(strict_types=1)` in utility files
- **Transactions**: Use `$pdo->beginTransaction()` / `$pdo->commit()` for multi-step operations
- **Error handling**: Catch `Throwable` or specific exceptions, return JSON errors with HTTP status codes

## Key Files Reference

| File | Purpose |
|------|---------|
| `control/lib/db.php` | PDO singleton factory |
| `control/lib/auth.php` | Session/login management |
| `control/config/database.php` | DB credentials |
| `control/public/index.php` | Admin router + layout shell |
| `control/views/*/edit.php` | CRUD form templates |
| `api/like.php` | Like count & tracking |
| `inc/rentList.php` | Public rental list page |
| `control/setup/create_tables.sql` | Schema |

## Common Tasks

**Add new property field:**
1. Add column to SQL schema in `create_tables.sql`
2. Add field name to `$fields` array in `edit.php`
3. Add to HTML form in same file
4. Add to SELECT/INSERT queries

**Add new admin page:**
1. Create `control/views/{module}/{action}.php`
2. Add route to `$routes` and `$titleMap` in `index.php`
3. Call `require_login()` at top, include layout partials

**Modify property queries:**
- Remember: public pages filter `status = 1`; admin shows all
- Join with `property_images` for images
- Order by `lastUpdateDate DESC` or `sort ASC`
