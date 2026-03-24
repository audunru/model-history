<?php

namespace audunru\ModelHistory\Traits;

use audunru\ModelHistory\Models\Change;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
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
