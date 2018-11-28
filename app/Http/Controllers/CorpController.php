<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Channel;
use App\Http\Models\Corp;
use App\Http\Models\News;
use Illuminate\Http\Request;

class CorpController extends Controller
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
    public function getCorp(Request $request){
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

    public function getDetail(Request $request){
        $id = $request->nid;
        $result = News::where('id', $id)->first();

        return $this->success($result);
    }
}
