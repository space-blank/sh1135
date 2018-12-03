<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Information;
use Illuminate\Http\Request;

class InformationController extends Controller
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
     * 获取信息列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInformation(Request $request){
        $rules = [
            'keyword' => 'string|max:50'
        ];
        $this->validate($request, $rules);

        $keyword = $request->keyword ?: '';
        $page = (int)$request->page ?: 1;
        $pageSize = (int)$request->pageSize ?: 10;

        $query = Information::query()->where('info_level', '>', 0)->orderByDesc('id');
        $columns = [
            'id',
            'title',
            'img_path',
            'content',
            'userid',
            'userid',
            'contact_who',
            'hit',
            'begintime'
        ];

        if($keyword){
            $query->where(function ($query) use ($keyword) {
                $query->Where('title', 'like', '%'.$keyword.'%');
                $query->orWhere('content', 'like', '%'.$keyword.'%');
            });
        }

        $result = $this->paginateLogic->paginate(
            $query,
            ['page_size'=>$pageSize, 'page_number'=>$page], $columns,
            function($item) use ($keyword){
                $item['title'] = HighLight($item['title'], $keyword);;
                $item['content'] = mb_substr($item['content'], 0, 50);
                $item['begintime'] = date('y-m-d', $item['begintime']);
                return $item;
            }
        );

        return $this->success($result);
    }

    /**
     * 获取信息详情
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(Request $request){
        $id = $request->iid ?: 87727;
        $result = Information::where('id', $id)->where('info_level', '>=', 1)->first();

        return $this->success($result);
    }
}
