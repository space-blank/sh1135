<?php

namespace App\Http\Models;


class Favor extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shoucang';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;


    /**
     * 获取对应的用户
     */
    public function user()
    {
        return $this->belongsTo(Member::class, 'userid', 'userid')->select();
    }

    /**
     * 获取对应的分类
     */
    public function information()
    {
        return $this->belongsTo(Information::class, 'infoid', 'id')->select();
    }

}
