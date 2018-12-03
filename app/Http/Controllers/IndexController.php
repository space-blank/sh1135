<?php

namespace App\Http\Controllers;

use App\Http\Models\Area;
use App\Http\Models\City;
use App\Http\Models\Street;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * 根据经纬度获取用户所在位置的城市信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCity(Request $request){
        $cityid = (int)$request->get('cityid', 1);
        $location = City::select(['cityname'])->where('cityid', $cityid)->value('cityname');

        return $this->success([
            'cityid' => $cityid,
            'cityname' => $location
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
