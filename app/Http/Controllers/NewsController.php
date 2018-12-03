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
     * 获取新闻列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNews(Request $request){
        $rules = [
            'catid' => 'int',
            'page' => 'int',
            'pageSize' => 'int'
        ];
        $this->validate($request, $rules);

        $catid = (int)$request->catid ?: 0;
        $page = (int)$request->page ?: 1;
        $pageSize = (int)$request->pageSize ?: 10;

        $channel = Channel::select(['catid', 'catname'])->where([
            'parentid' => 0,
            'if_view' => 2,
        ])->orderBy('catorder')->get();

        $channel->prepend([
            'catid' => 0,
            'catname' => '最新'
        ]);

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
                $item['imgpath'] = env('APP_URL').$item['imgpath'];
                $item['begintime'] = date('m-d', $item['begintime']);
                return $item;
            }
        );

        return $this->success([
            'channel' => $channel,
            'response' => $result,
        ]);
    }

    /**
     * 获取新闻详情
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(Request $request){
        $rules = [
            'nid' => 'required|int'
        ];
        $this->validate($request, $rules);
        $nid = (int)$request->nid;

        $result = News::where('id', $nid)->first();

        if($result){
            $result['begintime'] = date('Y-m-d H:i:s', $result['begintime']);
            $result['imgpath'] = env('APP_URL').$result['imgpath'];
            News::where('id', $nid)->increment('hit');
            $channel = Channel::select(['catid', 'catname'])->where('catid', $result['catid'])->first();
            $breadcrumbs= [
                ['catid'=>0, 'catname' => '新闻资讯'],
                $channel
            ];
            return $this->success([
                'breadcrumbs' => $breadcrumbs,
                'info' => $result
            ]);
        }

        return $this->fail('300', [], '该新闻不存在！');
    }
}
