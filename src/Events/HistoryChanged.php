<?php

namespace audunru\ModelHistory\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class HistoryChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @SuppressWarnings("unused")
     */
    public function __construct(
        /*
         * The changes to be recorded in history.
         */
        public Collection $changes,
        /*
         * The changed model.
         */
        public Model $model,
        /*
         * The model that made the change.
         */
        public Model $owner
    ) {
    }
}
