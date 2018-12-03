<?php

namespace App\Http\Controllers;

use App\Http\Models\Area;
use App\Http\Models\City;
use App\Http\Models\Street;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * 切换城市
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeCity(){
        $hotCity = City::select(['cityid', 'cityname'])->where('status', 1)->where('ifhot', 1)->orderBy('displayorder')->get();
        $allCity = City::select(['cityid', 'firstletter', 'cityname'])
            ->where('status', 1)->orderBy('firstletter')->orderBy('displayorder')->get();
        $firstLetters = City::select(['firstletter'])->where('status', 1)->groupBy('firstletter')->orderBy('firstletter')->get();

        $pinYin = [];
        foreach ($firstLetters as $f){
            $pinYin[] = strtoupper($f['firstletter']);
        }

        $allCities = [];
        foreach ($allCity as $a){
            $allCities[strtoupper($a['firstletter'])][] = [
                'cityid' => $a['cityid'],
                'cityname' => $a['cityname']
            ];
        }

        return $this->success([
            'hot_cities' => $hotCity,
            'pin_yin' => $pinYin,
            'cities' => $allCities,
        ]);
    }

    /**
     * 根据经纬度获取用户所在位置的城市信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCity(Request $request){
        $rules = [
            'lng' => 'required|string|max:50',
            'lat' => 'required|string|max:50'
        ];
        $this->validate($request, $rules);

        $cityid = get_latlng2cityid( $request->lat, $request->lng);

        $location = City::select(['cityname'])->where('cityid', $cityid)->value('cityname');

        if($location){
            return $this->success([
                'cityid' => $cityid,
                'cityname' => $location
            ]);
        }

        return $this->fail(20003);
    }

    /**
     * 获取二级地域
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArea(Request $request){
        $rules = [
            'cityid' => 'required|int'
        ];
        $this->validate($request, $rules);

        $cityid = (int)$request->get('cityid');
        $area = Area::select(['areaid', 'areaname'])->where('cityid', $cityid)->get();

        return $this->success($area);
    }

    /**
     * 获取三级地域
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStreet(Request $request){
        $rules = [
            'areaid' => 'required|int'
        ];
        $this->validate($request, $rules);

        $areaid  = (int)$request->get('areaid');
        $location = Street::select(['streetid', 'streetname'])->where('areaid', $areaid)->get();

        return $this->success($location);
    }
}
