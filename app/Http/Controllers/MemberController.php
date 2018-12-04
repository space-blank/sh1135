<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Channel;
use App\Http\Models\Corp;
use App\Http\Models\Favor;
use App\Http\Models\Member;
use App\Http\Models\News;
use App\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    protected $paginateLogic;

    /**
     * 注入分页类
     *
     * NewsController constructor.
     * @param PaginateLogic $paginateLogic]
     */
    public function __construct(PaginateLogic $paginateLogic){
        $this->paginateLogic = $paginateLogic;
    }

    /**
     * 获取列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mDel(Request $request){
        $cat_list = Corp::from('corp as a')->select([
            'a.corpid',
            'a.corpname',
            'b.corpid AS childid',
            'b.corpname AS childname'
        ])->leftjoin('corp as b', 'b.parentid', '=', 'a.corpid')
            ->orderBy('a.corporder')
            ->orderBy('a.corpid')
            ->orderBy('b.corporder')->get();

        $cat_arr = [];
        foreach ($cat_list as $row){
            $cat_arr[$row['corpid']]['corpid']    = $row['corpid'];
            $cat_arr[$row['corpid']]['corpname']  = $row['corpname'];

            if ($row['childid']) {
                $cat_arr[$row['corpid']]['children'][] = [
                    'corpid' => $row['childid'],
                    'corpname' => $row['childname']
                ];
            }
        }

        return $this->success(array_values($cat_arr));
    }

    /**
     * 我的收藏
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFavor(Request $request){
        $rules = [
            'uid' => 'required|string',
            'page' => 'int',
            'pageSize' => 'int'
        ];
        $this->validate($request, $rules);

        $uid = $request->uid;
        $page = (int)$request->page ?: 1;
        $pageSize = (int)$request->pageSize ?: 10;

        $userid = Member::select(['userid'])->where('id', $uid)->value('userid');
        if(!$userid){
            return $this->fail(30001);
        }

        $result = Favor::from('shoucang as a')->select(['a.id as fid', 'a.infoid', 'c.catname', 'a.title', 'intime'])
            ->leftjoin('information as b', 'a.infoid', '=', 'b.id')
            ->leftjoin('category  as c', 'b.catid', '=', 'c.catid')
            ->where('a.userid', $userid)
            ->skip(($page-1)*$pageSize)->take($pageSize)
            ->orderByDesc('a.id')->get();

        $result->map(function($item){
                $item['intime'] = date('Y-m-d H:i:s', $item['intime']);
                return $item;
            });

        return $this->success($result);
    }
}
