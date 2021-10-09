# Track changes to Laravel models

[![Build Status](https://app.travis-ci.com/audunru/model-history.svg?branch=master)](https://app.travis-ci.com/audunru/model-history)
[![Coverage Status](https://coveralls.io/repos/github/audunru/model-history/badge.svg?branch=master)](https://coveralls.io/github/audunru/model-history?branch=master)
[![StyleCI](https://github.styleci.io/repos/407974250/shield?branch=master)](https://github.styleci.io/repos/407974250)

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
```

# Development

## Testing

Run tests:

```bash
composer test
```
