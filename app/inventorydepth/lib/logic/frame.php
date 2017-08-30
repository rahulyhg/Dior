<?php
/**
 * 上下架同步处理类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_logic_frame extends inventorydepth_logic_abstract
{
    const STOCK_CHANGE_TIME = 300;  // 库存变动执行间隔

    const FIX_TIME = 60;            // 定时执行间隔

    /* 当前的执行时间 */
    public static $now;

    public function __construct($app)
    {
        $this->app = $app;
        self::$now = time();
    }

    public function start() {
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        
        # 获取已经连接的店铺
        $filter = array(
            'filter_sql' =>'{table}node_id is not null',
        );
        $tmp_shops = $this->app->model('shop')->getList('shop_id,shop_bn',$filter);
        foreach($tmp_shops as $shop){
            $shops[$shop['shop_id']] = $shop;
        }
        unset($tmp_shops);

       $regu = $this->getRegu();
       if(empty($regu)) return;

        $itemsModel = $this->app->model('shop_items');
        $stockCalLib = kernel::single('inventorydepth_stock_calculation');

        $adjustmentModel = $this->app->model('shop_adjustment');
        base_kvstore::instance('inventorydepth/apply/frame')->fetch('apply-lastexectime',$lastExecTime);
        foreach($regu as $r){
            if(!$r['apply_goods'] || !$r['regulation']) continue;

            switch ($r['style']) {
                case 'stock_change':
                    # 判断是否达到了间隔要求,第一次执行，直接跳过
                    if ($lastExecTime[$r['id']] && ($lastExecTime[$r['id']]+self::STOCK_CHANGE_TIME)>self::$now) {
                        // 没到5分钟
                        continue;
                    }
                    break;
                case 'fix':
                    # 判断是否达到了间隔要求
                    if ($lastExecTime[$r['id']] && ($lastExecTime[$r['id']] + self::FIX_TIME)>self::$now) {
                        // 没到1分钟
                        continue;
                    }
                    break;
            }
            # 更新规则应用信息
            $update = array(
                'al_exec' => ($r['style'] == 'fix') ? 'true' : 'false',
                'exec_time' => time(),
            );
            app::get('inventorydepth')->model('regulation_apply')->update($update, array('id'=>$r['id']));
            $approve_status = ($r['regulation']['content']['result'] == 'upper') ? 'onsale' : 'instock';

            if ($r['shop_id'] == array('_ALL_')) {
                # 获取已经连接的店铺
                $filter = array(
                    'filter_sql' =>'{table}node_id is not null and {table}node_id !=""',
                );
                $shops = $this->app->model('shop')->getList('shop_id',$filter);

                if ($shops) {
                    $r['shop_id'] = array_map('current',$shops);
                }
            }
            foreach($r['shop_id'] as $shop_id){
                # 店铺是否开启自动上下架
                $request = kernel::single('inventorydepth_shop')->getFrameConf($shop_id);
                if($request != 'true') { continue; }

                $filter = array(
                    'id' => $r['apply_goods'],
                    'frame_set' => 'true',
                    'shop_id' => $shop_id,
                    'approve_status' => ($approve_status == 'onsale' ? 'instock' : 'onsale')
                );
                $offset = 0; $limit = 50; $approve = array();
                do {
                    $apply_goods = $itemsModel->getList('iid,approve_status,shop_id,shop_bn,bn',$filter,$offset,$limit);
                    if(!$apply_goods) break;

                    $tmp_apply_goods = $apply_goods; unset($apply_goods);
                    foreach ($tmp_apply_goods as $key=>$item) {
                        $apply_goods[$item['iid']] = $item;
                    }
                    $skus = $this->app->model('shop_adjustment')
                                ->getList('shop_product_bn,shop_bn,shop_stock,release_stock,bind,shop_iid',array('shop_iid'=>array_keys($apply_goods),'shop_id'=>$shop_id,'mapping'=>'1'));
                    if(!$skus){ $offset += $limit; continue;}

                    foreach ($skus as $key=>$sku) {
                        $apply_goods[$sku['shop_iid']]['skus'][$sku['shop_product_bn']] = $sku;

                        $product_bn[] = $sku['shop_product_bn'];
                    }
                    unset($skus,$tmp_apply_goods);
                    # 数据写内存
                    if ($product_bn) {
                        $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$product_bn));
                        if(!$products){ $offset += $limit; continue; }

                        kernel::single('inventorydepth_stock_products')->writeMemory($products);
                        unset($products);
                    }



                    foreach ($apply_goods as $item) {
                        $valid = $this->valid($r['regulation']['content']['filters'],$item['skus'],$item['shop_id'],$item['shop_bn']);
                        if ($valid == false) { continue; }

                        $list = array(
                            'iid' => $item['iid'],
                            'bn' => $item['bn'],
                            'approve_status' => $approve_status,
                        );

                        if ($approve_status == 'onsale') {
                            if ($item['skus'][0]['bind'] == '1') {
                                $num = $stockCalLib->get_pkg_actual_stock($item['skus'][0]['shop_product_bn'],$item['shop_bn'],$item['shop_id']);
                            } else {
                                $num = $stockCalLib->get_goods_actual_stock(array_keys($item['skus']),$item['shop_bn'],$item['shop_id']);
                            }
                            if ($num === false) {
                                continue;
                            }
                            $list['num'] = $num;
                        }

                        $approve[] = $list;
                    }

                    if(empty($approve)) break;

                    if ($approve) {
                        # 获取店铺类型
                        $shop = $this->app->model('shop')->getList('shop_type',array('shop_id'=>$shop_id),0,1);
                        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop[0]['shop_type']);
                        if ($shopfactory) {
                            $shopfactory->doApproveBatch($approve,$shop_id);
                        }
                        #kernel::single('inventorydepth_service_shop_frame')->approve_status_list_update($approve,$shop_id);
                    }
                    $offset += $limit; $approve = array();

                    if(count($apply_goods) < 50) break;

                }while(true);
            }

           $lastExecTime[$r['id']] = time();
        }

        base_kvstore::instance('inventorydepth/apply/frame')->store('apply-lastexectime',$lastExecTime);
    }

    public function valid($regu_filter,$skus,$shop_id,$shop_bn)
    {
        if(!$skus) return false;

        $stockCalLib = kernel::single('inventorydepth_stock_calculation');
        $stockCalLib->init();
        $allow_update = false;
        foreach ($regu_filter as $filter) {
            switch ($filter['forsku']) {
                case 'each':
                    foreach ($skus as $sku) {
                        $params = array(
                            'shop_product_bn' => $sku['shop_product_bn'],
                            'shop_bn'         => $shop_bn,
                            'shop_id'         => $shop_id,
                        );

                        if ($sku['bind'] == '1') {
                            $stockCalLib->set_pkg_shop_stock($sku['shop_product_bn'],$shop_bn,$sku['shop_stock']);
                            $stockCalLib->set_pkg_release_stock($sku['shop_product_bn'],$shop_bn,$sku['release_stock']);
                            $allow_update = kernel::single('inventorydepth_logic_pkgstock')->check_condition($filter,$params);
                        } else {
                            $stockCalLib->set_shop_stock($sku['shop_product_bn'],$shop_bn,$sku['shop_stock']);
                            $stockCalLib->set_release_stock($sku['shop_product_bn'],$shop_bn,$sku['release_stock']);

                            # 有一个货品为false，不进行上下架...
                            $allow_update = $this->check_condition($filter,$params);
                        }
                        # 不满足规则，返回
                        if(!$allow_update) return false;

                    }

                    //-- 执行下一条规则
                    break;
                case 'some';
                    foreach ($skus as $sku) {
                        $params = array(
                            'shop_product_bn' => $sku['shop_product_bn'],
                            'shop_bn'         => $shop_bn,
                            'shop_id'         => $shop_id,
                        );

                        if ($sku['bind'] == '1') {
                            $stockCalLib->set_pkg_shop_stock($sku['shop_product_bn'],$shop_bn,$sku['shop_stock']);
                            $stockCalLib->set_pkg_release_stock($sku['shop_product_bn'],$shop_bn,$sku['release_stock']);
                            $allow_update = kernel::single('inventorydepth_logic_pkgstock')->check_condition($filter,$params);
                        } else {
                            $stockCalLib->set_shop_stock($sku['shop_product_bn'],$shop_bn,$sku['shop_stock']);
                            $stockCalLib->set_release_stock($sku['shop_product_bn'],$shop_bn,$sku['release_stock']);

                            # 有一个货品为false，不进行上下架...
                            $allow_update = $this->check_condition($filter,$params);
                        }

                        if($allow_update) { break;}
                    }

                    if(!$allow_update) { return false;}
                    //-- 执行下一条规则
                    break;
            }
        }
        return $allow_update;
    }

    public function getRegu() {
        if(!$this->regu) {
            $filter = array(
                'start_time|sthan' =>self::$now,
                'end_time|bthan' =>self::$now,
                'using' =>'true',
                'al_exec' => 'false',
                'condition' => 'frame',
            );
            $this->regu = $this->app->model('regulation_apply')->getList('*',$filter,0,-1,'type desc,priority desc');

            foreach($this->regu as $key=>$value){
                $this->regu[$key]['shop_id'] = explode(',',$value['shop_id']);
                $this->regu[$key]['apply_goods'] = explode(',',$value['apply_goods']);
                $this->regu[$key]['regulation'] = &$regulation[$value['regulation_id']];
            }

            if($regulation){
                $rr = $this->app->model('regulation')->getList('*',array('regulation_id'=>array_keys($regulation)));
                foreach($rr as $r){
                    $regulation[$r['regulation_id']] = $r;
                }
            }
        }

        return $this->regu;
    }

    public function getExecRegu($item)
    {
       $regu = $this->getRegu();
       if(empty($regu)) return '';

        foreach($regu as $r){
            if(!$r['apply_goods'] || !$r['regulation']) continue;

            if ((in_array($item['id'],$r['apply_goods']) || $r['apply_goods'][0]=='_ALL_') && (in_array($item['shop_id'],$r['shop_id']) || $r['shop_id'][0]=='_ALL_')) {
                $valid = $this->valid($r['regulation']['content']['filters'],$item['skus'],$item['shop_id'],$item['shop_bn']);
                if ($valid == false) { continue; }

                $exec_regu = array(
                    'regulation_id' => $r['regulation_id'],
                    'heading' => $r['regulation']['heading'],
                );
                return $exec_regu;
            }
        }

        return '';
    }

}
