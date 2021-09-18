<?php

namespace audunru\ModelHistory\Models;

use Database\Factories\ChangeFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Change extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['changes', 'owner_id', 'owner_type'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('model-history.history_table_name'));
        // The relations to eager load on every query.
        $this->with = array_keys(array_filter([
            'owner' => config('model-history.eager_load_owner', true),
            'model' => config('model-history.eager_load_model', false),
        ], function ($value) {
            return true === $value;
        }));
    }

    /**
     * Add global scopes.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ChangeFactory::new();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        $dateFormat = config('model-history.date_format', 'Y-m-d H:i:s');

        return $date->format($dateFormat);
    }

    /**
     * Get changed model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get owner that made the change.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
