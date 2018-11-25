<?php

namespace App\Http\Middleware;

use App\Http\Models\City;
use Closure;

class AssignCity
{
    /**
     * 分配城市
     *
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $cityid = (int)$request->cityid;
        if(empty($cityid)){
            $ip = GetIP();
            $url = 'http://api.map.baidu.com/location/ip?ak=D51025630ea7e0fa157ebb39af7f169c&ip='.$ip.'&coor=bd09ll';
            $fromcity = json_decode(file_get_contents($url), true);
            $city = isset($fromcity['content']['address_detail']['city']) ? $fromcity['content']['address_detail']['city'] : 'beijing';

            if($city){
                $citypy = Pinyin($city,'utf-8');
                $citypy = str_replace('shi','',$citypy);
                $cityid = City::select('cityid')->where('citypy', $citypy)->value('cityid');
            }

        }
//        return redirect('/home');
        return $next($request);
    }
}
