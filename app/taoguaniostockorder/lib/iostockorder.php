<?php
class taoguaniostockorder_iostockorder{
    var $iostockorder_bn;

    /**
     * 保存出入库单，并根据类型生成出入库明细
     * @param unknown_type $data
     * @param unknown_type $msg
     */
    function save_iostockorder($data,&$msg){
         //检查出入库类型id是否合法
         $type = $data['type_id'];
         $iostockorder_createtime = time();
         $iostockorder_bn = $this->get_iostockorder_bn($type);
         $isoObj = &app::get('taoguaniostockorder')->model('iso');
         //$iso_id = $this->gen_id();

         $product_cost = 0;
         $iso_items = array();
         foreach($data['products'] as $product_id=>$product){
            $iso_items[] = array(
                //'iso_id'=>$iso_id,
                'iso_bn'=>$iostockorder_bn,
                'product_id'=>$product_id,
                'product_name'=>$product['name'],
                'bn'=>$product['bn'],
                'unit'=>$product['unit'],
                'nums'=>$product['nums'],
                'price'=>$product['price'],
            );
            $product_cost+= $product['nums'] * $product['price'];
        }

        $confirm = isset($data['confirm']) && $data['confirm'] == 'Y' ? 'Y' : 'N';

        $operator = kernel::single('desktop_user')->get_name();
        $operator = $operator ? $operator : 'system';
        $iostockorder_data = array(
            // 'iso_id'=>$iso_id,
            'confirm'=>$confirm,
            'name' => $data['iostockorder_name'],
            'iso_bn' => $iostockorder_bn,
            'type_id' => $data['type_id'],
            'branch_id' => $data['branch'],
            'supplier_id' => $data['supplier_id'],
            'supplier_name' => $data['supplier'],
            'iso_price' => $data['iso_price'],
            'cost_tax' => 0,
            'oper' => $data['operator'],
            'create_time' => $iostockorder_createtime,
            'operator' => $operator ,
            'settle_method' => '',
            'settle_status' => '0',
            'settle_operator' => '',
            'settle_time' => '',
            'settle_num' => '',
            'settlement_bn' => '',
            'settlement_money' => '0',
            'product_cost'=>$product_cost,
            'memo' => $data['memo'],
            'emergency' => $data['emergency'] ? 'true' : 'false',
            'original_bn'=> $data['original_bn'] ? $data['original_bn'] : '',
            'original_id'=> $data['original_id'] ? $data['original_id'] : 0,
            'iso_items'=>$iso_items,
             'is_cod'=>'false',
        );

        $this->iostockorder_bn = $iostockorder_bn;

        if($this->set($iostockorder_data)){
            if($confirm == 'Y' ){
                if($this->confirm_iostockorder($iostockorder_data['iso_id'],$type,$msg,$data['extend'])){
                    $iso_id = $iostockorder_data['iso_id'];
                    $isoObj->update(array('check_status'=>'2','iso_status'=>'3'),array('iso_id'=>$iso_id));
                    return $iostockorder_data['iso_id'];
                }else{
                    return false;
                }
            }else{
                return $iostockorder_data['iso_id'];
            }
         }else{
            return false;
         }
    }

    function getIoStockOrderBn(){
        return $this->iostockorder_bn;
    }

