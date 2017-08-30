<?php
class iostock_mdl_purchaseReturns extends iostock_mdl_iostock{

    public function table_name($real=false){
        $table_name = "iostock";
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'iostock':
                $this->oSchema['csv'][$filter] = array(
                    '*:出入库单号' => 'iostock_id',
                    '*:仓库编号' => 'branch_id',
                    '*:出入库单号' => 'iostock_bn',
                    '*:出入库类型' => 'type_id',
                    '*:原始单据id' => 'original_id',
                    '*:原始单据号' => 'original_bn',
                    '*:供应商编号' => 'supplier_id',
                    '*:供应商名称' => 'supplier_name',
                    '*:货号' => 'bn',
                    '*:出入库价格' => 'iostock_price',
                    '*:数量' => 'nums',
                    '*:税率' => 'cost_tax',
                    '*:经手人' => 'oper',
                    '*:出入库时间' => 'create_time',
                    '*:操作人员' => 'operator',
                    '*:结算方式' => 'settle_method',
                    '*:结算状态' => 'settle_status',
                    '*:结算人' => 'settle_operator',
                    '*:结算时间' => 'settle_time',
                    '*:结算数量' => 'settle_num',
                    '*:结算单号' => 'settlement_bn',
                    '*:结算金额' => 'settlement_money',
                    '*:备注' => 'memo',
                    '*:原始单据明细id' => 'original_item_id',
                );
                break;
             case 'main':
                 $this->oSchema['csv'][$filter] = array(
                                                    '*:仓库编号' =>'',
                                                    '*:采购入库单号' =>'',
                                                    '*:供应商名称' =>'',
                                                    '*:经手人' => '',
                                                    '*:备注' => '',
                                                        );

                 break;
             case 'item':
                 $this->oSchema['csv'][$filter] = array(
                                                    '*:货号' =>'',
                                                    '*:退货单价' =>'',
                                                    '*:退货数量' =>'',
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
        //$this->io_title('item');
        $arrFieldsAll = array_merge($this->oSchema['csv']['main'],$this->oSchema['csv']['item']);
        $arrResult = array_diff_key($arrFrom,$arrFieldsAll);
        return empty($arrResult) ?  true : false;
    }

    function prepared_import_csv(){
        $this->iostock_same_name = array();
        $this->iostock_same_name_db = array();
        $this->branch = &app::get('ome')->model('branch');
        $this->products = &app::get('ome')->model('products');
        $this->iostock = &app::get('ome')->model('iostock');
        $this->ioObj->cacheTime = time();
        $this->aa = 0;
        $this->a = 0;



    }

    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        $num = $this->aa++;

        $action = $this->direction;
         if (empty($row)){
            return true;
        }
        $mark = false;
        $fileData = $this->im_data;
        if( !$fileData ) $fileData = array();
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
            if( $row[0] ){
                  if(array_key_exists('*:仓库编号',$title)){
                  $num = $this->a++;
                  if($num > 0){
                       $msg['error'] = "主信息必须是唯一，请重新维护导入信息";
                        return false;
                  }
                 //校验仓库号.
                    $branch = $this->branch->dump(array('branch_bn'=>$row[0]),'*');
                    $iostock_bn = $this->iostock->dump(array('iostock_bn'=>$row[1]),'*');
                    if(!$iostock_bn){
                        $msg['error'] = "采购入库单号".$row[1]."不存在，请重新维护导入信息";
                        return false;
                    }
                    if(!$branch){

                        $msg['error'] = "仓库不存在，请重新维护导入信息".$row[0];
                        return false;
                    }else{
                        $branch_id = $this->branch->getList('branch_id',array('branch_bn'=>$row[0]));
                        $row[0] = $branch_id[0]['branch_id'];
                    }
                    if(!is_string($row[0])){
                       $msg['error'] = "仓库编号必须为字符串";
                        return false;
                    }
                    if(!is_string($row[1])){
                       $msg['error'] = "单号必须为字符串";
                        return false;
                    }
                    if(!is_string($row[2])){
                       $msg['error'] = "供应商名称必须为字符串";
                        return false;
                    }
                    if(!is_string($row[3])){
                       $msg['error'] = "经手人名称必须为字符串";
                        return false;
                    }
                     if(!is_string($row[4])){
                       $msg['error'] = "备注必须为字符串";
                        return false;
                    }
                   $fileData[$row[0]]['iostock']['contents'][] = $row;
                }else{
                //校验货号
                    $products = $this->products->dump(array('bn'=>$row[0]),'*');
                    if($products == ''){
                        $msg['error'] = "货号".$row[0]."商品不存在，请重新维护导入信息";
                        return false;
                    }
                    if(!is_string($row[0])){
                        $msg['error'] = "货号必须为字符串";
                        return false;
                    }
                    if(!is_numeric($row[1])){
                        $msg['error'] = "退货单价必须为数字";
                        return false;
                    }
                    if(!is_numeric($row[2])){
                        $msg['error'] = "退货数量必须为数字";
                        return false;
                    }
                    $store = $this->products->getList('store',array('bn'=>$row[0]),0,1);
                    if($row[2] > $store[0]['store']){
                        $msg['error'] = ($num-1)."行数据库存不够退货出库，请重新维护导入信息";
                        return false;
                    }
                   
                     if(intval($row[2])!=$row[2]){
                        $msg['error'] = "退货数量必须为整数";
                        return false;
                    }
                    $fileData[$row[0]]['iostock']['contents'][] = $row;
                }
                  $this->im_data = $fileData;
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
        $data = $this->im_data;
        unset($this->im_data);
        $objTitle = array_flip( $this->io_title('iostock') );
        $pSchema = $this->oSchema['csv']['iostock'];
        $sdfArray = array();
        $operator = kernel::single('desktop_user')->get_name();
        $sdfArray[$pSchema['*:仓库编号']]   = '';
        $sdfArray[$pSchema['*:出入库单号']] = '';
        $sdfArray[$pSchema['*:出入库类型']] = '';
        $sdfArray[$pSchema['*:原始单据id']] = '';
        $sdfArray[$pSchema['*:原始单据号']] = '';
        $sdfArray[$pSchema['*:供应商编号']] = '';
        $sdfArray[$pSchema['*:供应商名称']] = '';
        $sdfArray[$pSchema['*:货号']]       =  '';
        $sdfArray[$pSchema['*:出入库价格']] =  '';
        $sdfArray[$pSchema['*:数量']]       =  '';
        $sdfArray[$pSchema['*:税率']]       =  0;
        $sdfArray[$pSchema['*:经手人']]     = '';
        $sdfArray[$pSchema['*:出入库时间']] = time();
        $sdfArray[$pSchema['*:操作人员']]   = $operator;
        $sdfArray[$pSchema['*:结算方式']]   = '0';
        $sdfArray[$pSchema['*:结算状态']]   = 0;
        $sdfArray[$pSchema['*:结算人']]     = '';
        $sdfArray[$pSchema['*:结算时间']]   = '';
        $sdfArray[$pSchema['*:结算数量']]   = 0;
        $sdfArray[$pSchema['*:结算单号']]   = '';
        $sdfArray[$pSchema['*:结算金额']]   = 0;
        $sdfArray[$pSchema['*:备注']]       = '';
        $sdfArray[$pSchema['*:原始单据明细id']] = '';
        $this->num = 0;
         foreach ($data as $k => $aPi){
            //$branch = $this->branch->dump(array('branch_id'=>$k),'*');
            $num = $this->num++;
            if($num < 1){
                //转换主数组
                foreach($aPi['iostock']['contents'] as $ad => $name ){
                    $sdfArray[$pSchema['*:仓库编号']]   = $name[0];
                    $sdfArray[$pSchema['*:原始单据号']] = $name[1];
                    $sdfArray[$pSchema['*:供应商名称']] = $name[2];
                    $sdfArray[$pSchema['*:经手人']]     = $name[3];
                    $sdfArray[$pSchema['*:备注']]       = $name[4];

                }

            }else{
                //转换子数组
                foreach($aPi['iostock']['contents'] as $bn => $s){
                    $sdfArray[$pSchema['*:货号']]       = $s[0];
                    $sdfArray[$pSchema['*:出入库价格']] = $s[1];
                    $sdfArray[$pSchema['*:数量']]       = $s[2];
                    $Array[] = $sdfArray;
                }
            }
        }

        $iostock = kernel::service('ome.iostock');
        $type = ome_iostock::PURCH_RETURN;
        $io = 0;
        $iostock_bn =  $iostock->get_iostock_bn($type);
        kernel::single('base_controller')->begin();
        if(!$iostock->set($iostock_bn,$Array,$type,$msg,$io)){
            kernel::single('base_controller')->end(false,app::get('base')->_('采购入库操作失败'));
            if($msg == ''){
                return null;
            }else{
                $msg_arr = implode('\n',$msg);
                header("content-type:text ml; charset=utf-8");
                echo "<script>top.MessageBox.error(\"上传失败\");alert(\"".$msg_arr."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
                 exit;
            }
        }else{
            $_SESSION['bn'] = $iostock_bn;
        }
        kernel::single('base_controller')->end(true,app::get('base')->_('成功'));
        return null;
     }

}
