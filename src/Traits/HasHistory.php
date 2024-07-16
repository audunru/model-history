<?php

namespace audunru\ModelHistory\Traits;

use audunru\ModelHistory\Events\HistoryChanged;
use audunru\ModelHistory\Models\Change;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasHistory
{
    public static function bootHasHistory(): void
    {
        static::retrieved(function (Model $model): void {
            $model->addIgnored([
                $model->getCreatedAtColumn(),
                $model->getUpdatedAtColumn(),
            ]);
        });

        static::updated(function (Model $model): void {
            $changes = $model->getHistoryChanges();

            if ($changes->isNotEmpty()) {
                if (! Auth::check()) {
                    Log::error(sprintf('Changes where made to model %s with ID %u, but no user was authenticated. Changes: %s', get_class($model), $model->id, $changes));

                    return;
                }
                HistoryChanged::dispatch($changes, $model, Auth::user());
            }
        });

        static::deleted(function (Model $model): void {
            if ($model->soft_deleting) {
                $changes = $model->getSoftDeletedChanges();

                if ($changes->isNotEmpty()) {
                    if (! Auth::check()) {
                        Log::error(sprintf('Changes where made to model %s with ID %u, but no user was authenticated. Changes: %s', get_class($model), $model->id, $changes));

                        return;
                    }
                    HistoryChanged::dispatch($changes, $model, Auth::user());
                }
            }
        });
    }

    /**
     * Changes to these attributes will not be recorded in history.
     *
     * @var string[]
     */
    protected array $ignored = [];

    /**
     * Get the ignored attributes for the model.
     *
     * @return string[]
     */
    public function getIgnored(): array
    {
        return $this->ignored;
    }

    /**
     * Set the ignored attributes for the model.
     *
     * @param string[] $ignored
     */
    public function setIgnored(array $ignored): self
    {
        $this->ignored = $ignored;

        return $this;
    }

    /**
     * Ignore a field.
     *
     * @param string[]|string $field
     */
    public function addIgnored(array|string $field): self
    {
        is_array($field)
            ? $this->setIgnored([...$this->getIgnored(), ...$field])
            : $this->setIgnored([...$this->getIgnored(), $field]);

        return $this;
    }

    /**
     * Get history of this model.
     *
     * @return MorphMany<Change, $this>
     */
    public function history(): MorphMany
    {
        return $this->morphMany(Change::class, 'model');
    }

    /**
     * Check if model has history.
     */
    public function getHasHistoryAttribute(): bool
    {
        return (bool) $this->history->count();
    }

    /**
     * Add change to model's history.
     *
     * @return $this|false
     */
    public function addChange(Change $change): Model|false
    {
        return $this->history()->save($change);
    }

    /**
     * Get recently made changes to the current model.
     *
     * @SuppressWarnings("unused")
     */
    public function getHistoryChanges(): Collection
    {
        return collect($this->getDirty())->reject(function ($newValue, string $key) {
            return in_array($key, $this->getIgnored());
        })->reduce(function (Collection $carry, $newValue, string $key) {
            $original = $carry->get('original', $this->newInstance());
            $updated = $carry->get('updated', $this->newInstance());
            // Use getOriginal() and getAttribute() to ensure that
            // values are transformed by the default accessors
            $original->setAttribute($key, $this->getOriginal($key));
            $updated->setAttribute($key, $this->getAttribute($key));

            return collect(['original' => $original, 'updated' => $updated]);
        }, collect());
    }

    /**
     * Get changes when model is soft deleted.
     */
    public function getSoftDeletedChanges(): Collection
    {
        $original = $this->newInstance();
        $updated = $this->newInstance();
        // The original value of "deleted_at" is this same as the updated value,
        // so we hard-code the original to null during a deleted event.
        $original->setAttribute($this->getDeletedAtColumn(), null);
        $updated->setAttribute($this->getDeletedAtColumn(), $this->getAttribute($this->getDeletedAtColumn()));

        return collect(['original' => $original, 'updated' =>  $updated]);
    }

    /**
     * Check if model uses soft deleting.
     */
    public function getSoftDeletingAttribute(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this));
    }
}
