<?php

namespace App\Http\Controllers;

use App\Http\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //
    public function index(Request $request){
        $input = $request->all();

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

        return $this->success(array_values($cat_arr));
    }
}
