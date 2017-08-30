<?php
class console_ctl_admin_goodssync extends desktop_controller{
    var $workground = "console_center";
	   function index(){
        $finder_id = $_REQUEST['_finder']['finder_id'];
        if(!isset($_GET['wms_id'])){
            $wms_list = kernel::single('channel_func')->getWmsChannelList();
            $_GET['wms_id'] = $wms_list[0]['wms_id'];
        }
        $filter = array('wms_id'=>$_GET['wms_id']);
        $params = array(
            'title'=>app::get('wms')->_('基础物料分配'),
            'actions'=>array(
                 array(
                    'label' => '批量同步',
                    'submit' => 'index.php?app=console&ctl=admin_goodssync&act=sync&wms_id='.$_GET['wms_id'],
                 ),
                 array(
                    'label' => '货品分配',
                    'href' => 'index.php?app=console&ctl=admin_goodssync&act=dispatch&wms_id='.$_GET['wms_id'].'&view='.$_GET['view']."&finder_id=".$finder_id,
                    'target' => "_blank",
                 ),
                 array(
                    'label' => '货品分配模板',
                     'href' => 'index.php?app=console&ctl=admin_goodssync&act=downloadTemplate',
                     'target' => "_blank",
                 ),
                array(
                    'label' => '货品分配导入',
                    'href' => 'index.php?app=console&ctl=admin_goodssync&act=importTemplate&wms_id='.$_GET['wms_id'],
                    'target' => "dialog::{width:400,height:170,title:'导入商品'}",
                ),
            ),
            'use_buildin_recycle'=>true,
            'use_buildin_selectrow'=>true,
            'use_bulidin_view'=>true,
            'use_buildin_filter'=>true,
        );
        if(isset($_GET['wms_id'])){
            if($_GET['wms_id'] != '0'){
                $params['base_filter'] = array('wms_id'=>$_GET['wms_id']);

                // 获取node_type
                $node_type = kernel::single('channel_func')->getWmsNodeTypeById($_GET['wms_id']);
                if ($node_type == 'qimen') {
                    $params['actions'][0] = array(
                        'label'  => '批量同步',
                        'submit' => 'index.php?app=console&ctl=admin_goodssync&act=batchSyncDialog&p[0]='.$_GET['wms_id'],
                        'target' => 'dialog::{width:690,height:200,title:\'批量同步\'}',
                    );
                }
            }
        }else{
            $wms_list = kernel::single('channel_func')->getWmsChannelList();
            $wms_id = $wms_list[0]['wms_id'];
            $params['base_filter'] = array('wms_id'=>$wms_id);
        }
        //商品主数据
        $this->finder('console_mdl_foreign_sku',$params);
    }

    function _views($flag = 'true'){

        $wfsObj = &app::get('console')->model('foreign_sku');
        $data = kernel::single('channel_func')->getWmsChannelList();
        $show_menu = array();
        foreach((array)$data as $c_k=>$c_v)
        {
            $result['label'] = $c_v['wms_name'];
            $result['optional'] = '';
            $result['filter'] = array('wms_id' => $c_v['wms_id']);
            $result['href'] =  $this->_views_href($c_k,$c_v['wms_id']);
            $result['addon'] = $wfsObj->count($result['filter']);
            $result['addon'] = $result['addon'] ?$result['addon'] :'_FILTER_POINT_';
            $result['show'] = 'true';
            $wms[] = $c_v['wms_id'];
            $show_menu[] = $result;
        }
        $count = count($show_menu);
        $show_menu[$count]['label'] = '全部';
        $show_menu[$count]['optional'] = '';
        $show_menu[$count]['filter'] = array('wms_id|in'=>$wms);
        $show_menu[$count]['href'] =  $this->_views_href($count,0);
        $show_menu[$count]['addon'] = $wfsObj->count($show_menu[$count]['filter']);
         $show_menu[$count]['addon'] =  $show_menu[$count]['addon'] ? $show_menu[$count]['addon'] :'_FILTER_POINT_';
        $show_menu[$count]['show'] = 'true';
        return $show_menu;
	   }

