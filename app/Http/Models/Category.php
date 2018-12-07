<?php

namespace App\Http\Models;


use Illuminate\Support\Facades\DB;

class Category extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'catid';

    public $timestamps = false;


    public static function getConfig($modid){
        $sign = [];
        $arr = [];
        $typemodels = InfoTypemodels::select(['id', 'options'])->where('id', $modid)->first();
        if ($typemodels) {
            $typeOptions = InfoTypeoptions::select([
                'optionid',
                'title',
                'identifier',
                'type',
                'rules',
                'search'
            ])->whereIn('optionid', explode(',', $typemodels['options']))
//                ->where('search', 'on')
                ->get();

            foreach ($typeOptions as $nrow) {
                if($nrow['search']=='on'){
                    $sign[] = $nrow['identifier'];
                }
                $extra = utf8_unserialize($nrow['rules']);
                if (in_array($nrow['type'], ['select', 'radio', 'checkbox', 'number'])) {
                    if (is_array($extra)) {
                        foreach ($extra as $k => $value) {
                            if ($nrow['type'] == 'radio' || $nrow['type'] == 'select' || $nrow['type'] == 'checkbox' || ($nrow['type'] == 'number' && $k == 'choices')) {
                                $extr = array_merge(['-1' => '不限'], arraychange($value));
                                foreach ($extr as $ekey => $eval) {
                                    $ar['id'] = $ekey;
                                    $ar['name'] = $eval;
//                                        $ar['identifier']  = $nrow['identifier'];
                                    $arr[$nrow['optionid']]['list'][] = $ar;
                                }
                            }
                            $arr[$nrow['optionid']]['title'] = $nrow['title'];
                            $arr[$nrow['optionid']]['type']  = $nrow['type'];
                            $arr[$nrow['optionid']]['identifier'] = $nrow['identifier'];
                        }
                    }
                }
            }
        }

        return [$sign, $arr];
    }

    /**
     * 获取发布门铺页面的配置信息
     *
     * @param int $modid
     * @param int $editId
     * @return array
     */
    public static function CategoryInfoOptions($modid = 0, $editId = 0){
        $typemodels = InfoTypemodels::select(['id', 'options'])->where('id', $modid)->first();
        $return = [];
        $get_value = '';

        if ($typemodels) {
            $typeOptions = InfoTypeoptions::select([
                'title',
                'identifier',
                'type',
                'rules',
                'required'
            ])->whereIn('optionid', explode(',', $typemodels['options']))->get();

            foreach ($typeOptions as $nrow) {
                $extra = utf8_unserialize($nrow['rules']);
                $required	= $nrow['required'] == 'on' ? 1 : 0;

                if (is_array($extra)) {
                    if($editId){
                        $get = DB::table('information_'. $modid)->where('id', $editId)->first();
                        $get_value = $get[$nrow['identifier']];
                    }
                    foreach ($extra as $k => $value) {
                        $returns = [];
                        if ($nrow['type'] == 'radio' || $nrow['type'] == 'select' || $nrow['type'] == 'checkbox') {
                            $extra = $extr = arraychange($value);
                        }elseif($nrow['type'] == 'number' && $k == 'units'){
                            continue;
                        }
//                        $returns['required']  =  $required;
                        $returns['title']	  =  $nrow['title'];
                        $returns['identifier']	  =  $nrow['identifier'];

                        if($returns['title'] == '价格' || $returns['title'] == '租金'){
                            $returns['value'] = [
                                'param' => 'danwei',
                                'value' => [
                                    '元/㎡/天',
                                    '元/月',
                                ]
                            ];
                        }else{
                            $returns['value'] = get_info_var_type($nrow['type'],
                                $nrow['identifier'], $extra, $get_value, 'back', $nrow['title'], $required);
                        }


                        $return[] = $returns;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * 获取子节点的所有父元素
     *
     * @param $catid
     * @return array
     */
    public static function getParentsByNode($catid){
        $info = self::select(['catid', 'catname', 'parentid'])->find($catid)->toArray();
        if($info){
            if($info['parentid']){
                $pre = self::getParentsByNode($info['parentid']);
                $pre[] = ['catid' => $info['catid'], 'catname' => $info['catname']];
                return $pre;
            }else{
                return [['catid' => $info['catid'], 'catname' => $info['catname']]];
            }
        }
        return [];
    }
}
