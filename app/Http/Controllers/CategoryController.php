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


}
