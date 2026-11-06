<?php

namespace audunru\ModelHistory\Tests\Models;

class ProductWithHiddenAttribute extends Product
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ['seller_phone'];
}
