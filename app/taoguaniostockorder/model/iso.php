<?php

class taoguaniostockorder_mdl_iso extends dbeav_model{
    var $has_many = array(
        'iso_items' => 'iso_items');
    var $key = 0;
    //var $mark = array();
    var $defaultOrder = array('create_time DESC ,iso_id DESC');
    #导入或导出商品标题格式
    
    #出库模板
    private $temple_out = array(
        '*:出库单名称' => 'name',//出入库单名称
        '*:是否紧急出库' => 'emergency',
        '*:供应商' => 'supplier_name',
        '*:出货仓库' => 'branch_id',
        '*:出库类型' => 'type_id',
        '*:出库费用'=>'iso_price',
        '*:经办人' => 'oper',
        '*:备注'=>'memo',
       '*:外部仓库'=>'extrabranch',   
            );
    #出库模板
    private $temple_in = array(
        '*:入库单名称' => 'name',//出入库单名称
        '*:是否紧急入库' => 'emergency',
        '*:供应商' => 'supplier_name',
        '*:入库仓库' => 'branch_id',
        '*:入库类型' => 'type_id',
        '*:入库费用'=>'iso_price',
        '*:经办人' => 'oper',
        '*:备注'=>'memo',       
        '*:外部仓库'=>'extrabranch',   

    );
    private $item = array(
        '*:货号' => 'bn',
        '*:货品名称'=>'product_name',
        '*:货品规格'=>'spec_info',
        '*:货品条形'=>'barcode',
        '*:数量'=>'nums',
        '*:价格'=>'price'
    );
    #这是用来转换数据的属性
    private $relation_iso = array(
        0=>'name',
        1=>'emergency',
        2=>'supplier_name',
        3=>'branch_id',
        4=>'type_id',
        5=>'iso_price',
        6=>'oper',
        7=>'memo',
        8=>'extrabranch',
    );
    #这是用来转换数据的属性
    private $relation_item = array(
        0=>'bn',
        1=>'product_name',
        2=>'spec_info',
        3=>'barcode',
        4=>'nums',
        5=>'price'
    );

