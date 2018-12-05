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

    public $fillable = [
        'title',
        'content',
        'begintime',
        'activetime',
        'endtime',
        'catid',
        'catname',
        'dir_typename',
        'cityid',
        'areaid',
        'streetid',
        'userid',
        'ismember',
        'info_level',
        'qq',
        'email',
        'tel',
        'contact_who',
        'img_count',
        'certify',
        'ip',
        'ip2area',
        'latitude',
        'longitude',
        'zhuangrang',
        'danwei'
    ];

    /**
     * 获取用户的收藏
     */
    public function favor()
    {
        return $this->hasMany(Favor::class, 'userid', 'userid')->select('id as fid', 'title', 'intime');
    }
}
