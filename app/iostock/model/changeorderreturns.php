<?php
class iostock_mdl_changeorderreturns extends iostock_mdl_iostock {

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'main':
                $this->oSchema[$ioType][$filter] =  array(
                                                        '*:仓库编号'     => 'branch_bn',
                                                        '*:销售出库单号' => 'sale_bn',
                                                        '*:售后申请人'   => 'oper',
                                                        '*:物流费'       => 'delivery_cost',
                                                        '*:附加费'       => 'additional_costs',
                                                        '*:订单折扣金额' => 'discount',
                                                        '*:预付款'       => 'deposit',
                                                        '*:会员名'       => 'member_uname',
                                                        '*:店铺编号'     => 'shop_bn',
                                                        '*:备注'         => 'memo',
                                                      );
                break;
            case 'item':
                $this->oSchema[$ioType][$filter] =  array(
                                                         '*:货号' => 'bn',
                                                         '*:销售单价' => 'price',
                                                         '*:退货数量' => 'nums',
                                   //                      '*:销售代码' => 'sell_code',
                                                         );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

    //具体检验明细条目的格式
    function _check_items($data){
         return true;
    }

//判断是否为本操作所需的csv文件
    function check_csv($title){
        $arrFrom = array_flip(array_filter(array_flip($title)));
      //  $this->io_title('main');
      //  $this->io_title('item');
        $arrFieldsAll = array_merge($this->oSchema['csv']['main'],$this->oSchema['csv']['item']);
        $arrResult = array_diff_key($arrFrom,$arrFieldsAll);
        return empty($arrResult) ?  true : false;
    }


    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        if (empty($row)){
            return false;
        }
        $mark = false;
        $fileData = $this->import_data;
        if( !$fileData )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            if(!$this->check_csv($titleRs)){
                $msg['error'] = "导入的csv文件字段与本操作所需不符，请使用正确的csv文件。";
            }
            return $titleRs;
        }else{
            //去除空行
            if(!count(array_filter($row))){
                 return false;
            }
            if( $row[0]){
               if(array_key_exists('*:销售出库单号',$title)){ //主表记录
                   $this->io_title('main');
                    isset($this->mainItem) ? $this->mainItem++ : $this->mainItem = 0;
                    isset($this->delivery) ? '' : $this->delivery = $row[1];
                    if($this->mainItem){
                        $msg['error'] = '主数据不唯一，一次只能有一条主数据';
                        return false;
                    }
                    if(!app::get('ome')->model('sales')->dump(array('sale_bn'=>$row[1]))){
                            $msg['error'] = "不存在的销售出库单号" . $row[1] ."请重新维护导入数据";
                            return false;
                    }
                    if(!app::get('ome')->model('branch')->dump(array('branch_bn'=>$row[0]))){
                            $msg['error'] = $row[0] . "仓库不存在，请重新维护导入数据";
                            return false;
                    }
                    if(strlen($row[1]) > 32){
                            $msg['error'] = $row[0] . "销售出库单号格式有误，数据过长，请重新维护导入数据";
                            return false;
                    }

                    if(!app::get('ome')->model('members')->dump(array('uname'=>$row[7]))){
                            $msg['error'] = $row[7] . '此会员名不存在';
                            return false;
                    }

                    $this->io_title('main');
                    foreach(array_values($this->oSchema['csv']['main']) as $key=>$field){
                        $fileData['main'][$this->mainItem][$field] = $row[$key];
                    }
                } elseif(array_key_exists('*:货号',$title)) { //详细记录
                    if(isset($this->detail_Items)){
                        $this->detail_Items++;
                        if($this->detail_Items > 100){
                            unset($this->import_data);
                            $msg['error'] = "一次换货过多，请减少到100条明细情况以下！";
                            return false;
                        }
                    } else {
                        $this->detail_Items = 1;
                    }

                    if($this->_check_items($row)){ //如果明细条目格式符合
                        $bn = app::get('ome')->model('products')->dump(array('bn'=>$row[0]));
                        $sale_id = app::get('ome')->model('sales')->dump(array('sale_bn'=>$this->delivery),'sale_id');
                        $pro = app::get('ome')->model('sales_items')->dump(array('sale_id'=>$sale_id['sale_id'],'bn'=>$row[0]),'bn');
                        $nums = app::get('ome')->model('sales_items')->dump(array('sale_id'=>$sale_id['sale_id'],'bn'=>$row[0]),'nums');
                        //校验明细项目数据是否存在
                        if(empty($bn)){ //检验退货商品是否存在
                                $msg['error'] = ($this->detail_Items+3) . "行货号不存在";
                                return false;
                        }
                        if(empty($pro)){ //检验退货商品是否存在于销售 出库单中
                                $msg['error'] = "该销售出库单中无货号为" . $row[0]. "的商品";
                                return false;
                        }
                        if(!is_numeric($row[2])){
                            $msg['error'] = '退货数量必须为数字';
                            return false;
                        }elseif(intval($row[2])!=$row[2]){
                            $msg['error'] = "商品退货数量必须为整数";
                            return false;
                        }elseif($nums['nums'] < $row[2]){ //检验退货数量是否大于原有数量
                                $msg['error'] = $row[0] . "商品退货数量不能大于发货数量，请重新维护数据";
                                return false;
                        }elseif($row[2] < 0){ //检验退货数量是否为负数
                                $msg['error'] = $row[0] . "商品退货数量不能为负数，请重新维护数据";
                                return false;
                        }
                        if(!is_numeric($row[1])){
                            $msg['error'] = '销售单价必须为数字';
                            return false;
                        }elseif($row[1] < 0 ){ //检验单价数据值
                                $msg['error'] = $row[0] . "商品单价不能为负数，请重新维护数据";
                                return false;
                        }

                        $this->io_title('item');
                        foreach(array_values($this->oSchema['csv']['item']) as $key=>$field){
                            $fileData['item'][($this->detail_Items-1)][$field] = $row[$key];
                        }
                    }
                }
                $this->import_data = $fileData;
            }else{
                $msg['error'] = "仓库编号和货号不能为空，请重新维护数据";
                return false;
            }

          }
          return null;
    }


     function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){
           return null;
    }

     function finish_import_csv(){

        $data = $this->import_data;
        //事务操作的数据的准备
        $iostockData = array();
        $salesData   = array();
        $salesItemsData = array();
        $operator = kernel::single('desktop_user')->get_name();//当前操作用户名
        $member = app::get('ome')->model('members')->dump(array('uname'=>$data['main'][0]['member_uname']),'member_id');
        $member_id = $member['member_id'];
        $shop = app::get('ome')->model('shop')->dump(array('shop_bn'=>$data['main'][0]['shop_bn']),'shop_id');
        $shop_id = $shop['shop_id'];
        $branch = app::get('ome')->model('branch')->dump(array('branch_bn'=>$data['main'][0]['branch_bn']),'branch_id');
        $branch_id = $branch['branch_id'];
        $iostockObj = kernel::service('ome.iostock');
        $type = ome_iostock::RE_STORAGE;
        $iostock_bn =  $iostockObj->get_iostock_bn($type); //当前操作出入库编号
        //开始事务
        kernel::single('base_controller')->begin();
        foreach($data as $key=>$val){
            foreach($val as $item=>$value){
                //主条目中的数据分配
                $iostockData[$item]['branch_id']                                         = $branch_id;
                $iostockData[$item]['original_bn']                                       = $data['main'][0]['sale_bn'];
                $iostockData[$item]['oper']                                              = $data['main'][0]['oper'];
                $salesData['delivery_cost']                                              = -$data['main'][0]['delivery_cost'];
                $salesData['additional_costs']                                           = -$data['main'][0]['additional_costs'];
                $salesData['discount']                                                   = -$data['main'][0]['discount'];
                $salesData['deposit']                                                    = -$data['main'][0]['deposit'];
                $salesData['member_id']                                                  = $member_id;
                $salesData['sale_time']                                                  = time();
                $salesData['shop_id']                                                    = $shop_id;
                $iostockData[$item]['memo'] = $salesData['memo']                         = $data['main'][0]['memo'];

                //明细条目中的数据分配
                $salesItemsData[$item]['bn']     =  $iostockData[$item]['bn']            = $data['item'][$item]['bn'];
                $salesItemsData[$item]['price']  =  $iostockData[$item]['iostock_price'] = $data['item'][$item]['price'];
                $salesItemsData[$item]['nums']                                           = -$data['item'][$item]['nums'];
                $iostockData[$item]['nums']                                              = $data['item'][$item]['nums'];
      //          $salesItemsData[$item]['sell_code']                                      = $data['item'][$item]['sell_code'];

         //补全事务操作影响的表中必须的数据项
                //出入库表补全
                $iostockData[$item]['operator'] = $operator; //操作人员
                $iostockData[$item]['type_id']  = $type;   //退货入库的类型ID
                //销售主表补全
                $salesData['iostock_bn']  = $iostock_bn;
                $salesData['branch_id']   = $branch_id;
                $salesData['operator']    = $operator;
                //销售子表信息补全
                $salesItemsData[$item]['branch_id']  = $branch_id;
            }
        }

       //调用出入库底层插入数据
        if(!$iostockObj->set($iostock_bn,$iostockData,$type,$msg)){
                if($msg == ''){
                    return null;
                }else{
                    $msg_arr = implode('\n',$msg);
                }
            kernel::single('base_controller')->end(false,app::get('base')->_('出入库事务操作失败'));
                header("content-type:text/html; charset=utf-8");
                 echo "<script>top.MessageBox.error(\"上传失败\");alert(\"".$msg_arr."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
        }
        //销售子表的iostock_id的获取
        foreach($iostockData as $key=>$value){
            $salesItemsData[$key]['iostock_id'] = $iostockData[$key]['iostock_id'];
        }
        //销售主表的金额
        foreach($salesItemsData as $key=>$value){
            $salesData['sale_amount'] += $salesItemsData[$key]['price']*$salesItemsData[$key]['nums'];
        }

        $salesObj = kernel::service('ome.sales');
        $salesData['sales_items'] = $salesItemsData;
        $salesData['sale_bn']     = $salesObj->get_salse_bn();
        if(!$salesObj->set($salesData,$msg)){
                if($msg == ''){
                    return null;
                }else{
                    $msg_arr = implode('\n',$msg);
                }
            kernel::single('base_controller')->end(false,app::get('base')->_('销售表事务操作失败'));
                header("content-type:text/html; charset=utf-8");
                 echo "<script>top.MessageBox.error(\"上传失败\");alert(\"".$msg_arr."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
        }
        kernel::single('base_controller')->end(true,app::get('base')->_('成功'));
        if(empty($msg)){
            $_SESSION['bn'] = $iostock_bn;
        }
        return null;
     }


}