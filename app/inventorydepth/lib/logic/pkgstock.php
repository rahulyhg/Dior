<?php
/**
 * 更新捆绑商品库存逻辑
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-8-6 17:22Z
 */
class inventorydepth_logic_pkgstock 
{
    /**
     * @description 计算捆绑商品库存
     * @access public
     * @param String $pkgbn 捆绑货号
     * @param Array $shop 店铺信息
     * @return void
     */
    public function getStock($pkgbn,$shop_id,$shop_bn,$node_type='') 
    {
        # 读取商品要执行的规则
        $quantity = $this->dealWithRegu($pkgbn,$shop_id,$shop_bn);
        if ($quantity === false) { return false; }
        
        $params = array(
            'shop_product_bn' => $pkgbn,
            'shop_bn'         => $shop_bn,
            'shop_id'         => $shop_id,
        );
        # 店铺冻结
        $stockCalLib = kernel::single('inventorydepth_stock_calculation');
        $store_freeze = call_user_func_array(array($stockCalLib,'get_pkg_shop_freeze'), $params);

        $memo = array(
                'store_freeze' => $store_freeze,
                'last_modified' => time(),
        );
        
        // 受1号店回写库存限制
        if($node_type == 'yihaodian' && $quantity >= 3000){
            $quantity = 2999;
        }

        $stock = array(
            'bn' => $pkgbn,
            'quantity' => $quantity,
            'memo' => json_encode($memo),
        );

        return $stock;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function dealWithRegu($pkgbn,$shop_id,$shop_bn,&$apply_regulation=array()) 
    {
        $pkg = kernel::single('inventorydepth_stock_pkg')->fetch_pkg($pkgbn);
        $goods_id = $pkg['goods_id'];

        $regu = kernel::single('inventorydepth_logic_stock')->getRegu($shop_id);
        foreach($regu as $r){
            if(empty($r['regulation'])) continue;

            if((in_array($goods_id,$r['apply_pkg']) || $r['apply_pkg'][0]=='_ALL_') && (in_array($shop_id,$r['shop_id']) || $r['shop_id'][0]=='_ALL_')) {
                
                /*
                if (isset($this->stock_quantity[$r['id']][$goods_id]) && $this->stock_quantity[$r['id']][$goods_id] >= 0) {
                    $quantity = (int)$this->stock_quantity[$r['id']][$goods_id];
                    break;
                }*/

                # 判断是否满足规则
                $params = array(
                    'shop_product_bn' => $pkgbn,
                    'shop_bn'         => $shop_bn,
                    'shop_id'         => $shop_id,
                );
                foreach ($r['regulation']['content']['filters'] as $filter) {
                    $allow_update = $this->check_condition($filter,$params);

                    if(!$allow_update){ continue 2;}
                }

                if ($r['regulation']['content']['stockupdate'] != 1) { return false; }

                $quantity = kernel::single('inventorydepth_stock')->formulaRun($r['regulation']['content']['result'],$params,$msg,'pkg_');

                if ($quantity === false){ continue; }

                //$this->stock_quantity[$r['id']][$goods_id] = $quantity;
                $apply_regulation = $r['regulation'];
                break;
            }
        }

        return is_null($quantity) ? false : $quantity;
    }

    public function check_condition($filter,$params)
    {
        $stockObject = kernel::single('inventorydepth_stock_calculation');

        $object_key = call_user_func_array(array($stockObject,'get_pkg_'.$filter['object']),$params);

        if($object_key === false) return false;

        $mathObj = kernel::single('inventorydepth_math');

        # 按百分比计算
        if ($filter['percent']=='true') {
            $objected_key = call_user_func_array(array($stockObject,'get_pkg_'.$filter['objected']),$params);

            if($objected_key === false) return false;

            if ($filter['comparison'] == 'between') {
                $objected_key_min = $objected_key * $filter['compare_increment'];
                $objected_key_min_comparison = $mathObj->get_comparison('bthan');

                $objected_key_max = $objected_key * $filter['compare_increment_after'];
                $objected_key_max_comparison = $mathObj->get_comparison('sthan');

                $expression = $object_key.$objected_key_min_comparison.$objected_key_min.' && '.$object_key.$objected_key_max_comparison.$objected_key_max;

                eval("\$result=$expression;");
                return $result;
            }else{
                $objected_key = $objected_key * $filter['compare_increment'];
                $comparison = $mathObj->get_comparison($filter['comparison']);
                
                $expression = $object_key.$comparison.$objected_key;
                eval("\$result=$expression;");
                return $result;
            }
        }else{
            # 按数值计算
            if ($filter['comparison'] == 'between') {
                $min_comparison = $mathObj->get_comparison('bthan');
                $max_comparison = $mathObj->get_comparison('sthan');

                $expression = $object_key.$min_comparison.$filter['compare_increment'].' && '.$object_key.$max_comparison.$filter['compare_increment_after'];

                eval("\$result=$expression;");
                return $result;
            }else{
                $comparison = $mathObj->get_comparison($filter['comparison']);

                $expression = $object_key.$comparison.$filter['compare_increment'];

                eval("\$result=$expression;");
                return $result;
            }
        }
    }

    /**
     * @description 获取真正执行的规则
     * @access public
     * @param void
     * @return void
     */
    public function getExecRegu($pkgbn,$shop_id,$shop_bn) 
    {
        $pkg = kernel::single('inventorydepth_stock_pkg')->fetch_pkg($pkgbn);
        $goods_id = $pkg['goods_id'];

        $regu = kernel::single('inventorydepth_logic_stock')->getRegu($shop_id);
        foreach($regu as $r){
            if(empty($r['regulation'])) continue;

            if((in_array($goods_id,$r['apply_pkg']) || $r['apply_pkg'][0]=='_ALL_') && (in_array($shop_id,$r['shop_id']) || $r['shop_id'][0]=='_ALL_')) {

                # 判断是否满足规则
                //$valid = $this->valid();
                $params = array(
                    'shop_product_bn' => $pkgbn,
                    'shop_bn'         => $shop_bn,
                    'shop_id'         => $shop_id,
                );
                foreach ($r['regulation']['content']['filters'] as $filter) {
                    $allow_update = $this->check_condition($filter,$params);

                    if(!$allow_update){ continue 2;}
                }
                
                $exec_regu = array(
                    'regulation_id' => $r['regulation']['regulation_id'],
                    'heading' => $r['regulation']['heading'],
                );

                break;
            }
        }

        return $exec_regu;
    }
}