    /**
     * 
     */
    function iso_items($iso_id) {
        $eoObj = &$this->app->model("iso_items");
        $rows['items'] = $eoObj->getList('product_name as name,nums as num,bn,price',array('iso_id'=>$iso_id));
        $total_num = 0;
        $total_price = 0;
        
        foreach($rows['items'] as $v){
            $total_num += intval($v['num']);
            $total_price += intval($v['num'])*floatval($v['price']);
        }
        $rows['total_num'] = $total_num;
        $rows['total_price'] = $total_price;
        return $rows;
    }
    #出入库模板
    function exportTemplate($filter=null,$iso_type=null){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }
    function io_title( $filter=null,$ioType ='csv'){
        if($filter == '1'||$filter == 'temple_in'){
            #导出入库模板
            $this->oSchema['csv']['iso'] = $this->temple_in;
            $this->ioTitle[$ioType] = array_keys($this->oSchema['csv']['iso']);
        }elseif($filter == '0'||$filter == 'temple_out'){
            #导出出库模板
            $this->oSchema['csv']['iso'] = $this->temple_out;
            $this->ioTitle[$ioType] = array_keys($this->oSchema['csv']['iso']);
        }elseif($filter == 'item' ){
            #导出出库模板
            $this->oSchema['csv']['item'] = $this->item ;
            $this->ioTitle[$ioType] = array_keys($this->oSchema['csv']['item']);
        }
        return $this->ioTitle[$ioType];
    }
    #这个功能经和产品沟通，暂时屏蔽
/*     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        @ini_set('memory_limit','64M');
        $limit = 1000;#指定导出1000行
        if(isset($_GET['io'])){
            #导出其他入库数据
            if($_GET['io'] == '1'){
                $type = 'temple_in';
            }elseif($_GET['io'] == '0'){#导出其他入库数据
                $type = 'temple_out';
            }
            $title = array();
            if( !$data['title']['iso']){
                foreach( $this->io_title($type) as $k => $v ){
                    $title_iso[] = $this->charset->utf2local($v);
                }
                $data['title']['iso'] = '"'.implode('","', $title_iso).'"';
            }
            if( !$data['title']['item']){
                foreach( $this->io_title('item') as $k => $v ){
                    $title_item[] = $this->charset->utf2local($v);
                }
                $data['title']['item'] = '"'.implode('","',$title_item).'"';
            }
            #获取导出数据的iso_id
            $arr_iso_id = $filter['iso_id'];
            $str_id = implode(',', $arr_iso_id);
            $obj_iso = &$this->app->model("iso");
            
            #根据主键id，获取相关导出数据
            $iso_data = $this->getIsoDataById($str_id,$offset*$limit,$limit);
            if(!$iso_data) return false;
            $pRow = array();
            #处理iso数据
            foreach($iso_data['iso'] as $key =>$value){
                foreach($this->oSchema['csv']['iso'] as $_key => $_val){
                    $isoRow[$_key] = $this->charset->utf2local($value[$_val]);
                }
                $data['content']['iso'][] = '"'.implode('","', $isoRow).'"';
            }
            #处理item数据
            foreach($iso_data['item'] as $key_item =>$val_item){
                foreach($this->oSchema['csv']['item'] as $_k => $_v){
                    $itemRow[$_k] = $this->charset->utf2local($val_item[$_v]);
                }
                $data['content']['item'][] = '"'.implode('","',  $itemRow).'"';
            }
            return true;
        }
    } 
    function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }
        echo implode("\n",$output);
    }
    #根据出入库iso_id，获取出入库数据
    function getIsoDataById($str_id,$offset,$limit){
        #获取item数据
        $sql_item = 'select
                item.bn,item.product_name,item.price,item.nums, product.barcode,product.spec_info
                from sdb_taoguaniostockorder_iso_items  as item 
                join sdb_ome_products product on item.bn=product.bn and item.iso_id in('.$str_id.')';
        #获取iso数据
        $sql_iso = 'select
                iso.iso_bn,iso.iso_price,iso.type_id,iso.name,iso.branch_id,iso.supplier_name,iso.oper,iso.memo,iso.emergency
                from sdb_taoguaniostockorder_iso as iso where iso_id in('.$str_id.')';
        $item = $this->db->selectlimit($sql_item,$limit,$offset);
        $iso = $this->db->selectlimit($sql_iso,$limit,$offset);
        if(empty($iso)){
            return false;
        }
        $data['item'] = $item;
        $data['iso'] = $iso;
        return $data;
    } */
    function prepared_import_csv_row($row,$title,&$Tmpl,&$mark,&$newObjFlag,&$msg){ 
        $fileData = $this->import_data;
        if( !$fileData ){
            $fileData = array();
        }
        if(!empty($row)){
            $fileData[ $this->key++] = $row;
            #获取所有csv导入数据数组
            $this->import_data = $fileData;
        }
        return null;
    }
    function prepared_import_csv_obj($data,&$mark,$Tmpl,&$msg = ''){
        return null;
    }
    #读取csv数据完成以后处理，处理相关业务, 注意：一次只能一笔出入库
    function finish_import_csv(){
        header("Content-type: text/html; charset=utf-8");
        $oBranchProduct = &app::get('ome')->model('branch_product');
        #获取已经定义好的入库数据的标题
        if($_GET['io'] == '1'){
            $iso_title = $this->temple_in;
        }
        #获取已经定义好的出库的标题
        if($_GET['io'] == '0'){
            $iso_title = $this->temple_out;
        }
        #获取所有已读取的csv导入数据
        $fileData = $this->import_data;
        #检测第一行是不是标题
        if(substr($fileData[0][0],0,1) == '*' ){
            #统计标题行的数量是否多余
            if(count($fileData[0]) != count($iso_title)){
                echo "<script>alert('第一行或第三行标题出错:')</script>";exit;
            }
            foreach($fileData[0] as $title){
                #检查从csv导入的标题是否存在于已定义的iso标题中
                if(!array_key_exists($title,  $iso_title)){
                    echo "<script>alert('有误标题:   '+'$title')</script>";exit;
                }
            }
        }
        $relation_iso = $this->relation_iso;
        #把第二行数据转为iso数据
        $iso_data = array();
        foreach($fileData[1] as $key=>$v){
          #通过转换,获取iso数据,对应的数据库字段名
          $iso_key = $relation_iso[$key];
          #检测数据的非空判断
          $iso_data[$iso_key] =$v;
        } 
        $iostock_type_obj = app::get('ome')->model('iostock_type');
        
        #入库与出库的总的类型
        $arr_iso_type = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type($_GET['io'],true);
        $type_id = $iostock_type_obj->getList('type_id',array('type_name'=>$iso_data['type_id']));
        if(empty($type_id[0]['type_id'])){
            echo "<script>alert('出入库名称类型')</script>";exit;
        }
        if(array_search($type_id[0]['type_id'], $arr_iso_type) === false){
            echo "<script>alert('请输入正确的出入库类型')</script>";exit;
        }
        #获取本次csv导入数据的出入库类型
        $iso_data['type_id'] = $type_id[0]['type_id'];
        //获取目的地
        $extrabranchObj = app::get('ome')->model('extrabranch');
        if ($iso_data['extrabranch']) {
            $extrabranch = $extrabranchObj->dump(array('name'=>$iso_data['extrabranch']),'branch_id');
            $iso_data['extrabranch_id'] = $extrabranch['branch_id'];
        }
        $supplier_obj = app::get('purchase')->model('supplier');
        #获取供应商supper_id,备注：供应商非必填
        if(!empty($iso_data['supplier_name'])){
            $supplier_id = $supplier_obj->getList('supplier_id',array('name'=>$iso_data['supplier_name']));
            if(empty($supplier_id[0]['supplier_id'])){
                echo "<script>alert('请输入正确的供应商')</script>";exit;
            }
            #不管读出来的数据中有几个雷同供应商，暂时只取第一个数组中的那个supplier_id
            $iso_data['supplier_id'] = $supplier_id[0]['supplier_id'];
        }
        
        #获取网站操作人员name
        $operator = kernel::single('desktop_user')->get_name();
        $operator = $operator ? $operator : 'system';
        $iso_data['operator'] = $operator;
        
        #检测出入库费用 
        #修改。当导入有价格时，用导入价，否则取商品上的价格
        if(empty($iso_data['iso_price'])){
            $iso_data['iso_price'] = 0;
        }
        else{
            $_iso_price =  kernel::single('ome_goods_product')->valiPositive($iso_data['iso_price']);
            if(!$_iso_price){
                echo "<script>alert('出入库费用必须大于等于0')</script>";exit;
            }
        }
        #检测是否紧急数据
        if($iso_data['emergency'] == '是'){
            $iso_data['emergency'] = 'true';
        }elseif($iso_data['emergency'] == '否'){
            $iso_data['emergency'] = 'false';
        }else{
            echo "<script>alert('是否紧急请填：是/否')</script>";exit;
        }
        
        #获取出入库仓库branch_id
        $branch_obj = app::get('ome')->model('branch');
        $branch_id = $branch_obj->getList('branch_id,type',array('name'=>$iso_data['branch_id']));
        
        if(empty($branch_id[0]['branch_id'])){
            echo "<script>alert('请填写正确的仓库名称')</script>";exit;
        }
        
        //判断是否残损
        if (in_array($branch_id[0]['type'],array('damaged')) || in_array($type_id[0]['type_id'],array('5','50'))) {
            if (($branch_id[0]['type'] == 'damaged' && !in_array($type_id[0]['type_id'],array('5','50'))) || ($branch_id[0]['type'] != 'damaged' && in_array($type_id[0]['type_id'],array('5','50')))) {
                echo "<script>alert('残损出入库和仓库类型必须一致!')</script>";exit;
            }
        }
        $iso_data['branch_id'] = $branch_id[0]['branch_id'];
        #生成出入库单号
        $iostockorder_bn = kernel::single(taoguaniostockorder_iostockorder)->get_iostockorder_bn($type_id[0]['type_id']);
        $iso_data['iso_bn'] = $iostockorder_bn;
        
        $item_count = count($this->item);
        #检测第三行是不是标题
        if(substr($fileData[2][0],0,1) == '*'){
            #如果第三行是标题,则获取已定义的的item标题
            $item_title = $this->item;
            foreach($fileData[2] as $key=>$title){
                if($key < $item_count){
                    #检查csv导入标题是否存在于既定的item标题中
                    if(empty($title)){
                            echo "<script>alert('第三行标题有空的列！')</script>";exit;
                    }else{
                        if(!array_key_exists($title,$item_title)){
                            echo "<script>alert('第三行有误标题:   '+'$title')</script>";exit;
                        }
                    }
                }
             }
        }else{
            echo "<script>alert('只能导入一笔数据，第三行数据多余!')</script>";exit;
        } 
         $relation_item = $this->relation_item;
         #删除前三行csv数据，从第四行开始，才是csv导入的货品数据
         unset($fileData[0],$fileData[1],$fileData[2]);
         $item_data = array();#货品数据数组
         $k = 3;
         $total_product_cost = 0;$pbnArr = array();
         foreach($fileData as $key=>$p){
            if ($pbnArr[trim($p[0])]) {
                echo "<script>alert('货号[{$p[0]}]重复！')</script>";exit;
            }

            $pbnArr[trim($p[0])] = trim($p[0]);

             $k++;  #$k是用来动态记录csv数据所在行号
             foreach($p as $_key=>$v){
                 if($_key < $item_count){

                         $name = $relation_item[$_key];
                         #检测价格必须大于等于0
                         if($name == 'price'){
                             if($v != '0'){
                                 $_price = kernel::single('ome_goods_product')->valiPositive($v);
                                 if(!$_price){
                                     echo "<script>alert('第'+$k+'行，价格必须大于等于0')</script>";exit;
                                 }
                                $price = $v;
                             }else{
                                 $product =  $this->db->selectRow('select product_id,price from sdb_ome_products where bn='."'$p[0]'");
                                 $price = (float) $product['price'];
                             }
                         }
                         #检测货品是否存在
                         if($name  == 'barcode'){
                            $v = trim($v);
                            $p[0] = trim($p[0]);
                             #如果条形码不为空时，联合检测
                             if(!empty($v)){
                                 #获取本csv数据行的货号,用货号与条形码联合查询，看看是否存在这样的货品
                                 $product_obj = app::get('ome')->model('products');
                                 $product_id =  $this->db->selectRow('select product_id,price from sdb_ome_products where barcode='."'$v'".'and bn='."'$p[0]'");
                                 if(empty($product_id['product_id'])){
                                     echo "<script>alert('第'+$k+'行，货号与条形码对应货品不存在')</script>";exit;
                                 }
                             }else{
                                    $product_id =  $this->db->selectRow('select product_id,price from sdb_ome_products where bn='."'$p[0]'");
                                    if(empty($product_id['product_id'])){
                                        echo "<script>alert('第'+$k+'行，该货号对应货品不存在')</script>";exit;
                                    }
                                 }
                                 $total_product_cost += $p[4]*$p[5];//产品价格与数量乘积
                         }
                         #检测价格必须大于0
                         if($name == 'nums'){
                             $_nums = kernel::single('ome_goods_product')->valiPositive($v);
                             if(!$_nums){
                                 echo "<script>alert('第'+$k+'行，数量必须大于0')</script>";exit;
                             }
                             #出库的时候，要检测出库数量是否大于仓库对应库存
                             if($_GET['io'] == '0'){
                                 $aRow = $oBranchProduct->dump(array('product_id'=> $product_id['product_id'], 'branch_id'=>$iso_data['branch_id']),'store');
                                 $store = $aRow['store'];
                                 if(empty($store)){
                                     echo "<script>alert('第'+$k+'行，出库仓库没有该货号库存')</script>";exit;
                                 }
                                 if($v>$aRow['store']){
                                     echo "<script>alert('第'+$k+'行，出库数量不能大于库存'+$store)</script>";exit;
                                 }
                             }
                         }
                         $item_data[$key][$name] = trim($v);
                         $item_data[$key]['price'] = $price;
                         $item_data[$key]['iso_bn'] = $iostockorder_bn;
                         $item_data[$key]['bn'] = trim($p[0]);
                         $item_data[$key]['product_id'] = $product_id['product_id'] ;
                         $item_data[$key]['unit'] = '';

                         #删除item数据库不需要的东西
                         unset($item_data[$key]['spec_info'],$item_data[$key]['barcode'] );
                 }
             }
         }
         #本次商品总金额
         $iso_data['product_cost'] = $total_product_cost;
         $oQueue = &app::get('base')->model('queue');
         $iso_obj = &app::get('taoguaniostockorder')->model('iso');
         $item_obj = &app::get('taoguaniostockorder')->model('iso_items');
         kernel::database()->exec('begin');
         $this->saveIsoDate($iso_obj,$iso_data);
         #检测iso数据是否插入成功
         if($iso_data['iso_id']){
            foreach($item_data as $v){
                $v['iso_id']  = $iso_data['iso_id'];
                $a = $item_obj->save($v);
                if(!$a){
                     kernel::database()->rollBack();
                    echo "<script>alert('导入失败')</script>";exit;
                }
            }

            //kernel::single('console_receipt_stock')->clear_stockout_store_freeze(array('iso_bn'=>$iso_data['iso_bn']),'+');
         }else{
             kernel::database()->rollBack();
             echo "<script>alert('导入失败')</script>";exit;
         }
         kernel::database()->commit();
    }
    #组织iso数据，并保存数据
    function saveIsoDate($iso_obj,&$iso_data){
      $iso_data['name'] = $iso_data['name'];#入库单名称
      $iso_data['iso_bn'] =$iso_data['iso_bn'];
      $iso_data['type_id'] = $iso_data['type_id'];#出入库类型
      $iso_data['branch_id'] = $iso_data['branch_id'];#出入库仓库
      $iso_data['original_bn'] = '';
      $iso_data['original_id'] = 0;
      $iso_data['supplier_id'] = $iso_data['supplier_id'];
      $iso_data['supplier_name'] = $iso_data['supplier_name'];#供应商
      $iso_data['product_cost'] = $iso_data['product_cost'];#商品总额
      $iso_data['iso_price'] = $iso_data['iso_price'];#出入库费用
      $iso_data['oper'] = $iso_data['oper'];#经办人
      $iso_data['create_time'] = time();
      $iso_data['operator'] = $iso_data['operator'];#网站操作人员
      $iso_data['memo'] = $iso_data['memo'];#备注
      $iso_data['emergency'] = $iso_data['emergency'];#是否紧急
      
      $iso_obj->save($iso_data); 

      return ;
    }
    #增加调拨单号的搜索
    function searchOptions(){
        if(($_GET['act'] == 'allocate_iostock') || ($_GET['act'] == 'search_iostockorder')){
            $parentOptions = parent::searchOptions();
            $childOptions = array(
                    'appropriation_no'=>app::get('base')->_('调拨单号'),
            );
            return $Options = array_merge($parentOptions,$childOptions);
        }
        if($_GET['act'] == 'search_iostockorder' && $_GET['io'] == '1'){
             $parentOptions = parent::searchOptions();
             $childOptions = array(
                     'purchase_name'=>app::get('base')->_('采购单名称'),
             );
             return $Options = array_merge($parentOptions,$childOptions);
         }
         if($_GET['act'] == 'search_iostockorder' && $_GET['io'] == '0'){
             $parentOptions = parent::searchOptions();
             $childOptions = array(
                     'return_name'=>app::get('base')->_('采购退货单名称'),
             );
             return $Options = array_merge($parentOptions,$childOptions);
         }
         
        return parent::searchOptions();
    }
    function _filter($filter,$tableAlias=null,$baseWhere=null){

        if(isset($filter['appropriation_no'])){
            $appropriation = app::get('taoguanallocate')->model('appropriation');
            $filter['appropriation_no'] = trim($filter['appropriation_no']);
            $sql = 'select
                        original_id
                    from  sdb_taoguanallocate_appropriation appropriation
                    left join sdb_taoguaniostockorder_iso iso
                    on iso.original_id=appropriation.appropriation_id
                    where appropriation.appropriation_no='."'{$filter['appropriation_no']}'";
            $original_id =   $this->db->selectRow($sql);
            unset($filter['appropriation_no']);
            $where = ' AND original_id='.$original_id['original_id'];
        }
        #采购单名称模糊查询
        if(!empty($filter['purchase_name'])){
            $purchase_name = trim($filter['purchase_name']);
            $sql  = 'select 
                        original_id 
                    from sdb_purchase_po  po
                    left join sdb_taoguaniostockorder_iso iso on iso.original_id=po.po_id
                    where  iso.type_id =\'1\' and po.name like \''.$purchase_name.'%\'';
            $original_id =   $this->db->selectRow($sql);
            unset($filter['purchase_name']);
            $where = ' AND type_id=\'1\' and original_id='.$original_id['original_id'];
        }
        #采购退货单名称模糊查询
        if(!empty($filter['return_name'])){
            $name = $filter['return_name'];
            $sql  = 'select
                        original_id
                    from sdb_purchase_returned_purchase  returned
                    left join sdb_taoguaniostockorder_iso iso on iso.original_id=returned.rp_id
                    where  iso.type_id =\'10\' and returned.name like \''.$name.'%\'';
            $original_id =   $this->db->selectRow($sql);
            unset($filter['return_name']);
            $where = ' AND type_id=\'10\' and original_id='.$original_id['original_id'];
        }
       
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }
    
