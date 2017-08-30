<?php
abstract class siso_receipt_iostock_abstract {

    protected $_iostock_types = array(
    	'1'=>array('code'=>'I', 'info'=>'采购入库', 'io'=>1, 'class'=>'purchase'),
    	'10'=>array('code'=>'H', 'info'=>'采购退货', 'io'=>0, 'class'=>'purchaseReturn'),
 		'3'=>array('code'=>'O', 'info'=>'销售出库', 'io'=>0, 'class'=>'sold'),
 		'30'=>array('code'=>'M', 'info'=>'退货入库', 'io'=>1, 'class'=>'aftersaleReturn'),
 		'31'=>array('code'=>'C', 'info'=>'换货入库', 'io'=>1, 'class'=>'aftersaleChange'),
		'32'=>array('code'=>'U', 'info'=>'拒收退货入库', 'io'=>1, 'class'=>'deliveryRefuse'),
 		'4'=>array('code'=>'T', 'info'=>'调拨入库', 'io'=>1, 'class'=>'allocationIn'),
 		'40'=>array('code'=>'R', 'info'=>'调拨出库', 'io'=>0, 'class'=>'allocationOut'),
 		'5'=>array('code'=>'B', 'info'=>'残损出库', 'io'=>0, 'class'=>'amagedOut'),
 		'50'=>array('code'=>'D', 'info'=>'残损入库', 'io'=>1, 'class'=>'amagedIn'),
 		'6'=>array('code'=>'L', 'info'=>'盘亏', 'io'=>0, 'class'=>'shortage'),
 		'60'=>array('code'=>'P', 'info'=>'盘盈', 'io'=>1, 'class'=>'overage'),
		'7'=>array('code'=>'A', 'info'=>'直接出库', 'is_new'=>true, 'io'=>0, 'class'=>'directOut'),
 		'70'=>array('code'=>'E', 'info'=>'直接入库', 'is_new'=>true, 'io'=>1, 'class'=>'directIn'),
 		'100'=>array('code'=>'F', 'info'=>'赠品出库', 'is_new'=>true, 'io'=>0, 'class'=>'giftOut'),
 		'200'=>array('code'=>'G', 'info'=>'赠品入库', 'is_new'=>true, 'io'=>1, 'class'=>'giftIn'),
 		'300'=>array('code'=>'J', 'info'=>'样品出库', 'is_new'=>true, 'io'=>0, 'class'=>'sampleOut'),
 		'400'=>array('code'=>'K', 'info'=>'样品入库', 'is_new'=>true, 'io'=>1, 'class'=>'sampleIn'),
        '500'=>array('code'=>'Q','info'=>'期初','io'=>1),
       '9'=>array('code'=>'W','info'=>'转储出库','io'=>0),
       '600'=>array('code'=>'V','info'=>'转储入库','io'=>1),
        '8'=>array('code'=>'N','info'=>'调账入库','io'=>1),
        '80'=>array('code'=>'S','info'=>'调账出库','io'=>0),
    );

    /**
     *
     * 出入库明细创建生成方法
     * @param array $params
     * @param string $msg
     */
    public function create($params, &$data, &$msg=null){
        //检查参数信息
        
        if(!$this->checkParams($params, $msg)){
            
            return false;
        }
       
        //获取出入库类型相关数据
        $this->_io_data = $this->get_io_data($params);
        
        //校验数据内容
        if(!$this->checkData($this->_io_data,$msg)){
            return false;
        }
        
        //格式化出入库信息内容
        $this->_io_data = $this->convertSdf($this->_io_data);
        
        //直接出入库数据的保存
        if($this->save($this->_io_data)){
            //其他事务处理
            $iostock_cost = kernel::service("iostock_cost");
            if(is_object($iostock_cost) && method_exists($iostock_cost,"iostock_set")){
                $iostock_cost->iostock_set($this->_io_type,$this->_io_data);
            }
            $data = $this->_io_data;
            return true;
        }else{
            $msg = 'save fail';
            return false;
        }
    }

    /**
     *
     * 检查参数
     * @param array $params
     * @param string $msg
     */
    protected function checkParams($params, &$msg){
        return true;
    }

