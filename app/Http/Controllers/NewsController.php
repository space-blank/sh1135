<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Channel;
use App\Http\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                $channel
            ];
            $relate_news = $this->mymps_get_news(8, $result['catid']);
            $newReturn = [];
            foreach ($relate_news as $r){
                $temp = [
                    'id' => $r['id'],
                    'title' => $r['title'],
                    'begintime' => date('m-d', $r['begintime']),
                ];
                $newReturn[] = $temp;
            }

            return $this->success([
                'breadcrumbs' => $breadcrumbs,
                'info' => $result,
                'recommends' => $newReturn
            ]);
        }

        return $this->fail('300', [], '该新闻不存在！');
    }

    function mymps_get_news($num=10,$catid=NULL,$ifimg=NULL,$leftjoin=NULL,$ifhot=NULL,$orderby=1,$cityid=0){
        $cat_limit  = empty($catid) ? '' : "AND a.catid IN(".$this->get_cat_children($catid,'channel').")";
        $img_limit  = !$ifimg ? '' : "AND a.imgpath != ''";
        $commend_limit = empty($ifhot) ? '' : " AND a.iscommend = '1'";
        $city_limit = "AND cityid=".$cityid;
        $orderby	= empty($orderby) ? "ORDER BY a.hit DESC" : "ORDER BY a.id DESC";
        $res = [];
        if($leftjoin){
            $query = DB::select("SELECT a.*,b.catname FROM `my_news` AS a LEFT JOIN `my_channel` AS b ON a.catid = b.catid WHERE 1 {$cat_limit} {$img_limit}{$city_limit} {$commend_limit} {$orderby} LIMIT 0,{$num}");
            foreach ($query as $row){
                $arr['id'] 			= $row['id'];
                $arr['title'] 		= $row['title'];
                $arr['iscommend']	= $row['iscommend'];
                $arr['imgpath'] 	= $row['imgpath'];
                $arr['content'] 	= clear_html($row['content']);
                $arr['begintime'] 	= $row['begintime'];
                $arr['catname']		= $row['catname'];
                $res[]      = $arr;
            }
        }else{
            $query = DB::select("SELECT a.* FROM `my_news` AS a WHERE 1 {$cat_limit} {$img_limit}{$city_limit} {$orderby} LIMIT 0,{$num}");
            foreach ($query as $row){
                $arr['id'] 			= $row['id'];
                $arr['title_bold'] 		= $row['isbold'] == 1 ? '<strong>'.$row['title'].'</strong>' : $row['title'];
                $arr['title'] 		= $row['title'];
                $arr['iscommend']	= $row['iscommend'];
                $arr['imgpath'] 	= $row['imgpath'];
                $arr['content'] 	= clear_html($row['content']);
                $arr['begintime'] 	= $row['begintime'];
                $res[]      = $arr;
            }
        }
        return $res;
    }

    function get_cat_children($catid, $type = 'category')
    {
        if($rows = DB::select("SELECT catid FROM `my_".$type."` WHERE parentid = '$catid'")){
            $cat = [];
            foreach ($rows as $k => $v){
                $cat[$v['catid']] = $v['catid'];
            }
            $cats = implode(',', $cat).','.$catid;
            return $cats;
        }else{
            return $catid;
        }
    }
}