    function pre_recycle($data=null) {
        if (is_array($_POST['iso_id'])) {
            foreach ($_POST['iso_id'] as $key => $val) {
                $iso = $this->dump($val, 'check_status');
                if ($iso['check_status'] == '2') {
                    $this->recycle_msg = '已审核单据不可以删除';
                    return false;
                }
            }
            return true;
        }
    }
    
    public function get_Schema()
    {
        
        if( $_GET['ctl']=='admin_iostockorder' && ($_GET['act']=='allocate_iostock' || $_GET['act']=='other_iostock')){
            $data = parent::get_Schema();
            $data['columns']['original_bn']['filtertype'] = '';
            $data['columns']['original_bn']['filterdefault'] = false;

            foreach($data['in_list'] as $k=>$v){
                if(in_array($v,array('original_bn'))){
                    unset($data['in_list'][$k]);
                }
            }
            foreach($data['deafult_in_list'] as $k1=>$v1){
                if(in_array($v1,array('original_bn'))){
                    unset($data['deafult_in_list'][$k1]);
                }
            }
            if ($_GET['act']=='other_iostock' && $_GET['app']=='console') {
                unset($data['columns']['type_id']['type']);
                if ($_GET['io'] == '1') {
                    $data['columns']['type_id']['type'] = array(
                        '70'=>'直接入库',
                        '50'=>'残损入库',
                        '200'=>'赠品入库',
                        '400'=>'样品入库',
                    );
                }else{
                   $data['columns']['type_id']['type'] = array(
                        '7'=>'直接出库',
                        '5'=>'残损出库',
                        '100'=>'赠品出库',
                        '300'=>'样品出库',
                    );
                }
                
            }
            return $data;
        }else{
            return parent::get_Schema();
        }
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'warehouse';
        if ($logParams['app'] == 'taoguaniostockorder' && $logParams['ctl'] == 'admin_iostockorder') {
            if ($logParams['act'] == 'search_iostockorder') {
                if ($params['type_id'][0] == 1) {
                    $type .= '_enterManager_enterFind';
                }
                else {
                    $type .= '_outManager_outFind';
                }
            }
            else {
                $type .= '_enterManager_other';
            }
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'warehouse';
        if ($logParams['app'] == 'taoguaniostockorder' && $logParams['ctl'] == 'admin_iostockorder') {
            $type .= '_other';
        }
        $type .= '_import';
        return $type;
    }
    
    

    
    
}