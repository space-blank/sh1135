<?php

namespace App\Http\Models;


class Channel extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'catid';

    public $timestamps = false;

}
