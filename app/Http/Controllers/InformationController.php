<?php

namespace App\Http\Controllers;

use App\Http\Logic\PaginateLogic;
use App\Http\Models\Area;
use App\Http\Models\Category;
use App\Http\Models\City;
use App\Http\Models\InfoImg;
use App\Http\Models\Information;
use App\Http\Models\InfoTypemodels;
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
     * 分类搜索结果
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListInCate(Request $request){
        $rules = [
            'catid' => 'required|int',
            'cityid' => 'required|int',
            'page' => 'int',
            'pageSize' => 'int'
        ];
        $this->validate($request, $rules);

        $catid = $request->catid ?: '';
        $cityid = $request->cityid ?: '';
        $page = (int)$request->page ?: 1;
        $pageSize = (int)$request->pageSize ?: 10;
        //导航
        $breadCrumbs = Category::getParentsByNode($catid);
        $cat = Category::select([
            'catid',
            'catname',
            'parentid',
            'modid'
        ])->where('catid', $catid)->first();
        $db_mymps = 'my_';
        //city
        $city = City::select(['cityid', 'cityname'])->find($cityid);

        if(!$cat){
            return $this->fail('30002');
        }
        //分类条件
        $catelist = Category::select([
            'catid',
            'catname'
        ])->where('parentid', $cat['catid'])->get();
        $conditions = [];
        if($catelist->isEmpty()){
            $catelist = Category::select([
                'catid',
                'catname'
            ])->where('parentid', $cat['parentid'])->get();
        }
        $conditions['category'] = $catelist;
        //地域条件
        $area = Area::select(['areaid', 'areaname'])->where('cityid', $cityid)->get();
        $conditions['area'] = $area;
        //动态的条件
        /*** start ***/
        $modid = $cat['modid'];
        $conditionConfig = Category::getConfig($modid);
        $allow_identifier = $conditionConfig[0];
        $allow_identifiers = array_merge(["mod", "catid", "cityid", "areaid", "streetid", "lat", "lng", "distance"], $allow_identifier);
        $mymps_extra_model = $conditionConfig[1];
        $conditions['others'] = array_values($mymps_extra_model);
        /*** end ***/
        //完全复制原来的逻辑
        DB::connection()->enableQueryLog();
        $sq = $s = "";
        if ( 1 < $cat['modid'] )
        {
            $s = "LEFT JOIN `".$db_mymps."information_{$cat['modid']}` AS g ON a.id = g.id";
            foreach ( $_GET as $key => $val )
            {
                if ( !in_array( $key, $allow_identifier ) || empty( $key ) )
                {
                    //$sq .= " AND g.`".$key."` = '{$val}' ";
                }
            }
        }

        $cate_limit = " AND ".$this->get_children( $catid );

        $lat = isset( $lat ) ? ( double )$lat : "";
        $lng = isset( $lng ) ? ( double )$lng : "";
        $distance = isset( $distance ) ? ( double )$distance : "";;
        $distance = !in_array( $distance, array( "0.5", "1", "3", "5" ) ) ? "0" : $distance;

        $city_limit = empty( $city['cityid'] ) ? "" : " AND a.cityid = '".$city['cityid']."'";
        if ( $distance )
        {
            $city_limit .= " AND latitude < '".( $lat + $distance )."' AND latitude > '".( $lat - $distance )."' AND longitude < '".( $lng + $distance )."' AND longitude > '".( $lng - $distance )."'";
        }
        else
        {
            $city_limit .= empty( $areaid ) ? "" : " AND a.areaid = '".$areaid."'";
            $city_limit .= empty( $streetid ) ? "" : " AND a.streetid = '".$streetid."'";
        }

        $orderby = $cat['parentid'] == 0 ? " ORDER BY a.upgrade_type DESC,a.begintime DESC" : " ORDER BY a.upgrade_type_list DESC,a.begintime DESC";

        $countTmp = DB::select("SELECT COUNT(a.id) as totalnum FROM `".$db_mymps."information` AS a {$s} WHERE a.info_level > 0 {$sq}{$cate_limit}{$city_limit}");
        if($countTmp){
            $countTmp = $countTmp[0]['totalnum'];
        }else{
            $countTmp = 0;
        }
        $rows_num = $cat['totalnum'] = $countTmp;

        $totalpage = ceil( $rows_num / $pageSize );

        $num = intval( $page - 1 ) * $pageSize;
        $idin = $this->get_page_idin( "id", "SELECT a.id FROM `".$db_mymps."information` AS a {$s} WHERE (a.info_level = 1 OR a.info_level = 2) {$sq}{$cate_limit}{$city_limit}{$orderby}", $pageSize);
        $idin = $idin ? " AND a.id IN (".$idin.") " : "";
        $sql = "SELECT a.* FROM ".$db_mymps."information AS a WHERE 1 {$idin} {$orderby}";

        $infolist = $idin ? DB::select( $sql ) : array( );
        if($infolist){
            $ids = '';
            foreach ( $infolist as $k => $row )
            {
                $arr['areaname'] = Area::select(['areaname'])->where('areaid', $row['areaid'])->value('areaname');
                $arr['id'] = $row['id'];
                $arr['title'] = $row['title'];
                $arr['hit'] = $row['hit'];
                $arr['img_path'] = $row['img_path'];
                $arr['ifred'] = $row['ifred'];
                $arr['ifbold'] = $row['ifbold'];
                $arr['img_count'] = $row['img_count'];
                $arr['upgrade_type'] = !$cat['parentid'] ? $row['upgrade_type'] : $row['upgrade_type_list'];
                $arr['contact_who'] = $row['contact_who'];
                $arr['begintime'] = $row['begintime'];
                $arr['danwei'] = $row['danwei'];
                $arr['zhuanrang'] = $row['zhuangrang'];
                $arr['catname'] = $row['catname'];
                $info_list[$row['id']] = $arr;
                $ids .= $row['id'].",";
            }
            if ( 1 < $cat['modid'] || $idin )
            {
                $des = $this->get_info_option_array();
                $extra = DB::select( "SELECT a.* FROM `".$db_mymps."information_{$cat['modid']}` AS a WHERE 1 {$idin}" );
                foreach ( $extra as $k => $v )
                {
                    unset( $v['iid'] );
                    unset( $v['content'] );

                    foreach ( $v as $u => $w )
                    {
                        if($u == 'id') continue;
                        $g = get_info_option_titval( $des[$u], $w );
                        $info_list[$v['id']]['extra'][$u] = $g;
                        /*if(!$g){
                            if($g['title']=='面积'){
                                $g['value'] = '<span style="color:red;font-size:16px;">'.str_replace('平方','',$g['value'])."</span>平方";
                            }
                            if($g['title']=='价格'){
                                $g['value'] = str_replace('元','',$g['value']);
                            }
                            $info_list[$v['id']]['extra'][$u] = $g;
                        }*/
                    }
                }
            }
        }
        $log = DB::getQueryLog();
//        print_r($log);die;
        return $this->success([
            'breadcrumbs' => $breadCrumbs,
            'conditions' => $conditions,
            'info' => array_values($info_list)
        ]);
    }

    /**
     * 获取信息详情
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(Request $request){
        $rules = [
            'iid' => 'required|int',
            'cityid' => 'int'
        ];
        $this->validate($request, $rules);

        $id = (int)$request->iid;
        $cityId = (int)$request->cityid;
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

//        $city = City::select(['cityid', 'cityname'])->where('cityid', $row['cityid'])->first();
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
//        $query = Category::from('category as c')->select([
//            'c.catid',
//            'c.modid',
//            'c.dir_typename',
//            'c.catname',
//            'c.usecoin',
//            'c.parentid',
//            'c.if_view',
//            'c.catorder',
//            'c.template_info',
//            DB::raw('COUNT( my_s.catid ) AS has_children')
//        ])->leftjoin('category as s', 's.parentid', '=', 'c.catid')->groupBy('c.catid')
//            ->orderBy('c.parentid')->orderBy('c.catorder')->first();
//
//        $recommends = Information::select('id')->where('catid', 1)
//            ->skip(0)->take(6)->orderByDesc('begintime')->get();
        $recommends = $this->mymps_get_infos( 6, "", "", "", $row['catid'], "", "", "", $cityId ?: false );;
        $newRec = [];
        foreach ($recommends as $r){
            $temp = [
                'id' => $r['id'],
                'title' => cutstr($r['title'],26),
                'begintime' => get_format_time($r['begintime'])
            ];
            $newRec[] = $temp;
        }
        return $this->success([
            'site_name' => config('custom.SiteName'),
            'breadcrumbs' => $parentcats,
            'detail' => $row,
//            'city' => $city,
            'recommends' => $newRec
        ]);
    }

    function get_info_option_array(){
        $infoOptions = InfoTypeoptions::select(['title', 'identifier', 'rules', 'type'])
            ->where('classid', '>', 0)->orderByDesc('displayorder')->get();
        $mymps = [];
        foreach ($infoOptions as $row){
            $mymps[$row['identifier']]['title'] = $row['title'];
            $mymps[$row['identifier']]['rules'] = $row['rules'];
            $mymps[$row['identifier']]['type'] = $row['type'];
        }
        return $mymps;
    }
    /**
     * 获得各模型允许提交的参数
     *
     * @return bool|mixed
     */
    function allow_identifier(){
        $query 	= $GLOBALS['db'] -> query("SELECT id,options  FROM `{$GLOBALS['db_mymps']}info_typemodels` ORDER BY displayorder DESC");
        $query = InfoTypemodels::select(['id', 'options'])->orderByDesc('displayorder')->get();
//        while($row = $GLOBALS['db'] -> fetchRow($query)){
        foreach ($query as $row){
            $option = explode(",",$row[options]);
            foreach($option as $w => $u){
                $newrow = $GLOBALS['db'] -> getRow("SELECT identifier,search FROM `{$GLOBALS['db_mymps']}info_typeoptions` WHERE optionid='$u'");

                if($newrow['search']=='on'){
                    $arr[$row[id]]['identifier'][] = $newrow['identifier'];
                }
            }
            $res = $arr;
        }
        return $res;
    }

    /**
     * copy原来的逻辑
     *
     * @param int $num
     * @param null $info_level
     * @param null $upgrade_type
     * @param null $userid
     * @param null $catid
     * @param null $certify
     * @param null $if_hot
     * @param null $tel
     * @param null $cityid
     * @return array
     */
    protected function mymps_get_infos($num=10,$info_level=NULL,$upgrade_type=NULL,$userid=NULL,$catid=NULL,$certify=NULL,$if_hot=NULL,$tel=NULL,$cityid=NULL){
        $db_mymps = 'my_';
        $where = '';
        $where .= !$info_level ? 'WHERE (a.info_level =1 OR a.info_level = 2)':'WHERE a.info_level = '.$info_level;
        $where .= $userid	? ' AND a.userid = "'.$userid.'" ' : '';
        $where .= $certify	? ' AND a.certify = "'.$certify.'" ' : '';
        $where .= $tel		? ' AND a.tel = "'.$tel.'" ' : '';
        $where .= $catid	? ' AND '.$this->get_children($catid,'a.catid') : '';
        $where .= $cityid > 0 ? ' AND a.cityid = "'.$cityid.'" ' : '';

        if($upgrade_type == 1){
            $where .= " AND a.upgrade_type_list = 2 ";
        } elseif($upgrade_type == 2){
            $where .= " AND a.upgrade_type = 2 ";
        } elseif($upgrade_type == 3){
            $where .= " AND a.upgrade_type_index = 2 ";
        }

        $where .= !empty($sql) ? $sql	: '';
        $orderby = $if_hot ? " ORDER BY a.hit DESC " : " ORDER BY a.begintime DESC ";
        $info_list = array();
        $idin = $this->get_page_idin("id","SELECT a.id FROM `{$db_mymps}information` AS a {$where} {$orderby}",$num);

        if($idin){
            $sql = "SELECT a.id,a.contact_who,a.danwei,a.title,a.content,a.begintime,a.catid,a.info_level,a.hit,a.dir_typename,a.ifred,a.ifbold,a.userid,a.catid,a.cityid,a.catname,a.img_path FROM `{$db_mymps}information` AS a WHERE id IN ($idin) {$orderby}";
            $do_mymps = DB::select($sql);
//            while($row = $db -> fetchRow($do_mymps)){
            foreach ($do_mymps as $row){
                $arr['id']        = $row['id'];
                $arr['danwei']    = $row['danwei'];
                $arr['catid']     = $row['catid'];
                $arr['title']     = $row['title'];
                $arr['content']   = clear_html($row['content']);
                $arr['ifred']     = $row['ifred'];
                $arr['ifbold']    = $row['ifbold'];
                $arr['hit']    	  = $row['hit'];
                $arr['begintime'] = $row['begintime'];
                $arr['img_path']  = $row['img_path'];
                $arr['catname']   = $row['catname'];
                $arr['info_level']= $row['info_level'];
                $arr['userid']    = $row['userid'];
                $arr['contact_who']= $row['contact_who'];
                $info_list[]      = $arr;
            }
        }
        return $info_list;
    }

    public function get_page_idin($column='id',$sql='',$cfg_page=''){
        global $page,$per_page,$per_screen,$pages_num,$rows_num,$mymps_global,$db,$db_mymps;
        $page = (empty($page) || $page <0 ||!is_numeric($page))?1:$page;
        $per_page = $cfg_page ? $cfg_page : ($per_page ? $per_page : config('custom.cfg_page_line'));
        $per_screen = !isset($per_screen)?10:$per_screen;
        $pages_num = ceil($rows_num/$per_page);

        $query = DB::select($sql." limit ".(($page-1)*$per_page).", ".$per_page);
        $idin = '';
//        while($row = $db -> fetchRow($query)){
        foreach ($query as $row){
            $idin .= $row[$column].',';
        }
        $idin = $idin ? substr($idin,0,-1) : NULL;
        return $idin;
    }

    protected function get_children($catid,$pre='a.catid'){
        return $this->create_in(array_unique(array_merge(array($catid), array_keys($this->cat_list('category',$catid,0, false)))),$pre);
    }
    /**
     * 获得指定分类下的子分类的数组
     *
     * @access  public
     * @param   int     $catid     分类的ID
     * @param   int     $selected   当前选中分类的ID
     * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param   int     $level      限定返回的级数。为0时返回所有级数
     * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
     * @return  mix
     */
    protected function cat_list($type = 'category',$catid = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true){
        $db_mymps = 'my_';
        if(in_array($type,array('area','corp'))){
            $sql = "SELECT c.".$type."id, c.".$type."name, c.parentid, c.".$type."order, COUNT(s.".$type."id) AS has_children FROM `{$db_mymps}".$type."` AS c LEFT JOIN `{$db_mymps}".$type."` AS s ON s.parentid=c.".$type."id GROUP BY c.".$type."id ORDER BY c.parentid, c.".$type."order ASC";
        }elseif($type == 'category') {
            $sql = "SELECT c.catid, c.modid, c.dir_typename, c.dir_typename, c.catname,c.usecoin, c.parentid, c.if_view, c.catorder, c.template_info, COUNT(s.catid) AS has_children FROM `{$db_mymps}".$type."` AS c LEFT JOIN `{$db_mymps}".$type."` AS s ON s.parentid=c.catid GROUP BY c.catid ORDER BY c.parentid, c.catorder ASC";
        }else {
            $sql = "SELECT c.catid, c.dir_typename, c.dir_typename, c.catname, c.parentid, c.if_view, c.catorder, COUNT(s.catid) AS has_children FROM `{$db_mymps}".$type."` AS c LEFT JOIN `{$db_mymps}".$type."` AS s ON s.parentid=c.catid GROUP BY c.catid ORDER BY c.parentid, c.catorder ASC";
        }

        $res = DB::select($sql);
        $sql = NULL;

        if (empty($res) == true){
            return $re_type ? '' : array();
        }

        $options = $this->cat_options($type, $catid, $res); // 获得指定分类下的子分类的数组

        $children_level = 99999; //大于这个分类的将被删除
        if ($is_show_all == false){
            foreach ($options as $key => $val){
                if ($val['level'] > $children_level){
                    unset($options[$key]);
                }else{
                    if ($val['is_show'] == 0){
                        unset($options[$key]);
                        if ($children_level > $val['level']){
                            $children_level = $val['level']; //标记一下，这样子分类也能删除
                        }
                    }else{
                        $children_level = 99999; //恢复初始值
                    }
                }
            }
        }

        /* 截取到指定的缩减级别 */
        if ($level > 0){
            if ($catid == 0){
                $end_level = $level;
            }else{
                $first_item = reset($options); // 获取第一个元素
                $end_level  = $first_item['level'] + $level;
            }

            /* 保留level小于end_level的部分 */
            foreach ($options AS $key => $val){
                if ($val['level'] >= $end_level){
                    unset($options[$key]);
                }
            }
        }

        /****************/
        /*如果为地区分类或商家分类*/
        /****************/
        if(in_array($type,array('area','corp'))){
            if ($re_type == true){
                $select = '';
                if(is_array($options)){
                    foreach ($options AS $var){
                        $select .= '<option value="' . $var[$type.'id'] . '" ';
                        if(is_array($selected)){
                            $select .= in_array($var[$type.'id'],$selected) ? "selected='ture' style='background-color:#6eb00c; color:white!important;'" : '';
                        } else {
                            $select .= ($selected == $var[$type.'id']) ? "selected='ture' style='background-color:#6eb00c; color:white!important;'" : '';
                        }
                        $select .= '>';
                        if ($var['level'] > 0){
                            $select .= str_repeat('&nbsp;', $var['level'] * 4);
                        }
                        $select .= '└ '.mhtmlspecialchars($var[$type.'name'], ENT_QUOTES) . '</option>';
                    }
                }

                return $select;
            }else{
                if(is_array($options)){
                    foreach ($options AS $key => $value){
                        $options[$key]['url'] = $value[$type.'id'];
                    }
                }
                return $options;
            }

            /****************/
            /*如果为信息栏目或新闻栏目分类*/
            /****************/
        } else {
            if ($re_type == true){
                $select = '';
                foreach ($options AS $var){
                    $select .= '<option value="' . $var['catid'] . '" ';
                    if(is_array($selected)){
                        $select .= in_array($var['catid'],$selected) ? "selected='ture' style='background-color:#6eb00c; color:white!important;'" : '';
                    } else {
                        $select .= ($selected == $var['catid']) ? "selected='ture' style='background-color:#6eb00c; color:white!important;'" : '';
                    }
                    $select .= '>';
                    if ($var['level'] > 0){
                        $select .= str_repeat('&nbsp;', $var['level'] * 4);
                    }
                    $select .= '└ '.mhtmlspecialchars($var['catname'], ENT_QUOTES) . '</option>';
                }
                return $select;
            }else{
                foreach ($options AS $key => $value){
                    $options[$key]['url'] = $value['catid'];
                }
                return $options;
            }
        }
    }

    protected function create_in($item_list, $field = '')
    {
        if (empty($item_list)){
            return $field . " IN ('') ";
        }else{
            if (!is_array($item_list)){
                $item_list = explode(',', $item_list);
            }
            $item_list = array_unique($item_list);
            $item_list_tmp = '';
            foreach ($item_list AS $item){
                if ($item !== ''){
                    $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
                }
            }
            if (empty($item_list_tmp)){
                return $field . " IN ('') ";
            }else{
                return $field . ' IN (' . $item_list_tmp . ') ';
            }
        }
    }

    /**
     * 过滤和排序所有分类，返回一个带有缩进级别的数组
     *
     * @access  private
     * @param   int     $catid     上级分类ID
     * @param   array   $arr        含有所有分类的数组
     * @param   int     $level      级别
     * @return  void
     */
    protected function cat_options($type='category',$spec_cat_id, $arr)
    {
        $cat_options = array();

        if (isset($cat_options[$spec_cat_id])){
            return $cat_options[$spec_cat_id];
        }

        if (!isset($cat_options[0])){
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = array();
            $data = false;

            if ($data === false){
                while (!empty($arr)){
                    foreach ($arr AS $key => $value){

                        $cat_id = $type == 'area' ? $value['areaid'] : ($type == 'corp' ? $value['corpid'] : $value['catid']);
                        if ($level == 0 && $last_cat_id == 0){
                            if ($value['parentid'] > 0){
                                break;
                            }
                            $options[$cat_id]          = $value;
                            $options[$cat_id]['level'] = $level;
                            $options[$cat_id]['id']    = $cat_id;
                            $options[$cat_id]['name']  = $type == 'category' ? $value['catname'] : $value[$type.'name'];
                            unset($arr[$key]);

                            if ($value['has_children'] == 0){
                                continue;
                            }
                            $last_cat_id  = $cat_id;
                            $cat_id_array = array($cat_id);
                            $level_array[$last_cat_id] = ++$level;
                            continue;
                        }

                        if ($value['parentid'] == $last_cat_id){
                            $options[$cat_id]          = $value;
                            $options[$cat_id]['level'] = $level;
                            $options[$cat_id]['id']    = $cat_id;
                            $options[$cat_id]['name']  = $type == 'category' ? $value['catname'] : $value[$type.'name'];
                            unset($arr[$key]);

                            if ($value['has_children'] > 0){
                                if (end($cat_id_array) != $last_cat_id){
                                    $cat_id_array[] = $last_cat_id;
                                }
                                $last_cat_id    = $cat_id;
                                $cat_id_array[] = $cat_id;
                                $level_array[$last_cat_id] = ++$level;
                            }
                        } elseif ($value['parentid'] > $last_cat_id){
                            break;
                        }
                    }

                    $count = count($cat_id_array);
                    if ($count > 1){
                        $last_cat_id = array_pop($cat_id_array);
                    }elseif ($count == 1){
                        if ($last_cat_id != end($cat_id_array)){
                            $last_cat_id = end($cat_id_array);
                        }else{
                            $level = 0;
                            $last_cat_id = 0;
                            $cat_id_array = array();
                            continue;
                        }
                    }

                    if ($last_cat_id && isset($level_array[$last_cat_id])){
                        $level = $level_array[$last_cat_id];
                    }else{
                        $level = 0;
                    }
                }
            }else{
                $options = $data;
            }
            $cat_options[0] = $options;
        }else{
            $options = $cat_options[0];
        }

        if (!$spec_cat_id){
            return $options;
        }else{
            if (empty($options[$spec_cat_id])){
                return array();
            }

            $spec_cat_id_level = $options[$spec_cat_id]['level'];

            foreach ($options AS $key => $value){
                if ($key != $spec_cat_id){
                    unset($options[$key]);
                }else{
                    break;
                }
            }

            $spec_cat_id_array = array();
            foreach ($options AS $key => $value){
                if (($spec_cat_id_level == $value['level'] && ($type == 'area' ? $value['areaid'] :$value['catid']) != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level'])){
                    break;
                }else{
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;

            return $spec_cat_id_array;
        }
    }
}
