<?php
/**
 * 规则处理类
 *
 * @author chenping<chenping@shopex.cn>
 *
 */
class inventorydepth_regulation {

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get_style($key)
    {
        $return = array(
                'stock_change' => $this->app->_('库存变动'),
                //'fix'          => $this->app->_('定时'),
            );

        return $key ? $return[$key] : $return;
    }

    public function get_condition($key)
    {
        $return = array(
                'frame' => $this->app->_('商品上下架'),
                'stock' => $this->app->_('库存更新')
            );

        return $key ? $return[$key] : $return;
    }

    public function get_condition_model($key)
    {
        $return = array(
                'frame' => 'shop_items',
                'stock' => 'shop_skus'
            );

        return $key ? $return[$key] : $return;
    }

    public function check_and_build($data, &$msg)
    {
        if ($data['condition'] == 'stock' && $data['content']['stockupdate'] == '1'){
            $formulaCurrect = kernel::single('inventorydepth_stock')->formulaRun($data['content']['result'],null,$msg);
            if ($formulaCurrect === false) {
                $msg = '公式：'.$msg; return false;
            }
        }


        if ($data['bn'] == '' || $data['heading'] == '') {
            $msg = '编号或者中文标识不能为空';

            return false;
        }

        $bnCount = $this->app->model('regulation')->count(array('bn' => $data['bn'], 'regulation_id|noequal' => $data['regulation_id'],'condition'=>$data['condition']));
        if ($bnCount > 0) {
            $msg = '规则编号重复'; return false;
        }

        if ($data['condition'] == '') {
            $msg = '规则类型必须选择';
            return false;
        }

        foreach ($data['content']['filters'] as $key => $value) {
            $i = $key + 1;
            # 判定选择对象
            if(!$value['object']){$msg = '请选择条件对象!'; return false;}

            # 判断比较符
            if(!$value['comparison']){$msg = '请选择比较符!'; return false;}

            if ($value['compare_increment'] == '') {
                $msg = "第{$i}行条件规则比较值不能这空!";
                return false;
            }

            if ($value['percent'] == 'true') {
                if (floatval($value['compare_increment']) > 1 || floatval($value['compare_increment']) < 0) {
                        $msg=  "第{$i}行条件规则比较值范围0~1,包含0、1!";
                        return false;
                    }
            }

            # 如果比较符是介于
            if ($value['comparison'] == 'between') {
                if ($value['compare_increment_after'] == '') {
                    $msg = "第{$i}行条件规则比较值不能这空!";
                    return false;
                }

                # 判定区间
                if (floatval($value['compare_increment']) > floatval($value['compare_increment_after'])) {
                    $msg = "第{$i}行条件规则区间有误!";
                    return false;
                }

                # 判定百分比
                if ($value['percent'] == 'true') {
                    if (floatval($value['compare_increment']) < 0 || floatval($value['compare_increment_after']) > 1) {
                        $msg = "第{$i}行条件规则区间数值增量范围0~1,包含0、1!";
                        return false;
                    }
                }
            }else{
                unset($data['content']['filters'][$key]['compare_increment_after']);
            }
        }

        $return['using'] = $data['using'] ? $data['using'] : 'false';//默认处于停用状态
        $return['type'] = $data['type'] ? $data['type'] : '2';
        
        $return['operator'] = kernel::single('desktop_user')->get_id();
        $return['operator_ip'] = kernel::single('base_component_request')->get_remote_ip();

        return array_merge($return, $data);
    }

        /** 规则翻译
         *
         * @param array $content
                array(
                    array(
                        comparison = between
                        compare_increment = 100
                        compare_increment_after = 200
                        result = upper
                    )
                    array(
                        comparison = bthan
                        compare_increment = 100
                        result = {实际库存}+10
                    )
                )
          * @return array
          */
    public function get_description($data)
    {
        if (!is_array($data['content']) || count($data['content']) <= 0) return array();

        # 前提条件
        $msg = '';
        /*
        $msg .= $data['condition'] == 'frame' ? '商品' : '货品';
        $msg .= $data['style'] == 'fix' ? '，' : '的可售库存发生变动，且';
        if (isset($data['content']['stockupdate']) && $data['content']['stockupdate'] == 0) {
            $msg .= '符合以下规则时，不更新店铺库存';
        }else{
            $msg .= '符合以下规则时，进行更新 ' . ($data['condition'] == 'frame' ? '上下架状态' : '店铺库存');
        }*/

        //$return[] = $msg;

        $i = 0;
        foreach ($data['content']['filters'] as $val) {
            $msg = $i===0 ? '当' : '且';
            //$msg = '当 可售库存 ';
            if ($data['condition'] == 'frame') {
                $msg .= kernel::single('inventorydepth_frame')->sku_option($val['forsku']);
                $msg .= '的';
            }

            $msg .= kernel::single('inventorydepth_stock')->get_benchobj($val['object']);

            $msg .= kernel::single('inventorydepth_math')->get_show_comparison($val['comparison']);

            if ($val['comparison'] == 'between'){
                $msg .= $val['percent']=='true' ?
                ($val['compare_increment']*100).'%'.kernel::single('inventorydepth_stock')->get_benchmark($val['objected']).' ~ '.($val['compare_increment_after']*100).'%'.kernel::single('inventorydepth_stock')->get_benchmark($val['objected']) :
                $val['compare_increment'].' ~ '.$val['compare_increment_after'];
            }else{
                $msg .= $val['percent']=='true' ? kernel::single('inventorydepth_stock')->get_benchmark($val['objected']).' * '.($val['compare_increment']*100).'%' : $val['compare_increment'];
            }

            $i++;
            $return[] = $msg;
        }

        if(isset($data['content']['stockupdate']) && $data['content']['stockupdate'] == 0) {
            $return[] = '不更新库存';
            return $return;
        }

        $msg = '';
        if ($data['content']['result'] == 'upper' || $data['content']['result'] == 'lower'){
            $msg .= '进行' . kernel::single('inventorydepth_frame')->get_benchmark($data['content']['result']).'操作';
        }else{
            $msg .= '按 店铺库存=' . $data['content']['result'].' 的规则进行更新';
        }
        $return[] = $msg;

        return $return;
    }

    /**
     * 根据货品ID找到规则
     *
     * @param Int $product_id
     **/
    public function getReguByPid($product_id) {
        # 找到对应的规则
        $apply_id = $this->app->model('regulation_mapping')->select()->columns('apply_id')
                            ->where('type=?','products')->where('pgid=?',$product_id)
                            ->instance()->fetch_one();
    }

}
