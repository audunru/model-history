<?php

namespace audunru\ModelHistory\Tests\Models;

use audunru\ModelHistory\Tests\Factories\ProductFactory;
use audunru\ModelHistory\Traits\HasHistory;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use HasHistory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'gross_cost',
        'password',
        'purchased_at',
        'secret_list',
        'secret_note',
        'seller_address',
        'seller_identification',
        'seller_name',
        'seller_phone',
        'tax_rate',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'secret_note' => 'encrypted',
        'secret_list' => AsEncryptedCollection::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }
}
