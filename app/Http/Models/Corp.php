<?php

namespace App\Http\Models;


class Corp extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'corp';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'corpid';

    public $timestamps = false;

}
