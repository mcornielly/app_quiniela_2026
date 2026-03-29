# README_SYSTEM

## Current system status (updated)

This document describes the real current state of the **Quiniela 2026** system from a functional, technical, and architectural perspective.

## 1) Main stack

- Backend: Laravel 12 (PHP 8.3)
- Frontend: Vue 3 + Inertia.js
- UI: TailwindCSS + Flowbite
- UI notifications: Element Plus (`notifySuccess`, `notifyError`, `confirmAction`)
- Realtime: Laravel Reverb + Echo
- DB: MySQL

## 2) Implemented functional modules

### End user

- User dashboard with:
  - points
  - daily results
  - upcoming games
  - tournament coverage
  - top ranking
- Match calendar with filters
- Pool entry detail view (`/pools/{id}`)
- User pool management:
  - create
  - inactivate (soft delete)
  - reactivate
  - inline per-match prediction editing (save/cancel)
- User profile (`/profile`)

### Administration

- Admin dashboard (`/admin/dashboard`)
- Admin CRUD modules using drawer pattern:
  - tournaments
  - teams
  - countries
  - groups
  - games
  - rules
  - users
- Tournament participants (`/admin/tournaments/{tournament}/participants`)
- Admin profile (`/admin/profile`)
- Realtime user activity notifications for admins

## 3) Tournament and pool architecture

### Supported stages

- `group` (group stage)
- `round_32` (round of 32)
- `round_16` (round of 16)
- `quarter` (quarterfinals)
- `semi` (semifinals)
- `third_place` (third place)
- `final` (final)

### Core services

Located in `app/Services/Tournament/`:

- `GroupStandingsService`
- `BracketResolverService`
- `BracketProgressionService`
- `PredictionBracketResolverService`
- `PredictionScoringService`
- `PoolRankingService`
- `PoolEntryRuleService`
- `ThirdPlaceAssignmentService`
- `BestThirdPlaceRankingService`
- `StandingsTableService`

## 4) Participation rules status (Rule)

### `rules` table

Migration: `database/migrations/2026_03_22_000000_create_rules_table.php`

Key fields:

- `tournament_starts_at`
- `participation_closes_at`
- `exact_score_points` (default 5)
- `correct_result_points` (default 3)
- `unpaid_after_window_action` (`locked` or `cancelled`)
- `active`

### Implemented logic (`PoolEntryRuleService`)

- **Paid** -> `paid_locked` (locked, not editable)
- **Unpaid + open window** -> `draft` (editable/inactivatable)
- **Unpaid + closed window** -> `locked` or `cancelled` based on rule

### Pool entry soft delete

Migration: `database/migrations/2026_03_22_000100_add_soft_deletes_to_pool_entries_table.php`

- Inactivate: soft delete + `status = inactive`
- Reactivate: restore + status sync from rule

## 5) Bracket prediction persistence

Migration:
`database/migrations/2026_03_21_220000_add_predicted_team_columns_to_predictions_table.php`

Stored in `predictions`:

- `predicted_home_team_id`
- `predicted_away_team_id`
- `predicted_winner_team_id`

This avoids fully resolving bracket teams only in UI and improves user-level traceability.

### Inline prediction editing flow

- Route:
  - `PATCH /pools/{poolEntry}/predictions/{prediction}` (`pools.predictions.update`)
- Controller:
  - `PoolEntryController@updatePrediction`
- Behavior:
  - Validates ownership and that prediction belongs to pool entry
  - Enforces rule-based editability (`can_edit`)
  - Validates numeric score range (`0..20`)
  - Disallows draws in knockout stages
  - Re-resolves bracket predicted teams after update
  - Recalculates pool summary (`total_points`, `exact_hits`, `correct_results`)

## 6) Admin notifications (new feature)

### Events that notify admin users

From `PoolEntryController` when a user:

- creates a pool (`created`)
- inactivates a pool (`inactivated`)
- reactivates a pool (`reactivated`)

### Channels and components

- Realtime event: `App\Events\AdminPoolActivityBroadcast` (`ShouldBroadcastNow`)
- DB notification: `App\Notifications\AdminPoolActivityNotification`
- Private channel: `admin.activity` (defined in `routes/channels.php`)
- Admin API:
  - `GET /admin/notifications`
  - `POST /admin/notifications/read-all`
  - `POST /admin/notifications/{id}/read`
  - `DELETE /admin/notifications`
- Frontend composable:
  - `resources/js/Composables/useAdminNotifications.js`
- Admin dropdown:
  - `resources/js/Components/Admin/Notifications/AdminNotificationsDropdown.vue`

### Current dropdown behavior

- Shows up to 10 unread notifications
- Item-level `X` marks as read and removes from dropdown view
- Trash button clears current view (mark all as read)
- Centered `View all` footer + unread count badge

### Backing table

Migration:
`database/migrations/2026_03_22_220000_create_notifications_table.php`

The same Laravel `notifications` table is used for admin notifications.

## 7) Session and redirects

- `SESSION_LIFETIME` default: 120 minutes (`config/session.php`)
- After login:
  - Admin -> `admin.dashboard`
  - User -> `dashboard`
- Configured in:
  - `AuthenticatedSessionController@store`
  - `bootstrap/app.php` (`redirectUsersTo`)

## 8) Layouts and profile

- Main admin layout: `resources/js/Layouts/AdminLayout.vue`
- Admin navbar with notifications and theme:
  - `resources/js/Layouts/Partials/Navbar.vue`
- Unified profile page by role:
  - `resources/js/Pages/Profile/Edit.vue`
  - Admin uses `AdminLayout`
  - User uses `AuthenticatedLayout`

## 9) Frontend utilities

### Notifications/toasts

File: `resources/js/Utils/notify.js`

Used helpers:

- `notifySuccess(message)`
- `notifyError(message)`
- `confirmAction({...})`

### Formatting

File: `resources/js/Utils/format.js`

Key helpers:

- `formatDateTime()`
- `formatRegistrationNumber(value, digits = 5)`

## 10) Notification devtools

Folder:
`storage/devtools/notifications/`

Includes:

- `check_notifications.php`
- `test_admin_notify.php`
- `test_admin_broadcast.php`
- `README.md`

Usage:

```bash
php storage/devtools/notifications/check_notifications.php
php storage/devtools/notifications/test_admin_notify.php
php storage/devtools/notifications/test_admin_broadcast.php
```

## 11) CRUD generation command

Internal command available:

- `app/Console/Commands/MakeAdminCrud.php`

Goal: accelerate admin module creation under the table + drawer pattern.

## 12) Current project checkpoint

The system currently has:

- complete user pool flow (create, detail, inactivate/reactivate)
- scoring and stage progression support
- tournament-based participation rules
- stable role-based redirects
- persistent + realtime admin notifications
- responsive UI improvements on key views (dashboard, calendar, pool detail, ranking)

Recommended next phase:

- consolidate stage label i18n (ES/EN)
- harden `Rule` CRUD validations (currently `store/update` validates only `name`)
- define final notification retention policy (read-only vs hard delete)
- optional: full admin notifications history page

## Author

Miguel Angel Cornielly H
