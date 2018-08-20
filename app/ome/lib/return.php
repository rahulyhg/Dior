<?php
/**
 * 售后服务类
 *
 *
 **/
class ome_return 
{

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @description 权限
     * @access public
     * @param void
     * @return void
     */
    public function chkground($workground,$url_params,$permission_id='') 
    {
        static $group;

        if($workground == 'desktop_ctl_recycle') { return true;}
        if($workground == 'desktop_ctl_dashboard') { return true;}
        if($workground == ''){return true;}
        if($_GET['ctl'] == 'adminpanel') return true;
        $menus = app::get('desktop')->model('menus');

        if (!$group) {
            $userLib = kernel::single('desktop_user');
            $group = $userLib->group();
        }
        

        $permission_id = $permission_id ? $permission_id : $menus->permissionId($url_params);
        if($permission_id == '0'){return true;}

        return in_array($permission_id,$group) ? true : false;

    }

    
    /**
     * 生成售后单.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function add($sdf,$shop_id,&$msg="操作失败",&$logTitle="",&$logInfo=""){
       
        $shop = app::get("ome")->model("shop");
        $shop_row = $shop->db->selectrow("select node_id,node_type from sdb_ome_shop where shop_id='".$shop_id."'");
        $log = &app::get('ome')->model('api_log');
        $sdf['node_id'] = $shop_row['node_id'];
        base_rpc_service::$node_id = $sdf['node_id'];
        $rs = kernel::single('apibusiness_router_response')->dispatch('aftersalev2','add',$sdf);
        $data = array('tid'=>$sdf['tid']);
        $rs['rsp'] == 'success';
        $logTitle = $rs['logTitle'];
        $logInfo = $rs['logInfo'];
        $msg = '';
        return true;
        

    }

    function get_return_log($sdf_return,$shop_id,&$msg){
        
        $log = &app::get('ome')->model('api_log');

        $result = $this->add($sdf_return,$shop_id,$msg,$logTitle,$logInfo);

        $class = 'ome_rpc_response_aftersalev2';

        $method = 'add';

        $rsp = 'fail';

        if($result){
            $rsp = 'success';
        }

        return $result;
    }

	/**
     * 保存质检信息
     *
     * @param Int $reship_id 退换单据ID
     * @param Int $status 8:质检通过 9:拒绝质检
     * @return void
     * @author
     **/
    function toQC($reship_id,$data,&$msg)
    {
        set_time_limit(0);
		kernel::database()->beginTransaction();
        $_POST = $data;

        $oProduct_pro = &$this->app->model('return_process');
        $oProduct = &$this->app->model('return_product');
        $oReship = &$this->app->model('reship');
        $oProblem_type = &$this->app->model('return_product_problem_type');
        $oProblem = &$this->app->model('return_product_problem');
        $oBranch = &app::get('ome')->model('branch');
        $productSerialObj = &$this->app->model('product_serial');
        $serialLogObj = &$this->app->model('product_serial_log');
        $oOperation_log = &$this->app->model('operation_log');//写日志
        $pro_items =&$this->app->model('return_process_items');
        $reship  = &$this->app->model('reship_items');

		$status = 8;
        switch($status){
            case '8' :   # 质检

                if(empty($_POST['process_id'])){
					$msg = $this->app->_('请先扫描货号/条形码');
					return false;
				}

                $opInfo = kernel::single('ome_func')->getDesktopUser();

                $row = $oReship->getList('reship_id',array('reship_id'=>$reship_id,'is_check'=>array('1','3','13')));
                if (!$row) {
					$msg = $this->app->_('退换货单据未审核');
					return false;
                }

                if (!$_POST['por_id']) {
					$msg = $this->app->_('请选择质检明细');
					return false;
                }

                # 已经扫描的货号
                $scans = $_POST['process_id'];

                # 验证可质检数
                $checknum = $noscan = array();
                foreach ($scans as $key=>$pbn) {
                    if($key<=0) continue;
                    # 可质检数
                    if (!$noscan[$pbn]) {
                        $filter  = array(
                            'bn' => $pbn,
                            'por_id' => $_POST['por_id'],
                            'is_check' => 'false',
                        );
                        $row = $pro_items->getList('sum(num) as _s',$filter,0,1);
                        $noscan[$pbn] = $row[0]['_s'];
                    }

                    $checkKey = $pbn.$key;

                    $checknum[$pbn] += $_POST['check_num'][$checkKey];

                    if (!isset($_POST['instock_branch'][$checkKey])) {
						$msg = $this->app->_('请选择入库类型');
						return false;
                    }
                }

                foreach ($noscan as $pbn=>$sum) {
                    if ($checknum[$pbn]!=$sum) {
                        $msg = $this->app->_('请全部扫描完!');
                        return false;
                    }
                }
                
                #将数据以货号+仓库+备注方式重新组合
                $new_processData = array();
                foreach ( $scans as  $sk=>$sbn) {
                    if($sk<=0) continue;
                    $checkKey = $sbn.$sk;
                    $tmp_branch = intval($_POST['instock_branch'][$checkKey]);
                    $probn = md5($sbn.$tmp_branch.$_POST['memo'][$checkKey]);
                    if (isset($new_processData[$probn])) {
                        $new_processData[$probn]['check_num']+=$_POST['check_num'][$checkKey];
                    }else{
                        $sale_price = $reship->getList('price',array('reship_id'=>$_POST['reship_id'],'bn'=>$sbn));//做售后时，用户输入的价格
                        $new_processData[$probn] = array(
                            'check_num' => $_POST['check_num'][$checkKey],
                            'memo'      => $_POST['memo'][$checkKey],
                            'store_type'=> $_POST['store_type'][$checkKey],
                            'branch_id' => $tmp_branch,
                            'acttime'   => time(),
                            'por_id'    => $_POST['por_id'],
                            'reship_id' => $_POST['reship_id'],
                            'is_check' => 'true',
                            'bn'       => $sbn,
                            'need_money' =>$sale_price[0]['price'],
                        );
                    }
                }
           
                if ($new_processData) {
                    foreach ($new_processData as $processdata ) {
                        $check_num  = $processdata['check_num'];
                        $memo       = $processdata['memo'];
                        $store_type = $processdata['store_type'];
                        $branch_id  = $processdata['branch_id'];
                        $acttime    = $processdata['acttime'];
                        $por_id     = $processdata['por_id'];
                        $reship_id  = $processdata['reship_id'];
                        $bn         = $processdata['bn'];
                        $need_money  = $processdata['need_money'];
                        $SQL = "UPDATE sdb_ome_return_process_items SET 
                                need_money=".$need_money.",
                                memo='$memo',store_type='".$store_type."',branch_id=".$branch_id.",
                                acttime=".$acttime.",is_check='true' WHERE item_id in (SELECT t.item_id FROM
                                (select * from sdb_ome_return_process_items WHERE reship_id=".$reship_id." AND por_id=".$por_id." AND bn='$bn' AND is_check='false' LIMIT 0,".$check_num.") as t )";

                        $pro_items->db->exec($SQL);
                        # 写日志
                        $memo = '有'.$check_num.'件货品【'.$bn.'】质检成功,进入'.$oProblem->get_store_type($store_type).':'.$oBranch->Get_name($branch_id);
                        if($_POST['return_id']){
                           $oOperation_log->write_log('return@ome',$_POST['return_id'],$memo);
                        }
                        $oOperation_log->write_log('reship@ome',$_POST['reship_id'],$memo);
                        #更新退货单上良品数量
                        $branch = $pro_items->db->selectrow('SELECT attr FROM sdb_ome_branch WHERE branch_id='.$branch_id);

                        if ($branch['attr']=='true') {
                            $ship_sql = 'UPDATE sdb_ome_reship_items SET normal_num='.$check_num.' WHERE reship_id='.$reship_id.' AND bn=\''.$bn.'\'';
                        }else{
                            $ship_sql = 'UPDATE sdb_ome_reship_items SET defective_num='.$check_num.' WHERE reship_id='.$reship_id.' AND bn=\''.$bn.'\'';
                        }

                        $pro_items->db->exec($ship_sql);
                    }
                }
                
//                foreach ($scans as $key=>$pbn) {
//                    if($key<=0) continue;
//                    $checkKey = $pbn.$key;
//
//                    $tmp_branch = intval($_POST['instock_branch'][$checkKey]);
//
//
//                    # 单个框中的数量
//                    for ($i=1; $i <= $_POST['check_num'][$checkKey]; $i++) {
//                        # 质检处理明细
//                        $process_items = $pro_items->select()->columns('item_id,product_id')
//                                                    ->where('bn=?',$pbn)
//                                                    ->where('is_check=?','false')
//                                                    ->where('por_id=?',$_POST['por_id'])
//                                                    ->instance()->fetch_row();
//                       if(!$process_items) continue; #已经质检
//
//                       $process_items_update = array(
//                            'item_id' => $process_items['item_id'],
//                            'memo' => $_POST['memo'][$checkKey],
//                            'acttime' => time(),
//                            'op_id' => $opInfo['op_id'],
//                            'is_check' => 'true',
//                            'product_id' => $process_items['product_id'],
//                            'por_id' => $_POST['por_id'],
//                            'reship_id' => $_POST['reship_id'],
//                            'store_type' => $_POST['store_type'][$checkKey],
//                            'branch_id' => $tmp_branch,
//                       );
//                       $pro_items->save($process_items_update);
//
//                    }
//
//                    # 写日志
//                    $memo = '有'.$_POST['check_num'][$checkKey].'件货品【'.$pbn.'】质检成功,进入'.$oProblem->get_store_type($_POST['store_type'][$checkKey]).':'.$oBranch->Get_name($tmp_branch);
//                    if($_POST['return_id']){
//                       $oOperation_log->write_log('return@ome',$_POST['return_id'],$memo);
//                    }
//                    $oOperation_log->write_log('reship@ome',$_POST['reship_id'],$memo);
//                }

                # 唯一码
                if($_POST['serial_id']){
                    
                    foreach($_POST['serial_id'] as $val){
                        $serialData = $productSerialObj->dump(array('serial_number'=>$val));
                        if($serialData && $serialData['item_id']>0){
                            $stock_type_serial = array_shift($_POST['store_type']);
                            $serialData['status'] = ($stock_type_serial>0) ? 2 : 0;
                            $productSerialObj->save($serialData);

                            $logData['item_id'] = $serialData['item_id'];
                            $logData['act_type'] = 1;
                            $logData['act_time'] = time();
                            $logData['act_owner'] = $opInfo['op_id'];
                            $logData['bill_type'] = 1;
                            $logData['bill_no'] = $_POST['reship_id'];
                            
                            $logData['serial_status'] = $serialData['status'];
 
                            $serialLogObj->save($logData);
                            
                            unset($serialData,$logData);
                        }
                    }
                }

                $return_id=$_POST['return_id'];
                $oProduct_pro->changeverify($_POST['por_id'],$_POST['reship_id'],$return_id,$_POST['process_memo']);
				
                $return_product_detail = $oProduct_pro->dump(array('por_id'=>$_POST['por_id']), 'verify');
				
                # 质检成功后的操作
                if ($return_product_detail['verify'] == 'true'){
                    #质检入库

                    if(!kernel::single('ome_return_process')->do_iostock($_POST['por_id'],1,$msg)){
                        $url = 'index.php?app=ome&ctl=admin_return_sv&act=edit&p[0]='.$_POST['reship_id'];
						$msg = $this->app->_('质检入库失败');
						return false;
                    }

                    //质检成功后，根据退换货类型生成相应的单据
                    if ($status == '8') {
                        $oReship->finish_aftersale($_POST['reship_id']);
                    }else if($return_id){
                        //售后申请状态更新同步
                        foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
                            if(method_exists($instance,'update_status')){
                                $instance->update_status($return_id);
                            }
                        }
                        
                        
                    }

                    # 更新状态
                    /*
                    if ($status == '10') {
                        $oReship->update(array('is_check'=>'10'),array('reship_id'=>$reship_id));
                        $oOperation_log->write_log('reship@ome',$_POST['reship_id'],'质检异常');
                        $this->end(true,$this->app->_('质检异常更新成功!'));
                    }*/
                }
				$msg = $this->app->_('质检成功');
				return true;
                break;
            default:
				$msg = $this->app->_('质检失败');
                return false;
                break;
        }

    }
}