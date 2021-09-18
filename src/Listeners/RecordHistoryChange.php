<?php

namespace audunru\ModelHistory\Listeners;

use audunru\ModelHistory\Events\HistoryChanged;
use audunru\ModelHistory\Models\Change;

class RecordHistoryChange
{
    /**
     * Handle the event.
     */
    public function handle(HistoryChanged $event): void
    {
        $event->model->addChange(new Change([
            'changes'    => $event->changes,
            'owner_id'   => $event->owner->id,
            'owner_type' => get_class($event->owner),
        ]));
    }
}
