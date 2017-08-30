<?php
/**
 * 库存同步处理类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_stock {
    //const PUBL_LIMIT = 100; //批量发布的最大上限数
    //const SYNC_LIMIT = 50; //批量下载的最大上限数

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get_benchmark($key=null)
    {
        $return = array(
                'actual_stock'   => '可售库存',
                'release_stock'  => '发布库存',
                //'shop_stock'     => '店铺库存',
                'shop_freeze'    => '店铺预占',
                'globals_freeze' => '全局预占',
            );
        return $key ? $return[$key] : $return;
    }

    public function get_benchobj($key=null)
    {
        $return = array(
                'actual_stock'   => '可售库存',
                //'release_stock'  => $this->app->_('发布库存'),
                //'shop_freeze'    => $this->app->_('店铺预占'),
                //'globals_freeze' => $this->app->_('全局预占'),
            );
        return $key ? $return[$key] : $return;
    }


    public function update_release_stock($merchandise_id, $result)
    {

        $r = $this->app->model('viewProduct_stock')->update(array('release_stock'=>$result,'release_status'=>'sleep'),array('merchandise_id'=>$merchandise_id));

        return $r;
    }

    public function update_check($data, $result, &$msg)
    {
        foreach($data as $d){
            $merchandise_id = $d['merchandise_id'];

            $resultVal = $this->check_and_build($merchandise_id, $result, $msg);
            if ($resultVal === false) {
                if (strpos($msg, '小于零') !== false)
                    $msg = $this->app->_('部分商品调整后的库存已经小于零，请重新填写');

                return false;
            }

            $this->update_release_stock($merchandise_id,$resultVal);
        }
        return true;
    }

    /**
     * 通过公式更新发布库存
     *
     * @return void
     * @author 
     **/
    public function updateReleaseByformula($filter,$result,&$errormsg)
    {
        $skusModel = $this->app->model('shop_skus');
        $calLib = kernel::single('inventorydepth_stock_calculation');

        $offset = 0; $limit = 100;
        do {
            $skus = $skusModel->getList('shop_product_bn,shop_bn,shop_id,id,release_stock,shop_stock',$filter,$offset,$limit);
            if(!$skus) break;

            foreach ($skus as $key => $sku) {
                $id = $sku['id'];

                # 字段初始化
                $calLib->init();
                $calLib->set_release_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['release_stock']);
                $calLib->set_shop_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_stock']);

                $params = array(
                    'shop_product_bn' => $sku['shop_product_bn'],
                    'shop_bn' => $sku['shop_bn'],
                    'shop_id' => $sku['shop_id'],
                );
                $release_stock = $this->formulaRun($result,$params,$msg);

                # 更新失败
                if ($release_stock === false) {
                    $error[] = $msg;
                    continue;
                }

                $skusModel->update(array('release_stock' => $release_stock),array('id' => $id));
            }

            $offset += $limit;

        } while (true);

        $errormsg = $error;
        
        return true;
    }

     /**
      * 运行公式
      *
      * @param Array $sku  商品明细
      * @param String $result 公式
      * @param String $msg 错信息
      * @return void
      * @author 
      **/
     public function formulaRun($result,$sku=array(),&$msg,$type='')
     {
       if (is_numeric($result)) {
            if ($result < 0) {
                $msg = $this->app->_('库存已经小于零，请重新填写');
                return false;
            }

            return  (int)$result;
        }

        # 过滤掉敏感字符
        $dange = array('select', 'update', 'drop', 'delete', 'insert', 'alter');
        $tmp = strtolower($result);
        foreach ($dange as $val){
            if (strpos($tmp, $val) !== false) {
                $msg = $this->app->_('what are you doing? go away!');
                return false;
            }
        }

        if (!$sku) {
            foreach ($this->get_benchmark() as $key => $val) {
                $result = str_replace('{'.$val.'}', '0', $result);
            }

            $result = @kernel::database()->selectrow('select ' . $result . ' as val');
            if ($result !== false) $result = $result['val'];

            if (!is_numeric($result)) {
                $msg = $this->app->_('公式错误，请重新填写。');

                return false;
            }

            return true; 
        }

        //-------------------变量实际替换校验------------------//
        preg_match_all('/{(.*?)}/',$result,$matches);
        $benchmark = $this->get_benchmark();

        # 符合条件的所有货品
        $calLib = kernel::single('inventorydepth_stock_calculation');

        if($matches) {
            foreach($matches[1] as $match){
                $m = array_search($match,$benchmark);

                if(false === $m) {
                    $msg = $this->app->_('公式错误!');
                    return false;
                }

                $stock = call_user_func_array(array($calLib,'get_'.$type.$m),$sku);

                $result = str_replace('{'.$match.'}', $stock, $result);
            }
        }

        # 验证 $result
        $result = @kernel::database()->selectrow('select ' . $result . ' as val');
        //@eval("\$result = $result;");

        if($result === false) {
            $msg = $this->app->_('公式有误!');
            return false;
        }

        $result = $result['val'];
        if (!is_numeric($result)) {
            $msg = $this->app->_('计算结果异常：可能仓库未绑定店铺!');
            return false;
        }else if (floor($result) < 0) {
            $msg = $sku['shop_product_bn'] . ' ' . $this->app->_('调整后的库存已经小于零，请重新填写');
            return false;
        }

        return (int)$result;  
     }
}
