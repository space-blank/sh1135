<?php

namespace App\Http\Models;


class Information extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'information';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * 获取对应的分类
     */
    public function category()
    {
        return $this->belongsTo(Information::class, 'catid', 'catid ')->select();
    }

}