    public function get_io_data($params){

        return '';
    }

    /**
     *
     * 格式化数据
     */
    private function convertSdf($data){

        $productObj = &app::get('ome')->model('products');
        $branchObj = &app::get('ome')->model('branch_product');

        $iostock_bn = $this->get_iostock_bn($this->_typeId);
        foreach($data as $key=>$v){
            $data[$key]['iostock_id'] = $v['iostock_id'] = $this->gen_id();
            $v['create_time'] = time();
            $v['iostock_bn'] = $iostock_bn;
            $v['type_id'] = $this->_typeId;

            $data[$key] = $v;
            $result = $productObj->getlist('product_id',array('bn'=>$v['bn']),0,1);
            $result = $result[0];
            $v['product_id'] = $result['product_id'];
            $branch_goods  = $branchObj->getlist('*',array('branch_id'=>$v['branch_id'],'product_id'=>$v['product_id']),0,1);
            $branch_goods  = $branch_goods[0];
            if(!$branch_goods){
                $branch_arr['branch_id'] = $v['branch_id'];
                $branch_arr['product_id'] = $v['product_id'];
                $branch_arr['store'] = 0;
                $branch_arr['last_modified'] = time();
                $branchObj->save($branch_arr);
            }

            if($this->_io_type){ //入库
                $this->updateBranchProduct($v['nums'],$v['product_id'],$v['branch_id'],'+');
                $this->updateProduct($v['nums'],$v['product_id'],'+');
            } else { //出库
                $this->updateBranchProduct($v['nums'],$v['product_id'],$v['branch_id']);
                $this->updateProduct($v['nums'],$v['product_id']);
            }

            $data[$key]['balance_nums'] = $this->get_branch_store($v['branch_id'],$v['product_id']);
            $data[$key]['balance_nums'] = $data[$key]['balance_nums'] ? $data[$key]['balance_nums'] : 0;
        }

        return $data;
    }

    /**
     *
     * 检查数据内容
     * @param array $data
     * @param string $msg
     * @return boolean
     */
    protected function checkData($data, &$msg){
        if(!$this->check_required($data,$msg)){
            
            return false;
        }

        if(!$this->check_value($data,$msg)){
            return false;
        }
        return true;
    }

