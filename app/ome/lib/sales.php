<?php
class ome_sales{
    function set(&$data,&$msg=array()){
        
        $itemsObj = &app::get('ome')->model('sales_items');
        if(is_array($data) && count($data)>0){
            if(!$this->check_required($data,$msg)){
                return false;
            }
            $this->divide_data($data,$main,$item);

            if($this->_mainvalue($main,$msg) && $this->_itemvalue($item,$msg)){
                $sale_id = $this->gen_id(); //获取销售编号
                $main['sale_id'] = $sale_id;
                foreach($item as $item_key=>$value){
                    $value['branch_id'] = $data['branch_id'];
                    $value['sale_id'] = $sale_id;
                    $item[$item_key] = $value;
                }

                $item_sql = ome_func::get_insert_sql($itemsObj,$item);
                if ( kernel::database()->exec($item_sql) ){
                    $this->_add_Sales($main);
                }
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

//添加销售主表记录
    function _add_Sales($data){
        $salesObj = &app::get('ome')->model('sales');
        return $salesObj->save($data);
    }

//添加销售明细表记录
    function _add_Sales_Items($data){
        $itemsObj = &app::get('ome')->model('sales_items');
        return $itemsObj->save($data);
    }

   function gen_id(){

       list($msec, $sec) = explode(" ",microtime());

        $id = $sec.strval($msec*1000000);
          $conObj = &app::get('ome')->model('concurrent');
        if($conObj->is_pass($id,'sales')){
            return $id;
        } else {
            return $this->gen_id();
        }
    }

//检查所有必填字段
    function check_required($data,&$msg){
        $msg = array();
        $arrMain = array('iostock_bn','sale_amount','delivery_cost','operator','branch_id'); //主表必须字段
        $arrItems = array('bn','nums'); //明细表必须字段
        if(is_array($data) && count($data) > 0){
            $arrExist = array_keys($data);

            if(count(array_diff($arrMain,$arrExist))){
                $msg[] = '主表中必填字段不全';
                return false;
            }
            foreach($arrMain as $key){
                $tmp_value = trim($data[$key]);
                if(is_null($tmp_value) || $tmp_value === ''){
                    $msg[]=$key.'主表中必填字段值不能为空';
                }
            }
            if(in_array('sales_items',$arrExist) && is_array($data['sales_items'])){

                foreach($data['sales_items'] as $keys=>$contents){
                    $arrI = array_keys($contents);
                    if(count(array_diff($arrItems,$arrI))){
                        $msg[] ='明细表-' . $keys .'-' . '必填字段不全';
                    }else{
                        foreach($contents as $key=>$value){
                            if(in_array($key,$arrItems)){
                               empty($value) ? $msg[] = '明细表' . $keys . '-' .$key . '必填字段值不能为空' : '';
                            }
                        }
                    }
                }

                if(count($msg)){
                    return false;
                }
            }
            return true;
        }
        return false;
    }

//拆分出主表与子表数据
    function divide_data($data,&$mainArr,&$itemArr){
        if($data){
            foreach($data as $key=>$value){
                if($key == 'sales_items'){
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
    function _itemvalue($data,&$msg){
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
    function _mainvalue($data,&$msg){
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
/**
* 生成销售单单号
**/
    function get_salse_bn($num = 0){
        $type = 'SALSE';

        if($num >= 1){
            $num++;
        }else{
            $sql = "SELECT id FROM sdb_ome_concurrent WHERE `type`='$type' and `current_time`>'".strtotime(date('Y-m-d'))."' and `current_time`<=".time()." order by id desc limit 0,1";
            $arr = kernel::database()->select($sql);
            if($id = $arr[0]['id']){
                $num = substr($id,-6);
                $num = intval($num)+1;
            }else{
                $num = 1;
            }
        }

        $po_num = str_pad($num,6,'0',STR_PAD_LEFT);
        $salse_bn = 'S'.date(Ymd).$po_num;

        $conObj = &app::get('ome')->model('concurrent');
        if($conObj->is_pass($salse_bn,$type)){
            return $salse_bn;
        } else {
            if($num > 999999){
                return false;
            }else{
                return $this->get_salse_bn($num);
            }
        }
    }
}

?>