<?php

namespace App\Http\Models;


class Area extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'area';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'areaid';

    public $timestamps = false;

}
