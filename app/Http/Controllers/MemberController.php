<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Area;
use App\Http\Models\Category;
use App\Http\Models\Channel;
use App\Http\Models\Corp;
use App\Http\Models\Favor;
use App\Http\Models\Information;
use App\Http\Models\Member;
use App\Http\Models\News;
use App\Http\Models\Street;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

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
     * 我的发布
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myPublish(Request $request){
        $rules = [
            'uid' => 'required|string',
            'page' => 'int',
            'pageSize' => 'int'
        ];
        $this->validate($request, $rules);

        $uid = $request->uid;
        $page = (int)$request->page ?: 1;
        $pageSize = (int)$request->pageSize ?: 10;


        $query = Information::query()->where('info_level', '>', 0)
            ->where('userid', $uid)->orderByDesc('id');
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

        $result = $this->paginateLogic->paginate(
            $query,
            ['page_size'=>$pageSize, 'page_number'=>$page], $columns,
            function($item){
                $item['content'] = mb_substr($item['content'], 0, 50);
                $item['begintime'] = date('y-m-d', $item['begintime']);
                return $item;
            }
        );

        return $this->success($result);
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

    /**
     * 发布门铺页面
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postStore(Request $request){
        $rules = [
            'uid' => 'required|string',
            'catid' => 'required|int',
            'areaid' => 'required|int',
            'streetid' => 'required|int',
        ];
        $this->validate($request, $rules);

        $uid = $request->uid;
        $catId = (int)$request->catid;
        $areaid = (int)$request->areaid;
        $streetid = (int)$request->streetid;

        $userInfo = Member::select(['qq', 'email', 'mobile', 'cname'])->where('userid', $uid)->first();
        $area = Area::where('areaid', $areaid)->value('areaname');
        $street = Street::where('streetid', $streetid)->value('streetname');
        $catInfo = Category::select(['catid', 'catname', 'parentid', 'modid', 'if_upimg'])->where('catid', $catId)->first();



        if($catInfo && $userInfo && $street && $area){
            $categories = Category::select(['catid', 'catname'])->where('parentid', $catId)->get();
            $parentname = Category::where('catid', $catInfo['parentid'])->value('catname');
            if($categories){
                $config = Category::CategoryInfoOptions($catInfo['modid']);
                if($parentname == '商铺转让'){
                    array_push($config, [
                        'title' => '转让费',
                        'identifier' => 'zhuanrang',
                        'value' => '万元',
                    ]);
                }
                //是否上传图片
                $upImg = $catInfo['if_upimg'] == 1 ? 1 : 0;

                return $this->success([
                    'category' => $categories,
                    'street' => $street,
                    'form' => $config,
                    'upImage' => $upImg
                ]);
            }
        }

        return $this->fail('30002');
    }

    /**
     * 发布门铺操作
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addStore(Request $request){
        $imageMaxSize = config('custom.cfg_upimg_size') * 1024;
        $rules = [
            'uid' => 'required|string',
            'contents' => 'required|string',
            'mymps_img' => 'image|max:'.$imageMaxSize,
            'title' => 'required|string',
            'contact_who' => 'required|string',
            'tel' => 'required|regex:/^1\d{10}$/',
            'qq' => 'regex:/^[1-9]\d{3,20}$/',
            'cityid' => 'required|int',
            'catid' => 'required|int',
            'areaid' => 'required|int',
            'streetid' => 'required|int',
        ];
        $this->validate($request, $rules);

        $uid = $request->uid;
        $cityid = (int)$request->cityid;
        $catId = (int)$request->catid;
        $areaid = (int)$request->areaid;
        $streetid = (int)$request->streetid;
        $content = $request->contents;
        $title = $request->title;
        $extra = $request->extra;


        $content = $content ? textarea_post_change($content) : "";
        $result = verify_badwords_filter(0 , $title, $content);
        $title = $result['title'];
        $content = $result['content'];
        $content = preg_replace( "/<a[^>]+>(.+?)<\\/a>/i", "$1", $content );
        $info_level = $result['level'];
        $manage_pwd = $request->manage_pwd ? trim($request->manage_pwd) : "";
        $lat = $request->lat ? ( double )$request->lat : "";
        $lng = $request->lng ? ( double )$request->lng : "";
        $activetime = $endtime = intval( $request->endtime );
        $begintime = intval( $request->begintime );
        $endtime = $endtime == 0 ? 0 : $endtime * 3600 * 24 + $begintime;
        $d = Category::select(['catname', 'dir_typename', 'modid'])->where('catid', $catId)->first();
        $userInfo = Member::select(['qq', 'email', 'mobile', 'cname'])->where('userid', $uid)->first();
        $area = Area::where('areaid', $areaid)->value('areaname');
        $street = Street::where('streetid', $streetid)->value('streetname');


        if($d && $userInfo && $area && $street){
            $catname = $d['catname'];
            $dir_typename = $d['dir_typename'];
            $backurl = true;

            if($uid){
                $row = Member::select(['per_certify', 'com_certify'])->where('userid', $uid)->first();
                if( $row['per_certify'] == 1 || $row['com_certify'] == 1 ){
                    $certify = 1;
                }else{
                    $certify = 0;
                }
                unset( $row );
            }
            $timestamp = time();
            $file = $request->file('mymps_img');

            $ip = getip( );
            $img_count = 1;
            $model = Member::create([
                'title'=> $title,
                'content'=> $content,
                'begintime'=> time(),
                'activetime'=> 0,
                'endtime'=> 0,
                'catid'=> $catId,
                'catname'=> $catname,
                'dir_typename'=> $dir_typename,
                'cityid'=> $cityid,
                'areaid'=> $areaid,
                'streetid'=> $streetid,
                'userid'=> $uid,
                'ismember'=> 1,
                'info_level'=> $info_level,
                'qq'=> $request->qq,
                'email'=> $request->email,
                'tel'=> $request->tel,
                'contact_who'=> $request->contact_who,
                'img_count'=> $img_count,
                'certify'=> $certify,
                'ip'=> $ip,
                'ip2area'=> 'wap',
                'latitude'=> $lat,
                'longitude'=> $lng,
                'zhuangrang'=> $request->zhuanrang,
                'danwei' => $request->danwei
            ]);
            $id = $model->id;
            $sql = '';
            $sql1 = '';
            $sql2 = '';
            if(is_array( $extra ) && 1 < $d['modid'] ) {
                foreach ( $extra as $k => $v ) {
                    $v = is_array( $v ) ? implode( ",", $v ) : $v;
                    $sql1 .= ",`".$k."`";
                    $sql2 .= ",'".$v."'";
                }
                $sql = "(id.".$sql1.")VALUES('{$id}','','')";
                DB::insert( "INSERT INTO `my_information_{$d['modid']}` (`id`{$sql1})VALUES('{$id}'{$sql2})" );
                unset( $sql1 );
                unset( $sql2 );
            }

            if($file->isValid()){
                $destination = "information/".date( "Ym" );
                $cfg_information_limit = config('custom.cfg_information_limit');
                //TODO 加水印
                $cfg_upimg_watermark = config('custom.cfg_upimg_watermark');
                //生成新的图片
                $newFile = $file->storeAs(
                    $destination, $timestamp.random()
                );
                //生成缩略图
                Image::make($newFile)->resize($cfg_information_limit['width'], $cfg_information_limit['height'])->save('pre_'.$newFile);
                $thumb = '';

                DB::insert( "INSERT INTO `my_info_img` (image_id,path,prepath,infoid,uptime) VALUES (0,'{$newFile}','{$thumb}','{$id}','{$timestamp}')" );
                DB::update("UPDATE `my_information` SET img_path = '{$thumb}' WHERE id = '{$id}'" );
            }
            $msg = 0 < $info_level ? "成功发布一条信息!" : "您的信息审核通过后将显示在网站上!";
            return $this->success([], $msg);
        }
        return $this->fail('30002');
    }
}
