<?php

namespace App\Http\Controllers;

use App\Http\Models\Area;
use App\Http\Models\Category;
use App\Http\Models\City;
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
        $input = $request->all();
        $cityid = (int)$request->get('cityid', 1);

        $cat_list = Category::from('category as a')->select([
            'a.catid',
            'a.catname',
            'b.catid AS childid',
            'b.catname AS childname'
        ])->leftjoin('category as b', 'b.parentid', '=', 'a.catid')
            ->orderBy('a.catorder')
            ->orderBy('b.catorder')->get();

        $cat_arr = [];
        foreach ($cat_list as $row){
            $cat_arr[$row['catid']]['catid']    = $row['catid'];
            $cat_arr[$row['catid']]['catname']  = $row['catname'];

            if ($row['childid']) {
                $cat_arr[$row['catid']]['children'][] = [
                    'catid' => $row['childid'],
                    'catname' => $row['childname']
                ];
            }
        }

        $location = City::select(['cityname'])->where('cityid', $cityid)->value('cityname');

        return $this->success([
            'location' => [
                'cityid' => $cityid,
                'cityname' => $location
            ],
            'list' => array_values($cat_arr)
        ]);
    }

    public function getCity(Request $request){
        $cityid = (int)$request->get('cityid', 1);
        $location = City::select(['cityname'])->where('cityid', $cityid)->value('cityname');

        return $this->success([
            'location' => [
                'cityid' => $cityid,
                'cityname' => $location
            ],
            'list' => array_values([])
        ]);
    }

    /**
     * 获取二级地域
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArea(Request $request){
        $cityid = (int)$request->get('cityid', 1);
        $location = City::select(['cityname'])->where('cityid', $cityid)->value('cityname');
        $area = Area::select(['areaid', 'areaname'])->where('cityid', $cityid)->get();

        return $this->success([
            'location' => [
                'cityid' => $cityid,
                'cityname' => $location
            ],
            'list' => $area
        ]);
    }

    /**
     * 获取三级地域
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStreet(Request $request){
        $areaid  = (int)$request->get('areaid', 1);
        $location = Street::select(['streetid', 'streetname'])->where('areaid', $areaid)->get();

        return $this->success($location);
    }
}
