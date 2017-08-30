<?php
/**
 * 规则执行
 *
 * @author chenping<chenping@shopex.cn>
 *
 **/

class inventorydepth_logic {
    const STOCK_CHANGE_TIME = 300;  // 库存变动执行间隔
    const FIX_TIME = 60;            // 定时执行间隔

    public static $now;

    public static $store_change_products;

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 执行开始
     *
     * @return void
     * @author
     **/
    public function start()
    {
        self::$now = time();
        self::$store_change_products = array();

        # 初始化读取货品表时间
        base_kvstore::instance('inventorydepth/apply')->fetch('read_store_lastmodify',$read_store_lastmodify);
        if (!$read_store_lastmodify || $read_store_lastmodify>self::$now) {
            $read_store_lastmodify = self::$now-self::STOCK_CHANGE_TIME;
            base_kvstore::instance('inventorydepth/apply')->store('read_store_lastmodify',$read_store_lastmodify);
        }

        # 读取库存变更货品
        $filter = array(
            'max_store_lastmodify|between' => array(
                0 => $read_store_lastmodify,
                1 => self::$now,
            )
        );
        $products = $this->app->model('products')->getList('product_id,bn',$filter);
        foreach ($products as $key=>$product) {
            self::$store_change_products[$product['product_id']] = $product;
        }

        # 读取可执行规则应用
        $applyList = self::have_run_apply();
        if(!$applyList) return false;

        set_time_limit(0);

        # 获取规则应用的最后一次执行时间
        base_kvstore::instance('inventorydepth/apply')->fetch('apply-lastexectime',$lastExecTime);

        foreach ($applyList as $key => $apply) {
            switch ($apply['style']) {
                case 'stock_change':        # 库存变更
                    # 判断是否达到了间隔要求,第一次执行，直接跳过
                    if ($lastExecTime[$apply['id']] && ($lastExecTime[$apply['id']]+self::STOCK_CHANGE_TIME)>self::$now) {
                        // 没到5分钟
                        continue;
                    }

                    break;
                case 'fix':                 # 定时
                    # 判断是否达到了间隔要求
                    if ($lastExecTime[$apply['id']] && ($lastExecTime[$apply['id']] + self::FIX_TIME)>self::$now) {
                        // 没到1分钟
                        continue;
                    }

                    break;
                default:
                    continue;
                    break;
            }


            switch ($apply['condition']) {
                case 'stock':           # 库存更新
                    if (self::$store_change_products) {
                        inventorydepth_logic::exec_regulation_stock($apply);
                    }
                    break;
                case 'frame':           # 上下架
                    inventorydepth_logic::exec_regulation_frame($apply);
                    break;
                default:
                    # code...
                    break;
            }


            $lastExecTime[$apply['id']] = time();
            $read_store_lastmodify = self::$now;
        }

        base_kvstore::instance('inventorydepth/apply')->store('apply-lastexectime',$lastExecTime);
        base_kvstore::instance('inventorydepth/apply')->store('read_store_lastmodify',$read_store_lastmodify);
    }

    /** 取出在时间点上全部可调用的规则
     *
     * @param int $time 时间戳
     * @param varchar $style 触发条件
     * @return mix
     */
    public static function have_run_apply($style=null)
    {
        if (is_null(kernel::single('inventorydepth_regulation')->get_style($style)))  return null;

        $filter['start_time|sthan']  = self::$now;
        $filter['end_time|bthan']    = self::$now;
        $filter['using']             = 'true';
        $filter['al_exec']           = 'false';
        if ($style) $filter['style'] = $style;

        # 获取满足条件的规则应用 优先级大的越先执行
        $applyList = app::get('inventorydepth')->model('regulation_apply')->getList('id,`condition`,style,shop_id,apply_goods,regulation_id,bn,type,priority', $filter ,0 , -1 ,'type desc,priority desc');
        if (empty($applyList))  return null;

        return $applyList;
    }

