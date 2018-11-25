<?php

namespace App\Http\Models;


class Category extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'catid';

    public $timestamps = false;

}
