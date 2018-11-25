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

    protected $fillable = [
//        self::FIELD_ID,
//        self::FIELD_ID_USER,
//        self::FIELD_ID_FRIEND,
//        self::FIELD_ID_FRIEND_GROUP,
//        self::FIELD_NICKNAME,
//        self::FIELD_TYPE,
//        self::FIELD_STATUS
    ];

//    public function user()
//    {
//        return $this->belongsTo(User::class,self::FIELD_ID_USER);
//    }
//
//    public function friend()
//    {
//        return $this->belongsTo(User::class,self::FIELD_ID_FRIEND)->select(User::FIELD_ID,User::FIELD_NICKNAME,User::FIELD_AVATAR);
//    }
}
