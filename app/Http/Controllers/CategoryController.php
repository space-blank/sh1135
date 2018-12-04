<?php

namespace App\Http\Controllers;

use App\Http\Models\Area;
use App\Http\Models\Category;
use App\Http\Models\City;
use App\Http\Models\InfoTypemodels;
use App\Http\Models\InfoTypeoptions;
use App\Http\Models\Street;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * 分类信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request){
        $cat_list = Category::from('category as a')->select([
            'a.catid',
            'a.catname',
            'a.icon',
            'b.catid AS childid',
            'b.catname AS childname'
        ])->leftjoin('category as b', 'b.parentid', '=', 'a.catid')
            ->where([
                'a.parentid' => 0,
                'a.if_view' => 2,
                'b.if_view' => 2,
            ])
            ->orderBy('a.catorder')
            ->orderBy('b.catorder')->get();

        $cat_arr = [];
        foreach ($cat_list as $row){
            $cat_arr[$row['catid']]['catid']    = $row['catid'];
            $cat_arr[$row['catid']]['catname']  = $row['catname'];
            $cat_arr[$row['catid']]['icon']  = env('APP_URL').$row['icon'];

            if ($row['childid']) {
                $cat_arr[$row['catid']]['children'][] = [
                    'catid' => $row['childid'],
                    'catname' => $row['childname']
                ];
            }
        }

        return $this->success(array_values($cat_arr));
    }

    public function searConfig(Request $request){
        $rules = [
            'catid' => 'required|int',
            'cityid' => 'int'
        ];
        $this->validate($request, $rules);

        $catid = (int)$request->catid;
        $cityid = (int)$request->cityid ?: 1;

        $cat = Category::select([
            'catid',
            'catname',
            'parentid',
            'modid'
        ])->where('catid', $catid)->first();

        $breadCrumbs = [];

        if($cat){
            $parentId = $cat['parentid'];
            $breadCrumbs[] = [
                'catid'   => $cat['catid'],
                'catname' => $cat['catname'],
            ];

            $catelist = Category::select([
                'catid',
                'catname'
            ])->where('parentid', $cat['catid'])->get();
            if($parentId != 0){
                $pCat = Category::select([
                    'catid',
                    'catname',
                    'parentid'
                ])->where('catid', $parentId)->first();
                array_unshift($breadCrumbs, $pCat);
                $parentId = $pCat['parentid'];
                if($parentId != 0){
                    $pCat = Category::select([
                        'catid',
                        'catname'
                    ])->where('catid', $parentId)->first();
                    array_unshift($breadCrumbs, $pCat);
                }
            }
            $modid = $cat['modid'];
            $typemodels = InfoTypemodels::select(['id', 'options'])->where('id', $modid)->first();
//            SELECT optionid,title,identifier,type,rules,search FROM `{$GLOBALS['db_mymps']}info_typeoptions` WHERE optionid='$u'"
            if($typemodels){
                $typeOptions = InfoTypeoptions::select([
                    'optionid',
                    'title',
                    'identifier',
                    'type',
                    'rules'
                ])->whereIn('optionid', explode(',', $typemodels['options']))->where('search', 'on')->get();
                $arr = [];
                foreach ($typeOptions as $nrow){
                    $extra = utf8_unserialize($nrow['rules']);
                    if(in_array($nrow['type'], ['select','radio','checkbox','number'])) {
                        if(is_array($extra)){
                            foreach($extra as $k => $value){
                                if($nrow['type'] == 'radio' || $nrow['type'] == 'select' || $nrow['type'] == 'checkbox' || ($nrow['type'] == 'number' && $k == 'choices')){
                                    $extr = array_merge(['-1'=>'不限'], arraychange($value));
                                    foreach($extr as $ekey => $eval){
                                        $ar['id']  = $ekey;
                                        $ar['name']  = $eval;
//                                        $ar['identifier']  = $nrow['identifier'];
                                        $arr[$nrow['optionid']]['list'][] = $ar;
                                    }
                                }
                                $arr[$nrow['optionid']]['title'] = $nrow['title'];
//                                $arr[$nrow['optionid']]['type']  = $nrow['type'];
                                $arr[$nrow['optionid']]['identifier'] = $nrow['identifier'];
//                                $arr[$row['id']][$nrow['optionid']]['publish'] = get_info_var_type($nrow['type'],$nrow['identifier'],$extr,$get_value,'front');
                            }
                        }
                    }
                }
                $conditions = [];
                if($catelist->isEmpty()){
                    $catelist = Category::select([
                        'catid',
                        'catname'
                    ])->where('parentid', $parentId)->get();

                }
                $conditions['category'] = $catelist;
                if($cityid){
                    $area = Area::select(['areaid', 'areaname'])->where('cityid', $cityid)->get();
                    $conditions['area'] = $area;
                }
                $conditions['others'] = array_values($arr);


                return $this->success([
                    'breadCrumbs' => $breadCrumbs,
                    'conditions' => $conditions,
                ]);
            }
        }

        return $this->fail(30002, [], '数据不存在！');
    }
}