    /**
     *
     * 检验必填字段是否全部填写
     * @param array $data
     * @param string $msg
     * @return boolean
     */
    private function check_required($data,&$msg){
        $msg = array();
        $arrFrom = array('branch_id','bn','iostock_price','nums','operator');
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
     *
     * 检验字段类型是否符合要求
     * @param array $data
     * @param string $msg
     * @return boolean
     */
    private function check_value($data,&$msg){
        $msg = array();
        $rea = '字段类型不符';
        $type_id = $this->_typeId;

        foreach($data as $keys=>$val){
            foreach($val as $key=>$value){
                switch($key){
                    case 'iostock_bn':
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
                        if(is_string($value) && mb_strlen($value,'utf-8')<=32){
                            continue;
                        } else{
                            $msg[] = $keys .'-'. $key.':'.$value.'('.mb_strlen($value,'utf-8').')-'.$rea;
                        }
                        break;
                    case 'nums':
                        if($type_id!='60' && $type_id!='6'){
                            if (is_numeric($value)){
                                
                                if(strlen($value)<=8 && $value>=0){
                                    continue;
                                }else{
                                    $msg[] = $keys .'-'. $key.'-'.$rea;
                                }
                                
                            }else{
                                $msg[] = $keys .'-'. $key.'-'.$rea;
                            }
                        }
                        break;
                    case 'balance_nums':
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
     *
     * 出入库明细保存
     */
    function save($data){
        $ioObj = &app::get('ome')->model('iostock');
        $sql = ome_func::get_insert_sql($ioObj,$data);
        if ( kernel::database()->exec($sql) ){
            return true;
        }else{
            return false;
        }
    }

    function gen_id(){
        list($msec, $sec) = explode(" ",microtime());
        $id = $sec.strval($msec*1000000);
        $conObj = &app::get('ome')->model('concurrent');
        if($conObj->is_pass($id,'iostock')){
            return $id;
        } else {
            return $this->gen_id();
        }
    }

    /**
     * 初始化库存数量为NULL的货品
     */
    public function initNullStore($table,$product_id,$branch_id){
        if($product_id && $table) {
            if($branch_id) {
                $sql = "UPDATE $table SET store=0 WHERE branch_id='" . $branch_id . "' AND product_id='" . $product_id ."' AND ISNULL(store) LIMIT 1";
            }else{
                $sql = "UPDATE $table SET store=0 WHERE product_id='" . $product_id ."' AND ISNULL(store) LIMIT 1";
            }
            return kernel::database()->exec($sql);
        }else{
            return false;
        }
    }

    function updateBranchProduct($num, $product_id, $branch_id,$operator='-'){
        $this -> initNullStore('sdb_ome_branch_product',$product_id,$branch_id);
        $branchObj = &app::get('ome')->model('branch_product');
        if($operator == "-"){
            $sql = "UPDATE sdb_ome_branch_product SET store=IF(store<".$num.",0,store-$num),last_modified=" . time() ." WHERE branch_id='" . $branch_id . "' AND product_id='" . $product_id ."'";
        } else {
            $sql = "UPDATE sdb_ome_branch_product SET store=store+" .$num. ",last_modified=" . time() ." WHERE branch_id='" . $branch_id . "' AND product_id='" . $product_id ."'";
        }
        return kernel::database()->exec($sql);//branch_product表
    }

    function updateProduct($num, $product_id,$operator='-'){
        $this -> initNullStore('sdb_ome_products',$product_id,false);
        $productObj = &app::get('ome')->model('products');
        if($operator == '-'){
             $sql = "UPDATE sdb_ome_products SET store=IF(store<".$num.",0,store-$num),last_modified=" . time().",real_store_lastmodify=" .time(). ",max_store_lastmodify=" .time(). " WHERE product_id='" . $product_id . "'";
        } else {
             $sql = "UPDATE sdb_ome_products SET store=store+" . $num . ",last_modified=" . time().",real_store_lastmodify=" .time(). ",max_store_lastmodify=" .time(). " WHERE product_id='" . $product_id . "'";
        }
        return kernel::database()->exec($sql);
    }

 	function get_branch_store($branch_id,$product_id){
        $branch_product = kernel::database()->selectRow("SELECT store FROM sdb_ome_branch_product WHERE product_id=".intval($product_id) ." AND branch_id=".intval($branch_id));
        return $branch_product['store'];
    }



    /**
    * 生成出入库单号
    * $type 类型 如：iostock-1
    **/
    function get_iostock_bn($type,$num = 0){
        $kt = $this->iostock_rules($type);
        $iostock_type = 'iostock-'.$type;

        if($num >= 1){
            $num++;
        }else{
            $sql = "SELECT id FROM sdb_ome_concurrent WHERE `type`='$iostock_type' and `current_time`>'".strtotime(date('Y-m-d'))."' and `current_time`<=".time()." order by id desc limit 0,1";
            $arr = kernel::database()->select($sql);
            if($id = $arr[0]['id']){
                $num = substr($id,-6);
                $num = intval($num)+1;
            }else{
                $num = 1;
            }
        }
        $po_num = str_pad($num,6,'0',STR_PAD_LEFT);
        $iostock_bn = $kt.date(Ymd).$po_num;

        $conObj = &app::get('ome')->model('concurrent');
        if($conObj->is_pass($iostock_bn,$iostock_type)){
            return $iostock_bn;
        } else {
            if($num > 999999){
                return false;
            }else{
                return $this->get_iostock_bn($type,$num);
            }
        }
    }

    /**
     * 出入库类型标识
     * $rules 出入库类型编号 如：30
     */
    function iostock_rules($rules){
    	return $this->_iostock_types[$rules]['code'];
    }

 	function get_iostock_types(){
 		return $this->_iostock_types;
    }

    function getIoByType($type){
    	if(isset($this->_iostock_types[$type])){
    		return $this->_iostock_types[$type]['io'];
    	}else{
    		return 1;
    	}
    }
}