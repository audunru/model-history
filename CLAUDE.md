# model-history

Records attribute changes to Eloquent models in a `history` table, associating each change with the authenticated user who made it; consumers retrieve per-model history via a `history()` relation and serialize it with the included API resource.

## Public API

- **`HasHistory` trait** (`src/Traits/HasHistory.php`) — add to any tracked model; hooks into `updated` and `deleted` events automatically; exposes:
  - `history()` — `MorphMany` of `Change` records
  - `has_history` accessor (boolean)
  - `addIgnored(array|string $field)` — suppress tracking for specific attributes (also `getIgnored()`, `setIgnored()`, or a `protected array $ignored` property)
  - `created_at` and `updated_at` are added to the ignored list in the `retrieved` event
  - attributes in the `default_ignored` config and attributes with an encrypted cast are never recorded
- **`MakesChanges` trait** (`src/Traits/MakesChanges.php`) — add to the owner model (e.g. `User`); exposes `changes()` `MorphMany`. The owner is stored with `getMorphClass()`, so a morph map alias is stored when one is defined
- **`Change` model** (`src/Models/Change.php`) — the stored record; `changes` column is cast to array; ordered by `created_at desc` globally; eager-loads `owner` by default
- **`History` resource** (`src/Resources/History.php`) — JSON API resource returning `id`, `changes`, `owner` (id/name/email), timestamps

## Service Provider

- Config key: `model-history` — published as `config/model-history.php`
- Migration stubs: `create_model_history_table` — creates the `history` table (name overridable via `history_table_name` config); `make_owner_nullable_in_history_table` — makes `owner_id` and `owner_type` nullable on an existing table (anonymous class)
- Registers `HistoryChanged` → `RecordHistoryChange` event/listener pair
- Config options: `history_table_name`, `eager_load_owner` (default `true`), `eager_load_model` (default `false`), `date_format`, `default_ignored` (default `password`, `remember_token`, `api_token`), `record_changes_without_owner` (default `false`)

## Testing

Run from the `model-history/` directory: `composer test`

Fixtures: `tests/Models/Product.php` (uses `HasHistory` + `SoftDeletes`) and `tests/Models/User.php` (uses `MakesChanges`) with corresponding factories. Test migrations are in `tests/database/migrations/`. Tests: `tests/Unit/HistoryTest.php`, `tests/Unit/MigrationTest.php` and `tests/Feature/ApiTest.php`.

## Conventions / Gotchas

- By default, a change is only recorded when a user **is authenticated** (`Auth::check()`). If no user is logged in, the change is silently dropped and an error is logged — no exception is thrown. With `record_changes_without_owner` set to `true`, the change is recorded with `owner_id` and `owner_type` set to `null` and no error is logged; `HistoryChanged::$owner` is then `null`.
- Soft deletes are tracked: `deleted_at` going from `null` to a timestamp is recorded as a `Change`. The original value is always hard-coded to `null` because the `deleted` event fires after the column is set.
- The `changes` column stores a pair of model instances (`original` / `updated`) that hold only the changed attributes — not a flat diff. Values are read with `getOriginal()` / `getAttribute()` when the change is recorded, so the model's casts and serialization apply before storage; reading the column back gives plain arrays.
- Timestamps are only ignored on models loaded from the database (`retrieved` event). A model that is created and then updated in the same request without a reload records `updated_at`.
- Encrypted attributes are skipped on purpose, because the history would hold the decrypted value. Keep this when you change `getHistoryChanges()`.
- Table name is set at model construction time from config; change `history_table_name` before running migrations if `history` conflicts with an existing table.
