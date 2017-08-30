<?php
class replacesku_finder_order{
    var $detail_basic = '订单明细';

    function detail_basic($order_id){
      $render = app::get('replacesku')->render();
      $oOrder = &app::get('ome')->model('orders');
      $logObj = &app::get('ome')->model('operation_log');
      if(isset($_POST['order_action'])){
           switch($_POST['order_action']){
                 case "order_pause":
                    $memo = "订单暂停";
                    $oOrder->pauseOrder($order_id);
                    break;
                case "order_renew":
                    $memo = "订单恢复";
                    $oOrder->renewOrder($order_id);
                    break;
                   case 'order_custom_mark':
                    $order_id = $_POST['order']['order_id'];
                    //取出原留言信息
                    $oldmemo = $oOrder->dump(array('order_id'=>$order_id), 'custom_mark');
                    $oldmemo= unserialize($oldmemo['custom_mark']);
                    $op_name = kernel::single('desktop_user')->get_name();
                    if ($oldmemo)
                    foreach($oldmemo as $k=>$v){
                     $memo[] = $v;
                    }
                    $newmemo =  htmlspecialchars($_POST['order']['custom_mark']);
                    $newmemo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>$newmemo);
                    $memo[] = $newmemo;
                    $_POST['order']['custom_mark'] = serialize($memo);
                    $plainData = $_POST['order'];
                    $oOrder->save($plainData);
                    //写操作日志
                    $memo = "买家留言修改";

                    //买家留言 API
                    foreach(kernel::servicelist('service.order') as $object=>$instance){
                     if(method_exists($instance, 'add_custom_mark')){
                      $instance->add_custom_mark($order_id, $newmemo);
                     }
                    }

                    $oOperation_log = &app::get('ome')->model('operation_log');
                    $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                    break;
            }
      }


        $item_list = $oOrder->getItemList($order_id,true);

        $item_list = ome_order_func::add_getItemList_colum($item_list);
        ome_order_func::order_sdf_extend($item_list);

        $configlist = array();
        if ($servicelist = kernel::servicelist('ome.service.order.products'))
        foreach ($servicelist as $object => $instance){
            if (method_exists($instance, 'view_list')){
                $list = $instance->view_list();
                $configlist = array_merge($configlist, is_array($list) ? $list : array());
            }
        }
          $order_detail = $oOrder->dump($order_id);
          $order_detail['custom_mark'] = unserialize($order_detail['custom_mark']);
            if ($order_detail['custom_mark'])
            foreach ($order_detail['custom_mark'] as $k=>$v){
                if (!strstr($v['op_time'], "-")){
                    $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                    $order_detail['custom_mark'][$k]['op_time'] = $v['op_time'];
                }
            }
          $history = $logObj->read_log(array('obj_id'=>$order_id,'obj_type'=>'orders@ome'),0,-1);
          foreach($history as $k=>$v){
            $history[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
          }
            $render->pagedata['history'] = $history;
            $render->pagedata['configlist'] = $configlist;
            $render->pagedata['item_list'] = $item_list;
            $render->pagedata['object_alias'] = $oOrder->getOrderObjectAlias($order_id);
            $render->pagedata['order']  = $order_detail;
            return $render->fetch('admin/order/detail_good.html');
        }
      var $addon_cols = "pause";
      var $column_confirm='操作';
      var $column_confirm_width = "180";
      function column_confirm($row){
            $order_id = $row['order_id'];

            $find_id = $_GET['_finder']['finder_id'];
            $result = "<a href='index.php?app=ome&ctl=admin_order&act=view_edit&p[0]=$order_id' target='_blank'>订单编辑</a>";
          if($row['pause']=='false')
           {
           $button = '<a href="javascript:if (confirm(\'是否暂停！\')){W.page(\'index.php?app=replacesku&ctl=admin_order&act=pause_order&action=pause&order_id='.$order_id.'&find_id='.$find_id.'\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);"> 暂停</a>';
           }else if($row['pause']=='true'){
           $button = '<a href="javascript:if (confirm(\'是否恢复！\')){W.page(\'index.php?app=replacesku&ctl=admin_order&act=pause_order&action=renew&order_id='.$order_id.'&find_id='.$find_id.'\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);"> 恢复</a>';
           }


            return $result.$button;
       }

}