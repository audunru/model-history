<?php

namespace audunru\ModelHistory\Traits;

use audunru\ModelHistory\Models\Change;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait MakesChanges
{
    /**
     * Get changes made by this owner.
     */
    public function changes(): MorphMany
    {
        return $this->morphMany(Change::class, 'owner');
    }
}
