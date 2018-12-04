<?php

namespace App\Http\Models;


class Member extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * 获取用户的收藏
     */
    public function favor()
    {
        return $this->hasMany(Favor::class, 'userid', 'userid')->select('id as fid', 'title', 'intime');
    }
}