    function _views_href($view,$wms_id)
    {
        $href = "index.php?app=console&ctl=admin_goodssync&act=".$_GET['act']."&view=".($view)."&wms_id=".($wms_id);
        return $href;
    }

    //商品同步
    function sync(){
        if ($_POST['filter']) {
            parse_str($_POST['filter'],$filter);unset($_POST['filter']);
            $_POST = array_merge((array)$_POST, (array)$filter); 
            $_REQUEST = array_merge((array)$_REQUEST, (array)$filter);
        }

        if(empty($_REQUEST['inner_product_id']) && $_REQUEST['isSelectedAll'] != '_ALL_') return NULL;
        $wms_id = $_POST['wms_id'] ? $_POST['wms_id'] : $_GET['wms_id'];
        $view =  $_POST['view'] ? intval($_POST['view']) : intval($_GET['view']);
        // $this->begin('index.php?app=console&ctl=admin_goodssync&act=index&wms_id='.$wms_id.'&view='.$view);

        if(empty($_REQUEST['inner_product_id']) && $_REQUEST['isSelectedAll'] != '_ALL_') return NULL;
        $productsObj = &app::get('ome')->model('products');
        $wfsObj = &app::get('console')->model('foreign_sku');
        $title = '货品同步';
        //全部选中处理
        if($_POST['isSelectedAll'] == '_ALL_'){

            //同步全部商品
            kernel::single('console_goodssync')->sync_all($_POST);
            // $this->end(true,'商品已放入系统队列,正在同步中,请稍候...');

        }else{
            $product_ids = $_REQUEST['inner_product_id'];
        }
        if(count($product_ids) > 2000){

            $product_ids_tmp = array_chunk($product_ids,2000,true);
            $count = ceil(count($product_ids)/2000);
            for($i=0;$i<$count;$i++){
                #插入队列
                $product_ids_tmp[$i]['wms_id'] = $_REQUEST['wms_id'];
                $product_ids_tmp[$i]['branch_bn'] = $_POST['branch_bn'];
                kernel::single('console_goodssync')->sync_all($product_ids_tmp[$i]);

            }
        }else{
            if ($product_ids){
                $product_sdf = array();
                $product_ids = (array)$product_ids;

                $sql = 'SELECT p.bn,p.name,p.product_id,p.barcode,p.price,p.weight,p.spec_info as property,b.brand_name as brand,t.name as goods_cat FROM `sdb_ome_products` as p LEFT JOIN sdb_ome_goods as g on p.goods_id=g.goods_id LEFT JOIN sdb_ome_brand as b on g.brand_id=b.brand_id LEFT JOIN sdb_ome_goods_type as t ON t.type_id=g.type_id WHERE p.`product_id` IN ('.implode(',',$product_ids).')';

                $product_sdf = $productsObj->db->select($sql);

            }
            #$product_sdf['wms_id'] = $_REQUEST['wms_id'];
            // 发起商品同步
           $wms_id = $_REQUEST['wms_id'];
           $branch_bn = $_POST['branch_bn'];

           kernel::single('console_goodssync')->syncProduct_notifydata($wms_id,$product_sdf,$branch_bn);
        }

        // $this->end(true,'操作成功');
        $this->splash('success','index.php?app=console&ctl=admin_goodssync&act=index&wms_id='.$wms_id.'&view='.$view);
    }

