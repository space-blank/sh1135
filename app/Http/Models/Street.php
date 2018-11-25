<?php

namespace App\Http\Models;


class Street extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'street';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'streetid';

    public $timestamps = false;

}
