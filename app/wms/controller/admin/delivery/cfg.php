<?php
class wms_ctl_admin_delivery_cfg extends desktop_controller{

	function index(){
        # 发货组配置
        $typeobj = kernel::single('omeauto_auto_type');
        $orderTypes = $typeobj->getDeliveryGroupTypes();
        $this->pagedata['orderTypes'] = $orderTypes;


        $data = app::get('wms')->getConf('wms.delivery.status.cfg');
        $this->pagedata['setting'] = $this->options();
        $this->pagedata['data'] = $data['set'] ? $data['set'] : array();

        $cfgr = app::get('wms')->getConf('wms.delivery.cfg.radio');

        $this->pagedata['basic_cfg'] = ($cfgr!=2) ? true : false;

        #[拆单]未处理的订单[部分拆分、部分发货] ExBOY
        if($data['set']['split'] == '1')
        {
            $fields     = "order_id, order_bn, shop_id, shop_type, process_status, ship_status, total_amount, last_modified";
            $where      = " WHERE (process_status='splitting' || ship_status='2') AND `status`='active' ";
            $order_num  = kernel::database()->select("SELECT count(*) as num FROM ".DB_PREFIX."ome_orders ".$where);
            $order_list = kernel::database()->select("SELECT ".$fields." FROM ".DB_PREFIX."ome_orders ".$where." ORDER BY order_id DESC LIMIT 5");
            
            #关联发货单_数量
            if(!empty($order_list))
            {
                //确认状态、发货状态
                $ship_array     = array (0 => '未发货', 1 => '已发货', 2 => '部分发货', 3 => '部分退货', 4 => '已退货');
                $process_array  = array('unconfirmed' => '未确认','confirmed' => '已确认','splitting' => '部分拆分', 
                                    'splited' => '已拆分完', 'cancel' => '取消', 'remain_cancel' =>'余单撤销');
                
                //店铺
                $shop_list  = array();
                $oShop      = &app::get('ome')->model('shop');
                $data_shop  = $oShop->getList('shop_id, name', null, 0, -1);
                foreach ($data_shop as $key => $val)
                {
                    $sel_shop_id    = $val['shop_id'];
                    $shop_list[$sel_shop_id]    = $val['name'];
                }
                
                $data_dly   = array();
                foreach ($order_list as $key => $val)
                {
                    $sel_order_id   = $val['order_id'];
                    $sql    = "SELECT dord.delivery_id, d.status FROM ".DB_PREFIX."ome_delivery_order AS dord 
                            LEFT JOIN ".DB_PREFIX."ome_delivery AS d ON (dord.delivery_id=d.delivery_id) 
                            WHERE dord.order_id=".$sel_order_id." AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                            AND d.status NOT IN('failed','cancel','back','return_back')";
                    $data_dly   = kernel::database()->select($sql);
                    
                    $order_list[$key]['dly_count']  = count($data_dly);
                    $order_list[$key]['delivery']   = $data_dly;
                    $order_list[$key]['dly_succ']   = 0;
                    
                    foreach ($data_dly as $key_j => $val_j)
                    {
                        if($val_j['status'] == 'succ')
                        {
                            $order_list[$key]['dly_succ']++;//已发货数量
                        }
                    }
                    
                    $sel_shop_id    = $val['shop_id'];
                    $ship_status    = $val['ship_status'];
                    $process_status = $val['process_status'];
                    $order_list[$key]['shop_name']       = $shop_list[$sel_shop_id];
                    $order_list[$key]['ship_status']     = $ship_array[$ship_status];
                    $order_list[$key]['process_status']  = $process_array[$process_status];
                }
            }
            $this->pagedata['order_num']    = $order_num[0]['num'];
            $this->pagedata['order_list']   = $order_list;
        }
        
        $this->page('admin/delivery/delivery_cfg.html');
	}