    /** 执行库存更新规则
     *
     * @param int $apply_id 规则应用ID
     * @return bool
     */
    public static function exec_regulation_stock($apply)
    {
        # 规则详细
        $regulation = app::get('inventorydepth')->model('regulation')
                            ->select()->columns('content')
                            ->where('`condition`=?',$apply['condition'])
                            ->where('regulation_id=?',$apply['regulation_id'])
                            ->where('`using`=?','true')
                            ->instance()->fetch_row();
        if (empty($regulation)) {return false;}

        # 定时的，标记已经执行过了
        $update = array(
            'al_exec' => ($apply['style'] == 'fix') ? 'true' : 'false',
            'exec_time' => time(),
        );
        app::get('inventorydepth')->model('regulation_apply')->update($update, array('id'=>$apply['id']));

        # 应用店铺
        $shop_ids = $apply['shop_id'];
        if ($shop_ids[0] == '_ALL_') {
            $shops = app::get('inventorydepth')->model('shop')->getList('shop_id');
            $shop_ids = array_map('current',$shops);
        }

        # 要回写的货品
        $products = self::$store_change_products;
        if (!$apply['apply_goods']) {
            $mapping = app::get('inventorydepth')->model('regulation_mapping')->getList('pgid',array('type'=>'products','apply_id'=>$apply['id'],'pgid'=>array_keys($products)));
            if(!$mapping) return false;

            $p = $products; unset($products);
            foreach ($mapping as $key=>$value) {
                $products[$value['pgid']] = $p[$value['pgid']];
            }
        }

        $stockObject = kernel::single('inventorydepth_stock_calculation');
        $adjustmentModel = app::get('inventorydepth')->model('shop_adjustment');
        $shopModel = app::get('inventorydepth')->model('shop');
        foreach ($shop_ids as $key => $shop_id) {
            $shop_bn = $shopModel->select()->columns('shop_bn')->where('shop_id=?',$shop_id)->instance()->fetch_one();

            # 判断店铺是否具有回写功能
            $request = kernel::single('inventorydepth_shop')->getStockConf($shop_id);
            if ($request != 'true') {
                continue;
            }

            # 判断是否已经下载，对单独商品进行设置
            foreach ($products as $pid => $product) {

                $row = $adjustmentModel->getList('id,shop_bn,shop_product_bn,shop_stock,release_stock,request,mapping,shop_sku_id',array('shop_product_bn'=>$product['bn']),0,1);
                $sku = $row[0];
                if ($sku && $sku['request'] == 'false') {
                    continue;
                }

                # 数据初始化妆
                $stockObject->init();
                if ($sku) {
                    $stockObject->set_shop_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_stock']);
                    $stockObject->set_release_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['release_stock']);
                }

                $params = array(
                    'shop_product_bn' => $product['bn'],
                    'shop_bn'         => $shop_bn,
                    'shop_id'         => $shop_id,
                );

                // 判断是否满足规则条件
                foreach ($regulation['content']['filters'] as $filter) {
                    $allow_update = self::check_condition($filter,$params);

                    # 不满足，跳过...
                    if(!$allow_update){
                        # 记录错误信息
                        $logParams = "货号【{$product['bn']}】执行公式：{$regulation['content']['result']}。参数：".var_export($params)."执行应用编号：{$apply['bn']}";
                        $logData = array(
                            'bn' => $product['bn'],
                            'sku_id' => $sku['shop_sku_id'],
                            'shop_id' => $shop_id,
                            'shop_bn' => $shop_bn,
                            'type' => 'stock',
                            'status' => 'fail',
                            'params' => $logParams,
                            'msg' => '规则条件不满足！',
                        );
                        app::get('inventorydepth')->model('log')->saveLog($logData);
                        continue 2;
                    }
                }

                # 规则是否要求更新店铺库存
                if ($regulation['content']['stockupdate'] != 1) { return false;}

                $quantity = kernel::single('inventorydepth_stock')->formulaRun($regulation['content']['result'],$params,$msg);

                if ($quantity === false){
                    # 记录错误信息
                    $logParams = "货号【{$product['bn']}】执行公式：{$regulation['content']['result']}。参数：".var_export($params)."执行应用编号：{$apply['bn']}";
                    $logData = array(
                        'bn' => $product['bn'],
                        'sku_id' => $sku['shop_sku_id'],
                        'shop_id' => $shop_id,
                        'shop_bn' => $shop_bn,
                        'type' => 'stock',
                        'status' => 'fail',
                        'params' => $logParams,
                        'msg' => $msg,
                    );
                    app::get('inventorydepth')->model('log')->saveLog($logData);
                    continue;
                }

                # 店铺冻结
                $store_freeze = call_user_func_array(array($stockObject,'get_shop_freeze'), $params);

                $product = kernel::single('inventorydepth_stock_products')->fetch_products($product['bn']);
                $last_modified = $product['last_modified'];

                if ($sku) {
                    $adjustmentModel->update(array('shop_stock'=>$quantity),array('id'=>$sku['id']));
                }


                $memo = array(
                        'store_freeze' => $store_freeze,
                        'last_modified' => $last_modified,
                );
                $stocks[] = array(
                    'bn' => $product['bn'],
                    'quantity' => $quantity,
                    'memo' => json_encode($memo),
                );

                unset(self::$store_change_products[$pid]);
            }

            if ($stocks) {
                kernel::single('inventorydepth_shop')->doStockRequest($stocks,$shop_id);
            }
            $stocks = array();
        }
    }

    /**
     * @description 验证规则条件
     * @access public
     * @param void
     * @return void
     */
    public static function check_condition($filter,$params,$type='')
    {
        $stockObject = kernel::single('inventorydepth_stock_calculation');

        $object_key = call_user_func_array(array($stockObject,'get_'.$type.$filter['object']),$params);

        if($object_key === false) return false;

        $mathObj = kernel::single('inventorydepth_math');

        # 按百分比计算
        if ($filter['percent']=='true') {
            $objected_key = call_user_func_array(array($stockObject,'get_'.$type.$filter['objected']),$params);

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

                eval("\$result=$object_key.$comparison.$objected_key;");
                return $result;
            }
        }else{
            # 按数值计算
            if ($filter['comparison'] == 'between') {
                $min_comparison = $mathObj->get_comparison('bthan');
                $max_comparison = $mathObj->get_comparison('sthan');

                $expression = $object_key.$min_comparison.$filter['compare_increment'].' && '.$object_key.$max_comparison.$filter['compare_increment'];

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

        /** 执行商品上下架更新规则
         *
         * @param int $regulationId 规则ID
         * @return bool
         */
    public static function exec_regulation_frame($apply)
    {
        # 规则详细
        $regulation = app::get('inventorydepth')->model('regulation')
                            ->select()->columns('content')
                            ->where('`condition`=?',$apply['condition'])
                            ->where('regulation_id=?',$apply['regulation_id'])
                            ->where('`using`=?','true')
                            ->instance()->fetch_row();
        if (empty($regulation)) {return false;}

        $frame = $regulation['content']['result'];

        # 更新规则应用信息
        $update = array(
            'al_exec' => ($apply['style'] == 'fix') ? 'true' : 'false',
            'exec_time' => time(),
        );
        app::get('inventorydepth')->model('regulation_apply')->update($update, array('id'=>$apply['id']));

        $approve_status = ($frame == 'upper') ? 'onsale' : 'instock';

        $mfilter = $apply['apply_goods'];
        if (!$apply['apply_goods']) {
            $mapping = app::get('inventorydepth')->model('regulation_mapping')->getList('pgid',array('type'=>'goods','apply_id'=>$apply['id']));
            if(!$mapping) return false;

            $mfilter['id'] = array_map('current',$mapping);
        }

        # 商品允许上下架回写
        $mfilter['frame_set'] = 'true';

        # 应用的店铺
        $shop_ids = $apply['shop_id'];
        if ($shop_ids[0] == '_ALL_') {
            $shops = app::get('inventorydepth')->model('shop')->getList('shop_id');

            $shop_ids = array_map('current', $shops);
        }
        //--

        $itemModel       = app::get('inventorydepth')->model('shop_items');
        $adjustmentModel = app::get('inventorydepth')->model('shop_adjustment');
        $calLib          = kernel::single('inventorydepth_stock_calculation');

        foreach ($shop_ids as $key => $shop_id) {
            $offset = 0; $limit = 100; $mfilter['shop_id'] = $shop_id;
            do {
                # 取商品
                $items = $itemModel->getList('iid,approve_status,shop_id,shop_bn,bn',$mfilter,$offset,$limit);
                if(!$items) break;

                foreach ($items as $item) {
                    # 获取所有已经匹配的货品
                    $skus = $adjustmentModel->getList('shop_product_bn,shop_bn,shop_stock,release_stock',array('shop_iid'=>$item['iid'],'shop_id'=>$item['shop_id'],'mapping'=>'1'));

                    if(!$skus) continue;

                    $calLib->init();

                    foreach ($regulation['content']['filters'] as $filter) {
                        if ($filter['forsku'] == 'each') {
                            foreach ($skus as $key => $sku) {
                                $params = array(
                                    'shop_product_bn' => $sku['shop_product_bn'],
                                    'shop_bn'         => $item['shop_bn'],
                                    'shop_id'         => $item['shop_id'],
                                );

                                $calLib->set_shop_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_stock']);
                                $calLib->set_release_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['release_stock']);

                                # 有一个货品为false，不进行上下架...
                                $allow_update = self::check_condition($filter,$params);

                                if(!$allow_update) continue 3;

                            }
                        } elseif ($filter['forsku'] == 'some') {
                            $allow = false;
                            foreach ($skus as $key => $sku) {
                                $params = array(
                                    'shop_product_bn' => $sku['shop_product_bn'],
                                    'shop_bn'         => $item['shop_bn'],
                                    'shop_id'         => $item['shop_id'],
                                );

                                $calLib->set_shop_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_stock']);
                                $calLib->set_release_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['release_stock']);

                                $allow_update = self::check_condition($filter,$params);

                                # 有一个货品满足，则不再进行判断
                                if ($allow_update == true) {
                                    $allow = true;
                                    break;
                                }

                            }

                            if ($allow === false) {
                                continue 2;
                            }
                        }
                    }

                    $list = array(
                        'iid' => $item['iid'],
                        'bn' => $item['bn'],
                        'approve_status' => $approve_status,
                    );

                    if ($approve_status == 'onsale') {
                        $num = $calLib->get_goods_actual_stock(array_map('current', $skus),$item['shop_bn'],$item['shop_id']);
                        if ($num === false) {
                            continue;
                        }
                        $list['num'] = $num;
                    }

                    $approve[] = $list;
                }

                if ($approve) {
                    kernel::single('inventorydepth_service_shop_frame')->approve_status_list_update($approve,$shop_id);
                }

                $offset += $limit; $approve = array();
            } while (true);
        }
    }

}
