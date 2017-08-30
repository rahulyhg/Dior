<?php
class purchase_ctl_admin_eo extends desktop_controller{
    var $name = "入库管理";
    var $workground = "purchase_manager";
    function index(){
        $params = array(
                        'title'=>'入库单',
                        'actions' => array(
                            array(
                                'label' => '导出模板',
                                'href' => 'index.php?app=purchase&ctl=admin_eo&act=exportTemplate',
                                'target' => '_blank',
                            ),
                        ),
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
                        'orderBy'=>'entry_time desc'
                    );
        $this->finder('purchase_mdl_eo', $params);
    }
    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=RK".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('eo');
        $title1 = $pObj->exportTemplate('eo');
        $title2 = $pObj->exportTemplate('item');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }

    function eo_confirm($po_id){
        $oPo = &$this->app->model("po");
        $oPo_items = &$this->app->model("po_items");
        $oProducts  = &app::get('ome')->model("products");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");
        $obj_goods = &app::get('ome')->model("goods");
        $perpage = 200;
        $page = intval($_GET['page'])?intval($_GET['page']):1;
        $start = ($page-1)*$perpage;

        if($page>=200){
            $top_start=$start+1;
        }else{
            $top_start=$page;
        }
        $top_title = $top_start.'-'.$page*$perpage;
        $base_url = 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm_list&p[0]='.$po_id;
        $count = count($oPo_items->getList('*',array('po_id'=>$po_id), 0, -1));
        $multi = $this->multipages($count,$perpage,$page,$base_url);
        $Po_items = $oPo_items->getList('*',array('po_id'=>$po_id));
        $Po = $oPo->dump($po_id,'branch_id,supplier_id');
        foreach($Po_items as $k=>$v){
            $product = $oProducts->dump($v['product_id'],'unit,goods_id');
            $goods_bn = $obj_goods->dump($product['goods_id'],'bn');
            $Po_items[$k]['goods_bn'] = $goods_bn['bn'];        

            //$Po_items[$k]['name'] = $v['name'];
            $Po_items[$k]['unit'] = $product['unit'];
           // $Po_items[$k]['barcode'] = $v['barcode'];
            $assign = $oProduct_pos->get_pos($v['product_id'],$Po['branch_id']);
            if(empty($assign)){
                $pos_list = $oProduct_pos->get_unassign_pos($Po['branch_id']);
                $Po_items[$k]['is_new']="true";
            }else{
                $Po_items[$k]['is_new']="false";
                $pos_list = $assign;
            }
            $Po_items[$k]['spec_info'] = $v['spec_info'];
            $Po_items[$k]['entry_num'] = $v['num']-$v['in_num'];
            $Po_items[$k]['pos_list']=$pos_list;
        }

        //获取采购单供应商经办人/负责人
        $oSupplier = &$this->app->model('supplier');
        $supplier = $oSupplier->dump($Po['supplier_id'], 'operator');
        if (!$supplier['operator']) $supplier['operator'] = '未知';
        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['Po_items'] = $Po_items;
        $this->pagedata['po_id'] = $po_id;
        $this->pagedata['multi']=$multi;
        $this->pagedata['count']=$count;//branch_id
        $this->pagedata['branch_id']=$Po['branch_id'];
        $this->pagedata['top_title'] = $top_title;
        $this->singlepage("admin/eo/eo_confirm.html");
    }

    function eo_confirm_list(){
        $po_id = $_GET['po_id'];
        $oPo = &$this->app->model("po");
        $oPo_items = &$this->app->model("po_items");
        $oProducts  = &app::get('ome')->model("products");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");

        $perpage = 200;
        $page = intval($_GET['page'])?intval($_GET['page']):1;
        $start = ($page-1)*$perpage;

        if($page>=200){
            $top_start=$start+1;
        }else{
            $top_start=$page;
        }
        $top_title = $top_start.'-'.$page*$perpage;
        $base_url = 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm_list&p[0]='.$po_id;
        $count = count($oPo_items->getList('*',array('po_id'=>$po_id), 0, -1));
        $multi = $this->multipages($count,$perpage,$page,$base_url);
        $Po_items = $oPo_items->getList('*',array('po_id'=>$po_id), $start, $perpage);
        $Po = $oPo->dump($po_id,'*');
        foreach($Po_items as $k=>$v){
           $product = $oProducts->dump($v['product_id'],'*');
           if(empty($product)){
            $Po_items[$k]['name'] = '此商品已不存在';
           }

            $assign = $oProduct_pos->get_pos($v['product_id'],$Po['branch_id']);

            if(empty($assign)){
                $pos_list = $oProduct_pos->get_unassign_pos($Po['branch_id']);
                //print_r($pos_list);
                $Po_items[$k]['is_new']="true";
            }else{
                $Po_items[$k]['is_new']="false";
                $pos_list = $assign;
            }
            $Po_items[$k]['entry_num'] = $v['num']-$v['in_num'];
            $Po_items[$k]['pos_list']=$pos_list;
            //print_r($pos_list);

        }

        //获取采购单供应商经办人/负责人
        $oSupplier = &$this->app->model('supplier');
        $supplier = $oSupplier->dump($Po['supplier_id'], 'operator');
        if (!$supplier['operator']) $supplier['operator'] = '未知';
        $this->pagedata['operator'] = $supplier['operator'];
        $this->pagedata['Po_items'] = $Po_items;
        $this->pagedata['po_id'] = $po_id;
        $this->pagedata['multi']=$multi;
        $this->pagedata['count']=$count;//branch_id
        $this->pagedata['branch_id']=$Po['branch_id'];
        $this->pagedata['top_title'] = $top_title;

      $this->display("admin/eo/eo_confirmlist.html");
    }
    /*
     * 保存采购信息入库
     */
    function save_eo_confirm(){
        $this->begin('index.php?app=purchase&ctl=admin_eo&act=supplier_pf');
        $oPo_items = &$this->app->model("po_items");
        $oEo = &$this->app->model("eo");
        $entry_num = $_POST['entry_num'];
        $po_id = $_POST['po_id'];
        $ids = $_POST['ids'];
        $branch_id = $_POST['branch_id'];
        /**Begin liaoyu message: 新增判断采购单是否终止**/
        $oPo = &$this->app->model("po");
        $filter = array('po_id' => $po_id, 'po_status');
        $po_detail = $oPo->dump($filter);
        if ($po_detail['po_status'] == '2') {
            $this->end(false, '采购单已终止', 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
        }
        /**by sunjing 采购单已完成判断 防并发处理**/
        if ($po_detail['po_status'] == '4') {
            $this->end(false, '采购单已完成', 'index.php?app=purchase&ctl=admin_purchase&act=eoList&p[0]=i');
        }
        if (empty($ids)){
            $this->end(false, '请选择需要入库的商品', 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
        }
        $ret = array();
        foreach($ids as $i){
            if ($entry_num[$i] <= 0){
                $this->end(false, '入库量必须大于0', 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
            }
            $Po_items=$oPo_items->dump(array('po_id'=>$po_id,'item_id'=>$i),'num,in_num,product_id');
            $p_entry_num = $Po_items['num']-$Po_items['in_num'];
            if($entry_num[$i]>$p_entry_num){
               $this->end(false, '入库量大于可入库量', 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
            }
            if(app::get('taoguaninventory')->is_installed()){
                $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($Po_items['product_id'],$branch_id);

                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以入库!', 'index.php?app=purchase&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
                }
            }
        }
        $supplier_id=$oEo->save_eo($_POST);
        $this->end(true, '入库成功', 'index.php?app=purchase&ctl=admin_eo&act=eo_list&supplier_id='.$supplier_id);

    }
    function supplier_pf(){
        if($_POST){
            $this->begin('index.php?app=purchase&ctl=admin_purchase&act=eoList');

            $oSupplier = &$this->app->model("supplier");
            $data =array('supplier_id'=>$_POST['supplier_id'],'credit_lv'=>$_POST['credit_lv']);
            $oSupplier->save($data);
            $this->end(true, '评分成功....');
        }
        $supplier_id = $_GET['supplier_id'];
        $this->pagedata['supplier_id']=$supplier_id;

        $this->page("admin/eo/supplier.html");
    }
    function eo_cancel($eo_id){
        $oEo = &$this->app->model("eo");
        $eo = $oEo->eo_detail($eo_id);
        $this->pagedata['eo'] = $eo;

        $returnedObj = &app::get('purchase')->model('returned_purchase');
        $rp = $returnedObj->dump(array('rp_type'=>'eo','object_id'=>$eo_id), 'amount');
        $this->pagedata['return_amount'] = $rp['amount'];

        //获取采购单供应商经办人/负责人
        $oSupplier = &$this->app->model('supplier');
        $supplier = $oSupplier->dump($eo['supplier_id'], 'operator');
        //if (!$supplier['operator']) $supplier['operator'] = '未知';
        $supplier['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['operator'] = $supplier['operator'];

        $this->singlepage("admin/eo/eo_cancel.html");
    }
/*
 * 采购退货
 * 针对已入库单操作
 * 影响 扣减库存 生成退货单 退款单
 */
    function save_eo_cancel(){
        $eo_id = $_POST['eo_id'];
        $this->begin('index.php?app=purchase&ctl=admin_eo');
        $oEo = &$this->app->model("eo");
        $oEo_items = &$this->app->model("eo_items");
        $ckid = $_POST['ckid'];
        if(empty($ckid)){
            $this->end(false, '请选择您要退货的产品!');
        }
        $data = array();
        foreach($ckid as $k=>$v){
            if(intval($_POST['out_num'][$v])==0){
                $this->end(false, '退货数量不可为0');
            }
            $price = $_POST['price'][$v] ? $_POST['price'][$v] : '0';
            $over_num = $oEo_items->Get_num($v);
            if($_POST['out_num'][$v]>$over_num){
                $this->end(false, '退货数量不可大于剩余数量..');
            }
            $data[]=array('out_num'=>$_POST['out_num'][$v],'item_memo'=>$_POST['item_memo'][$v],'item_id'=>$v,'price'=>$price,'operator'=>$_POST['operator'],'delivery_cost'=>$_POST['delivery_cost'],'logi_no'=>$_POST['logi_no'],'memo'=>$_POST['memo']);
        }
        $oEo->save_eo_cancel($eo_id,$data);
        $this->end(true, '退货成功!');
    }

    /*
     * 入库单明细
     * @param eo_id
     */
    function eo_items($eo_id=null){
        if ($eo_id){
            $iostock_instance = kernel::service('taoguaniostockorder.iostockorder');
            $oGoods = &app::get('ome')->model('goods');
            $oProducts = &app::get('ome')->model('products');
            $eo_items = $iostock_instance->getIsoItems($eo_id);
            $detail = array();
            if ($eo_items)
            foreach ($eo_items as $k => $v){
                $products = $oProducts->dump($v['product_id'], 'goods_id');
                $goods = $oGoods->dump($products['goods_id'], 'name');
                $v['goods_name'] = $goods['name'];
                $detail[] = $v;
            }
        }
        $this->pagedata['detail'] = $detail;
        $this->display("admin/eo/eo_items.html");
    }

    /**
     * 条码控制入库
     */
    function Barcode_stock($po_id){
        $oPo = &$this->app->model("po");
        $Po = $oPo->dump($po_id,'branch_id,supplier_id');
        $this->pagedata['branch_id']=$Po['branch_id'];
        $this->pagedata['po_id'] = $po_id;
        $stock_confirm= &app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= &app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        $this->singlepage("admin/eo/eo_barcode.html");
    }

    /**
     * 分页函数
     */
    function multipages($count,$perpage,$curr_page,$mpurl) {

        //if($count > $perpage) {
                $page = 200;
                $offset = 200;
                $pages = ceil($count / $perpage);
                //$multipage .= "<span class=nohref>".$curr_page." - ".$pages."</span>";
                $from = $curr_page - $offset;
                $to = $curr_page + $page - $offset - 1;

                if($page > $pages) {
                        $from = 1;
                        $to = $pages;
                } else {
                        if($from < 1) {
                                $to = $curr_page + 1 - $from;
                                $from = 1;
                                if(($to - $from) < $page && ($to - $from) < $pages) {
                                        $to = $page;
                                }
                        } elseif($to > $pages) {
                                $from = $curr_page - $pages + $to;
                                $to = $pages;
                                if(($to - $from) < $page && ($to - $from) < $pages) {
                                        $from = $pages - $page + 1;
                                }
                        }
                }
                $prepage = $curr_page - 1;
                $nextpage = $curr_page + 1;

                for($i = $from; $i <= $to; $i++) {
                        $sd = $i*$perpage;
                        if($i>=200){
                            $start=(($i-1)*$perpage)+1;
                        }else{
                            $start=$i;
                        }
                          $multipage .= "<div class=\"ome-stock-title ome-stock-list\" page=$i><span class=\"handler\"></span>序号".$start."-".$sd."入库商品明细</div>";

                }


        //}
        return $multipage;
    }

    function add_pos(){
        $obranch_pos = &app::get('ome')->model("branch_pos");
        $obranch_product_pos = &app::get('ome')->model("branch_product_pos");
        $branch_id = $_GET['branch_id'];
        $pos_value = $_GET['pos_value'];
        $product_id = $_GET['product_id'];
        $pos = $obranch_pos->dump(array('branch_id'=>$branch_id,'store_position'=>$pos_value),'pos_id');
        if(empty($pos)){
            $pos_data = array(
                'branch_id'=>$branch_id,
                'store_position'=>$pos_value
            );
            $result = $obranch_pos->save($pos_data);
            echo $pos_data['pos_id'];

        }else{
            echo '0';
        }
    }

    function get_po_info($po_ids='',$barcode=''){
        $barcode = $barcode ? $barcode : $_GET['barcode'];
        $po_id = $po_ids ? $po_ids :$_GET['po_id'];
        $oProducts  = &app::get('ome')->model("products");
        $obj_goods  = &app::get('ome')->model("goods");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");
        $oPo = &$this->app->model("po");
        $oPo_items = &$this->app->model("po_items");
        $po_items = $oPo_items->dump(array('po_id'=>$po_id,'barcode'=>$barcode),'*');

        if(empty($po_items)){
            echo (1);
            return false;
        }
        $po = $oPo->dump($po_id,'branch_id,operator');
        $po_items['operator'] = $po['$po'];
        $assign = $oProduct_pos->get_pos($po_items['product_id'],$po['branch_id']);

        //$po_items['entry_num'] = $po_items['num']-$po_items['in_num'];
        $po_items['entry_num'] = 1;#条码入库初始值为1
        /*商品相关信息*/
        //$po_items['barcode'] = $po_items['barcode'];
        //$po_items['name'] = $po_items['name'];

        //$po_items['spec_info'] = $po_items['spec_info'];
        //$po_items['bn'] = $po_items['bn'];
        if(empty($assign)){
            $pos_list = $oProduct_pos->get_unassign_pos($po['branch_id']);
            $po_items['is_new']="true";
            $po_items['is_new_value']= "是";
        }else{
            $Po_items['is_new']="false";
            $po_items['is_new_value']="否";
            $pos_list = $assign;
        }
        //$pos_list = $assign;
        $po_items['is_new_value'] =  $po_items['in_num'] ? '否' : '是';
        $po_items['pos_list']=$pos_list;
        $product_id = $po_items['product_id'];
        $unit = $oProducts->dump(array('product_id'=>$product_id),'unit,visibility,goods_id');
        $po_items['unit'] = $unit['unit'];
        $po_items['visibility'] = $unit['visibility'];
        $goods_bn = $obj_goods->dump(array('goods_id'=>$unit['goods_id']),'bn');
        $po_items['goods_bn'] = $goods_bn['bn'];

        if (empty($po_ids)){
            echo json_encode($po_items);
        }else{
            if ($po_items) return true;
            else return false;
        }

    }
/*
 * 条码入库保存
 */
    function save_barcode(){
    	$pObj = &$this->app->model("po");
        $po_id = $_POST['po_id'];
        $operator = $pObj->dump(array('po_id'=>$po_id),'operator');
        $_POST['operator'] = $operator['operator'];
        $gotourl = 'index.php?app=purchase&ctl=admin_eo&act=Barcode_stock&p[0]='.$po_id.'&find_id='.$_POST['find_id'];
        $this->begin('');
        $oPo_items = &$this->app->model("po_items");
        $oEo = &$this->app->model("eo");
        $entry_num = $_POST['entry_num'];
        $pos_name = $_POST['pos_name'];
        $branch_id = $_POST['branch_id'];
        $ids = $_POST['ids'];
        if (empty($_POST['submit_flag'])){
            if ($_POST['some_name']){
                $po_id = $_POST['po_id'];
                $barcode = $_POST['some_name'];
                $items = $this->get_po_info($po_id, $barcode);
                if ($items){
                    $msg = '加载成功';
                    $result = true;
                }else{
                    $msg = '没有找到货品';
                    $result = false;
                }
            }else{
                $msg = '请输入条码';
                $result = false;
            }
            $this->end($result, $msg, '', array('flag'=>'true'));
        }
        $timeout = array('autohide'=>5000);
        if (empty($ids)){
            $this->end(false, '没有任何商品入库，点击关闭页面退出当前入库操作', $gotourl, $timeout);
        }
        $ret = array();
        foreach ($ids as $id) {

            if ($entry_num[$id] == 0){
                $this->end(false, '货品入库量不可为0', $gotourl);
            }
        }
        foreach($ids as $i){
           $Po_items=$oPo_items->dump(array('po_id'=>$po_id,'item_id'=>$i),'num,in_num');
            $p_entry_num = $Po_items['num']-$Po_items['in_num'];
            if($entry_num[$i]>$p_entry_num){
               $this->end(false, '入库量大于可入库量', $gotourl);
            }

        }
        $supplier_id=$oEo->save_eo($_POST);
        $this->end(true, '入库成功', $gotourl);
    }

    /*打印入库单*/
    function printeo($eo_id){
        $iostock_instance = kernel::service('taoguaniostockorder.iostockorder');
        $eo_de = $iostock_instance->getIso($eo_id);
        $eo['detail'] = $eo_de;
        $oBranch = &app::get('ome')->model("branch");
        $oProducts = &app::get('ome')->model("products");//读取商品条码
        $oSupplier = &$this->app->model("supplier");
        $bppObj = kernel::single('ome_mdl_branch_product_pos');//读取商品货位
        $Branch = $oBranch->dump($eo_de['branch_id'],'name');
        $supplier = $oSupplier->dump($eo_de['supplier_id'],'name');
        $eo['supplier_name'] = $supplier['name'];
        $eo['branch_name'] = $Branch['name'];
        $items = $iostock_instance->getIsoItems($eo_id);
        //echo('<pre>');var_dump($items);
        $product_cost = 0;
        if($items) {
            foreach($items as $k=>$v) {
                $productInfo = array();
                $items[$k]['store_position'] = $bppObj->get_product_pos($v['product_id'],$eo_de['branch_id']);
                $productInfo = $oProducts->getList('barcode,spec_info,price',array('product_id'=>$v['product_id']));
                $items[$k]['barcode'] = $productInfo[0]['barcode'];//读取商品条码
                $items[$k]['spec_info'] = $items[$k]['spec_info'] ? $items[$k]['spec_info'] : $productInfo[0]['spec_info'];
                #出入库单增加吊牌价
                $items[$k]['sale_price'] = $productInfo[0]['price'];
                $product_cost += $items[$k]['nums']*$items[$k]['price'];
            }
        }
        $this->pagedata['product_cost'] = $product_cost;
        $eo['items'] = $items;
        if($eo['detail']['memo']){
            //$eo['detail']['memo'] = kernel::single('ome_func')->format_memo($eo['detail']['memo']);
            if(!empty($eo['detail']['memo'])){
                foreach($eo['detail']['memo'] as $k => $v){
                    $arr[]= $v['op_content'];
                }
                //$eo['detail']['memo'] = implode(',',$arr);
            }
        }
        #金额总计=商品总额+出入库费用
        $eo['detail']['amount'] = $eo['detail']['product_cost'] +$eo['detail']['iso_price'];
        $this->pagedata['eo'] = $eo;

        $this->pagedata['process_name'] = '入库';
        if($_GET['t'] == 0) {
            $this->pagedata['process_name'] = '出库';
        }

        # 改用新打印模板机制 chenping
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'pureo',$this);

        /*
        $this->_systmpl = &app::get('ome')->model('print_tmpl_diy');
        $this->_systmpl->singlepage('purchase','admin/eo/eo_print',$this->pagedata);
        $this->display("admin/prints.html");
        */
    }
}
?>
