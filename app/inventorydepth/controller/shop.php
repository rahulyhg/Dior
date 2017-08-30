<?php
/**
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_ctl_shop extends desktop_controller {

    var $workground = 'resource_center';
    var $defaultWorkground = 'resource_center';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }

    /**
     * 店铺资源列表
     *
     * @return void
     * @author
     **/
    public function index()
    {
        $base_filter = array('filter_sql'=>'{table}node_id is not null and {table}node_id !=""');
        if (app::get('drm')->is_installed()) {
            $channelShopObj = &app::get('drm')->model('channel_shop');
            $rows = $channelShopObj->getList('shop_id');
            foreach($rows as $val){
                $shopIds[] = $val['shop_id'];
                $base_filter['shop_id|notin'] = $shopIds;
            }
        }
        $params = array(
            'title' => $this->app->_('店铺资源'),
            'actions' => array(
                //0 => array('label'=>$this->app->_('开启回写库存'),'submit'=>'index.php?app=inventorydepth&ctl=shop&act=set_request&p[0]=true'),
                //1 => array('label'=>$this->app->_('关闭回写库存'),'submit'=>'index.php?app=inventorydepth&ctl=shop&act=set_request&p[0]=false'),
                //2 => array('label'=>$this->app->_('开启自动上下架'),'submit'=>'index.php?app=inventorydepth&ctl=shop&act=set_frame&p[0]=true'),
                //3 => array('label'=>$this->app->_('关闭自动上下架'),'submit'=>'index.php?app=inventorydepth&ctl=shop&act=set_frame&p[0]=false'),
            ),
            //'finder_cols' => 'shop_bn,name,last_store_sync_time',
            'use_buildin_recycle' => false,
            'base_filter' => $base_filter,
        );

        $this->finder('inventorydepth_mdl_shop',$params);
    }


    /**
     * 回写设置
     *
     * @return void
     * @author
     **/
    public function set_request($config = 'true',$shop_id = null)
    {
        if($shop_id) $shop_id = array($shop_id);

        if($_POST['shop_id']) $shop_id = $_POST['shop_id'];

        if($_POST['isSelectedAll'] == '_ALL_'){
            $shops = $this->app->model('shop')->getList('shop_id',$_POST);
            $shop_id = array_map('current', $shops);
        }

        if ($shop_id) {
            foreach ($shop_id as $key => $value) {
                //app::get('ome')->setConf('request_auto_stock_' . $value, $config);
                kernel::single('inventorydepth_shop')->setStockConf($value,$config);

                // 记录操作日志
                $optLogModel = app::get('inventorydepth')->model('operation_log');
                $optLogModel->write_log('shop',$value,'stockset',($config=='true' ? '开启库存回写' : '关闭库存回写'));
            }
            $this->splash('success','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);',$this->app->_('设置成功'));
        }else{
            $this->splash('error','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);',$this->app->_('请选择店铺'));
        }
    }

    /**
     * 上下架设置
     *
     * @return void
     * @author
     **/
    public function set_frame($config = 'true',$shop_id = null)
    {
        if($shop_id) $shop_id = array($shop_id);

        if($_POST['shop_id']) $shop_id = $_POST['shop_id'];

        if($_POST['isSelectedAll'] == '_ALL_'){
            $shops = $this->app->model('shop')->getList('shop_id',$_POST);
            $shop_id = array_map('current', $shops);
        }

        if ($shop_id) {
            foreach ($shop_id as $key => $value) {
                //app::get('ome')->setConf('request_auto_frame_' . $value, $config);
                kernel::single('inventorydepth_shop')->setFrameConf($value,$config);
            }
            $this->splash('success','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);',$this->app->_('设置成功'));
        }else{
            $this->splash('error','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);',$this->app->_('请选择店铺'));
        }
    }

    /**
     * 商品下载页
     *
     * @return void
     * @author
     **/
    public function download_page($downloadType='',$shop_id='')
    {
        $downloadType = $downloadType ? $downloadType : $_GET['downloadType'];
        $shop_id = $shop_id ? $shop_id : $_GET['shop_id'];
        switch ($downloadType) {
            case 'shop':
                $url = 'index.php?app=inventorydepth&ctl=shop&act=downloadByShop&p[0]='.$shop_id;
                $shop = $this->app->model('shop')->getList('shop_type,business_type',array('shop_id'=>$shop_id),0,1);
                $shopfactory = inventorydepth_service_shop_factory::createFactory($shop[0]['shop_type'],$shop[0]['business_type']);
                if ($shopfactory) {
                    $loadList = $shopfactory->get_approve_status();
                }
                $this->pagedata['shop_id'] = $shop_id;
                break;
            case 'iid':
                $item = $this->app->model('shop_items')->getList('id,title',array('id'=>$_GET['id']),0,1);
                if($item){
                    $loadList[$item[0]['id']] = array('name'=>($item ? 'ITEM:'.$item[0]['title'] : '空'));
                }
                $url = 'index.php?app=inventorydepth&ctl=shop&act=downloadByIId&p[0]='.$_GET['id'];
                break;
            case 'sku_id':
                $sku = $this->app->model('shop_skus')->getList('id,shop_title',array('id'=>$_GET['id']),0,1);
                if($sku){
                    $loadList[$sku[0]['id']] = array('name'=>($sku ? 'SKU:'.$sku[0]['shop_title'] : '空'));
                }
                $url = 'index.php?app=inventorydepth&ctl=shop&act=dowloadBySkuId&p[0]='.$_GET['id'];
                break;
            case 'iids':
                $url = 'index.php?app=inventorydepth&ctl=shop&act=downloadByIIds&p[0]='.$_GET['id'];
                break;
            default:
                $url = '';
                break;
        }

        $this->pagedata['url'] = $url;
        $this->pagedata['loadList'] = $loadList;
        $this->pagedata['width'] = intval(100/count($loadList));
        $this->pagedata['downloadType'] = $downloadType;

        if ($_GET['redirectUrl']) {
            $this->pagedata['redirectUrl'] = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }

        $_POST['time'] = time();
        if ($_POST) {
            $inputhtml = '';
            $post = http_build_query($_POST);
            $post = explode('&', $post);
            foreach ($post as $p) {
                list($name,$value) = explode('=', $p);
                $params = array(
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        $this->display('shop/download_page.html');
    }

    /**
     * 按店铺下载
     *
     * @return void
     * @author
     **/
    public function downloadByShop($shop_id)
    {
        if(!$shop_id) $this->splash('error',null,$this->app->_('请选择店铺！'));
        $page = $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $flag = $_GET['flag'];

        $shop = $this->app->model('shop')->select()->columns('name,shop_type,business_type')
                        ->where('shop_id=?',$shop_id)->instance()->fetch_row();

        if (!inventorydepth_shop_api_support::items_all_get_support($shop['shop_type'])) {
            $this->splash('error',null,$this->app->_("暂不支持对店铺【{$shop['name']}】商品的同步!"));
        }

        $shopLib = kernel::single('inventorydepth_shop');
        # 查看是否在同步中
        $sync = $shopLib->getShopSync($shop_id);
        if ($sync === 'true') {
            $this->splash('error',null,$this->app->_("其他人正在同步中，请稍后同步!"));
        }

        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'],$shop['business_type']);
        if ($shopfactory == false) {
            $this->splash('error',null,$this->app->_("工厂生产类失败!"));
        }

        $approve_status = $shopfactory->get_approve_status($flag,$exist);
        if ($exist == false) {
            $this->splash('error',null,$this->app->_("标记异常!"));
        }

        $shopLib->setShopSync($shop_id,'true');
        try{
            $result = $shopLib->downloadList($shop_id,$approve_status['filter'],$page,$errormsg);
        } catch (Exception $e) {
            $errormsg = '同步失败：网络异常';
        }
        $shopLib->setShopSync($shop_id,'false');

        $errormsg = is_array($errormsg) ? implode('<br/>',$errormsg) : $errormsg;

        if ($result === false) {
            $this->splash('error',null,$errormsg);
        }else{
            $loading = $shopfactory->get_approve_status();
            $rate = $loading ? 100/count($loading) : 100;
            $totalResults = $shopfactory->getTotalResults();
            $msg = '同步完成';
            $downloadStatus = 'running';
            # 判断是否已经全部下载完
            if($page >= ceil($totalResults/inventorydepth_shop::DOWNLOAD_ALL_LIMIT) || $totalResults==0){
                $msg = '全部下载完';
                $downloadStatus = 'finish';
                $downloadRate = $rate * ($flag+1);
                if($_POST['time'] && count($loading)==($flag+1)){
                    base_kvstore::instance('inventorydepth/batchframe')->store('downloadTime'.$shop_id,$_POST['time']);
                    $itemModel = $this->app->model('shop_items');
                    $skuModel = $this->app->model('shop_skus');
                    $itemModel->deletePassData($shop_id,$_POST['time']);
                    $skuModel->deletePassData($shop_id,$_POST['time']);
                }
            } else {
                $downloadRate = $rate*$flag+$page*inventorydepth_shop::DOWNLOAD_ALL_LIMIT/$totalResults*$rate;
            }
            $this->splash('success',null,$msg,'redirect',array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>intval($downloadRate),'downloadStatus'=>$downloadStatus));
        }
    }

    /**
     * 通过IID下载
     *
     * @return void
     * @author
     **/
    public function downloadByIIds()
    {die('方法废弃');

        if(!$_POST['id'] && !$_POST['isSelectedAll']) $this->splash('error',null,$this->app->_('请选择商品'));

        # 验证是否包含非淘宝SKU
        $_POST['shop_type|noequal'] = 'taobao';

        $itemModel = $this->app->model('shop_items');
        $other = $itemModel->getList('id',$_POST,0,1);
        if ($other) {
            $this->splash('error',null,$this->app->_('除淘宝店铺外，暂不支持其他店铺下载'));
        }
        unset($_POST['shop_type|noequal']);


        $_POST['shop_type'] = 'taobao';

        # 获取所有淘宝店铺
        set_time_limit(0);
        $itemModel->appendCols = '';
        $taobao_shops = $itemModel->getList(' distinct shop_id',$_POST);
        foreach ($taobao_shops as $shop) {
            $offset = 0; $limit = 20; $_POST['shop_id'] = $shop['shop_id'];
            do {
                $taobao_iids = $itemModel->getList('iid',$_POST,$offset,$limit);

                if (!$taobao_iids) break;

                $iids = array_map('current', $taobao_iids);

                kernel::single('inventorydepth_shop')->downloadByIId($iids,$shop['shop_id'],$errormsg);

                $offset += $limit;

            } while (true);
        }

        $this->splash('success',null);
    }

    /**
     * 通过IID下载单个
     *
     * @return void
     * @author
     **/
    public function downloadByIId($id=null)
    {

        if(!$id) $this->splash('error',null,$this->app->_('请选择商品!'));

        $item = $this->app->model('shop_items')->select()->columns('iid,shop_id,shop_type,shop_name')
                ->where('id=?',$id)->instance()->fetch_row();

        if (!$item) {
            $this->splash('error',null,$this->app->_('商品记录为空!'));
        }

        # 验证是否包含非淘宝SKU
        if (!inventorydepth_shop_api_support::items_get_support($item['shop_type'])) {
            $msg = '暂不支持对'.$item['shop_name'].'店铺商品下载';
            $this->splash('error',null,$msg);
        }

        $result = kernel::single('inventorydepth_shop')->downloadByIId($item['iid'],$item['shop_id'],$errormsg);

        $status = $result ? 'success' : 'error';
        $downloadRate = $result ? '100' : '0';
        $downloadStatus = $result ? 'finish' : 'running';

        $this->splash($status,null,$errormsg,'redirect',array('downloadRate'=>$downloadRate,'downloadStatus'=>$downloadStatus));

    }

    /**
     * 通过SKU_ID下载货品， 针对单个
     *
     * @param Int $id  货品记录ID
     * @return void
     * @author
     **/
    public function dowloadBySkuId($id = null)
    {

        if(!$id) $this->splash('error',null,$this->app->_('请选择SKU'));

        # 获取货品必要信息
        $sku = $this->app->model('shop_skus')->select()->columns('shop_id,shop_bn,shop_type,shop_sku_id,shop_name,shop_iid')
                ->where('id=?',$id)->instance()->fetch_row();

        if(!$sku) $this->splash('error',null,$this->app->_('该货品不存在'));

        # 验证货品对应的店铺是否支持接口
        if (!inventorydepth_shop_api_support::item_sku_get_support($sku['shop_type'])) {
            $msg = '暂不支持对'.$sku['shop_name'].'店铺商品下载';
            $this->splash('error',null,$msg);
        }

        $data = array(
            'sku_id' => $sku['shop_sku_id'],
            'iid'   => $sku['shop_iid'],
            'id' => $id,
        );

        # 同步
        $result = kernel::single('inventorydepth_shop')->dowloadBySkuId($data,$sku['shop_id'],$errormsg);

        if ($result) {
            $status = 'success';
            $msg = $this->app->_('同步完成!');
            $downloadRate = '100';
            $downloadStatus = 'finish';
        }else{
            $status = 'error';
            $msg = $errormsg;
            $downloadRate = '0';
            $downloadStatus = 'running';
        }

        $this->splash($status,null,$msg,'redirect',array('downloadRate'=>$downloadRate,'downloadStatus'=>$downloadStatus));
    }

    /**
     * 上下架调整
     *
     * @return void
     * @author
     **/
    public function jump($type)
    {
        switch ($type) {
            case 'item':
                $this->pagedata['url'] = 'index.php?app=inventorydepth&ctl=shop_frame&act=index';
                break;
            case 'sku':
                $this->pagedata['url'] = 'index.php?app=inventorydepth&ctl=shop_adjustment&act=index';
                break;
            case 'frame':
                $this->pagedata['url'] = 'index.php?app=inventorydepth&ctl=shop_frame&act=index';
                break;
            case 'warning':
                $this->pagedata['url'] = 'index.php?app=inventorydepth&ctl=shop_batchframe&act=redownload';
                break;
            default:
                # code...
                break;
        }

        $shops = $this->app->model('shop')->getList('shop_id,shop_bn,name,shop_type,node_id');

        $s = array_intersect(inventorydepth_shop_api_support::$item_sku_get_shops,inventorydepth_shop_api_support::$items_all_get_shops,inventorydepth_shop_api_support::$items_get_shops);

        if (app::get('drm')->is_installed()) {
            $channelShopObj = &app::get('drm')->model('channel_shop');
            $rows = $channelShopObj->getList('shop_id');
            foreach($rows as $val){
                $channelShop[] = $val['shop_id'];
            }
        }

        $support_shops = $unsupport_shops = array();
        foreach ($shops as $key=>$shop) {
            if (!in_array($shop['shop_id'],$channelShop) && in_array($shop['shop_type'],$s) && $shop['node_id']) {
                $support_shops[] = $shop;
            } else {
                $unsupport_shops[] = $shop;
            }
        }

        $this->pagedata['support_shops'] = $support_shops;

        $this->pagedata['unsupport_shops'] = $unsupport_shops;

        $this->pagedata['type'] = $type;

        $this->page('shop/shopjump.html');
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function downloadfinish($shop_id,$time=0)
    {
        if(!$shop_id) {echo '请选择店铺！';exit;}

        $shop = $this->app->model('shop')->select()->columns('name,shop_type')
                        ->where('shop_id=?',$shop_id)->instance()->fetch_row();
        if(!$shop) {echo '店铺不存在！';exit;}

        $shopLib = kernel::single('inventorydepth_shop');
        # 查看是否在同步中
        $shopLib->setShopSync($shop_id,'false');

        echo '店铺同步解锁成功';exit;
    }

}
