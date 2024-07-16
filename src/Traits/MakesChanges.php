<?php

namespace audunru\ModelHistory\Traits;

use audunru\ModelHistory\Models\Change;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait MakesChanges
{
    /**
     * Get changes made by this owner.
     *
     * @return MorphMany<Change, $this>
     */
    public function changes(): MorphMany
    {
        return $this->morphMany(Change::class, 'owner');
    }
}
