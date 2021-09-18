<?php

namespace audunru\ModelHistory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class History extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @SuppressWarnings("unused")
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $dateFormat = config('model-history.date_format', 'Y-m-d H:i:s');

        return [
            'id'          => $this->id,
            'changes'     => $this->changes,
            'owner'       => [
                'id'    => $this->owner->id,
                'name'  => $this->owner->name,
                'email' => $this->owner->email,
            ],
            'created_at' => $this->created_at->format($dateFormat),
            'updated_at' => $this->updated_at->format($dateFormat),
        ];
    }
}