    function set(&$data,&$msg=array()){
        $itemsObj = &app::get('taoguaniostockorder')->model('iso_items');
        if(is_array($data) && count($data)>0){
            if(!$this->check_required($data,$msg)){
                return false;
            }
            $this->divide_data($data,$main,$item);
            if($this->_mainvalue($main,$msg) && $this->_itemvalue($item,$msg)){
                if($this->_add_iso($main)){
                    $data['iso_id'] = $main['iso_id'];
                    foreach($item as $item_key=>$value){
                        $value['iso_id'] = $data['iso_id'];
                        $item[$item_key] = $value;
                    }
                    $item_sql = ome_func::get_insert_sql($itemsObj,$item);
                    kernel::database()->exec($item_sql) ;
                }
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function _add_iso(&$data){
        $objIoStockOrder = &app::get('taoguaniostockorder')->model('iso');
        
        return $objIoStockOrder->save($data);
    }

    /**
     *
     * 生成各类型出入库单的相关单据
     * @param unknown_type $iso_id
     * @param unknown_type $io
     * @param unknown_type $msg
     */
    function confirm_iostockorder($iso_id,$type_id,&$msg,$extend=null){
        switch($type_id){
            case '1'://采购入库
                $io = 1;
                if( isset($extend['po_type']) && $extend['po_type'] == 'credit' ){
                    return $this->do_iostock_credit_sheet($iso_id,$io,$msg);
                }else{
                    return $this->do_iostock($iso_id,$io,$msg);
                }
                break;
            case '10'://采购退货
                $io = 0;
                return $this->do_iostock_refunds($iso_id,$io,$msg);
                break;
            case '3'://销售出库
                return 'O';
                break;
            case '30': //退货入库
                return 'M';
                break;
            case '31'://换货入库
                return 'C';
                break;
            case '4'://调拨入库
                $io = 1;
                return $this->do_iostock($iso_id,$io,$msg);
                break;
            case '40'://调拨出库
                $io = 0;
                if($this->do_iostock($iso_id,$io,$msg)){
                    $appropriation_instance = kernel::service('taoguanallocate.appropriation');
                    if ( method_exists($appropriation_instance, 'do_in_iostockorder') ){//生成入库单
                        $db = kernel::database();
                        $sql = 'SELECT original_id FROM `sdb_taoguaniostockorder_iso` WHERE `iso_id`=\''.$iso_id.'\'';
                        $iso_detail = $db->selectrow($sql);
                        return $appropriation_instance->do_in_iostockorder($iso_detail['original_id'],$msg);
                    }else{
                        return true;
                    }
                }else{
                    return false;
                }
                break;
            case '5'://残损出库
                return 'B';
                break;
            case '50':;//残损入库
                return 'D';
                break;
            case '6'://盘亏
                return 'L';
                break;
            case '60':
                return 'P';//盘盈
                break;
            case '7'://直接出库
                $io = 0;
                // 直接出库不生成销售单 2011.11.08
                //return $this->do_iostocksale($iso_id,$io,$msg);
                return $this->do_iostock_refunds($iso_id,$io,$msg);
                break;
            case '70'://直接入库
                $io = 1;
                return $this->do_iostock_credit_sheet($iso_id,$io,$msg);
                break;
            case '100'://赠品出库
                $io = 0;
                return $this->do_iostock($iso_id,$io,$msg);
                break;
            case '200'://赠品入库
                $io = 1;
                return $this->do_iostock($iso_id,$io,$msg);
                break;
            case '300'://样品出库
                $io = 0;
                return $this->do_iostock($iso_id,$io,$msg);
                break;
            case '400'://样品入库
                $io = 1;
                return $this->do_iostock($iso_id,$io,$msg);
                break;
        }

        return true;
    }

    /**
     *
     * 生成出入库单的销售单以及出入库明细
     * @param unknown_type $iso_id
     * @param unknown_type $io
     * @param unknown_type $msg
     */
    function do_iostocksale($iso_id,$io,&$msg){
        //出入库及销售单记录
        $iostock_sales_set_result = false;
        $iostock_sales_data = array();
        $iostock_data = $this->get_iostock_data($iso_id,$type);
        $sales_data = $this->get_sales_data($iso_id);
        $iostock_sales_data['iostock'] = $iostock_data;
        $iostock_sales_data['sales'] = $sales_data;
        $iostock_instance = kernel::service('ome.iostock');
        if ( $iostock_sales_service = kernel::service('ome.service.iostock_sales') ){
            if ( method_exists($iostock_sales_service, 'set') ){
               // $io = '0';//出入库类型：0出库1入库
                //eval('$type='.get_class($iostock_instance).'::DIRECT_LIBRARAY;');
                $iostock_sales_set_result = $iostock_sales_service->set($iostock_sales_data, $io, $msg,$type);
            }
        }else{
            $msg = '出入库明细接口不存在';
        }

        return $iostock_sales_set_result;
    }

	function do_iostock_credit_sheet($iso_id,$io,&$msg){
        //出入库及赊购单记录
        $allow_commit = false;
        kernel::database()->exec('begin');
        $iostock_instance = kernel::service('ome.iostock');

        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $this->get_iostock_data($iso_id,$type);
            //('$type='.get_class($iostock_instance).'::DIRECT_STORAGE;');
            $iostock_bn = $iostock_instance->get_iostock_bn($type);

            if ( $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io) ){
                $credit_sheet_instance = kernel::service('purchase.credit_sheet');
                if ( method_exists($credit_sheet_instance, 'save_credit_sheet') ){
                    if($this->isCredit($iso_id,$credit_sheet_instance)){
                        $credit_sheet =  $this->get_credit_sheet_data($iso_id,$credit_sheet_instance->gen_id());
                        if($credit_sheet_instance->save_credit_sheet($credit_sheet,$credit_sheet_msg)){
                            $allow_commit = true;
                        }
                    }else{
                        $allow_commit = true;
                    }
                }
            }
        }
        
        if ($allow_commit == true){
            kernel::database()->commit();
            return true;
        }else{
            kernel::database()->rollBack();
            $msg['iostock_msg'] = $iostock_msg;
            $msg['credit_sheet_msg'] = $credit_sheet_msg;
            return false;
        }
    }
    
    /**
     * 
     * 是否要生成赊购单
     * @param unknown_type $iso_id
     * @param unknown_type $credit_sheet_instance
     */
    function isCredit($iso_id,$credit_sheet_instance){
        $iso = $this->getIso($iso_id,'original_id');
        // 当original_id为0时，直接返回true，对应直接入库生成赊购单
        if($iso['original_id']==0) {
            return true;
        }
        return $credit_sheet_instance->isCredit($iso['original_id']);
    }

    function do_iostock_refunds($iso_id,$io,&$msg){
        //出入库及赊购单记录
        $allow_commit = false;
        kernel::database()->exec('begin');
        $iostock_instance = kernel::service('ome.iostock');
        $refunds_instance = kernel::service('purchase.refunds');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $this->get_iostock_data($iso_id,$type);
            //('$type='.get_class($iostock_instance).'::DIRECT_STORAGE;');
            $iostock_bn = $iostock_instance->get_iostock_bn($type);
            if ( $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io) ){
                if ( method_exists($refunds_instance, 'save_refunds') ){
                    $refunds_data =  $this->get_refunds_data($iso_id);
                    if($refunds_data['po_type'] == 'cash'){
                        if($refunds_instance->save_refunds($refunds_data,$refunds_msg)){
                            $allow_commit = true;
                        }
                    }else{
                        $allow_commit = true;
                    }
                }
            }

        }
        if ($allow_commit == true){
            kernel::database()->commit();
            return true;
        }else{
            kernel::database()->rollBack();
            $msg['iostock_msg'] = $iostock_msg;
            $msg['refunds_msg'] = $refunds_msg;
            return false;
        }
    }

    function do_iostock($iso_id,$io,&$msg){
        //生成出入库明细
        $allow_commit = false;
        kernel::database()->exec('begin');
        $iostock_instance = kernel::service('ome.iostock');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $this->get_iostock_data($iso_id,$type);
            //eval('$type='.get_class($iostock_instance).'::DIRECT_STORAGE;');
            $iostock_bn = $iostock_instance->get_iostock_bn($type);
            if ( $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io) ){
                $allow_commit = true;
            }
        }
        if ($allow_commit == true){
            kernel::database()->commit();
            return true;
        }else{
            kernel::database()->rollBack();
            $msg = $iostock_msg;
            return false;
        }
    }

    function gen_id(){
        list($msec, $sec) = explode(" ",microtime());
        $id = $sec.str_pad(strval($msec*1000000),6,0,STR_PAD_LEFT);
        $conObj = &app::get('ome')->model('concurrent');
        if($conObj->is_pass($id,'iostockorder')){
            return $id;
        } else {
            return $this->gen_id();
        }
    }

    /**
     *检验必填字段是否全部填写
     *
     **/
    function check_required($data,&$msg){return true;
        $msg = array();
        $arrFrom = array('branch_id','bn','iostockorder_price','nums','operator');
        if($data){
            foreach($data as $key=>$val){
                $arrExit = array_keys($val);
                if( count(array_diff($arrFrom,$arrExit)) ){
                   $msg[] =$key . '- -所有必填字段';
                }
            }
            if(count($msg)){
                return false;
            }
        }
        return true;
    }

    /**
    *检验字段类型是否符合要求
    *
    **/
    function check_value($data,&$msg){
        $msg = array();
        $rea = '字段类型不符';
        foreach($data as $keys=>$val){
            foreach($val as $key=>$value){
                switch($key){
                    case 'iostockorder_bn':
                        if(!empty($value)){
                            if(is_string($value) && strlen($value)<=32){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'original_bn':
                        if(!empty($value)){
                            if(is_string($value) && strlen($value)<=32){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'original_id':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=10 && $value>0){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'original_item_id':
                       if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=10 && $value>0){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'supplier_id':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=10 && $value>0){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'bn':
                        if(is_string($value) && strlen($value)<=32){
                            continue;
                        } else{
                            $msg[] = $keys .'-'. $key.'-'.$rea;
                        }
                        break;
                    case 'nums':
                        if(is_numeric($value) && strlen($value)<=8 && $value>0){
                            continue;
                        } else{
                            $msg[] = $keys .'-'. $key.'-'.$rea;
                        }
                        break;
                    case 'cost_tax':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=20){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'oper':
                        if(!empty($value)){
                            if(is_string($value) && strlen($value)<=30){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'operator':
                        if(is_string($value) && strlen($value)<=30){
                            continue;
                        } else{
                            $msg[] = $keys .'-'. $key.'-'.$rea;
                        }
                        break;
                    case 'settle_method':
                        if(!empty($value)){
                            if(is_string($value) && strlen($value)<=32){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'settle_status':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=2){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'settle_operator':
                        if(!empty($value)){
                            if(is_string($value) && strlen($value)<=30){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'settle_time':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=10 && $value>0){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'settle_num':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=8 && $value>0){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'settlement_bn':
                        if(!empty($value)){
                            if(is_string($value) && strlen($value)<=32){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'settlement_money':
                        if(!empty($value)){
                            if(is_numeric($value) && strlen($value)<=20){
                                continue;
                            } else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                }
            }
        }
        if(!count($msg)){
            return true;
        } else {
            return false;
        }
    }

    /**
    * 生成出入库单号
    * $type 类型 如：iostock-1
    **/
    function get_iostockorder_bn($type,$num = 0){
        $iostock_instance = kernel::service('ome.iostock');
        $kt = $iostock_instance->iostock_rules($type);
        $iostockorder_type = 'iostockorder-'.$type;

        if($num >= 1){
            $num++;
        }else{
            $sql = "SELECT id FROM sdb_ome_concurrent WHERE `type`='$iostockorder_type' and `current_time`>'".strtotime(date('Y-m-d'))."' and `current_time`<=".time()." order by id desc limit 0,1";
            $arr = kernel::database()->select($sql);
            if($id = $arr[0]['id']){
                $num = substr($id,-6);
                $num = intval($num)+1;
            }else{
                $num = 1;
            }
        }

        $po_num = str_pad($num,6,'0',STR_PAD_LEFT);
        $iostockorder_bn = $kt.date(Ymd).$po_num;

        $conObj = &app::get('ome')->model('concurrent');
        if($conObj->is_pass($iostockorder_bn,$iostockorder_type)){
            return $iostockorder_bn;
        } else {
            if($num > 999999){
                return false;
            }else{
                return $this->get_iostockorder_bn($type,$num);
            }
        }
    }

    /**
     * 组织出库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($iso_id,&$type){
        $objIsoItems = &app::get('taoguaniostockorder')->model('iso_items');

        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguaniostockorder_iso` WHERE `iso_id`=\''.$iso_id.'\'';
        $iso_detail = $db->selectrow($sql);
        $iso_items_detail = $objIsoItems->getList('*', array('iso_id'=>$iso_id), 0, -1);
        if ($iso_items_detail){
            foreach ($iso_items_detail as $k=>$v){
                $iostock_data[$v['iso_items_id']] = array(
                    'branch_id' => $iso_detail['branch_id'],
                    'original_bn' => $iso_detail['iso_bn'],
                    'original_id' => $iso_id,
                    'original_item_id' => $v['iso_items_id'],
                    'supplier_id' => $iso_detail['supplier_id'],
                    'supplier_name' => $iso_detail['supplier_name'],
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['nums'],
                    'cost_tax' => $iso_detail['cost_tax'],
                    'oper' => $iso_detail['oper'],
                    'create_time' => $iso_detail['create_time'],
                    'operator' => $iso_detail['operator'],
                    'settle_method' => $iso_detail['settle_method'],
                    'settle_status' => $iso_detail['settle_status'],
                    'settle_operator' => $iso_detail['settle_operator'],
                    'settle_time' => $iso_detail['settle_time'],
                    'settle_num' => $iso_detail['settle_num'],
                    'settlement_bn' => $iso_detail['settlement_bn'],
                    'settlement_money' => $iso_detail['settlement_money'],
                    //'memo' => $iso_detail['memo'],
                );
            }
        }
        $type = $iso_detail['type_id'];

        return $iostock_data;
    }

    /**
     * 组织销售单数据
     * @access public
     * @param String $iso_id 出入库单ID
     * @return sdf 销售单数据
     */
    public function get_sales_data($iso_id){
        $db = kernel::database();
        $sales_items_data = array();
        $sales_data = array();
        $goods_amount = $delivery_cost = $additional_costs = $pkg_remain_money = $discount = $deposit = 0;
        $operator = $order_text = $member_id = $shop_id = $pay_status = '';
        $order_ids = $obj_ids = array();

        $iostockObj = &app::get('ome')->model('iostock');
        $objIsoItems = &app::get('taoguaniostockorder')->model('iso_items');

        $iso_items_detail = $objIsoItems->getList('*', array('iso_id'=>$iso_id), 0, -1, '`bn`');
        $sql = 'SELECT * FROM `sdb_taoguaniostockorder_iso` WHERE `iso_id`=\''.$iso_id.'\'';
        $iso_detail = $db->selectrow($sql);
        $branch_id = $iso_detail['branch_id'];

        if ($iso_items_detail){
            foreach ($iso_items_detail as $k=>$v){
                $iso_items = array();
                //销售单明细
                $sales_items_data[] = array(
                    'item_detail_id' => $v['iso_items_id'],
                    'bn' => $v['bn'],
                    'price' => $v['price'],
                    'nums' => $v['nums'],
                    'branch_id' => $branch_id,
                    //'cost' => $v['cost'],
                );
            }
        }

        //销售单数据
        $operator = kernel::single('desktop_user')->get_name();
        $operator = $operator ? $operator : 'system';
        $sales_data = array(
            'sale_amount' => $goods_amount,
            'delivery_cost' => $delivery_cost,
            'additional_costs' => $additional_costs,
            'deposit' => $deposit,
            'discount' => $discount,
            'memo' => $iso_detail['memo'],
            'member_id' => $member_id,
            'branch_id' => $branch_id,
            'pay_status' => $pay_status,
            'shop_id' => $shop_id,
            'operator' => $operator,
            'sale_time' => time(),
            'sales_items' => $sales_items_data,
        );

        return $sales_data;
    }

    public function get_credit_sheet_data($iso_id,$gen_id){
          $iso_detail = $this->getIso($iso_id);
          $payable = $iso_detail['product_cost'] + $iso_detail['iso_price'];
          $credit_data = array(
               'cs_bn'=>$gen_id,
               'po_bn'=>$iso_detail['original_bn'],
               'add_time'=>time(),
               'supplier_id'=>$iso_detail['supplier_id'] ? $iso_detail['supplier_id'] : 0,
               'operator'=>kernel::single('desktop_user')->get_name(),
               'op_id'=>kernel::single('desktop_user')->get_id(),
               'iso_bn'=>$iso_detail['iso_bn'],
               'payable'=>$payable,
               'eo_id'=>$iso_id,
               'delivery_cost'=>$iso_detail['iso_price'],
               'product_cost'=>$iso_detail['product_cost']
           );

           return $credit_data;
    }


    public function get_refunds_data($iso_id){
        $iso_detail = $this->getIso($iso_id);

        if(intval($iso_detail['original_id']) == 0) {
            // 处理直接出库
            $iso_detail = $this->getIso($iso_id);
            $payable = $iso_detail['product_cost'] + $iso_detail['iso_price'];
            $credit_data = array(
               'cs_bn'=>$gen_id,
               'add_time'=>time(),
               'supplier_id'=>$iso_detail['supplier_id'] ? $iso_detail['supplier_id'] : 0,
               'operator'=>kernel::single('desktop_user')->get_name(),
               'op_id'=>kernel::single('desktop_user')->get_id(),
               'refund'=>$payable,
               'eo_id'=>$iso_id,
               'delivery_cost'=>$iso_detail['iso_price'],
               'product_cost'=>$iso_detail['product_cost'],
               'type'=>'iso',
               'rp_id' => $iso_id,
               'po_type'=>'cash'
            );
            return $credit_data;
        }

        // 根据original_id查询sdb_purchase_returned_purchase
        $data = kernel::single('purchase_mdl_returned_purchase')->getList('amount,product_cost,delivery_cost,delivery_cost,po_type',array('rp_id'=>$iso_detail['original_id']));
        $total = $data[0]['amount'];
        //$total = $data[0]['product_cost'];

        $refund = array();
        $refund['add_time'] = time();
        $refund['refund'] = $total;
        $refund['product_cost'] = $data[0]['product_cost'];
        $refund['delivery_cost'] = $data[0]['delivery_cost'];
        $refund['po_type'] = $data[0]['po_type'];
        $refund['type'] = 'eo';
        $refund['rp_id'] = $iso_detail['original_id'];
        $refund['supplier_id'] = $iso_detail['supplier_id'];
        $refund['op_id'] = kernel::single('desktop_user')->get_id();
        $refund['operator'] = kernel::single('desktop_user')->get_name();
       return $refund;
    }

    function get_create_iso_type($io=1,$isReturnId=false){
        $iostock_instance = kernel::service('ome.iostock');
        if(!$iostock_instance)return array();

        $iso_types = array();
        foreach($iostock_instance->get_iostock_types() as $id=>$type){
            if(isset($type['is_new']) && $type['io'] == $io){
                $iso_types[$id] = $type['info'];
            }
        }

        if($isReturnId){
            return array_keys($iso_types);
        }else{
            return $iso_types;
        }
    }

    function get_iso_type($io=1,$isReturnId=false){
        $iostock_instance = kernel::service('ome.iostock');
        if(!$iostock_instance)return array();

        $iso_types = array();
        foreach($iostock_instance->get_iostock_types() as $id=>$type){
            if($type['io'] == $io){
                $iso_types[$id] = $type['info'];
            }
        }

        if($isReturnId){
            return array_keys($iso_types);
        }else{
            return $iso_types;
        }

    }

	//拆分出主表与子表数据
    function divide_data($data,&$mainArr,&$itemArr){
        if($data){
            foreach($data as $key=>$value){
                if($key == 'iso_items'){
                    $itemArr = $data[$key];
                }else{
                    $mainArr[$key] = $data[$key];
                }
            }
            return true;
        }
        return false;
    }

	//检查明细表值是否符合
    function _itemvalue($data,&$msg){return true;
        $rea = '字段类型不符(子表)';
        if(is_array($data)){
            foreach($data as $key=>$val){
                foreach($val as $field=>$content){
                    if($content != ''){
                        switch ($field){
                                //bigint(20) unsigned
                            case 'sale_id':
                            case 'iostock_id':
                                if(is_numeric($content) && strlen($content)<=20 && $content>0){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                //int(10) unsigned
                            case 'item_id':
                                if(is_numeric($content) && strlen($content)<=10 && $content>0){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                //varchar(32)
                            case 'bn':
                            case 'sell_code':
                                if(is_string($content) && strlen($content)<=32){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                // mediumint(8) unsigned
                            case 'nums':
                            case 'branch_id':
                                if(is_numeric($content) && strlen($content)<=8 && $content>0){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                //decimal(20,3)
                            case 'price':
                            case 'cost':
                            case 'cost_tax':
                                if(is_numeric($content) && strlen($content)<=20){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }


//检查主表字段值是否符合
    function _mainvalue($data,&$msg){return true;
        $rea = '字段类型不符(主表)';
        foreach($data as $key=>$content){
            if($content != ''){
                switch ($key){
                        //bigint(20) unsigned
                    case 'sale_id':
                        if(is_numeric($content) && strlen($content)<=20 && $content>0){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //varchar(32)
                    case 'sale_bn':
                    case 'iostock_bn':
                    case 'shop_id':
                        if(is_string($content) && strlen($content)<=32){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //int(10) unsigned
                    case 'sale_time':
                    case 'member_id':
                        if (!empty($content)){
                            if(is_numeric($content) && strlen($content)<=10 && $content>0){
                                continue;
                            } else{
                                $msg[] = $key .'-'.$rea;
                            }
                        }
                        break;
                        //decimal(20,3)
                    case 'sale_amount':
                    case 'cost':
                    case 'delivery_cost':
                    case 'additional_costs':
                    case 'deposit':
                    case 'discount':
                        if(is_numeric($content) && strlen($content)<=20){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //varchar(30)
                    case 'operator':
                        if(is_string($content) && strlen($content)<=30){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //mediumint(8) unsigned
                    case 'branch_id':
                        if(is_numeric($content) && strlen($content)<=8 && $content>0){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //enum('0','1')
                    case 'pay_status':
                        if(is_numeric($content) && strlen($content)<=2){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                }
            }
        }
        return true;
    }

    function check_iostockorder($data,&$msg){
        $iso_id = $data['iso_id'];
        $type = $data['type_id'];
        $oper = $data['operator'];//经手人
        $appropriation_id = $data['original_id'];//调拨单ID  
        if($this->confirm_iostockorder($iso_id,$type,$msg)){
            $objIoStockOrder = &app::get('taoguaniostockorder')->model('iso');
            $data = array(
                        'iso_id'   => $iso_id,
                        'confirm'  => 'Y',
                        'oper'     => $oper,//经手人
                        'operator' => kernel::single('desktop_user')->get_name(),//操作员
            );
            #生成调拨入库确认时间
           if($type == '4' || $type == '40'){
               $data['complete_time'] = time();
           }
           #调拨出库时，增加在途库存
           if($type == '40'){
               $this->addArriveStore($appropriation_id);
           }
           #调拨入库时，扣减在途库存
           if($type == '4'){
               $this->reduceArriveStore($appropriation_id);
           }
            return $objIoStockOrder->save($data);
        }else{
            return false;
        }
    }

    function getIsoList($original_id,$type_id){
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguaniostockorder_iso` WHERE `original_id`="'.$original_id.'" AND `type_id`="'.$type_id.'"';
        return $db->select($sql);
    }

    function getIsoItems($iso_id){
        $objIsoItems = &app::get('taoguaniostockorder')->model('iso_items');
        $iso_items_detail = $objIsoItems->getList('*', array('iso_id'=>$iso_id), 0, -1);
        return $iso_items_detail;
    }

    function getIso($iso_id,$field='*'){
        $db = kernel::database();
        $sql = 'SELECT '.$field.' FROM `sdb_taoguaniostockorder_iso` WHERE `iso_id`=\''.$iso_id.'\'';
        return $db->selectrow($sql);
    }
    #调拨出库时，增加在途库存
    function addArriveStore($appropriation_id = null){
       $sql='
            SELECT
                items.from_branch_id,items.to_branch_id, items.product_id, items.num
            from  sdb_taoguanallocate_appropriation appropriation
            left join sdb_taoguanallocate_appropriation_items items on  appropriation.appropriation_id=items.appropriation_id
            WHERE appropriation.appropriation_id=\''.$appropriation_id.'\' and appropriation.create_time>=UNIX_TIMESTAMP(\'2013-08-06 00:00:00\')';
       $branch_product_info  = kernel::database()->select($sql);
       if(empty($branch_product_info)){
           return false;
       }
       $last_modified = time();
       foreach($branch_product_info as $key=>$v){
           $to_branch_id = $v['to_branch_id'];
           #检查仓库与货品是否存在相关数据
           $_sql = 'select count(*) count from sdb_ome_branch_product where  product_id='.$v['product_id'].' and branch_id='.$to_branch_id; 
           $count_info  = kernel::database()->selectRow($_sql);
           if(!$count_info['count']){
               $_unit_cost = 0;#先默认是0
               $_inventory_cost = 0;
               #如果不存在库存数据，则插入一条（包含单位成本、库存成本）
               $sql = 'insert into sdb_ome_branch_product(branch_id,product_id,arrive_store,unit_cost,inventory_cost,last_modified)values('.$to_branch_id.','.$v['product_id'].','.$v['num'].','.$_unit_cost.','.$_inventory_cost.','.$last_modified.')';
           }else{
               #如果存在库存记录，则更新在途库存
               $sql = 'update sdb_ome_branch_product set arrive_store=arrive_store+'.$v['num'].',last_modified='.$last_modified.' where product_id='.$v['product_id'].' and branch_id='.$to_branch_id;
           }
           kernel::database()->exec($sql);
       }
    }
    #调拨入库时，扣减在途库存
    function reduceArriveStore($appropriation_id = null){
        $last_modified = time();
        $sql='
            SELECT 
               items.to_branch_id, items.product_id, items.num
            from  sdb_taoguanallocate_appropriation appropriation
            left join sdb_taoguanallocate_appropriation_items items on  appropriation.appropriation_id=items.appropriation_id 
            WHERE appropriation.appropriation_id=\''.$appropriation_id.'\' and appropriation.create_time>=UNIX_TIMESTAMP(\'2013-08-06 00:00:00\')';
        $product_info  = kernel::database()->select($sql);
        if(empty($product_info)){
            return false;
        }
        foreach($product_info as $v){
            #入库确认时，扣减在途库存
            $num = $v['num'];
            $sql = "update sdb_ome_branch_product set arrive_store=IF((arrive_store-$num)>0,arrive_store-$num,0),last_modified=".$last_modified.' where product_id='.$v['product_id'].' and branch_id='.$v['to_branch_id'];
            kernel::database()->exec($sql);
        }
    }
}
