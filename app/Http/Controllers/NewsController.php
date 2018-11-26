<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Channel;
use App\Http\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
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
     * 获取网站新闻频道
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChannel(Request $request){
        $channel = Channel::select(['catid', 'catname'])->where([
            'parentid' => 0,
            'if_view' => 2,
        ])->orderBy('catorder')->get();
        return $this->success($channel);
    }

    /**
     * 获取新闻列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNews(Request $request){
        $catid = $request->catid ?: 0;
        $page = $request->page ?: 1;
        $pageSize = $request->pageSize ?: 10;

        $query = News::select([
            'id',
            'title',
            'imgpath',
            'begintime',
            'introduction'
        ])->orderByDesc('id');

        if($catid){
            $query = $query->where('catid', $catid);
        }

        $result = $this->paginateLogic->paginate(
            $query,
            ['page_size'=>$pageSize, 'page_number'=>$page], '*',
            function($item){
                $item['begintime'] = date('m-d', $item['begintime']);
                return $item;
            }
        );

        return $this->success($result);
    }

    public function getDetail(Request $request){
        $id = $request->nid;
        $result = News::where('id', $id)->first();

        return $this->success($result);
    }
}