    /**
     * 保存发货配置
     *
     * @return void
     * @author
     **/
    public function save()
    {
        #获取_发货组配置 ExBOY
        $opObj          = &app::get('ome')->model('operation_log');
        $config_data    = app::get('wms')->getConf('wms.delivery.status.cfg');
        
        $post = kernel::single('base_component_request')->get_post();
        $this->begin();
        if ($post) {
            $post['set']['expre'] = '1';
            
            #[拆单]配置 ExBOY
            if($post['set']['split'] == '1')
            {
                if($post['set']['split_model'] == '1')
                {
                    if($post['set']['split_class'] == '1')
                    {
                        $post['set']['split_type']     = '1';
                    }
                    elseif($post['set']['split_class'] == '2')
                    {
                        $post['set']['split_type']     = '2';
                    }               
                    unset($post['set']['split_send']);
                }
                elseif($post['set']['split_model'] == '2')
                {
                    if($post['set']['split_send'] == '1')
                    {
                        $post['set']['split_type']     = '1';
                    }
                    elseif($post['set']['split_send'] == '2')
                    {
                        $post['set']['split_type']     = '2';
                    }
                    unset($post['set']['split_class']);
                }
                
                #配置日志
                $log_msg   = '开启拆单功能;';
                $log_msg   .= ($post['set']['split_model'] == '1' ? '按子订单方式进行拆分订单' : '按sku方式拆分订单');
                $log_msg   .= '-';
                $log_msg   .= ($post['set']['split_type'] == '1' ? '回写第一张' : '回写最后一张');
                
                $opObj->write_log('order_split@ome', 0, $log_msg);
            }
            else 
            {
                #关闭拆单功能_配置日志
                if($config_data['set']['split'] == '1')
                {
                    $log_msg   = '关闭拆单功能';
                    $opObj->write_log('order_split@ome', 0, $log_msg);
                }
                
                unset($post['set']['split_model'], $post['set']['split_type'], $post['set']['split_class'], $post['set']['split_send']);
            }
            
            # 验证数据
            if ($post['set']['merge'] == 1 && $post['set']['wms_batch_print_nums'] < $post['set']['wms_eachgroup_print_count']) {
                $this->end(false,'联合分组数不能大于批量打印数量！');
            }
            if ($post['set']['single']['merge'] == 1 && $post['set']['single']['wms_batch_print_nums'] < $post['set']['single']['wms_eachgroup_print_count']) {
                $this->end(false,'联合分组数不能大于批量打印数量！');
            }
            if ($post['set']['multi']['merge'] == 1 && $post['set']['multi']['wms_batch_print_nums'] < $post['set']['multi']['wms_eachgroup_print_count']) {
                $this->end(false,'联合分组数不能大于批量打印数量！');
            }
            app::get('wms')->setConf('wms.delivery.status.cfg',$post);
        }
        $this->end(true,'保存成功');
    }


    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    private function options()
    {
        $set = array(
            'stock' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'wms_delivery_is_printstock' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'),
                ),
            ),
            'delie' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'wms_delivery_is_printdelivery' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'),
                ),
            ),
            'merge' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'wms_delivery_merge_print' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'),
                ),
            ),
            'expre' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'wms_delivery_is_printship' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'),
                ),
            ),
            'verify' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'consign' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'print_merge' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'print_style'=>array(
                'options'=>array(

                    '0'=>$this->app->_('按销售清单'),
                    '1'=>$this->app->_('按拣货格式'),
                    )

                ),
            'print_devision'=>array(
                 'options'=>array(

                    '0'=>$this->app->_('老版本'),
                    '1'=>$this->app->_('新版本'),
                    )
                ),
           'print_order'=>array(
                   'options'=>array(
                        0=>$this->app->_('自然排序'),
                        1=>$this->app->_('货位排序'),       
                    )
                   ),
         'print_pkg_goods'=>array(
                'options'=>array(
                        0=>$this->app->_('不打印捆绑商品货号'),
                        1=>$this->app->_('打印捆绑商品货号'),
                )
         ),
         'sellagent' => array(
                    'options' => array(
                            '0' => $this->app->_('使用ERP店铺发货信息'),
                            '1' => $this->app->_('启用分销王代销人发货信息'),
                    )
            ), 
         'print_mode'=>array(
                'options'=>array(
                        0=>$this->app->_('html风格'),
                        1=>$this->app->_('控件风格'),
                )
         ),
         'stock_print_mode'=>array(
                'options'=>array(
                        0=>$this->app->_('html风格'),
                        1=>$this->app->_('控件风格'),
                )
         ),
         'split' => array(//[拆单]拆分配置 ExBOY
                'options' => array(
                    '0' => $this->app->_('关闭拆单'),
                    '1' => $this->app->_('开启拆单'),
                ),
         ),
         'split_model' => array(
                'options' => array(
                    '1' => $this->app->_('按淘宝规定物流订单的子订单方式进行拆单'),
                    '2' => $this->app->_('按sku进行拆单，回写最后一张发货单物流信息至平台'),
                ),
         ),
         'split_type' => array(
                'options' => array(
                    '1' => $this->app->_('第一次发货时,回写发货单'),
                    '2' => $this->app->_('全部发完货时,回写发货单'),
                ),
         ),
         
        );
        return $set;
    }


}