    //分配商品
    function dispatch(){
        $wms_id = $_REQUEST['wms_id'];

        $view = $_REQUEST['view'];
        $finder_id = $_REQUEST['finder_id'];
        $page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
        $pagelimit = 50;
        $offset = ($page-1) * $pagelimit;
        if($_REQUEST['search']){
            $params['wms_id'] = $wms_id;
            $params['search_key'] = $_REQUEST['search_key'];
            if(empty($_REQUEST['search_value'])){
                $params['search_value'] = $_REQUEST['search_value_'.$_REQUEST['search_key']];
                $this->pagedata['search_value_key'] = $params['search_value'];
            }else{
                $params['search_value'] = $_REQUEST['search_value'];
                $this->pagedata['search_value'] = $params['search_value'];
            }
            $search_filter = kernel::single("console_goodssync")->get_filter($params,$offset,$pagelimit);
            if($search_filter === false){
                return false;
            }
            $data = kernel::single("console_goodssync")->get_goods_by_product_ids($search_filter);
            $count = kernel::single("console_goodssync")->get_goods_count_by_search($params);
            $link = 'index.php?app=console&ctl=admin_goodssync&act=dispatch&view='.$view.'&wms_id='.$params['wms_id'].'&search=true&search_value='.$params['search_value'].'&search_key='.$params['search_key'].'&target=container&page=%d';
            $this->pagedata['search_key'] = $params['search_key'];
            $this->pagedata['search_value_last'] = $params['search_value'];
        }else{
            $data = kernel::single("console_goodssync")->get_goods_by_wms($wms_id,$offset,$pagelimit);
            $count = kernel::single("console_goodssync")->get_goods_count_by_wms($wms_id);
            $count = $count[0]['count'];
            $link = 'index.php?app=console&ctl=admin_goodssync&act=dispatch&view='.$view.'&wms_id='.$wms_id.'&target=container&page=%d';
        }
        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>$link,
        ));
        //获取自定义搜索选项
        $search = kernel::single("console_goodssync")->get_search_options();
        //获取自定义搜索项下拉列表
        $search_list = kernel::single("console_goodssync")->get_search_list();
        //echo '<pre>';
        //print_r($data);
        $this->pagedata['search'] = $search;
        $this->pagedata['count'] = $count;
        $this->pagedata['search_list'] = $search_list;
        $this->pagedata['rows'] = $data;
        $this->pagedata['pager'] = $pager;
        $this->pagedata['wms_id'] = $wms_id;
        $this->pagedata['finder_id'] = $finder_id;
        if($_GET['target'] || $_POST['search'] =='true'){
            return $this->display('admin/goodssync/index.html');
        }
        $this->singlepage('admin/goodssync/index.html');
    }

    function do_save(){
        $wms_id = $_POST['wms_id'];
        $product_ids = $_POST['product_id'];

        $finder_id = $_POST['finder_id'];
        $wfsObj = &app::get('console')->model('foreign_sku');
        $db = kernel::database();
        $limit = 50;//设置多少个货品组一个sql语句
        $this->begin();
        //全选时候的处理
        if($_POST['select_all'] == 'true'){
            $search_key = $_POST['search_key'];
            $search_value = $_POST['search_value'];
            if(!empty($search_key) && !empty($search_value)){
                $data = kernel::single("console_goodssync")->get_data_by_search($search_key,$search_value,$wms_id);
            }else{
                $data = kernel::single("console_goodssync")->get_goods_by_wms($wms_id);
            }
        }else{
            $data = kernel::single("console_goodssync")->get_wms_goods($product_ids);
        }


        //标签为全部 表示所有wms都分配商品
        if($wms_id =='0'){
            $all = 'true';
            //$wms = $wmsObj->getList('wms_id',array('connect_type|noequal'=>'omeselfwms'));
            $wms = kernel::single('channel_func')->getWmsChannelList();
            $sdf = array();
            foreach($wms as $wms_id){
                $sdf[] = $this->get_foreign_sku_sdf($data,$wms_id['wms_id']);
            }
        }else{
            $sdf = $this->get_foreign_sku_sdf($data,$wms_id);
        }

        if($all == 'true'){
            foreach($sdf as $value){
                foreach($value as $v){
                    $sql_find = "select inner_product_id from sdb_console_foreign_sku where inner_sku = '".$v['inner_sku']."' and wms_id = '".$v['wms_id']."'";
                    $rs = $db->select($sql_find);
                    if(!$rs){
                        $update_value[] = "('".$v['inner_sku']."','".$v['inner_product_id']."','".$v['wms_id']."')";
                    }
                }
            }
        }else{
            foreach($sdf as $value){
                $update_value[] = "('".$value['inner_sku']."','".$value['inner_product_id']."','".$value['wms_id']."')";
            }
        }
        $count = ceil(count($update_value) / $limit);
        $update_value_tmp = array_chunk($update_value, $limit,true);
        for($i=0;$i<$count;$i++){
            //插入数据
            $update_sql = "insert into sdb_console_foreign_sku (`inner_sku`,`inner_product_id`,`wms_id`) values ".implode($update_value_tmp[$i], ',');

            $db->exec($update_sql);
        }
        $this->end(true,'操作成功');
    }

    function get_foreign_sku_sdf($data,$wms_id){
        $sdf = array();
        foreach($data as $v){
            $sdf[] = array(
                'inner_sku'=>$v['bn'],
                'inner_product_id'=>$v['product_id'],
                'wms_id'=>$wms_id,
            );
        }
        return $sdf;
    }

    function downloadTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=货品分配模板.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $oObj = &$this->app->model('foreign_sku');
        $title = $oObj->exportTemplate();
        echo '"'.implode('","',$title).'"';

    }

   /**
    * 追加导出模板内容
    *
    * @param Array $filter
    */
    public function importTemplate(){
       $wms_id = $_GET['wms_id'];
       if ($wms_id){

            $wms_info = array(
                'wms_name' => kernel::single('channel_func')->getChannelNameById($wms_id),
                'wms_id' => $wms_id
            );
        }else{
            $wms_info = array(
                'wms_name' => '全部',
                'wms_id' => '_ALL_'
            );
        }
        $this->pagedata['wms_name'] = $wms_info['wms_name'];
        $this->pagedata['wms_id'] = $wms_info['wms_id'];

        return $this->page('admin/goodssync/create_import.html');
    }

    //导入（默认当前wms，所有时，所有wms都插）
    public function import(){
        if( $_POST ){
            $this->begin();
            //所有wms 不包括自有仓储
            if($_POST['wms_id']=='0'){
                $wms = kernel::single('channel_func')->getWmsChannelList();
                $wms_id = array();
                foreach($wms as $v){
                    $wms_id[] = $v['wms_id'];
                }
            }else{
                $wms_id = (array)$_POST['wms_id'];
            }
            $files = $_FILES['upload_file'];

            if( $files['name'] == ''){
                $result['status'] = 'fail';
                $result['msg'] = '文件不能为空，请重新选择';
                $this->end(false,'上传失败!','',$result);
                exit;
            }

            $tmp = explode('.',$files['name']);
            $file_type = $tmp[(count($tmp)-1)];
            if( $file_type != 'csv' ){
                $result['status'] = 'fail';
                $result['msg'] = '文件类型错误，请重新选择';
                $this->end(false,'上传失败!','',$result);
                exit;
            }

            $_temp_file = iconv('UTF-8','gb2312',$files['tmp_name']);
            @chmod($_temp_file,0777);
            if(file_exists($_temp_file)){
                $result['status'] = 'success';
                $result['msg'] = '文件已成功上传，并进入导入队列';

                //生成本次导入任务
                $params = &app::get('console')->model('foreign_sku')->import_params();
                $params['read_line'] = $params['read_line']>0?$params['read_line']:1000;
                $params['name'] = '导入任务('.$files['name'].')';
                $params['type'] = 'import';
                $params['filetype'] = 'csv';
                $params['file'] = $_temp_file;
                $params['app'] = 'console';
                $params['model'] = 'foreign_sku';
                $public = array(
                    'wms_id' => $wms_id,
                );
                $params['public'] = $public;

                $task = kernel::service('service.queue.ietask');
                $task->create($params);
                $this->end(true,'上传成功!','',$result);
                exit;
            }
        }
        $this->pagedata['wms_id'] = $_GET['_params']['wms_id'];
        $this->display('admin/goodssync/create_import.html');
    }

    /**
     * 奇门同步物料
     *
     * @return void
     * @author 
     **/
    public function batchSyncDialog($wms_id)
    {
        // 根据WMS获取仓库
        $branchMdl = app::get('ome')->model('branch');

        $branchList = $branchMdl->getList('branch_bn,name',array('wms_id'=>$wms_id));

        $branchopts = array();
        foreach ($branchList as $key => $value) {
            $branchopts[$value['branch_bn']] = $value['name'];
        }

        $this->pagedata['branchopts'] = $branchopts;
        $this->pagedata['wms_id'] = $wms_id;
        $this->pagedata['filter'] = http_build_query($_POST);

        $this->display('admin/goodssync/batch_dialog.html');
    }
}