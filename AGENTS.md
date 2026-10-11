# model-history

Records attribute changes to Eloquent models in a `history` table, associating each change with the authenticated user who made it; consumers retrieve per-model history via a `history()` relation and serialize it with the included API resource.

## Public API

- **`HasHistory` trait** (`src/Traits/HasHistory.php`) — add to any tracked model; hooks into `updated` and `deleted` events automatically; exposes:
  - `history()` — `MorphMany` of `Change` records
  - `has_history` accessor (boolean)
  - `addIgnored(array|string $field)` — suppress tracking for specific attributes
  - `created_at` and `updated_at` are ignored by default
- **`MakesChanges` trait** (`src/Traits/MakesChanges.php`) — add to the owner model (e.g. `User`); exposes `changes()` `MorphMany`
- **`Change` model** (`src/Models/Change.php`) — the stored record; `changes` column is cast to array; ordered by `created_at desc` globally; eager-loads `owner` by default
- **`History` resource** (`src/Resources/History.php`) — JSON API resource returning `id`, `changes`, `owner` (id/name/email), timestamps

## Service Provider

- Config key: `model-history` — published as `config/model-history.php`
- Migration stub: `create_model_history_table` — creates the `history` table (name overridable via `history_table_name` config)
- Registers `HistoryChanged` → `RecordHistoryChange` event/listener pair
- Config options: `history_table_name`, `eager_load_owner` (default `true`), `eager_load_model` (default `false`), `date_format`

## Testing

Run from the `model-history/` directory: `composer test`

Fixtures: `tests/Models/Product.php` (uses `HasHistory` + `SoftDeletes`) and `tests/Models/User.php` (uses `MakesChanges`) with corresponding factories. Tests in `tests/Feature/`: `HistoryTest.php` and `ApiTest.php`.

## Conventions / Gotchas

- A change is only recorded when a user **is authenticated** (`Auth::check()`). If no user is logged in, the change is silently dropped and an error is logged — no exception is thrown.
- Soft deletes are tracked: `deleted_at` going from `null` to a timestamp is recorded as a `Change`. The original value is always hard-coded to `null` because the `deleted` event fires after the column is set.
- The `changes` column stores a serialised pair of model instances (`original` / `updated`) — not a flat diff — so attribute accessors/casts are applied when reading values back.
- Table name is set at model construction time from config; change `history_table_name` before running migrations if `history` conflicts with an existing table.
