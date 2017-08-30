<?php
class omepkg_mdl_pkg_goods extends dbeav_model{
    var $has_many = array(
        'product' => 'pkg_product:replace'
    );
    #导入或导出商品标题格式
    private $temple = array(
        '*:捆绑商品货号' => 'pkg_bn',
        '*:捆绑商品名称' => 'name',
        '*:捆绑商品重量(g)' => 'weight',
        '*:普通商品货号' => 'pkgbn',
        '*:普通商品名称' => 'pkgname',
        '*:普通商品数量'=>'pkgnum'
    );
    #模板
    function exportTemplate($filter=null){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }
    function io_title( $filter=null,$ioType ='csv'){
       switch($filter){
           #导出模板
           case 'template':
               $this->oSchema['csv']['template'] = $this->temple;
               $this->ioTitle[$ioType][$filter] = array_keys($this->oSchema['csv']['template']);
               break;
       }
        return $this->ioTitle[$ioType][$filter];
    }
    #导入处理
    function prepared_import_csv_row($row,$title,&$Tmpl,&$mark,&$newObjFlag,&$msg){
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
                if( array_key_exists( '*:捆绑商品货号',$title )  ) {
                    $fileData['contents'][] = $row;
                }else{
                    $fileData['contents'][] = $row;
                }
                $this->import_data = $fileData;
            }
        }
        return null;
    }
    function prepared_import_csv_obj($data,&$mark,$Tmpl,&$msg = ''){
        return null;
    }
    //导出捆绑商品
    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        set_time_limit(0); // 30分钟
        $max_offset = 1000; // 最多一次导出10w条记录
        if ($offset>$max_offset) return false;// 限制导出的最大页码数
        if( !$data['title']){
            $title = array();
            foreach( $this->io_title('template') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['products'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        $sql = 'select
                     goods.pkg_bn,goods.name ,goods.weight, products.bn pkgbn,products.name pkgname,products.pkgnum
                 from sdb_omepkg_pkg_goods goods
                left join sdb_omepkg_pkg_product products on goods.goods_id=products.goods_id  where '.$this->_filter($filter);
        $sql = str_replace("`sdb_omepkg_pkg_goods`", 'goods', $sql);
        $row = $this->db->selectlimit($sql,$limit,$offset*$limit);
        if(empty($row)){
            return false;
        }
        foreach($row as $_val){
            foreach( $this->oSchema['csv']['template'] as $k => $v ){
                $row2[$k] = $this->charset->utf2local(utils::apath( $_val,explode('/',$v) ));
            }
            $data['content']['products'][] = '"'.implode('","',$row2).'"';    
        }
        return true; 
    }    
    function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        } 
        echo implode("\n",$output);
    } 
    function finish_import_csv(){
        header("Content-type: text/html; charset=utf-8");
        $data = $this->import_data;
        $csv_data = $data['contents'];
        unset($data,$this->import_data);
        if(empty($csv_data)){
            echo "<script>alert('不能导入空数据')</script>";exit;
        }
        if(count($csv_data)>=3000){
            echo "<script>alert('导入的数据量过大，请减少到3000行以下')</script>";exit;
        }
        $title = array_flip( $this->io_title('template'));
        $Schema = $this->oSchema['csv']['template'];
       
        $obj_pkg_product = &app::get('omepkg')->model('pkg_product');
        //$obj_product = &app::get('ome')->model('products');
        $_pkg = $_arr = $arr_lastdata = array();
        foreach($csv_data as $key => $data ){
            $keynum = $key+2;#csv表格中的行号
            $arr_lastdata[$key] = $this->ioObj->csv2sdf( $data ,$title,$Schema); #把行数据与列数据对应起来
            #检测捆绑商品货号，如果已经存在，不能上传
            $goods_id = $this->checkPkgBn(trim($arr_lastdata[$key]['pkg_bn']));
            if(!empty($goods_id)){
               echo "<script> var _key=$keynum;alert('第 '+_key+' 行，捆绑商品货号已经存在，请检查！')</script>";exit;
            }
            if(empty($arr_lastdata[$key]['name'])){
                echo "<script> var _key=$keynum;alert('第 '+_key+' 行，捆绑商品名称不能为空，请检查！')</script>";exit;
            }
            if(empty($arr_lastdata[$key]['pkgbn'])){
               echo "<script> var _key=$keynum;alert('第 '+_key+' 行，普通商品货号不能为空，请检查！')</script>";exit;
            }
            if(empty($arr_lastdata[$key]['pkgname'])){
                echo "<script> var _key=$keynum;alert('第 '+_key+' 行，普通商品名称不能为空，请检查！')</script>";exit;
            } 
            if(!$this->valiInt($arr_lastdata[$key]['pkgnum'])){
                echo "<script> var _key=$keynum;alert('第 '+_key+' 行，普通商品绑定数量必须大于0，请检查！')</script>";exit;
            }
            $_weight =  kernel::single('ome_goods_product')->valiPositive($arr_lastdata[$key]['weight']);
            if(empty($_weight)){
                echo "<script> var _key=$keynum;alert('第 '+_key+' 行，捆绑商品重量必须大于0，请检查！')</script>";exit;
            }
            $arr_lastdata[$key]['pkgbn'] = trim($arr_lastdata[$key]['pkgbn']);
            #根据普通商品货号，找到该货号对应product_id
            $arr = $this->getProductIdByBn($arr_lastdata[$key]['pkgbn']);
            if(empty($arr['product_id'])){
                echo "<script> var _key=$keynum;alert('第 '+_key+' 行，普通商品不存在，请检查！')</script>";exit;
            }
            #如果验证通过，将普通货品product_id存在捆绑货品中
            $arr_lastdata[$key]['product_id'] = $arr['product_id'];
            #本条csv数据的普通商品货号
            $pkgbn = $arr_lastdata[$key]['pkgbn'];
            #本条csv数据捆绑商品编号
            $pkg_bn =$arr_lastdata[$key]['pkg_bn'];
            #第二行开始，检测同一商品不能捆绑相同货号的货品
            if($keynum > 2){
                #先检测是否存在,如果存在，检查普通商品货号是否相同
                if(!empty($_pkg[$pkg_bn])){
                    $arr_pkgbn = $_pkg[$pkg_bn];#同一捆绑商品的货号数组
                    $result =array_search($pkgbn, $arr_pkgbn);
                    if($result !==false){
                        echo "<script> var _key=$keynum;alert('第 '+_key+' 行，不能捆绑相同货号的普通商品，请检查！')</script>";exit;
                    }
                }
            } 
            $_arr[$pkg_bn][] = $arr_lastdata[$key];
            #每次遍历都把普通商品货号存入到捆绑商品数组中去
            $_pkg[$pkg_bn][] = $pkgbn;
        }
        #以上验证完毕,正式添加数据
        kernel::database()->exec('begin');
        foreach($_arr as $key=>$value){
            $_count = count($value);#统计捆绑了货品个数
            if(1 == $_count){
                if($value[0]['pkgnum']==1){
                    $_pkg_bn = "'".$value[0]['pkg_bn']."'";
                    echo "<script> var _key=$_pkg_bn;alert('只有一个货号 '+_key+'，捆绑商品数量必须大于1')</script>";
                    kernel::database()->rollBack();exit;
                } 
                $goods = array();
                #统一使用一个捆绑商品信息
                $goods['pkg_bn'] = $value[0]['pkg_bn'];#捆绑商品货号
                $goods['name'] = $value[0]['name'];#捆绑商品名称
                $goods['weight'] = $value[0]['weight'];#捆绑商品重量
                
                #保存捆绑主商品，并生成捆绑商品goods_id
                $this->save($goods);
                
                $product['product_id'] = $value[0]['product_id'];#普通商品对应的product_id
                $product['bn'] = $value[0]['pkgbn'];#普通商品货号
                $product['name'] = $value[0]['pkgname'];#普通商品名字
                $product['goods_id'] = $goods['goods_id'];#捆绑商品对应的goods_id，在捆绑商品入库以后生成，否侧为空
                $product['pkgnum'] = $value[0]['pkgnum'];#普通商品绑定数量
                #生成捆绑货品数据
                $this->saveDate($product); 
            }elseif($_count > 1){
                $goods = array();
                $goods['pkg_bn'] = $value[0]['pkg_bn'];#捆绑商品货号
                $goods['name'] = $value[0]['name'];#捆绑商品名称
                $goods['weight'] = $value[0]['weight'];#捆绑商品重量
                #保存捆绑主商品，并生成捆绑商品goods_id
                $this->save($goods);
                
                #一个商品，捆绑多个货品
                foreach($value as $v){
                    $product['product_id'] = $v['product_id'];#普通商品对应的product_id
                    $product['bn'] = $v['pkgbn'];#普通商品货号
                    $product['name'] = $v['pkgname'];#普通商品名字
                    $product['goods_id'] = $goods['goods_id'];#捆绑商品对应的goods_id，在捆绑商品入库以后生成，否侧为空
                    $product['pkgnum'] = $v['pkgnum'];#普通商品绑定数量
                    #生成捆绑货品数据
                    $this->saveDate($product); 
                } 
            }
        }
        kernel::database()->commit();
    }
    #保存数据的对象
    function saveDate($data=null){
        $product_id = "'".$data['product_id']."',";
        $bn= "'".$data['bn']."',";
        $name= "'".$data['name']."',";
        $goods_id = "'".$data['goods_id']."',";
        $pkgnum = "'".$data['pkgnum']."'";
        $sql = "
                insert into sdb_omepkg_pkg_product
                    (`product_id`,`bn`,`name`,`goods_id`,`pkgnum`)values(
                      {$product_id}
                      {$bn}
                      {$name}
                      {$goods_id}
                      {$pkgnum}
                    )
                ";
        return $this->db->exec($sql);
    }
    #验证正整数型
    function valiInt($data = null){
        $patter = '/^[1-9]{1}[0-9]{0,9}$/';
        preg_match($patter,$data,$arr);
        if(empty($arr)){
            return false;
        }else{
            return true;
        }
    }
    #根据商品货号，找到货号对应product_id
    function getProductIdByBn($bn = null){
        $sql = 'select product_id from sdb_ome_products where bn='."'$bn'";
        return  $this->db->selectrow($sql);
    }
    function getgoods($id){
        return $this->db->select("SELECT * FROM sdb_omepkg_pkg_goods where goods_id = '".$id."'"); 
    }
    
    function checkPkgBn($pkg_bn){
        return $this->db->select("SELECT goods_id FROM sdb_omepkg_pkg_goods where pkg_bn = '".addslashes($pkg_bn)."'");
    }

    function getPkgBn($bn){
        return $this->db->selectrow("SELECT goods_id FROM sdb_omepkg_pkg_goods where pkg_bn = '".addslashes($bn)."'");
    }

    function save_log($goods_id,$goods_name,$memo) {
        $opObj  = &app::get('ome')->model('operation_log');
        $opinfo = kernel::single('ome_func')->getDesktopUser();
        $data = array(
               'obj_id' => $goods_id,
               'obj_name' => $goods_name,
               'obj_type' => 'pkg_goods@omepkg',
               'operation' => 'omepkg_modify@omepkg',
               'op_id' => $opinfo['op_id'],
               'op_name' => $opinfo['op_name'],
               'operate_time' => time(),
               'memo' => $memo,
               'ip' => kernel::single("base_request")->get_remote_addr(),
            );

        $opObj->save($data);
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
        $type = 'goods';
        if ($logParams['app'] == 'omepkg' && $logParams['ctl'] == 'admin_pkg') {
            $type .= '_goodsBingding_bindingGoods';
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
        $type = 'goods';
        if ($logParams['app'] == 'omepkg' && $logParams['ctl'] == 'admin_pkg') {
            $type .= '_goodsBingding_bindingGoods';
        }
        $type .= '_import';
        return $type;
    }
}
?>