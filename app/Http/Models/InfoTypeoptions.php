<?php

namespace App\Http\Models;


class InfoTypeoptions extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'info_typeoptions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'optionid';

    public $timestamps = false;

}
