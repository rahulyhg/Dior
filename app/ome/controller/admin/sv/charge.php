<?php
class ome_ctl_admin_sv_charge extends desktop_controller{
    var $name = "收货";
    var $workground = "aftersale_center";

    function index(){

        switch ($_GET['flt'])
        {
            case 1:
                $title='';

                break;
            case 2:
                $title='未';
                $filter['recieved'] = 'false';
                //$this->base_filter = array('recieved' => 'false');
                break;
            case 3:
                $title='已';
                $filter['recieved'] = 'true';
                //$this->base_filter = array('recieved' => 'true');
                break;
        }

        // 获取操作员管辖仓库
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_ids = $oBranch->getBranchByUser(true);
           if ($branch_ids){
                $filter['branch_id'] = $branch_ids;
           }else{
                $filter['branch_id'] = 'false';
           }
        }

       $params = array(
            'title'=>$title.'收货',
            'base_filter' => $filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            );
       $this->finder('ome_mdl_return_process', $params);
    }


    /*
     * 列编辑
     */
    function edit($por_id){
         $oProduct_pro = &$this->app->model('return_process');
         $oOrder = &$this->app->model('orders');
         $oProduct_pro_detail = $oProduct_pro->product_detail($por_id);

         if (!is_numeric($oProduct_pro_detail['attachment'])){
            $this->pagedata['attachment_type'] = 'remote';
         }

         $this->pagedata['pro_detail']=$oProduct_pro_detail;
         $this->pagedata['order'] = $oOrder->dump($oProduct_pro_detail['order_id']);
        $this->singlepage("admin/sv_charge/editsv.html");
    }

    /*
     * 执行收货
     *
     */
    function save(){
        $oProduct= &$this->app->model('return_product');
        $oProduct_pro = &$this->app->model('return_process');
        //写日志
        $oOperation_log = &$this->app->model('operation_log');
        $por_id = $_POST['info']['por_id'];
        $this->begin('index.php?app=ome&ctl=admin_sv_charge&act=index');
        $memo='';
        $oProduct_pro_detail = $oProduct_pro->product_detail($por_id);
        $return_id = $oProduct_pro_detail['return_id'];

        //增加售后收货前的扩展
        foreach(kernel::servicelist('ome.aftersale') as $o){
            if(method_exists($o,'pre_sv_charge')){
                if(!$o->pre_sv_charge($_POST,$memo)){
                    $this->end(false, app::get('base')->_($memo));
                }
            }
        }

        $data['branch_name'] = $oProduct_pro_detail['branch_name'];
        $data['shipcompany'] = $_POST['info']['shipcompany'];
        $data['shiplogino'] = $_POST['info']['shiplogino'];
        $data['shipmoney'] = $_POST['info']['shipmoney'];
        $data['shipdaofu'] = $_POST['info']['daofu'] == 1 ? 1 : 0;
        $data['shiptime'] = time();
        $product_data = $oProduct_pro_detail['items'];
        $bn=array();//对应货品数量
        foreach($product_data as $k => $v){
            $v['bn'] = trim($v['bn']);
            $bn[$v['bn']]+=1;
            $productdata[$v['bn']]=array('bn'=>$v['bn'],'name'=>$v['name'],'branch_id'=>$v['branch_id'],'num'=>$bn[$v['bn']]);
        }
        $num_array = array();
        $bn_array = array();
        $sum = 0;
        $num=0;
        foreach($product_data as $k => $v){
            $v['bn'] = trim($v['bn']);
            $bn_array[$v['bn']] = $v['branch_id'];
            $sum+=$v['num'];//总数
            $num_array[$v['bn']] += $v['num'];
        }
       $tmp_num_array=array();
       $prdustr = explode(',', $_POST['bn_list']);
       if ($prdustr){
           foreach ($prdustr as $k=>$val){
                $val = trim($val);
                if(!isset($bn_array[$val])){
                    $this->end(false, app::get('base')->_($val.'该货品不在申请中!'));
                }
                $num=$num+1;
                if(isset($tmp_num_array[$val])){
                    $tmp_num_array[$val] +=1;
                }else{
                    $tmp_num_array[$val] =1;
                }
           }
       }
       foreach($num_array as $k => $v){
            if($tmp_num_array[$k] != $v){
                $this->end(false, app::get('base')->_('处理货品数量不符,请将所有货品全部输入再重新申请！'));
            }
       }
       /*收货完成后。需要将状态更新为收货，主表更新为已收货*/

       $prodata = array('por_id'=>$por_id ,'recieved'=>'true','process_data'=>serialize($data));
       $oProduct_pro->save($prodata);
       /*将仓库信息写入主表*/
       $product = $oProduct->dump($return_id,'process_data');
       $process_data = unserialize($product['process_data']);
       $process_data[$oProduct_pro_detail['branch_id']] = $data;

       $sdf_product = array('process_data'=>serialize($process_data),
                      'last_change_time'=>time(),
                      'return_id'=>$return_id
                   );
       $oProduct->save($sdf_product);
       $oProduct_pro->changestatus($por_id,$return_id,'recieved',6);
       $memo='仓库:'.$oProduct_pro_detail['branch_name'].'收货成功';
       $oOperation_log->write_log('return@ome',$oProduct_pro_detail['return_id'],$memo);

       //售后申请状态更新
        foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
            if(method_exists($instance,'update_status')){
                $instance->update_status($oProduct_pro_detail['return_id']);
            }
        }

       //增加售后收货前的扩展
        foreach(kernel::servicelist('ome.aftersale') as $o){
            if(method_exists($o,'after_sv_charge')){
                $o->after_sv_charge($_POST);
            }
        }

       $this->end(true, app::get('base')->_('收货成功'));
    }

}
?>
