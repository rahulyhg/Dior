<?php
/**
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_shop {
    const DOWNLOAD_ALL_LIMIT = 40;
    
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 店铺批量下载
     *
     * @return void
     * @author
     **/
    public function downloadList($shop_id,$filter,$page,&$errormsg)
    {
        $shop = $this->app->model('shop')->select()->columns('shop_id,shop_bn,name,node_id,shop_type,business_type')
                ->where('shop_id=?',$shop_id)
                ->instance()->fetch_row();

        if (!$shop) {
            $errormsg = $this->app->_('店铺不存在！'); return false;
        }

        if (!$shop['node_id']) {
            $errormsg = $this->app->_('店铺未绑定！'); return false;
        }
        
        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'],$shop['business_type']);
        if ($shopfactory === false) {
            $errormsg = $this->app->_('店铺类型有误！'); return false;
        }

        set_time_limit(0); ini_set('memory_limit','1024M');

        $data = $shopfactory->downloadList($filter,$shop_id,$page,self::DOWNLOAD_ALL_LIMIT,$errormsg);
        if($data === false) return false;

        if($data){
            $itemModel = $this->app->model('shop_items');
            $skuModel = $this->app->model('shop_skus');
            $skuModel->batchInsert($data,$shop,$stores);
            $itemModel->batchInsert($data,$shop,$stores);
        }

        return true;
    }

    /**
     * 批量下载商品 一次调用不超过20
     *
     * @return void
     * @author
     **/
    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $shop = $this->app->model('shop')->select()->columns('shop_id,shop_bn,name,node_id,shop_type,business_type')
                ->where('shop_id=?',$shop_id)
                ->instance()->fetch_row();

        if (!$shop) {
            $errormsg = $this->app->_('店铺不存在！'); return false;
        }

        if (!$shop['node_id']) {
            $errormsg = $this->app->_('店铺未绑定！'); return false;
        }

        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'],$shop['business_type']);
        if ($shopfactory === false) {
            $errormsg = $this->app->_('店铺类型有误！'); return false;
        }

        $result = $shopfactory->downloadByIIds($iids,$shop_id,$errormsg);
        if ($result) {
            # 保存数据
            $itemModel = $this->app->model('shop_items');
            foreach ($result as $item) {
                $itemModel->saveItem($item);
            }

            return true;
        }

        return false;
    }

    /**
     * 通过IID下载 单个
     *
     * @return void
     * @author
     **/
    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $shop = $this->app->model('shop')->select()->columns('shop_id,shop_bn,name,node_id,shop_type,business_type')
                ->where('shop_id=?',$shop_id)
                ->instance()->fetch_row();

        if (!$shop) {
            $errormsg = $this->app->_('店铺不存在！'); return false;
        }

        if (!$shop['node_id']) {
            $errormsg = $this->app->_('店铺未绑定！'); return false;
        }

        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'],$shop['business_type']);
        if ($shopfactory === false) {
            $errormsg = $this->app->_('店铺类型有误！'); return false;
        }
        
        $data = $shopfactory->downloadByIId($iid,$shop_id,$errormsg);

        if ($data) {
            $itemModel = $this->app->model('shop_items');
            $itemModel->saveItem($data,$shop);
        }

        return $data ? true : false;
    }

    /**
     * 通过SKU_ID下载,单个
     *
     * @param Array $sku SKU信息
     * @param String $shop_id 店铺ID
     * @param String $errormsg 错误信息
     * @return void
     * @author
     **/
    public function dowloadBySkuId($sku,$shop_id,&$errormsg)
    {
        $shop = $this->app->model('shop')->select()->columns('shop_id,shop_bn,name,node_id,shop_type')
                ->where('shop_id=?',$shop_id)
                ->instance()->fetch_row();

        if (!$shop) {
            $errormsg = $this->app->_('店铺不存在！'); return false;
        }

        if (!$shop['node_id']) {
            $errormsg = $this->app->_('店铺未绑定！'); return false;
        }

        $id = $sku['id'];

        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type']);
        if ($shopfactory === false) {
            $errormsg = $this->app->_('店铺类型有误！'); return false;
        }

        $data = $shopfactory->dowloadBySkuId($sku,$shop_id,$errormsg);
        if($data){
            # 更新货品
            $this->app->model('shop_skus')->updateSku($data,$id);
        }

        return $data ? true : false;
    }

    /**
     * 获取店铺对应的仓库
     *
     * @return void
     * @author
     **/
    public function getBranchByshop($shop_bn='')
    {
        if (!$this->branches) {
            $this->branches = app::get('ome')->getConf('shop.branch.relationship');
        }
        
        if(!$this->branches) return false;

        return $shop_bn ? $this->branches[$shop_bn] : $this->branches;
    }

    /**
     * 执行发布
     *
     * @param Array 商品记录ID
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function doRelease($ids,$shop_id,$dorelease = false)
    {
        $skus = $this->app->model('shop_adjustment')->getList('shop_product_bn,shop_stock,addon',array('shop_id'=>$shop_id,'id'=>$ids));
        if (!$skus) return false;

        if ($dorelease) {
            $update_columns['operator'] = kernel::single('desktop_user')->get_id();
            $update_columns['operator_ip'] = kernel::single('base_component_request')->get_remote_ip();
            $this->app->model('shop_adjustment')->update($update_columns,array('id'=>$ids));
        }

        foreach ($skus as $key => $sku) {
            $s = array(
                'bn'         => $sku['shop_product_bn'],
                'quantity'   => $sku['shop_stock'],
                'lastmodify' => time(),
            );

            if ($dorelease == false) {
                $s['memo'] = $sku['addon']['stock'];
            }

            $stocks[] = $s;
        }

        # 回写
        kernel::single('inventorydepth_service_shop_stock')->items_quantity_list_update($stocks,$shop_id,$dorelease);
    }

    /**
     * 往前端回写库存
     *
     * @return void
     * @author
     **/
    public function doStockRequest($stocks,$shop_id,$doRelease=false)
    {
        # 如果是手动发布，记录发布操作人
        if ($doRelease == true) {
            $data['operator']    = kernel::single('desktop_user')->get_id();
            $data['operator_ip'] = kernel::single('base_component_request')->get_remote_ip();

            $ids = array_keys($stocks);

            $adjustmentModel = $this->app->model('shop_adjustment');
            $adjustmentModel->update($data,array('id'=>$ids));

            //$adjustmentModel->update_shop_stock($ids);
        }

        # 回写开始
        kernel::single('inventorydepth_service_shop_stock')->items_quantity_list_update($stocks,$shop_id,$doRelease);
    }

    /**
     * 实时单个商品上下架
     *
     * @return void
     * @author
     **/
    public function doApproveSync($item,$shop_id,$shop_bn,&$msg)
    {
        # 如果是上架，计算总上架数
        if ($item['approve_status'] == 'onsale') {
            # 读取所有SKU
            $skus = $this->app->model('shop_skus')->getList('shop_product_bn,bind',array('shop_id'=>$shop_id,'shop_iid'=>$item['iid'],'mapping'=>'1'));
            if (!$skus) {
                $msg = '上架失败：货品未关联！';
                return false;
            }
            //$product_bn = array_map('current',$skus);

            # 捆绑商品上下架
            if ($skus[0]['bind'] == '1') {
                $num = kernel::single('inventorydepth_logic_pkgstock')->dealWithRegu($skus[0]['shop_product_bn'],$shop_id,$shop_bn);
            } else {
                $num = 0;
                foreach ($skus as $sku) {
                    $skuNum = kernel::single('inventorydepth_logic_stock')->dealWithRegu($sku['shop_product_bn'],$shop_id,$shop_bn);
                    if($skuNum === false) continue;

                    $num += (int)$skuNum;
                }
            }

            if($num === false){
                $msg = '上架失败：没有仓库为店铺【'.$shop_bn.'】供货！';
                return false;
            }
            if ($num == 0) {
                $msg = '上架失败：商品【'.$item['title'].'】库存为0，不能上架！';
                return false;
            }
            $item['num'] = $num;
        }
        
        # 获取店铺类型
        $shop = $this->app->model('shop')->getList('shop_type,business_type',array('shop_id'=>$shop_id),0,1);
        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop[0]['shop_type'],$shop[0]['business_type']);
        if ($shopfactory === false) {
            $msg = $this->app->_('店铺类型有误！'); return false;
        }
        $result = $shopfactory->doApproveSync($item,$shop_id,$msg);
        #$result = kernel::single('inventorydepth_service_shop_frame')->approve_status_update($item,$shop_id,$msg);

        if ($result == true) {
            $data = array(
                'approve_status' => $item['approve_status'],
            );
            $this->app->model('shop_items')->update($data,array('id'=>$item['id']));
        }

        return $result;
    }

    /**
     * 批量上下架商品
     *
     * @return void
     * @author
     **/
    public function doApproveBatch($filter,$approve_status,$offset=0,$limit=100,$oper=array())
    {
        // 记录操作日志
        $optLogModel = app::get('inventorydepth')->model('operation_log');

        $skuModel = $this->app->model('shop_skus');
        $calLib = kernel::single('inventorydepth_stock_calculation');
        $approve = array();

        # 读取店铺商品
        $itemModel = $this->app->model('shop_items');
        $itemModel->filter_use_like = true;
        $items = $itemModel->getList('iid,shop_id,shop_bn,bn,id',$filter,$offset,$limit);
        foreach ($items as $item) {
            $list = array(
                'iid'=>$item['iid'],
                'approve_status' => $approve_status,
                'bn' => $item['bn'],
            );

            # 上架，计算上架库存
            if ($approve_status == 'onsale') {
                $skuFilter = array(
                    'shop_iid' => $item['iid'],
                    'shop_id' => $item['shop_id'],
                    'mapping' => '1',
                );
                # SKU
               $skus = $skuModel->getList('shop_product_bn,bind',$skuFilter);
                if ($skus) {
                    //$product_bn = array_map('current', $skus);
                    if ($skus[0]['bind'] == '1') {
                        $num = kernel::single('inventorydepth_logic_pkgstock')->dealWithRegu($skus[0]['shop_product_bn'],$item['shop_id'],$item['shop_bn']);
                    } else {
                        //$num = kernel::single('inventorydepth_logic_stock')->dealWithRegu($product_bn,$item['shop_id'],$item['shop_bn']);
                        $num = 0;
                        foreach ($skus as $sku) {
                            $skuNum = kernel::single('inventorydepth_logic_stock')->dealWithRegu($sku['shop_product_bn'],$item['shop_id'],$item['shop_bn']);
                            if($skuNum === false) continue;
                            $num += (int)$skuNum;
                        }
                    }
                    if ($num === false || $num == 0) continue;

                    $list['num'] = $num;
                } else {
                    continue;
                }
            }

            $approve[] = $list;

            $optLogModel->write_log('item',$item['id'],'approve',($approve_status=='onsale' ? '批量上架' : '批量下架'),$oper);
        }

        if ($approve && $filter['shop_id']) {
            # 获取店铺类型
            $shop = $this->app->model('shop')->getList('shop_type',array('shop_id'=>$filter['shop_id']),0,1);
            $shopfactory = inventorydepth_service_shop_factory::createFactory($shop[0]['shop_type']);
            if ($shopfactory === false) {
                $msg = $this->app->_('店铺类型有误！'); return false;
            }
            $shopfactory->doApproveBatch($approve,$filter['shop_id'],false);
            # 同步前端
            #kernel::single('inventorydepth_service_shop_frame')->approve_status_list_update($approve,$filter['shop_id'],false);
        }
    }


    /**
     * 获取自动回写值
     *
     * @return void
     * @author
     **/
    public function getStockConf($shop_id)
    {
        $request = app::get('ome')->getConf('request_auto_stock_' . $shop_id);
        if ($request == 'false') {
            return 'false';
        }

        $request = $this->app->getConf('request_auto_stock_'.$shop_id);

        return ($request === 'true') ? 'true' : 'false';
    }

    /**
     * 保存自动回写值
     *
     * @return void
     * @author
     **/
    public function setStockConf($shop_id,$value)
    {
        $this->app->setConf('request_auto_stock_'.$shop_id,$value);

        app::get('ome')->setConf('request_auto_stock_' . $shop_id, $value);
    }

    /**
     * 获取自动上下架设置
     *
     * @return void
     * @author
     **/
    public function getFrameConf($shop_id)
    {
        $request = $this->app->getConf('request_auto_frame_'.$shop_id);

        return ($request === 'true') ? 'true' : 'false';
    }

    /**
     * 保存自动上下架设置
     *
     * @return void
     * @author
     **/
    public function setFrameConf($shop_id,$value)
    {
        $this->app->setConf('request_auto_frame_'.$shop_id,$value);
    }

    /**
     * 保存店铺同步状态
     *
     * @return void
     * @author
     **/
    public function setShopSync($shop_id,$value)
    {
        base_kvstore::instance('inventorydepth/shop/synchronizing')->store('shop_synchronizing_'.$shop_id,$value,(time()+3600));
    }

    /**
     * 获取同步状态
     *
     * @return void
     * @author
     **/
    public function getShopSync($shop_id)
    {
        base_kvstore::instance('inventorydepth/shop/synchronizing')->fetch('shop_synchronizing_'.$shop_id,$sync);
        return ($sync === 'true') ? 'true' : 'false';
    }

    public static function array_addslashes($temp_arr) {
        foreach ($temp_arr as $key => $value) {
            if (is_array($value)) {
                $value = self::array_addslashes($value);
                $array_temp[$key] = $value;
            } else {
                $array_temp[$key]=addslashes($value);
            }
        }
        return $array_temp;
    }
}
