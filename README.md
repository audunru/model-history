# Track changes to Laravel models

[![Build Status](https://github.com/audunru/model-history/actions/workflows/validate.yml/badge.svg)](https://github.com/audunru/model-history/actions/workflows/validate.yml)
[![Coverage Status](https://coveralls.io/repos/github/audunru/model-history/badge.svg?branch=main)](https://coveralls.io/github/audunru/model-history?branch=main)

Keep a record of changes to models in your application. If a user changes the name of a product from A to B, that change will be stored in a `Change` model and stored in your database. Only the changed attributes are stored. You can then use this to retrieve the model's history, including which user made the change.

# Installation

## Step 1: Install with Composer

```bash
composer require audunru/model-history
```

## Step 2: Publish and run migrations

Note: Changes are stored in a table called `history`. You can change this in the configuration, but you will have to publish the configuration before publishing and running the migrations.

```php
php artisan vendor:publish --tag=model-history-migrations
php artisan migrate
```

The migrations are `create_model_history_table` and `make_owner_nullable_in_history_table`. The second migration makes the `owner_id` and `owner_type` columns nullable. If you installed an earlier version of the package, run the two commands again to get the new migration. The command does not overwrite the migrations that you published before.

## Step 3: Add traits to your models

Add the `MakesChanges` trait to your `User` model:

```php
namespace App\Models;

use audunru\ModelHistory\Traits\MakesChanges;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use MakesChanges;
```

Add the `HasHistory` trait to any model where you want to track changes:

```php
namespace App\Models;

use audunru\ModelHistory\Traits\HasHistory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasHistory;
```

## Step 4: Retrieve model changes

Assuming that you've added the `HasHistory` trait to a model named `Product`, you can retrieve the changes like this:

```php
$product = Product::create([
    'description' => 'Old description',
]);

$product->update([
    'description' => 'New description',
]);

dump($product->changes);
```

## Step 5: Return history as JSON (optional)

The package includes a `History` API resource for `Change` models:

```php
use audunru\ModelHistory\Resources\History;

return History::collection($product->history);
```

Each item contains `id`, `changes`, `owner` (`id`, `name` and `email` of the user who made the change), `created_at` and `updated_at`.

Note that the resource shows the email address of each user who changed the model. If you give the history to other users, for example to everyone who can see a product, they also see these email addresses. If you do not want this, write your own `JsonResource` for the `Change` model and use it instead of `History`.

# Configuration

Publish the configuration file by running:

```php
php artisan vendor:publish --tag=model-history-config
```

Available options:

```php
    /*
     * Table where the "Change" model will be stored
     */
    'history_table_name' => 'history',
    /*
     * Eager load the change model's owner
     */
    'eager_load_owner' => true,
    /*
     * Eager load the change model's model
     */
    'eager_load_model' => false,
    /*
     * Date format used when returning the Change model as JSON
     */
    'date_format' => 'Y-m-d H:i:s',
    /*
     * Attribute names that are never recorded in history, regardless of each model's own $ignored
     * list. Override in your published config to add or remove entries.
     */
    'default_ignored' => [
        'password',
        'remember_token',
        'api_token',
    ],
    /*
     * Record changes when no user is authenticated, for example from artisan commands, queued jobs
     * and seeders. The change is stored with no owner. Requires nullable owner columns.
     */
    'record_changes_without_owner' => false,
```

## Changes with no authenticated user

By default, a change is recorded only when a user is authenticated. Changes from artisan commands, queued jobs, seeders, the scheduler and guest requests are not recorded. The package logs an error for each change that it does not record.

To record these changes, set `record_changes_without_owner` to `true`. The change is then recorded with `owner_id` and `owner_type` set to `null`, and the package does not log an error. The `History` resource shows `owner` with `null` values for `id`, `name` and `email`.

The `owner_id` and `owner_type` columns must be nullable. Run the `make_owner_nullable_in_history_table` migration before you enable the option (see [Step 2](#step-2-publish-and-run-migrations)).

A rollback of this migration makes the owner columns NOT NULL again. The rollback fails while the history table has rows with no owner. Before you roll back, delete these rows or give them an owner.

## Ignoring attributes

Changes to the attributes in the `default_ignored` configuration option are never recorded. The `created_at` and `updated_at` attributes are also ignored.

To ignore more attributes on a model, use `addIgnored()`:

```php
$product->addIgnored(['gross_cost', 'tax_rate']);
```

Attributes with an encrypted cast (`encrypted`, `encrypted:array`, `encrypted:collection`, `encrypted:json`, `encrypted:object`, `AsEncryptedArrayObject` and `AsEncryptedCollection`) are never recorded. If they were, the history table would contain the decrypted value.

Attributes in the model's `$hidden` array are not recorded either. The changes are serialised before they are stored, and serialisation removes hidden attributes. If only hidden attributes change, the history still gets an entry with the user who made the change, but with no attributes in `original` and `updated`.

# Development

## Testing

Run tests:

```bash
composer test
```
