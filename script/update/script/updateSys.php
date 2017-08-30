<?php
/**
 * 根据传入的域名做初始化工作
 *
 * @author hzjsq@msn.com
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

//更新app
kernel::single('base_shell_webproxy')->exec_command("update --ignore-download");

//安装app
kernel::single('base_shell_webproxy')->exec_command("install wms");
kernel::single('base_shell_webproxy')->exec_command("install eccommon");
kernel::single('base_shell_webproxy')->exec_command("install console");
kernel::single('base_shell_webproxy')->exec_command("install middleware");
kernel::single('base_shell_webproxy')->exec_command("install wmsmgr");
kernel::single('base_shell_webproxy')->exec_command("install siso");
kernel::single('base_shell_webproxy')->exec_command("install rpc");
kernel::single('base_shell_webproxy')->exec_command("install channel");
kernel::single('base_shell_webproxy')->exec_command("install crm");
kernel::single('base_shell_webproxy')->exec_command("install wangwang");

//数据库连接对象
$db = kernel::database();

//清除模板缓存关于ectools组件的cache/template

$sql = "select `key` from sdb_base_kvstore where prefix ='cache/template'";
$cache_template_tmp = $db->select($sql);
if($cache_template_tmp){
    foreach($cache_template_tmp as $kk =>$vv){
        base_kvstore::instance('cache/template')->store($vv['key'],'',1);
    }
}

$sql = "delete from sdb_base_kvstore where prefix ='cache/template'";
$db->exec($sql);



//打印模板的数据库修改
$sql = "update sdb_ome_print_otmpl set content=replace(content,'app=ome','app=wms') where type='delivery' or type='stock' or type='merge'";
$db->exec($sql);

//发货相关的配置
$all_settings =array(
    'ome.delivery.check' => 'wms.delivery.check',
    'ome.delivery.check_show_type' => 'wms.delivery.check_show_type',
    'ome.delivery.check_ident' => 'wms.delivery.check_ident',
    'ome.delivery.weight' => 'wms.delivery.weight',
	'ome.delivery.weightwarn' => 'wms.delivery.weightwarn',
    'ome.delivery.minWeight' => 'wms.delivery.minWeight',
    'ome.delivery.maxWeight' => 'wms.delivery.maxWeight',
    'ome.delivery.cfg.radio' => 'wms.delivery.cfg.radio',
    'ome.delivery.min_weightwarn' => 'wms.delivery.min_weightwarn',
    'ome.delivery.max_weightwarn' => 'wms.delivery.max_weightwarn',
    'ome.delivery.maxpercent' => 'wms.delivery.maxpercent',
    'ome.delivery.minpercent' => 'wms.delivery.minpercent',
    'ome.delivery.problem_package' => 'wms.delivery.problem_package',
    'ome.groupCalibration.intervalTime' => 'wms.groupCalibration.intervalTime',
    'ome.groupDelivery.intervalTime' => 'wms.groupDelivery.intervalTime',
    'ome.delivery.status.cfg' => 'wms.delivery.status.cfg',
    'lastGroupCalibration' => 'lastGroupCalibration',
    'lastGroupDelivery' => 'lastGroupDelivery',
);

foreach($all_settings as $old => $new){
    $data ='';
    $new_data = array();
    if($old == 'ome.delivery.status.cfg'){
        $data = app::get('ome')->getConf($old);
        if($data){
            foreach ($data['set'] as $k => $val){
                if($k == 'single' || $k == 'multi'){
                    foreach($data['set'][$k] as $kk => $vv){
                        $new_k = str_replace('ome_','wms_',$kk);
                        $new_data['set'][$k][$new_k] = $vv;
                    }
                }else{
                    $new_k = str_replace('ome_','wms_',$k);
                    $new_data['set'][$new_k] = $val;
                }
            }
            app::get('wms')->setConf($new,$new_data);
        }
    }else{
        $data = app::get('ome')->getConf($old);
        if($data){
            app::get('wms')->setConf($new,$data);
        }
    }
}

//快递单模板、大头笔数据迁移
$sql = "insert into sdb_wms_print_tmpl select * from sdb_ome_print_tmpl";
$db->exec($sql);

$sql = "insert into sdb_wms_print_tag select * from sdb_ome_print_tag";
$db->exec($sql);

//清除原来的eccommon地址信息表，将ectools的迁移过来
$sql = "truncate table sdb_eccommon_regions";
$db->exec($sql);

$sql = "insert into sdb_eccommon_regions select * from sdb_ectools_regions";
$db->exec($sql);

//初始化仓库绑定关系
$sql = "INSERT INTO `sdb_channel_channel` (`channel_id`, `channel_bn`, `channel_name`, `channel_type`, `config`, `crop_config`, `last_download_time`, `last_upload_time`, `active`, `disabled`, `last_store_sync_time`, `area`, `zip`, `addr`, `default_sender`, `mobile`, `tel`, `filter_bn`, `bn_regular`, `express_remark`, `delivery_template`, `order_bland_template`, `node_id`, `node_type`, `secret_key`, `memo`, `api_version`, `addon`) VALUES
(1, 'selfwms', '默认自有仓储', 'wms', NULL, NULL, NULL, NULL, 'false', 'false', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'false', NULL, NULL, NULL, NULL, 'selfwms', 'selfwms', '', NULL, NULL, NULL);
";
$db->exec($sql);

//更新仓库wms_id
$updatesql = "UPDATE sdb_ome_branch SET wms_id=1";
$db->exec($updatesql);

$sql = "INSERT INTO `sdb_channel_adapter` (`channel_id`, `adapter`) VALUES(1, 'selfwms');";
$db->exec($sql);

//卸载app
kernel::single('base_shell_webproxy')->exec_command("uninstall ectools");
#采購開始
//更新采購退貨主單 已退貨和已拒絕狀態更新為已審核
$sql1 = 'UPDATE sdb_purchase_returned_purchase SET check_status=\'2\' WHERE return_status in(2,3) AND rp_type=\'eo\'';
$db->exec($sql1);
//更新采購退貨明細單
$sql2 = "UPDATE sdb_purchase_returned_purchase_items SET out_num=num WHERE rp_id in (select rp_id from sdb_purchase_returned_purchase WHERE return_status in(2) AND rp_type='eo')";
$db->exec($sql2);
#調拔單
#出入庫表 将已审核更新为已入库
$sq3 = "UPDATE sdb_taoguaniostockorder_iso SET iso_status='3',check_status='2' WHERE confirm='Y'";
$db->exec($sq3);
#更新已入库明细申请数量=入库数量
$sql4 = "UPDATE sdb_taoguaniostockorder_iso_items SET normal_num=nums WHERE iso_id in (select iso_id FROM sdb_taoguaniostockorder_iso WHERE confirm='Y')";
$db->exec($sql4);

#给出库单加冻结库存
$oProducts = &app::get('ome')->model('products');
$oBranch_product = &app::get('ome')->model('branch_product');
#采购出库等上加冻结库存
$return_sql = "SELECT rp.branch_id,i.num,i.product_id FROM sdb_purchase_returned_purchase as rp LEFT JOIN sdb_purchase_returned_purchase_items as i ON rp.rp_id=i.rp_id WHERE rp.check_status='2' AND rp.return_status='1' AND i.num=0";
$return_purchase = $db->select($return_sql);
foreach($return_purchase as $purchase){
    $product_id = $purchase['product_id'];
    $branch_id = $purchase['branch_id'];
    $nums = $purchase['num'];
     $oProducts->chg_product_store_freeze($product_id,$nums,'+','return_purchase');
     $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$nums,'+','return_purchase');
}

#其它出库单上加冻结库存

$iso_sql = "SELECT s.branch_id,i.product_id,i.nums FROM sdb_taoguaniostockorder_iso as s LEFT JOIN sdb_taoguaniostockorder_iso_items as i ON i.iso_id=s.iso_id WHERE s.confirm='N' AND s.type_id in (40,7,100,300) AND i.nums>0";
$iso = $db->select($iso_sql);
foreach ($iso as $iso) {
    $product_id = $iso['product_id'];
    $branch_id = $iso['branch_id'];
    $nums = $iso['nums'];
    $oProducts->chg_product_store_freeze($product_id,$nums,'+','other');
    $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$nums,'+','other');
}
#退货单数据
#更新退货单良品数量
$reship_itemsql = "UPDATE sdb_ome_reship_items SET normal_num=num WHERE reship_id in (SELECT reship_id FROM sdb_ome_reship WHERE is_check='7')";
$db->exec($reship_itemsql);
#更新退货单上新增仓库id
$reship_sql = "select i.branch_id,s.reship_id from sdb_ome_reship  as s LEFT JOIN sdb_ome_reship_items AS i ON s.reship_id=i.reship_id GROUP BY s.reship_id";
$reship = $db->select($reship_sql);
foreach($reship as $reship){
    $db->exec("UPDATE sdb_ome_reship SET branch_id=".$reship['branch_id']." WHERE reship_id=".$reship['reship_id']);
}
//原有发货单信息迁移
//获取当前ome中有效的发货单
$dlyWmsObj = app::get('wms')->model('delivery');
$dlyBillWmsObj = app::get('wms')->model('delivery_bill');

$dly_ids = array();

$sql = "select count(delivery_id) as all_count from sdb_ome_delivery where parent_id=0 and disabled='false' and status not in('failed','cancel','back')";
$dly_tmp = $db->select($sql);

$need_trans = true;
if($dly_tmp[0]['all_count'] <= 0){
    $need_trans = false;
}

if($need_trans){
    $page_limit = 1000;
    $page_count = ceil($dly_tmp[0]['all_count']/$page_limit);

    for($page=1;$page<=$page_count;$page++){

        $page_offset = ($page-1)*$page_limit;

        $sql = "select delivery_id,delivery_bn,logi_no,logi_number,delivery_logi_number,status,stock_status,deliv_status,expre_status,verify,process,pause,print_status,last_modified,delivery_time from sdb_ome_delivery where parent_id=0 and disabled='false' and status not in('failed','cancel','back') limit ".$page_offset.",".$page_limit."";
        $dly_ids = $db->select($sql);

        if(count($dly_ids) > 0){
            foreach($dly_ids as $dly){
                $original_data = kernel::single('ome_event_data_delivery')->generate($dly['delivery_id']);
                $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
                $res = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, true);//var_dump($res);exit;

                //初始化临时变量
                $print_status = 0;
                $wms_dlyInfo = array();
                $items = array();
                $billItems = array();

                $wms_dlyInfo = $db->selectrow("select delivery_id from sdb_wms_delivery where outer_delivery_bn='".$dly['delivery_bn']."'");

                //当前发货单打印过什么单据
                if($dly['stock_status'] == 'true'){
                    $print_status += 1;
                }

                if($dly['deliv_status'] == 'true'){
                    $print_status += 2;
                }

                if($dly['expre_status'] == 'true'){
                    $print_status += 4;
                }

                if($dly['process'] == 'true'){
                    //更新发货单主表
                    $sql = "update sdb_wms_delivery set status=3, process_status=7, print_status=".$print_status.", last_modified=".$dly['last_modified'].", delivery_time=".$dly['delivery_time'].", logi_number=".$dly['logi_number'].", delivery_logi_number=".$dly['logi_number']." where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                    $db->exec($sql);

                    //更新发货单明细表
                    $items = $db->select("select item_id,number,verify_num from sdb_wms_delivery_items where delivery_id='".$wms_dlyInfo['delivery_id']."'");
                    foreach($items as $item){
                        $db->exec("update sdb_wms_delivery_items set verify_num=".$item['number'].", verify='true' where item_id=".$item['item_id']."");
                    }

                    //更新主包裹单
                    $sql = "update sdb_wms_delivery_bill set status=1, logi_no='".$dly['logi_no']."', delivery_time=".$dly['delivery_time']." where type=1 and delivery_id=".$wms_dlyInfo['delivery_id']."";
                    $db->exec($sql);

                    //保存其他拆分的包裹单
                    $billItems = $db->select("select logi_no,weight,delivery_cost_expect,delivery_cost_actual,create_time,delivery_time from sdb_ome_delivery_bill where delivery_id='".$dly['delivery_id']."'");
                    foreach($billItems as $billItem){
                        $tmp_billdata = array(
                            'delivery_id' => $wms_dlyInfo['delivery_id'],
                            'logi_no' => $billItem['logi_no'],
                        	   'weight' => $billItem['weight'],
                            'delivery_cost_expect' => $billItem['delivery_cost_expect'],
                            'delivery_cost_actual' => $billItem['delivery_cost_actual'],
                            'create_time' => $billItem['create_time'],
                            'delivery_time' => $billItem['delivery_time'],
                            'type' => 2,
                            'status' => $billItem['status'],
                        );
                        $dlyBillWmsObj->save($tmp_billdata);
                    }

                }elseif($dly['verify'] == 'true'){
                    //更新发货单主表
                    $sql = "update sdb_wms_delivery set process_status=3, print_status=".$print_status.", last_modified=".$dly['last_modified'].", logi_number=".$dly['logi_number']." where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                    $db->exec($sql);

                    //更新发货单明细表
                    $items = $db->select("select item_id,number,verify_num from sdb_wms_delivery_items where delivery_id='".$wms_dlyInfo['delivery_id']."'");
                    foreach($items as $item){
                        $db->exec("update sdb_wms_delivery_items set verify_num=".$item['number'].", verify='true' where item_id=".$item['item_id']."");
                    }

                    //更新主包裹单
                    $sql = "update sdb_wms_delivery_bill set logi_no='".$dly['logi_no']."' where type=1 and delivery_id=".$wms_dlyInfo['delivery_id']."";
                    $db->exec($sql);

                    //保存其他拆分的包裹单
                    $billItems = $db->select("select logi_no,weight,delivery_cost_expect,delivery_cost_actual,create_time,delivery_time from sdb_ome_delivery_bill where delivery_id='".$dly['delivery_id']."'");
                    foreach($billItems as $billItem){
                        $tmp_billdata = array(
                            'delivery_id' => $wms_dlyInfo['delivery_id'],
                            'logi_no' => $billItem['logi_no'],
                        	'weight' => $billItem['weight'],
                            'delivery_cost_expect' => $billItem['delivery_cost_expect'],
                            'delivery_cost_actual' => $billItem['delivery_cost_actual'],
                            'create_time' => $billItem['create_time'],
                            'type' => 2,
                            'status' => $billItem['status'],
                        );
                        $dlyBillWmsObj->save($tmp_billdata);
                    }

                    if($dly['pause'] == 'true'){
                        $sql = "update sdb_wms_delivery set status=2 where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                        $db->exec($sql);
                    }

                }elseif($dly['print_status'] == 1){
                    //更新发货单主表
                    $sql = "update sdb_wms_delivery set process_status=1, print_status=".$print_status.", last_modified=".$dly['last_modified']." where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                    $db->exec($sql);

                    //更新主包裹单
                    $sql = "update sdb_wms_delivery_bill set logi_no='".$dly['logi_no']."' where type=1 and delivery_id=".$wms_dlyInfo['delivery_id']."";
                    $db->exec($sql);

                    if($dly['pause'] == 'true'){
                        $sql = "update sdb_wms_delivery set status=2 where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                        $db->exec($sql);
                    }

                }elseif($dly['status'] == 'ready'){
                    //更新发货单主表
                    $sql = "update sdb_wms_delivery set print_status=".$print_status.", last_modified=".$dly['last_modified']." where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                    $db->exec($sql);

                    if($dly['pause'] == 'true'){
                        $sql = "update sdb_wms_delivery set status=2 where delivery_id='".$wms_dlyInfo['delivery_id']."'";
                        $db->exec($sql);
                    }
                }
            }
        }
    }
}

//更新产品版本
app::get('desktop')->setConf('banner','TP-ERP');
app::get('desktop')->setConf('logo','TP-ERP');
app::get('desktop')->setConf('logo_desc','TP-ERP');



