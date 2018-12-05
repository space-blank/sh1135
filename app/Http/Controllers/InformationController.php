<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Category;
use App\Http\Models\City;
use App\Http\Models\InfoImg;
use App\Http\Models\Information;
use App\Http\Models\InfoTypeoptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'keyword' => 'string|max:50',
            'tel' => 'regex:/^\d{1,20}$/',
            'page' => 'int',
            'pageSize' => 'int'
        ];
        $this->validate($request, $rules);

        $keyword = $request->keyword ?: '';
        $tel = $request->tel ?: '';
        $page = (int)$request->page ?: 1;
        $pageSize = (int)$request->pageSize ?: 10;

        $query = Information::query()->where('info_level', '>', 0)->orderByDesc('id');
        $columns = [
            'id',
            'title',
            'img_path',
            'content',
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

        if($tel){
            $query->Where('tel', 'like', '%'.$tel.'%');
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
        $rules = [
            'iid' => 'required|int'
        ];
        $this->validate($request, $rules);

        $id = (int)$request->iid;
        $row = Information::select([
            'id',
            'catid',
            'title',
            'begintime',
            'hit',
            'cityid',
            'endtime',
            'img_count',
            'qq',
            'tel',
            'contact_who',
            'content',
        ])->where('id', $id)->where('info_level', '>=', 1)->first()->toArray();
        if(!$row){
            return $this->fail('30002');
        }

        $city = City::select(['cityid', 'cityname'])->where('cityid', $row['cityid'])->first();
        $row['endtime'] = get_info_life_time( $row['endtime'] );
        $row['contactview'] = $row['endtime'] == "<font color=red>已过期</font>" && config('custom.cfg_info_if_gq') != 1 ? 0 : 1;
        $rowr = Category::select(['catid', 'parentid', 'catname', 'template_info', 'modid', 'usecoin'])->find($row['catid']);

        $row['catid'] = $rowr['catid'];
        $row['parentid'] = $rowr['parentid'];
        $row['catname'] = $rowr['catname'];
        $row['template_info'] = $rowr['template_info'];
        $row['modid'] = $rowr['modid'];
        $row['usecoin'] = $rowr['usecoin'];
        $row['image'] = 0 < $row['img_count'] ? InfoImg::select(['prepath', 'path'])->where('infoid', $id)->orderByDesc('id')->get() : false;

        if ( 1 < $rowr['modid'] ) {
            $extr = DB::table('information_'.$rowr['modid'])->where('id', $id)->first();
            if ( $extr ) {
                $des = [];
                $infoOptions = InfoTypeoptions::select(['title', 'identifier', 'rules', 'type'])->where('classid', '>', 0)->orderByDesc('displayorder')->get();

                foreach ($infoOptions as $infoOption){
                    $des[$infoOption['identifier']]['title'] = $infoOption['title'];
                    $des[$infoOption['identifier']]['rules'] = $infoOption['rules'];
                    $des[$infoOption['identifier']]['type']  = $infoOption['type'];
                }
                unset( $extr['iid'] );
                unset( $extr['id'] );
                unset( $extr['content'] );
                foreach ( $extr as $k => $v ) {
                    $val = get_info_option_titval( $des[$k], $v );
                    $arr['title'] = $val['title'];
                    $arr['value'] = $val['value'];
                    $row['extra'][] = $arr;
//                    $row[$k] = $v;
                }
                $des = NULL;
            }
        }

//        $relevant = mymps_get_infos( 6, "", "", "", $row['catid'], "", "", "", false );
        $parentcats = Category::getParentsByNode($row['catid']);
        //推荐信息
        $query = Category::from('category as c')->select([
            'c.catid',
            'c.modid',
            'c.dir_typename',
            'c.catname',
            'c.usecoin',
            'c.parentid',
            'c.if_view',
            'c.catorder',
            'c.template_info',
            DB::raw('COUNT( my_s.catid ) AS has_children')
        ])->leftjoin('category as s', 's.parentid', '=', 'c.catid')->groupBy('c.catid')
            ->orderBy('c.parentid')->orderBy('c.catorder')->first();

        $recommends = Information::select('id')->where('catid', 1)
            ->skip(0)->take(6)->orderByDesc('begintime')->get();

        return $this->success([
            'site_name' => config('custom.SiteName'),
            'breadcrumbs' => $parentcats,
            'detail' => $row,
            'city' => $city
        ]);
    }
}
