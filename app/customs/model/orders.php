<?php
/**
 +----------------------------------------------------------
 * 跨境申报类
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_mdl_orders extends dbeav_model
{
    var $import_data        = array();
    var $abnormal_type_id   = 998;//订单异常类型
    var $export_flag        = false;//导入增加付款状态
    
    /*------------------------------------------------------ */
    //-- 获取列表数据[自定义]
    /*------------------------------------------------------ */
    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $where        = $this->_filter($filter);
        if(!empty($where))
        {
            $where    = ' WHERE '.$where;
        }
        
        $sql      = "SELECT a.*, b.total_amount, b.process_status, b.is_cod, b.pay_status, b.ship_status, b.createtime 
                     FROM ". DB_PREFIX ."customs_orders as a 
                     LEFT JOIN ". DB_PREFIX ."ome_orders as b ON a.order_id=b.order_id 
                      ". $where ." ORDER BY a.cid DESC";
        $rows     = $this->db->selectLimit($sql, $limit, $offset);
        $this->tidy_data($rows, $cols);

        return $rows;
    }
    
    /*------------------------------------------------------ */
    //-- 过滤条件[自定义]
    /*------------------------------------------------------ */
    public function _filter($filter)
    {
        $where[]    = 1;
        $key_array  = array('order_bn', 'declare_bn', 'member_id', 'shop_type', 'card_no', 'payment_bn', 'payment', 
                            'status', 'currency', 't_end', 'logis_id', 'disabled', 'op_id', 'shop_sid', 'dateline', 
                            'lastdate', 'declare_check', 'declare_status');
        
        if(!empty($filter['cid']))
        {
            if(is_array($filter['cid']))
            {
                $where[]    = "a.cid in(" . implode(',', $filter['cid']) .")";
            }
            else
            {
                $where[]    = "a.cid='" . $filter['cid'] ."'";
            }
        }
        if(!empty($filter['order_id']))
        {
            if(is_array($filter['order_id']))
            {
                $where[]    = "a.order_id in(" . implode(',', $filter['order_id']) .")";
            }
            else
            {
                $where[]    = "a.order_id='" . $filter['order_id'] ."'";
            }
        }
        if(!empty($filter['status']) || $filter['status'] == '0')
        {
            if(is_array($filter['status']))
            {
                $_temp      = array();
                foreach ($filter['status'] as $key => $val)
                {
                    $_temp[]       = "'".intval($val)."'";
                }
                $_temp      = implode(',', $_temp);
        
                $where[]    = "a.status in (".$_temp.")";
            }
            else
            {
                $where[]     = "a.status = '".$filter['status']."'";
            }
        }
        
        foreach ($filter as $key => $val)
        {
            if(in_array($key, $key_array))
            {
                $where[]     = "a.". $key . " = '" . $val ."'";
            }
        }
        
        if(!empty($filter['dateline']))
            {
                $create_time_hour     = $filter['_DTIME_']['H']['dateline'];
                $create_time_minute   = $filter['_DTIME_']['M']['dateline'];             
                $create_time_start    = strtotime($filter['dateline'].' '.$create_time_hour.':'.$create_time_minute.':00');
                if($filter['_dateline_search']=='nequal')
                {
                    $where[]    = "a.dateline='".$create_time_start."'";
                }
                elseif($filter['_dateline_search']=='than')
                {
                    $where[]    = "a.dateline>'".$create_time_start."'";
                }
                elseif($filter['_dateline_search']=='lthan')
                {
                    $where[]    = "a.dateline<'".$create_time_start."'";
                }
                elseif($filter['_dateline_search']=='between' && $filter['dateline_from'] && $filter['dateline_to'])
                {
                    $from_hour            = $filter['_DTIME_']['H']['dateline_from'];
                    $from_minute          = $filter['_DTIME_']['H']['dateline_from'];
                    $create_time_from     = $filter['dateline_from'];
                    $create_time_from     = strtotime($create_time_from.' '.$from_hour.':'.$from_minute.':00');
                    
                    $to_hour            = $filter['_DTIME_']['H']['dateline_to'];
                    $to_minute          = $filter['_DTIME_']['H']['dateline_to'];
                    $create_time_to     = $filter['dateline_to'];
                    $create_time_to     = strtotime($create_time_to.' '.$to_hour.':'.$to_minute.':00');
                    
                    $where[]    = "(a.dateline>='".$create_time_from."' AND a.dateline<='".$create_time_to."')";
                }
        }
        
        return implode($where, ' AND ');
    }
    
    /*------------------------------------------------------ */
    //-- 获取总数[自定义]
    /*------------------------------------------------------ */
    public function count($filter=null)
    {
        $where        = $this->_filter($filter);
        if(!empty($where))
        {
            $where    = ' WHERE '.$where;
        }
        
        $sql      = "SELECT count(*) as num FROM ". DB_PREFIX ."customs_orders as a 
                      ". $where;
        
        $row      = $this->db->select($sql);
        return $row[0]['num'];
    }
    
    /*------------------------------------------------------ */
    //-- 获取分类[自定义]
    /*------------------------------------------------------ */
    public function get_typename($typename = '', $keyval = '')
    {
        #店铺类型
        //$data['shop_type']    = ome_shop_type::get_shop_type();
        $data['shop_type']      = array(
                                        'taobao' => array('shop_name' => '淘宝', 'code' => '0001'),
                                        'tmall' => array('shop_name' => '天猫', 'code' => '0002'),
                                        'tmall_hk' => array('shop_name' => '天猫国际', 'code' => '0002'),
                                        '360buy' => array('shop_name' => '京东', 'code' => '0010'),
                                        'jumei' => array('shop_name' => '聚美优品', 'code' => '0011'),
                                        'yihaodian' => array('shop_name' => '1号店', 'code' => '0005'),
                                        'suning' => array('shop_name' => '苏宁易购', 'code' => '0006'),
                                        'vip' => array('shop_name' => '唯品会', 'code' => '0012'),
                                        'koudaitong' => array('shop_name' => '口袋通', 'code' => '0013'),
                                        'ccb' => array('shop_name' => '善融商务', 'code' => '0004'),
                                        'ymatou' => array('shop_name' => '洋码头', 'code' => '0008'),
                                        'sfheike' => array('shop_name' => '顺丰嘿客', 'code' => '0009'),
                                        'cnbuyers' => array('shop_name' => '畅购天下保税超市', 'code' => '0007'),
                                    );
        
        #币种('USD'=>'美元', 'JPY'=>'日元', 'EUR'=>'欧元', 'GBP'=>'英镑', 'HKD'=>'港币')
        $data['currency']     = array('RMB'=>'人民币');
        
        #支付方式
        $data['payment']     = array(
                                    '00'=>'其他方式',
                                    '01'=>'中国银联',
                                    '02'=>'支付宝',
                                    '03'=>'盛付通',
                                    '04'=>'建设银行',
                                    '05'=>'中国银行',
                                    '06'=>'易付宝',
                                    '07'=>'农业银行',
                                );
        
        #物流公司
        $data['logistics']   = array(1=>'顺丰速运', '邮政速递', '中通速递', '邮政小包');
        $data['logi_list']   = array(
                                1 => array('name' => '顺丰速运', 'type' => 'SF', 'kdapi_code'=>'shunfeng'),
                                2 => array('name' => '邮政平邮', 'type' => 'POST', 'kdapi_code'=>'youzhengpingyou'),
                                3 => array('name' => '中通速递', 'type' => 'ZTO', 'kdapi_code'=>'zhongtong'),
                                4 => array('name' => '邮政国内小包', 'type' => 'POSTB', 'kdapi_code'=>'youzhengguoneixiaobao'),
                             );
        
        #电子口岸
        $data['company_id']     = array(1=>'宁波');
        
        #申报单状态
        $data['declare_status']      = array(
                                            '00' => '未开始',
                                            '01' => '库存不足',
                                            '11' => '已报国检',
                                            '12' => '国检放行',
                                            '13' => '国检审核未过',
                                            '14' => '国检抽检',
                                            '21' => '已报海关',
                                            '22' => '海关单证放行',
                                            '23' => '海关单证审核未过',
                                            '24' => '海关货物放行',
                                            '25' => '海关查验未过',
                                            '99' => '已关闭',
                                        );
        
        if(!empty($typename) && !empty($keyval))
        {
            $data    = $data[$typename][$keyval];
        }
        elseif($typename)
        {
            $data    = $data[$typename];
        }
        
        return $data;
    }
    
    /*------------------------------------------------------ */
    //-- 获取跨境电子口岸
    /*------------------------------------------------------ */
    public function get_company($sid)
    {
        $oSetting        = &app::get('customs')->model('setting');
        $copyList        = $oSetting->dump(array('sid' => $sid), 'sid, company_code, company_name, username');
        
        #店铺节点、类型
        $copyList['node_type']    = 'kjb2c';
        $copyList['to_node_id']   = '1183376836';
        $copyList['customs']      = '3105';//关区代码(保税备货：3105, 保税集货：3115, 一般进口：3109)
        $copyList['user_id']      = 'iloveshopex';#写死的
        $copyList['user_secret']  = '85196319-0dec-4f48-b0c6-ed86fbf99781';#写死的
        
        return $copyList;
    }
    
    /**
     +----------------------------------------------------------
     * 批量设置为跨境订单[操作当前订单栏目]
     +----------------------------------------------------------
     * @param   Array    $order_ids 订单ID数组
     * return   Array
     +----------------------------------------------------------
     */
    public function create_declare($order_ids)
    {
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oOrder          = &app::get('ome')->model('orders');
        $oPayment        = &app::get('ome')->model('payments');
        $oMembers        = &app::get('ome')->model('members');
        $oCustoms        = $this->app->model('orders');
        
        $field         = 'order_id, order_bn, member_id, payment, shop_id, shop_type, op_id, group_id';
        $filter        = array('order_id'=>$order_ids, 'process_status'=>'unconfirmed', 'pay_status'=>'1', 'status'=>'active');
        $order_list    = $oOrder->getList($field, $filter, 0, -1);
        if(empty($order_list))
        {
            return array('rsp'=>'fail', 'error_msg' => '没有找到有效订单');
        }
        
        #申报订单是否已经存在
        $declare_list  = array();
        $result        = $oCustoms->getList('order_id', array('order_id'=>$order_ids), 0, -1);
        if($result)
        {
            foreach ($result as $key => $val)
            {
                $declare_list[]    = $val['order_id'];
            }
            
            $result    = array_diff($order_ids, $declare_list);
            if(empty($result))
            {
                return array('rsp'=>'fail', 'error_msg' => '跨境订单已经存在');
            }
        }
        
        #添加申报订单
        $result   = array();
        $op_id    = kernel::single('desktop_user')->get_id();//操作人
        $field    = 'payment_id, payment_bn, currency, money, pay_type, payment, paymethod, t_end';
        
        foreach ($order_list as $key => $val)
        {
            //支付单详情
            $payinfo    = $oPayment->dump(array('order_id'=>$val['order_id'], 'status'=>'succ'), $field);
            
            //会员详情
            $member_info    = $oMembers->dump(array('member_id'=>$val['member_id']), 'member_id, uname, name, mobile, tel, email, area, addr');
            
            //新增跨境数据
            $data    = array();
            $data['order_id']     = $val['order_id'];
            $data['order_bn']     = $val['order_bn'];
            $data['declare_bn']   = '';//申报单号
            $data['shop_type']    = $val['shop_type'];//购物网站
            $data['logis_id']     = 1;//快递公司:默认顺丰速运
            
            #支付单信息
            $data['payment']      = '02';//支付方式:默认支付宝$val['payment']
            $data['payment_bn']   = $payinfo['payment_bn'];
            $data['currency']     = 'RMB';//货币:默认RMB
            $data['t_end']        = ($payinfo['t_end'] ? $payinfo['t_end'] : '');
            
            #会员信息
            $data['card_no']         = '';//会员身份证号
            $data['member_id']       = $val['member_id'];
            $data['member_uname']    = $member_info['account']['uname'];
            $data['member_name']     = $member_info['contact']['name'];
            $data['member_mobile']   = $member_info['contact']['phone']['mobile'];
            $data['member_tel']      = $member_info['contact']['phone']['telephone'];
            $data['member_email']    = $member_info['contact']['email'];
            $data['member_area']     = $member_info['contact']['area'];
            $data['member_addr']     = $member_info['contact']['addr'];
            
            #other
            $data['status']     = 0;
            $data['remarks']    = '';
            $data['op_id']      = $op_id;
            $data['dateline']   = time();
            $data['lastdate']   = time();
            
            $insert_info    = $oCustoms->insert($data);
            if($insert_info)
            {
                #更新订单状态
                $update_order  = array();
                $update_order['order_id']          = $val['order_id'];
                $update_order['process_status']    = 'is_declare';
                $update_order['pause']             = 'true';//订单暂停
                $update_order['abnormal']          = 'true';//异常
                
                $oOrder->save($update_order);
                
                #[设置]为订单异常
                $abnormal_data  = array();
                $abnormal_data['order_id']         = $val['order_id'];
                $abnormal_data['op_id']            = $val['op_id'];
                $abnormal_data['group_id']         = $val['group_id'];
                $abnormal_data['abnormal_type_id'] = $this->abnormal_type_id;//订单异常类型
                $abnormal_data['is_done']          = 'false';
                $abnormal_data['abnormal_memo']    = '订单进入跨境申报';
                
                $oOrder->set_abnormal($abnormal_data);
                
                #订单操作日志
                $oOperation_log->write_log('order_edit@ome', $val['order_id'], '订单进行跨境申报');
                $oOperation_log->write_log('customs_create@ome', $val['order_id'], '跨境申报订单创建成功');
                
                #返回日志
                $result[]    = $val['order_bn'];
            }
        }
        
        //查检订单是否有效
        $filter   = array('order_id' => $order_ids);
        $oCustoms->check_decalre($filter, true);
        
        return array('rsp'=>'succ', 'msg' => '成功新建'.count($result).'跨境订单');
    }
    
    /**
     +----------------------------------------------------------
     * 检查申报数据是否有效
     +----------------------------------------------------------
     * @param   Array    $filter 查询条件
     * @param   Boolean  $is_update 是否更新为无效
     * return   Array
     +----------------------------------------------------------
     */
    public function check_decalre($filter, $is_update=false)
    {
        if(empty($filter['cid']) && empty($filter['order_id']))
        {
            return false;
        }
        
        $oCustoms    = $this->app->model('orders');
        $result      = $oCustoms->getList('*', $filter, 0, -1);
        
        if(empty($result))
        {
            return false;
        }
        
        $flag    = true;//标记
        foreach ($result as $key => $val)
        {
            $cid    = $val['cid'];
            
            #检查数据
            $error    = array();
            if(empty($val['shop_type']))
            {
                $error[]    = '购物网站';
            }
            if(empty($val['member_uname']))
            {
                $error[]    = '买家账号';
            }
            if(empty($val['payment']))
            {
                $error[]    = '支付方式';
            }
            if(empty($val['currency']))
            {
                $error[]    = '币种';
            }
            if(empty($val['logis_id']))
            {
                $error[]    = '物流公司';
            }
            if(empty($val['shop_sid']))
            {
                $error[]    = '申报电子口岸';
            }
            if(empty($val['card_no']))
            {
                $error[]    = '身份证号';
            }
            if(empty($val['member_name']))
            {
                $error[]    = '姓名';
            }
            if(empty($val['member_mobile']) && empty($val['member_tel']))
            {
                $error[]    = '联系电话';
            }
            if(empty($val['member_email']))
            {
                $error[]    = '邮箱';
            }
            
            $error_mg    = implode('、', $error);
            $error_mg    = '错误信息：'. $error_mg . '不能为空';
            
            #是否更新
            if(!empty($error) && $is_update)
            {
                #备注
                $remarks[0]['op_name']    = kernel::single('desktop_user')->get_name();//操作人
                $remarks[0]['op_time']    = time();
                $remarks[0]['op_content']  = $error_mg;
                
                $sql    = "UPDATE ".DB_PREFIX."customs_orders SET disabled='true', remarks='".serialize($remarks)."' WHERE cid='".$cid."'";
                kernel::database()->exec($sql);
            }
            elseif($is_update)
            {
                $sql    = "UPDATE ".DB_PREFIX."customs_orders SET disabled='false', remarks='' WHERE cid='".$cid."'";
                kernel::database()->exec($sql);
            }
            
            if(!empty($error))
            {
                $flag    = false;
            }
        }
        
        return $flag;
    }
    
    /*------------------------------------------------------ */
    //-- csv导入新订单step 1
    /*------------------------------------------------------ */
    function io_title( $filter=null,$ioType='csv' )
    {
        switch( $ioType ){
            case 'csv':
                default:
                    $this->oSchema['csv']['order'] = array(
                        '*:订单号' => 'order_bn',
                        '*:支付方式' => 'payinfo/pay_name',
                        '*:下单时间' => 'createtime',
                        '*:付款时间' => 'paytime',
                        '*:配送方式' => 'shipping/shipping_name',
                        '*:配送费用' => 'shipping/cost_shipping',
                        '*:来源店铺编号' => 'shop_id',
                        '*:来源店铺' => 'shop_name',
                        '*:订单附言' => 'custom_mark',
                        '*:收货人姓名' => 'consignee/name',
                        '*:收货地址省份' => 'consignee/area/province',
                        '*:收货地址城市' => 'consignee/area/city',
                        '*:收货地址区/县' => 'consignee/area/county',
                        '*:收货详细地址' => 'consignee/addr',
                        '*:收货人固定电话' => 'consignee/telephone',
                        '*:电子邮箱' => 'consignee/email',
                        '*:收货人移动电话' => 'consignee/mobile',
                        '*:邮编' => 'consignee/zip',
                        '*:货到付款' => 'shipping/is_cod',
                        '*:是否开发票' => 'is_tax',
                        '*:发票抬头' => 'tax_title',
                        '*:税金(非开票总额)' => 'cost_tax',
                        '*:优惠方案' => 'order_pmt',
                        '*:订单优惠金额' => 'pmt_order',
                        '*:商品优惠金额' => 'pmt_goods',
                        '*:折扣' => 'discount',
                        '*:返点积分' => 'score_g',
                        '*:商品总额' => 'cost_item',
                        '*:订单总额' => 'total_amount',
                        '*:买家会员名' => 'account/uname',
                        '*:订单类型' => 'order_source',
                        '*:订单备注' => 'mark_text',
                        '*:商品重量' =>'weight',
                        '*:发票号'=>'tax_no',
                        '*:发票抬头'=>'tax_title',
                        '*:周期购'=>'createway',
                        '*:是否跨境订单' => 'is_declare',//同时插入到跨境订单表 ExBOY
                        '*:付款状态' => 'pay_status',//已支付才会插入到跨境订单 ExBOY
                        '*:支付单号' => 'payment_bn',//支付单号
                        '*:会员身份证号' => 'card_no',//客户身份证号
                    );
                    
                    $this->oSchema['csv']['obj'] = array(
                                                        '*:订单号' => '',
                                                        '*:商品货号' => '',
                                                        '*:商品名称' => '',
                                                        '*:购买单位' => '',
                                                        '*:商品规格' => '',
                                                        '*:购买数量' => '',
                                                        '*:商品原价' => '',
                                                        '*:销售价' =>'',
                                                        '*:商品优惠金额' => '',
                                                        '*:商品类型' => '',
                                                        '*:商品品牌' => '',
                                                    );
                    break;
        }
        
        #新增导出字段
        if($this->export_flag)
        {
            $title = array(
                        '*:发货状态'=>'ship_status',
                        '*:付款状态'=>'pay_status'
                      );
            
            $this->oSchema['csv']['order'] = array_merge($this->oSchema['csv']['order'], $title);
        }
        
        #导出模板时，将不需要的字段从这里清除
        if(!$this->export_flag)
        {
            unset($this->oSchema['csv']['order']['*:来源店铺']);
        }
        
        $this->ioTitle[$ioType]['order'] = array_keys( $this->oSchema[$ioType]['order'] );
        $this->ioTitle[$ioType]['obj'] = array_keys( $this->oSchema[$ioType]['obj'] );
        return $this->ioTitle[$ioType][$filter];
    }
    
    /*------------------------------------------------------ */
    //-- csv导入新订单step 2
    /*------------------------------------------------------ */
    function prepared_import_csv_row($row, $title, &$tmpl, &$mark, &$newObjFlag,&$msg)
    {
        //定义一个商品货号状态，为的是区别商品明细是否有值(2011_12_21_luolongjie)
        static $has_products = 0;
        if(empty($row)){
            $error_msg = array();
            //当商品没有货号时候，停止导入（有其他商品明细，却没货号，或者货号不对）
            if(isset($this->not_exist_product_bn)){
                if(count($this->not_exist_product_bn) > 10){
                    for($i=0;$i<10;$i++){
                        $not_exist_product_bn[] = current($this->not_exist_product_bn);
                        next($this->not_exist_product_bn);
                    }
                    $more = "...";
                }else{
                    $not_exist_product_bn = $this->not_exist_product_bn;
                    $more = "";
                }
                $error_msg[] = "不存在的货号：".implode(",",$not_exist_product_bn).$more;
            }elseif($has_products == 0){ //没有任何商品明细的时候
                $error_msg[] = "缺少商品明细";
            }

            if(isset($this->duplicate_order_bn_in_file)){
                if(count($this->duplicate_order_bn_in_file) > 10){
                    for($i=0;$i<10;$i++){
                        $duplicate_order_bn_in_file[] = current($this->duplicate_order_bn_in_file);
                        next($this->duplicate_order_bn_in_file);
                    }
                    $more = "...";
                }else{
                    $more = "";
                }
                $error_msg[] = "文件中以下订单号重复：".implode(",",$this->duplicate_order_bn_in_file).$more;
            }
            if(isset($this->duplicate_order_bn_in_db)){
                if(count($this->duplicate_order_bn_in_db) > 10){
                    for($i=0;$i<10;$i++){
                        $duplicate_order_bn_in_db[] = current($this->duplicate_order_bn_in_db);
                        next($this->duplicate_order_bn_in_db);
                    }
                    $more = "...";
                }else{
                    $more = "";
                }
                $error_msg[] = "以下订单号在系统中已经存在：".implode(",",$this->duplicate_order_bn_in_db).$more;
            }
            if(!empty($error_msg)){
                unset($this->import_data);
                $msg['error'] = implode("     ",$error_msg);
                return false;
            }
        }


        $mark = false;
        $fileData = $this->import_data;

        if( !$fileData )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);

            $mark = 'title';

            return $titleRs;
        }else{

            if( $row[0] ){
                $row[0] = trim($row[0]);
                if( array_key_exists( '*:商品货号',$title )  ) {
                    if(!app::get('ome')->model('products')->dump(array('bn'=>$row[1]))){
                        $product_status = false;
                        foreach(kernel::servicelist('ome.product') as $name=>$object){
                            if(method_exists($object, 'checkProductByBn')){
                                $product_info = $object->checkProductByBn($row[1]);
                                if($product_info){
                                    $product_status = true;
                                    break;
                                }
                            }
                        }
                        if ($product_status==false) $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[1])):array($row[1]);
                    }
                    //说明商品明细有过值，并非为空(2011_12_21_luolongjie)
                    $has_products = 1;
                    $fileData[$row[0]]['obj']['contents'][] = $row;
                }else{
                    //计数判断，是否超过1000条记录，超过就提示数据过多
                    if(isset($this->order_nums)){
                        kernel::log($this->order_nums);
                        $this->order_nums ++;
                        if($this->order_nums > 1000){
                            unset($this->import_data);
                            $msg['error'] = "导入的数据量过大，请减少到1000单以下！";
                            return false;
                        }
                    }else{
                        $this->order_nums = 0;
                    }

                    if(isset($fileData[$row[0]])){
                        $this->duplicate_order_bn_in_file = isset($this->duplicate_order_bn_in_file)?array_merge($this->duplicate_order_bn_in_file,array($row[0])):array($row[0]);
                    }
                    if($this->dump(array('order_bn'=>$row[0]))){
                        $this->duplicate_order_bn_in_db = isset($this->duplicate_order_bn_in_db)?array_merge($this->duplicate_order_bn_in_db,array($row[0])):array($row[0]);
                    }

                    if(empty($row[6])){
                        unset($this->import_data);
                        $msg['error'] = "来源店铺编号不能为空";
                        return false;
                    }

                    $shopModel = app::get('ome')->model('shop');
                    $shop = $shopModel->getList('shop_bn',array('shop_bn'=>$row[6]),0,1);
                    if (!$shop) {
                            unset($this->import_data);
                            $msg['error'] = "来源店铺【".$row[6]."】不存在";
                            return false;
                    }

                    $fileData[$row[0]]['order']['contents'][] = $row;
                }

                $this->import_data = $fileData;
            }
        }
        
        return null;
    }
    
    /*------------------------------------------------------ */
    //-- csv导入新订单[判断是否为本操作所需的csv文件]
    /*------------------------------------------------------ */
    function check_csv($title)
    {
        $arrFrom = array_flip(array_filter(array_flip($title)));
        $this->io_title('order');
        $arrFieldsAll = $this->oSchema['csv']['order'];
        $arrResult = array_diff_key($arrFrom,$arrFieldsAll);
    
        return empty($arrResult) ?  true : false;
    }
    
    /*------------------------------------------------------ */
    //-- 导入csv订单[只针对订单已经存在]
    /*------------------------------------------------------ */
    function finish_import_csv_old()
    {
        header("Content-type: text/html; charset=utf-8");
        $data = $this->import_data;
        unset($this->import_data);
        
        $oQueue = &app::get('base')->model('queue');
        
        #标题
        $orderTitle = array_flip( $this->io_title('order') );
        $orderSchema = $this->oSchema['csv']['order'];
        
        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();
        
        #处理数据
        foreach( $data as $ordre_id => $aOrder )
        {
            if($count < $limit)
            {
                $count ++;
            }
            else
            {
                $count = 0;
                $page ++;
            }
    
            $orderSdfs[$page][]    = $aOrder;
        }
        
        #新增数据
        foreach($orderSdfs as $v)
        {
            $queueData = array(
                    'queue_title'=>'订单导入',
                    'start_time'=>time(),
                    'params'=>array(
                            'sdfdata'=>$v,
                            'app' => 'customs',
                            'mdl' => 'orders'
                    ),
                    'worker'=>'customs_order_import.run',
            );
            $oQueue->save($queueData);
        }
        app::get('base')->model('queue')->flush();
    }
    
    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }
    
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }
    
    /**
     +----------------------------------------------------------
     * csv导入新订单[不存在就新建订单 并且 新建越境订单]step 3
     +----------------------------------------------------------
     * @param   Array    $order_ids 订单ID数组
     * return   Array
     +----------------------------------------------------------
     */
    function finish_import_csv()
    {
        header("Content-type: text/html; charset=utf-8");
        $data = $this->import_data;
        unset($this->import_data);
        
        $orderTitle = array_flip( $this->io_title('order') );
        $objTitle = array_flip( $this->io_title('obj') );
        $orderSchema = $this->oSchema['csv']['order'];
        $objSchema =$this->oSchema['csv']['obj'];
        $oQueue = &app::get('base')->model('queue');
        
        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();
        foreach( $data as $ordre_id => $aOrder ){
            $orderSdf = array();
            $orderSdf = $this->ioObj->csv2sdf( $aOrder['order']['contents'][0] ,$orderTitle, $orderSchema  );
            
            $orderObjectItem = 0;
            foreach( $aOrder['obj']['contents'] as $k => $v ){
                $product_info = &app::get('ome')->model('products')->dump(array('bn'=>$v[$objTitle['*:商品货号']]));
                if (!$product_info){
                    foreach(kernel::servicelist('ome.product') as $name=>$object){
                        if(method_exists($object, 'getProductInfoByBn')){
                            $product_data = $object->getProductInfoByBn($v[$objTitle['*:商品货号']]);
                            if($product_data){
                                $orderSdf['order_objects'][$k]['bn']        = $v[$objTitle['*:商品货号']];
                                $orderSdf['order_objects'][$k]['name']      = $v[$objTitle['*:商品名称']];
                                $orderSdf['order_objects'][$k]['quantity']  = $v[$objTitle['*:购买数量']];
                                $orderSdf['order_objects'][$k]['price']     = $v[$objTitle['*:商品原价']];
                                $orderSdf['order_objects'][$k]['amount']    = $v[$objTitle['*:商品原价']]*$v[$objTitle['*:购买数量']];
                                $orderSdf['order_objects'][$k]['sale_price']  = $v[$objTitle['*:销售价']]*$v[$objTitle['*:购买数量']];
                                $orderSdf['order_objects'][$k]['obj_type']  = $product_data['product_type'];
                                $orderSdf['order_objects'][$k]['obj_alias'] = $product_data['product_desc'];
                                $orderSdf['order_objects'][$k]['goods_id']  = $product_data['goods_id'];
                                //$orderSdf['order_objects'][$k]['pmt_price'] = $v[$objTitle['*:商品优惠金额']]?$v[$objTitle['*:商品优惠金额']]:0;
                                $orderSdf['order_objects'][$k]['pmt_price'] = $orderSdf['order_objects'][$k]['amount'] - $orderSdf['order_objects'][$k]['sale_price'] ;
                                if ($product_data['items']){
                                    $orderObjectItem = $k;
                                    foreach ($product_data['items'] as $inc => $iv){
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['bn']          = $iv['bn'];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['name']        = $iv['name'];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['quantity']    = $iv['nums'] * $v[$objTitle['*:购买数量']];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['price']       = $iv['price']?$iv['price']:0;
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['amount']      = $iv['nums'] * ($iv['price']?$iv['price']:0);
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['item_type']    = $product_data['product_type'];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['product_id']  = $iv['product_id'];
                                    }
                                }else {
                                    $orderObjectItem = $k;
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['bn']          = $v[$objTitle['*:商品货号']];
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['name']        = $v[$objTitle['*:商品名称']];
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['quantity']    = $v[$objTitle['*:购买数量']];
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['price']       = $v[$objTitle['*:商品原价']];
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['amount']      = $v[$objTitle['*:商品原价']]*$v[$objTitle['*:购买数量']];
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['item_type']    = $product_data['product_type'];
                                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['product_id']  = $product_data['product_id'];
                                }
                                break;
                            }
                        }
                    }
                }else {
                    $orderSdf['order_objects'][$k]['bn']         = $v[$objTitle['*:商品货号']];
                    $orderSdf['order_objects'][$k]['name']       = $v[$objTitle['*:商品名称']];
                    $orderSdf['order_objects'][$k]['quantity']   = 1; //写死一个object一个item
                    $orderSdf['order_objects'][$k]['price']      = $v[$objTitle['*:商品原价']];
                    $orderSdf['order_objects'][$k]['amount']     = $v[$objTitle['*:商品原价']] * $v[$objTitle['*:购买数量']];
                    $orderSdf['order_objects'][$k]['obj_type']   = 'goods';   //写死一个object一个item，并且类型是goods
                    $orderSdf['order_objects'][$k]['obj_alias']  = '商品';    //写死一个object一个item，并且类型是商品
                    $orderSdf['order_objects'][$k]['goods_id']   = $product_info['goods_id'];
                    $orderSdf['order_objects'][$k]['sale_price'] = $orderSdf['order_objects'][$k]['amount'];
    
                    $orderObjectItem = $k;
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['bn'] = $v[$objTitle['*:商品货号']];
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['name'] = $v[$objTitle['*:商品名称']];
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['quantity'] = $v[$objTitle['*:购买数量']];
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['price'] = $v[$objTitle['*:商品原价']];
                    //$orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['pmt_price'] = $v[$objTitle['*:商品优惠金额']]?$v[$objTitle['*:商品优惠金额']]:0;
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['sale_price'] = $v[$objTitle['*:销售价']] * $v[$objTitle['*:购买数量']];
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['amount'] = $v[$objTitle['*:商品原价']]*$v[$objTitle['*:购买数量']];
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['obj_type'] = 'product';   //写死product
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['product_id'] = $product_info['product_id'];
                    $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['pmt_price'] = $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['amount']-$orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['sale_price'] ;
                }
    
            }
            //处理店铺信息
            $shop = &app::get('ome')->model('shop')->dump(array('shop_bn'=>$orderSdf['shop_id']));
            if(!$shop) continue;
            $is_code = strtolower($orderSdf['shipping']['is_cod']);
            #检测货到付款
            if( ($is_code == '是') || ($is_code == 'true') || ($is_code == 'TRUE') ||($is_code == 'yes') ||($is_code == 'YES')){
                $is_code = 'true';
            }else{
                $is_code = 'false';
            }
            $is_tax = strtolower($orderSdf['is_tax']);
            
            #是否开票
            if( ($is_tax == '是') || ($is_tax == 'true')||($is_tax == 'TRUE') ||($is_tax == 'yes') || ($is_tax == 'YES')){
                $is_tax = 'true';
            }else{
                $is_tax = 'false';
            }
            $aOrder['is_tax'] = $is_tax;
            $createway = strtolower($orderSdf['createway']);
            
            $pay_status    = $orderSdf['pay_status'];
            if($pay_status == '已支付')
            {
                $aOrder['pay_status']    = '1';
                $orderSdf['pay_status']  = '1';
            }
            else 
            {
                unset($aOrder['pay_status'], $orderSdf['pay_status']);
            }
            
            #是否跨境订单 ExBOY
            $is_declare    = strtolower($orderSdf['is_declare']);
            if(($is_declare == '是') || ($is_declare == 'true') || ($is_declare == 'yes'))
            {
                $is_declare = 'true';
            }
            else
            {
                $is_declare = 'false';
            }
            $aOrder['is_declare']    = $is_declare;
            $orderSdf['is_declare']  = $is_declare;
            
            $aOrder['payment_bn']    = $orderSdf['payment_bn'];//支付单号
            $orderSdf['payment_bn']  = $orderSdf['payment_bn'];

            $aOrder['card_no']       = $orderSdf['card_no'];//客户身份证号
            $orderSdf['card_no']     = $orderSdf['card_no'];
            
            #检测货到付款
            if( ($createway == '是') || ($createway == 'true')){
                $createway = 'matrix';
            }else{
                $createway = 'import';
            }
    
            $orderSdf['shop_id']            = $shop['shop_id'];
            $orderSdf['shop_type']          = $shop['shop_type'];
            $orderSdf['createtime']         = strtotime($orderSdf['createtime']);
            $orderSdf['paytime']            = strtotime($orderSdf['paytime']);
            $orderSdf['consignee']['area']  = $orderSdf['consignee']['area']['province']."/".$orderSdf['consignee']['area']['city']."/".$orderSdf['consignee']['area']['county'];
            $orderSdf['shipping']['is_cod'] = $is_code;#$orderSdf['shipping']['is_cod']?strtolower($orderSdf['shipping']['is_cod']):'false';
            $orderSdf['is_tax']             = $is_tax;
            $orderSdf['cost_tax']           = $orderSdf['cost_tax'] ? $orderSdf['cost_tax'] : '0';
            $orderSdf['discount']           = $orderSdf['discount'] ? $orderSdf['discount'] : '0';
            $orderSdf['score_g']            = $orderSdf['score_g'] ? $orderSdf['score_g'] : '0';
            $orderSdf['cost_item']          = $orderSdf['cost_item'] ? $orderSdf['cost_item'] : '0';
            $orderSdf['total_amount']       = $orderSdf['total_amount'] ? $orderSdf['total_amount'] : '0';
            $orderSdf['pmt_order']          = $orderSdf['pmt_order'] ? $orderSdf['pmt_order'] : '0';
            $orderSdf['pmt_goods']          = $orderSdf['pmt_goods'] ? $orderSdf['pmt_goods'] : '0';
            $tmp_order_source               = ome_order_func::get_order_source();
            $tmp_order_source               = array_flip($tmp_order_source);
            $orderSdf['order_source']       = $tmp_order_source[$orderSdf['order_source']]?$tmp_order_source[$orderSdf['order_source']]:'direct';
            $orderSdf['custom_mark']        = kernel::single('ome_func')->append_memo($orderSdf['custom_mark']);
            $orderSdf['mark_text']          = kernel::single('ome_func')->append_memo($orderSdf['mark_text']);
            $orderSdf['createway']          = $createway;
            $orderSdf['source']             = 'local';
            //增加会员判断逻辑
            $memberObj = &app::get('ome')->model('members');
            $tmp_member_name = trim($orderSdf['account']['uname']);
            $memberInfo = $memberObj->dump(array('uname'=>$tmp_member_name),'member_id');
            if($memberInfo){
                $orderSdf['member_id'] = $memberInfo['member_id'];
            }else{
                $members_data = array(
                        'account' => array(
                                'uname' => $tmp_member_name,
                        ),
                        'contact' => array(
                                'name' => $tmp_member_name,
                        ),
                );
                if($memberObj->save($members_data)){
                    $orderSdf['member_id'] = $members_data['member_id'];
                }
            }
    
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            
            $orderSdfs[$page][] = $orderSdf;
        }
        
        foreach($orderSdfs as $v){
            $queueData = array(
                    'queue_title'=>'订单导入',
                    'start_time'=>time(),
                    'params'=>array(
                            'sdfdata'=>$v,
                            'app' => 'customs',
                            'mdl' => 'orders'
                    ),
                    'worker'=>'customs_order_import.run',
            );
            $oQueue->save($queueData);
    
        }
        app::get('base')->model('queue')->flush();
    }
    
    /**
     +----------------------------------------------------------
     * create_order 订单创建step 4
     +----------------------------------------------------------
     * @param   Array    $sdf 订单数据
     * return   Array
     +----------------------------------------------------------
     */
    function create_order(&$sdf)
    {
        $oOrders    = &app::get('ome')->model('orders');
        
        //判断订单号是否重复
        if($oOrders->dump(array('order_bn'=>$sdf['order_bn'],'shop_id'=>$sdf['shop_id']))){
            return false;
        }
    
        //收货人/发货人地区转换
        $area = $sdf['consignee']['area'];
        kernel::single("ome_func")->region_validate($area);
        $sdf['consignee']['area'] = $area;
        $consigner_area = $sdf['consigner']['area'];
        kernel::single("ome_func")->region_validate($consigner_area);
        $sdf['consigner']['area'] = $consigner_area;
    
        $oProducts = &app::get('ome')->model('products');
    
        //如果有OME捆绑插件设定的捆绑商品，则自动拆分
        if($oPkg = kernel::service('omepkg_order_split')){
            if(method_exists($oPkg,'order_split')){
                $sdf = $oPkg->order_split($sdf);
            }
        }
    
        //去除货号空格
        foreach($sdf['order_objects'] as $key=>$object){
            $object['bn'] = trim($object['bn']);
            foreach($object['order_items'] as $k=>$item){
                $item['bn'] = trim($item['bn']);
                $object['order_items'][$k] = $item;
            }
            $sdf['order_objects'][$key] = $object;
        }
    
        foreach($sdf['order_objects'] as $key=>$object){
            foreach($object['order_items'] as $k=>$item){
                //货品属性
                $product_attr = array();
                $product_attr = $oOrders->_format_productattr($item['product_attr'], $item['product_id'],$item['original_str']);
                $sdf['order_objects'][$key]['order_items'][$k]['addon'] = $product_attr;
                //danny_freeze_stock_log
                $GLOBALS['frst_shop_id'] = $sdf['shop_id'];
                $GLOBALS['frst_shop_type'] = $sdf['shop_type'];
                $GLOBALS['frst_order_bn'] = $sdf['order_bn'];
                //修改预占库存
                $oProducts->chg_product_store_freeze($item['product_id'],(intval($item['quantity'])-intval($item['sendnum'])),"+");
            }
        }
    
        if(app::get('replacesku')->is_installed()){
            $sku_tran = new replacesku_order;
            $taotrans_sku = $sku_tran->order_sku_filter($sdf['order_objects']);
            if(count($taotrans_sku)>=1){
                $sdf['is_fail'] = 'true';
                $sdf['auto_status'] =1;
            }
    
        }
        //注册service来对订单结构数据进行扩充和修改
        if($order_sdf_service = kernel::service('ome.service.order.sdfdata')){
            if(method_exists($order_sdf_service,'modify_sdfdata')){
                $sdf = $order_sdf_service->modify_sdfdata($sdf);
            }
        }
    
        if(!$oOrders->save($sdf)) return false;
    
        $c2c_shop_list = ome_shop_type::shop_list();
    
        // 0元订单是否需要财审:不支持来自平台的
        if( !in_array($sdf['shop_type'], $c2c_shop_list) && (bccomp('0.000', $sdf['total_amount'],3) == 0) && $sdf['source'] != 'matrix'){
            kernel::single('ome_order_order')->order_pay_confirm($sdf['shop_id'],$sdf['order_id'],$sdf['total_amount']);
        }
    
        //增加订单创建日志
        $logObj = &app::get('ome')->model('operation_log');
        $logObj->write_log('order_create@ome',$sdf['order_id'],'订单创建成功');
    
        //创建订单后执行的操作
        if($oServiceOrder = kernel::servicelist('ome_create_order_after')){
            foreach($oServiceOrder as $object){
                if(method_exists($object,'create_order_after')){
                    $object->create_order_after($sdf);
                }
            }
        }
    
        //如果有KPI考核插件，会增加客服的考核
        if($oKpi = kernel::service('omekpi_servicer_incremental')){
            if(method_exists($oKpi,'getOrderIncremental')){
                $oKpi->getOrderIncremental($sdf);
            }
        }
    
        //订单创建api
        foreach(kernel::servicelist('service.order') as $object){
            if(method_exists($object, 'create_order')){
                $object->create_order($sdf);
            }
        }
        
        #新建_跨境订单 ExBOY
        $oCustoms        = $this->app->model('orders');
        $declareRow    = $oCustoms->dump(array('order_bn'=>$sdf['order_bn']), '*');
        if(empty($declareRow) && !empty($sdf['order_id']))
        {
            $order_id    = $sdf['order_id'];
            $op_id       = kernel::single('desktop_user')->get_id();//操作人
            
            #查询订单是否有效
            $field         = 'order_id, order_bn, member_id, payment, shop_id, shop_type, op_id, group_id';
            $filter        = array('order_id'=>$order_id, 'process_status'=>'unconfirmed', 'pay_status'=>'1', 'status'=>'active');
            $orderRow      = $oOrders->getList($field, $filter, 0, 1);
            $orderRow      = $orderRow[0];
            
            if(!empty($orderRow))
            {
                $oPayment        = &app::get('ome')->model('payments');
                $oMembers        = &app::get('ome')->model('members');
                $oOperation_log  = &app::get('ome')->model('operation_log');
                
                //支付单详情
                $field      = 'payment_id, payment_bn, currency, money, pay_type, payment, paymethod, t_end';
                $payinfo    = $oPayment->dump(array('order_id'=>$order_id, 'status'=>'succ'), $field);
                
                //会员详情
                $field          = 'member_id, uname, name, mobile, tel, email, area, addr';
                $member_info    = $oMembers->dump(array('member_id'=>$orderRow['member_id']), $field);
                
                //新增跨境数据
                $data    = array();
                $data['order_id']     = $orderRow['order_id'];
                $data['order_bn']     = $orderRow['order_bn'];
                $data['declare_bn']   = '';//申报单号
                $data['shop_type']    = $orderRow['shop_type'];//购物网站
                $data['logis_id']     = 1;//快递公司:默认顺丰速运
                
                #支付单信息
                $data['payment']      = '02';//支付方式:默认支付宝$val['payment']
                $data['payment_bn']   = ($sdf['payment_bn'] ? $sdf['payment_bn'] : $payinfo['payment_bn']);
                $data['currency']     = 'RMB';//货币:默认RMB
                $data['t_end']        = ($payinfo['t_end'] ? $payinfo['t_end'] : '');
                
                #会员信息
                $data['card_no']         = $sdf['card_no'];//会员身份证号
                $data['member_id']       = $orderRow['member_id'];
                $data['member_uname']    = $member_info['account']['uname'];
                $data['member_name']     = $member_info['contact']['name'];
                $data['member_mobile']   = $member_info['contact']['phone']['mobile'];
                $data['member_tel']      = $member_info['contact']['phone']['telephone'];
                $data['member_email']    = $member_info['contact']['email'];
                $data['member_area']     = $member_info['contact']['area'];
                $data['member_addr']     = $member_info['contact']['addr'];
                
                #other
                $data['status']     = 0;
                $data['remarks']    = '';
                $data['op_id']      = $op_id;
                $data['dateline']   = time();
                $data['lastdate']   = time();
                
                $insert_info    = $oCustoms->insert($data);
                
                if($insert_info)
                {
                    #更新订单状态
                    $update_order  = array();
                    $update_order['order_id']          = $order_id;
                    $update_order['process_status']    = 'is_declare';
                    $update_order['pause']             = 'true';//订单暂停
                    $update_order['abnormal']          = 'true';//异常
                    
                    $oOrders->save($update_order);
                    
                    #[设置]为订单异常
                    $abnormal_data  = array();
                    $abnormal_data['order_id']         = $order_id;
                    $abnormal_data['op_id']            = $orderRow['op_id'];
                    $abnormal_data['group_id']         = $orderRow['group_id'];
                    $abnormal_data['abnormal_type_id'] = $this->abnormal_type_id;//订单异常类型
                    $abnormal_data['is_done']          = 'false';
                    $abnormal_data['abnormal_memo']    = '订单进入跨境申报';
                    
                    $oOrders->set_abnormal($abnormal_data);
                    
                    #订单操作日志
                    $oOperation_log->write_log('order_edit@ome', $order_id, '订单进行跨境申报');
                    $oOperation_log->write_log('customs_create@ome', $order_id, '跨境申报订单创建成功');
                }
                
                //查检跨境订单是否有效
                $filter   = array('order_id' => array($order_id));
                $oCustoms->check_decalre($filter, true);
            }
        }
        
        return true;
    }
    
    /**
     +----------------------------------------------------------
     * 海关审核成功_订单自动审核发货
     +----------------------------------------------------------
     * @param   intval    $order_id
     * return   boolue
     +----------------------------------------------------------
     */
    function order_auto_delivery($order_id)
    {
        if(empty($order_id))
        {
            return false;
        }
        
        $oOperation_log     = &app::get('ome')->model('operation_log');
        $oOrders            = &app::get('ome')->model('orders');
        $itemsObj           = &app::get('ome')->model('order_items');
        $branchPro          = &app::get('ome')->model('branch_product');
        $oAbnormal          = &app::get('ome')->model('abnormal');
        $combineObj         = kernel::single('omeauto_auto_combine');
        
        $oCustoms           = $this->app->model('orders');
        $oSetting           = &app::get('customs')->model('setting');
        
        #订单详细信息
        $field        = 'order_id, order_bn, process_status, status, ship_name, ship_area, ship_addr, ship_zip, ship_tel, ship_email,
                        ship_time, ship_mobile, custom_mark, mark_text';
        $order_row    = $oOrders->dump(array('order_id' => $order_id), $field);
        
        if(!in_array($order_row['process_status'], array('unconfirmed', 'is_declare')) || $order_row['status'] != 'active')
        {
            $oOperation_log->write_log('customs_edit@ome', $order_id, '发货失败，订单不是活动订单、未确认状态');
            
            return false;
        }
        
        /*------------------------- step 1----------------------------- */
        $sql    = "SELECT cid, order_bn, logis_id, shop_sid, declare_bn, logis_id, logis_no FROM ".DB_PREFIX."customs_orders 
                   WHERE order_id=".$order_id;
        $customs_row    = kernel::database()->selectrow($sql);
        $logi_no        = ($customs_row['logis_no'] ? $customs_row['logis_no'] : $customs_row['declare_bn']);#没有物流单号使用申报单号
        
        #电子口岸
        $setting_row    = $oSetting->getList('sid, branch_ids', array('sid' => $customs_row['shop_sid']));
        $setting_row    = $setting_row[0];
        
        #物流公司
        $logis_list  = $oCustoms->get_typename('logi_list');//物流分类
        
        $logis_id    = $customs_row['logis_id'];
        $logis_type  = $logis_list[$logis_id]['type'];
        
        $sql        = "SELECT corp_id, name FROM ".DB_PREFIX."ome_dly_corp WHERE type='".$logis_type."' AND disabled='false'";
        $dlyCorp    = kernel::database()->selectrow($sql);
        $logiId     = $dlyCorp['corp_id'];
        
        if(empty($logiId))
        {
            $sql        = "SELECT corp_id, name FROM ".DB_PREFIX."ome_dly_corp WHERE disabled='false'";
            $dlyCorp    = kernel::database()->selectrow($sql);
            $logiId     = $dlyCorp['corp_id'];//随机读取一个物流公司
        }
        
        #发货仓库
        $branch_ids    = unserialize($setting_row['branch_ids']);
        $branch_id     = $branch_ids[0]['branch_id'];
        
        if(empty($logiId) || empty($branch_id))
        {
            $oOperation_log->write_log('customs_edit@ome', $order_id, '失败，未找到物流公司或者发货仓库');
        
            return false;
        }
        
        /*------------------------- step 2----------------------------- */
        #去除订单异常
        $oAbnormal->update(array('is_done'=>'true'), array('order_id'=>$order_id, 'abnormal_type_id'=>'998'));
        
        #更新订单状态
        $update_order  = array();
        $update_order['order_id']          = $order_id;
        $update_order['process_status']    = 'unconfirmed';//未确认
        $update_order['pause']             = 'false';
        $update_order['abnormal']          = 'false';
        
        $oOrders->save($update_order);
        
        #检查商品库存
        $item_list      = array();
        $field          = 'item_id, obj_id, product_id, bn, nums';
        $order_items    = $itemsObj->getList($field, array('order_id' => $order_id, 'delete' => 'false'), 0, -1);
        foreach ($order_items as $key => $val)
        {
            $product_id    = $val['product_id'];
        
            $item_list[$product_id]['item_id']       = $val['item_id'];
            $item_list[$product_id]['obj_id']        = $val['obj_id'];
            $item_list[$product_id]['product_id']    = $val['product_id'];
            $item_list[$product_id]['bn']            = $val['bn'];
            $item_list[$product_id]['nums']          = intval($item_list[$product_id]['nums']) + intval($val['nums']);
        }
        
        $product_ids    = array_keys($item_list);
        $field          = 'branch_id, product_id, store, store_freeze';
        $branchProList  = $branchPro->getList($field, array('branch_id' => $branch_id, 'product_id' => $product_ids), 0, -1);
        foreach ($branchProList as $key => $val)
        {
            $product_id    = $val['product_id'];
        
            $item_list[$product_id]['store']    = ($val['store'] - $val['store_freeze']);
        }
        
        $product_error    = array();
        foreach ($item_list as $key => $val)
        {
            if(empty($val['store']) || $val['store'] < 1)
            {
                $product_error[]    = $val['bn'];//库存不足货号
            }
        }
        
        if(!empty($product_error))
        {
            $oOperation_log->write_log('customs_edit@ome', $order_id, '货号库存不足：'.implode('、', $product_error));
            
            return false;
        }
        
        /*------------------------- step 3-审核&&发货单---------------------------- */
        $orders[0]    = $order_id;
        $consignee    = array (
                            'name' => $order_row['consignee']['name'],
                            'area' => $order_row['consignee']['area'],
                            'addr' => $order_row['consignee']['addr'],
                            'telephone' => $order_row['consignee']['telephone'],
                            'mobile' => $order_row['consignee']['mobile'],
                            'r_time' => $order_row['consignee']['r_time'],
                            'zip' => $order_row['consignee']['zip'],
                            'email' => $order_row['consignee']['email'],
                    );
        $consignee['memo']         = '';#快递单备注
        $consignee['branch_id']    = $branch_id;
        
        $result    = $combineObj->mkDelivery($orders, $consignee, $logiId);//生成发货单
        
        if($result === false)
        {
            $oOperation_log->write_log('customs_edit@ome', $order_id, '系统自动生成发货单失败');
            return false;
        }
        
        /*------------------------- step 4-自动发货---------------------------- */
        #订单对应发货单
        $sql    = "SELECT d.delivery_id, d.delivery_bn, d.status, d.logi_id, d.logi_name, d.logi_no, d.branch_id, d.net_weight 
                   FROM ".DB_PREFIX."ome_delivery_order AS dord 
                            LEFT JOIN ".DB_PREFIX."ome_delivery AS d ON (dord.delivery_id=d.delivery_id) 
                            WHERE dord.order_id=".$order_id." AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                            AND d.status='ready'";
        $delivery_row    = kernel::database()->selectrow($sql);
        
        if(empty($delivery_row))
        {
            $oOperation_log->write_log('customs_edit@ome', $order_id, '发货失败，未找到对应发货单号');
            
            return false;
        }
        
        $weight    = ($delivery_row['net_weight'] ? $delivery_row['net_weight'] : 0);#默认使用_商品重量
        //更新ome发货单底单物流信息和打印状态
        $sql    = "UPDATE ".DB_PREFIX."ome_delivery SET logi_no='".$logi_no."', stock_status='true', deliv_status='true', 
                   expre_status='true', verify='true', weight='". $weight ."', print_status=1 WHERE delivery_id=".$delivery_row['delivery_id'];
        kernel::database()->exec($sql);
        
        //变更wms发货单打印状态物流信息
        $dlyObj = &app::get('wms')->model('delivery');
        $wmsdly = $dlyObj->dump(array('outer_delivery_bn' => $delivery_row['delivery_bn']),'delivery_id');
        if($wmsdly)
        {
            $dly    = array();
            $dly['logi_id']      = $delivery_row['logi_id'];
            $dly['logi_name']    = $delivery_row['logi_name'];
            $dly['weight']        = $weight;
            $dly['print_status']  = 7;#已打印
            
            $dly_result    = $dlyObj->update($dly, array('delivery_id' => $wmsdly['delivery_id']));
            
            $sql    = "UPDATE ".DB_PREFIX."wms_delivery_bill SET logi_no='".$logi_no."' WHERE delivery_id=".$wmsdly['delivery_id'];
            kernel::database()->exec($sql);
        }

        //更新订单打印状态
        $update_order  = array();
        $update_order['order_id']        = $order_id;
        $update_order['print_finish']    = 'true';
        $update_order['print_status']    = 7;
        $oOrders->save($update_order);
        
        //触发wms自动发货等后续流程
        $result    = $this->order_auto_consign_delivery($order_id, $logi_no, $weight);

        return $result;
    }
    
    /**
     +----------------------------------------------------------
     * WMS自动发货
     +----------------------------------------------------------
     * @param   intval    $order_id
     * @param   string    $logi_no
     * @param   number    $weight
     * return   boolue
     +----------------------------------------------------------
     */
    function order_auto_consign_delivery($order_id, $logi_no, $weight)
    {
        if(empty($order_id) || empty($logi_no))
        {
            return false;
        }
        
        $dlyObj = &app::get('wms')->model('delivery');
        $dlyBillObj = &app::get('wms')->model('delivery_bill');
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $opObj = &app::get('ome')->model('operation_log');
        $wmsCommonLib = kernel::single('wms_common');
        $dlyProcessLib = kernel::single('wms_delivery_process');
        
        //如果没有发货单信息，则根据物流单号识别是主单还是次单,并获取相关信息
        
        $delivery_id = $deliveryBillLib->getDeliveryIdByPrimaryLogi($logi_no);
        
        if(empty($delivery_id))
        {
            $opObj->write_log('customs_edit@ome', $order_id, '发货失败，未找到对应发货单号');
            return false;
        }
        
        $dly = $dlyObj->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
        if(empty($dly))
        {
            $opObj->write_log('customs_edit@ome', $order_id, '发货失败，未找到发货单信息');
            return false;
        }
        
        $logi_number = $dly['logi_number'];
        $delivery_logi_number =$dly['delivery_logi_number'];
        
        //获取物流费用
        $area = $dly['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$dly['logi_id'],$weight);
        
        //发货
        $data = array(
                'status'=>'1',
                'weight'=>$weight,
                'delivery_cost_actual'=>$delivery_cost_actual,
                'delivery_time'=>time(),
                'type' => 1,
        );
        $filter = array('logi_no'=>$logi_no);
        $dlyBillObj->update($data,$filter);
        
        $data = array('delivery_logi_number'=>$delivery_logi_number+1,'weight'=>$dly['weight'],'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual);
        $filter = array('delivery_id'=>$dly['delivery_id']);
        $dlyObj->update($data,$filter);
        
        if ($dlyProcessLib->consignDelivery($dly['delivery_id']))
        {
            $opObj->write_log('customs_edit@ome', $order_id, '发货完成');
            return true;
        }else {
            $opObj->write_log('customs_edit@ome', $order_id, '发货失败,发货单:'.$dly['delivery_bn']);
            return false;
        }
    }
}
