<?php
class iostock_mdl_inventory extends iostock_mdl_iostock{

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
                                                        '*:盘点单号'     => 'original_bn',
                                                        '*:经手人'       => 'oper',
                                                        '*:备注'         => 'memo',
                                                      );

                break;
            case 'item':
                $this->oSchema[$ioType][$filter] =  array(
                                                         '*:货号'     => 'bn',
                                                         '*:盘点数量' => 'nums',
                                                         '*:商品单价' => 'price',
                                                         );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }


//判断是否为本操作所需的csv文件
    function check_csv($title){
        $arrFrom = array_flip(array_filter(array_flip($title)));
        $this->io_title('main');
        $this->io_title('item');
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
            if( $row[0] or $row[0]== 0){
               if(array_key_exists('*:盘点单号',$title)){ //主表记录
                   $this->io_title('main');
                    isset($this->mainItem) ? $this->mainItem++ : $this->mainItem = 0;
                    isset($this->delivery) ? '' : $this->delivery = $row[1];
                    if($this->mainItem){
                        $msg['error'] = '主数据不唯一，一次只能有一条主数据';
                        return false;
                    }elseif(!app::get('ome')->model('branch')->dump(array('branch_bn'=>$row[0]))){
                            $msg['error'] = $row[0] . "仓库不存在，请重新维护导入数据";
                            return false;
                    }elseif(strlen($row[1]) > 32){
                            $msg['error'] = $row[0] . "盘点单号格式有误，数据过长，请重新维护导入数据";
                            return false;
                    }

                    $this->io_title('main');
                    foreach(array_values($this->oSchema['csv']['main']) as $key=>$field){
                        $fileData['main'][$this->mainItem][$field] = $row[$key];
                    }
                } elseif(array_key_exists('*:货号',$title)) { //详细记录
                    if(isset($this->detail_Items)){
                        $this->detail_Items++;
                    } else {
                        $this->detail_Items = 1;
                    }

                    if(!isset($this->mainItem)){
                        $msg['error'] = '主数据不能为空，请重新维护数据';
                        return false;
                    }

                    //校验明细项目数据是否存在
                    if(!app::get('ome')->model('products')->dump(array('bn'=>$row[0]))){ //检验盘点商品是否存在
                            $msg['error'] = ($this->detail_Items+3) . "行货号不存在";
                            return false;
                    }

                    if(!is_numeric($row[1])){
                        $msg['error'] = '盘点数量必须为数字';
                        return false;
                    }elseif($row[1] < 0){ //检验盘点数量是否为负数
                            $msg['error'] = $row[0] . "商品盘点数量不能为负数，请重新维护数据";
                            return false;
                    }elseif(intval($row[1])!=$row[1]){
                        $msg['error'] = "商品盘点数量必须为整数";
                        return false;
                    }

                    if(empty($row[2])){
                        $msg['error'] = "商品价格必须存在，且大于零";
                        return false;
                    }elseif(!is_numeric($row[2])){
                        $msg['error'] = '商品单价必须为数字';
                        return false;
                    }elseif($row[2] < 0 ){ //检验单价数据值
                            $msg['error'] = $row[0] . "商品盘点单价不能为负数，请重新维护数据";
                            return false;
                    }


                    $this->io_title('item');
                    foreach(array_values($this->oSchema['csv']['item']) as $key=>$field){
                        $fileData['item'][($this->detail_Items-1)][$field] = $row[$key];
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
        $iostockLess = array();
        $iostockMore = array();
        $operator = kernel::single('desktop_user')->get_name();//当前操作用户名
        $branch = app::get('ome')->model('branch')->dump(array('branch_bn'=>$data['main'][0]['branch_bn']),'branch_id');
        $branch_id = $branch['branch_id'];
        $productsObj = app::get('ome')->model('products');
        $iostockObj = kernel::service('ome.iostock');
        $type_less = ome_iostock::INVENTORY;//盘亏
        $type_more = ome_iostock::OVERAGE;//盘盈
        //事务开启
        kernel::single('base_controller')->begin();
        foreach($data as $key=>$val){
            foreach($val as $item=>$value){
                $iostockData[$item]['branch_id']        = $branch_id;
                $iostockData[$item]['original_bn']      = $data['main'][0]['original_bn'];
                $iostockData[$item]['oper']             = $data['main'][0]['oper'];
                $iostockData[$item]['operator']         = $operator;
                $iostockData[$item]['memo']             = $data['main'][0]['memo'];
                $iostockData[$item]['bn']               = $data['item'][$item]['bn'];
                $iostockData[$item]['iostock_price']    = $data['item'][$item]['price'];

                //判断是盘亏还是盘盈，并分别存储
                $product = $productsObj->dump(array('bn'=>$data['item'][$item]['bn'],'branch_id'=>$branch_id),'product_id');
                $product_id = $product['product_id'];
                $store  =  app::get('ome')->model('branch_product')->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id),'store');
                if($store['store'] < $data['item'][$item]['nums'] && $key=='item'){
                    $iostockData[$item]['nums']         = $data['item'][$item]['nums']-$store['store'];
                    $iostockData[$item]['type_id']      = $type_more;
                    array_push($iostockMore,$iostockData[$item]);
                }elseif($store['store'] > $data['item'][$item]['nums'] && $key=='item'){
                    $iostockData[$item]['nums']         = $store['store']-$data['item'][$item]['nums'];
                    $iostockData[$item]['type_id']      = $type_less;
                    array_push($iostockLess,$iostockData[$item]);
                }
            }
        }
        if(count($iostockLess)){
            $iostock_bn_less =  $iostockObj->get_iostock_bn($type_less); //当前盘亏操作出入库编号
            if(!$iostockObj->set($iostock_bn_less,$iostockLess,$type_less,$msg,0)){
                if($msg == ''){
                    return null;
                }else{
                    $msg_arr = implode('\n',$msg);
                }

                kernel::single('base_controller')->end(false,app::get('base')->_('出入库事务盘亏操作失败'));
                header("content-type:text/html; charset=utf-8");
                 echo "<script>top.MessageBox.error(\"上传失败\");alert(\"".$msg_arr."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";

            }
        }
        if(count($iostockMore)){
            $iostock_bn_more =  $iostockObj->get_iostock_bn($type_more); //当前盘盈操作出入库编号
            if(!$iostockObj->set($iostock_bn_more,$iostockMore,$type_more,$msg,1)){
                if($msg == ''){
                    return null;
                }else{
                    $msg_arr = implode('\n',$msg);
                }
                kernel::single('base_controller')->end(false,app::get('base')->_('出入库事务盘盈操作失败'));
                 header("content-type:text/html; charset=utf-8");
                echo "<script>top.MessageBox.error(\"上传失败\");alert(\"".$msg_arr."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";

            }
        }

        kernel::single('base_controller')->end(true,app::get('base')->_('成功'));
        if(empty($msg)){
            $_SESSION['bn_more'] = $iostock_bn_more;
             $_SESSION['bn_less'] = $iostock_bn_less;
        }

        return null;
     }


}