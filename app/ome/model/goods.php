<?php
class ome_mdl_goods extends dbeav_model{

    //var $has_export_cnf = true;

    var $export_name = '商品列表';

    var $defaultOrder = array('d_order',' DESC',',p_order',' DESC',',goods_id',' DESC');
    var $has_many = array(
        'product' => 'products:contrast',
        //'keywords'=>'goods_keywords:replace',
        //'images' => 'image_attach@image:contrast:target_id',
        'tag'=>'tag_rel@desktop:replace:goods_id^rel_id',
    );
    var $has_one = array(

    );
    var $flag = false;
    var $subSdf = array(
            'default' => array(

                'keywords'=>array('*'),
                'product'=>array(
                    '*'
                    ,
//                    array(
//                        'price/member_lv_price'=>array('*')
//                    )
                ),
                ':goods_type'=>array(
                    '*'
                ),
                'tag'=>array(
                    '*',array(
                        ':tag'=>array('*')
                    )
                ),
            )
        );

    function __construct($app){
        parent::__construct($app);
        //member中的扩展属性将通过meta系统进行存储
        $this->use_meta();
    }
    var $ioSchema = array(
        'csv' => array(
            'bn:商品编号' => 'bn',
            'barcode:条形码' => 'barcode',
            'ibn:货号' => array('bn','product'),
            //'col:分类' => 'category/cat_id',
            'col:品牌' => 'brand/brand_id',
            //'col:市场价' => array('price/mktprice/price','product'),
            'col:成本价' => array('price/cost/price','product'),
            'col:销售价' => array('price/price/price','product'),
            'col:商品名称' => 'name',
            //'col:上架' => 'status',
            'col:规格' => 'spec',
            //'col:商品简介' => 'brief',
            //'col:详细介绍' => 'description',
            'col:重量' => 'weight',
            'col:单位' => 'unit',
            'col:图片地址'=>'picurl',
            'col:商品简介'=>'brief'
        ),
    );

    function checkProductBn($bn, $gid=0){
        $bn = addslashes($bn);
        if($bn == ''){
            return false;
        }
        if($gid){
            $sql = 'SELECT count(*) AS num FROM sdb_ome_products WHERE bn = \''.$bn.'\' AND goods_id != '.$gid;
            $Gsql = 'SELECT count(*) AS num FROM sdb_ome_goods WHERE bn = \''.$bn.'\' AND goods_id != '.$gid;
        }else{
            $sql = 'SELECT count(*) AS num FROM sdb_ome_products WHERE bn = \''.$bn.'\'';
            $Gsql = 'SELECT count(*) AS num FROM sdb_ome_goods WHERE bn = \''.$bn.'\'';
        }
        $aTmp = $this->db->selectrow($sql);
        $GaTmp = $this->db->selectrow($Gsql);
        return $aTmp['num']+$GaTmp['num'];
    }

    /**
     * 检测条形码，不允许重复
     */
    function checkBarcode($barcode, $gid=0){
        $barcode = addslashes($barcode);
        if($barcode == ''){
            return false;
        }
        if($gid){
            $sql = 'SELECT count(*) AS num FROM sdb_ome_products WHERE barcode = \''.$barcode.'\' AND goods_id != '.$gid;
            $Gsql = 'SELECT count(*) AS num FROM sdb_ome_goods WHERE barcode = \''.$barcode.'\' AND goods_id != '.$gid;
        }else{
            $sql = 'SELECT count(*) AS num FROM sdb_ome_products WHERE barcode = \''.$barcode.'\'';
            $Gsql = 'SELECT count(*) AS num FROM sdb_ome_goods WHERE barcode = \''.$barcode.'\'';
        }
        $aTmp = $this->db->selectrow($sql);
        $GaTmp = $this->db->selectrow($Gsql);
        return $aTmp['num']+$GaTmp['num'];
    }


    function dump($filter,$field = '*',$subSdf = null){
        $dumpData = &parent::dump($filter,$field,$subSdf);
        $oSpec = &$this->app->model('specification');
        if( $dumpData['spec_desc'] ){
            foreach( $dumpData['spec_desc'] as $specId => $spec ){
                $dumpData['spec'][$specId] = $oSpec->dump($specId,'*');
                if ($spec)
                foreach( $spec as $pSpecId => $specValue ){
                    $dumpData['spec'][$specId]['option'][$pSpecId] = array_merge( array('private_spec_value_id'=>$pSpecId), $specValue );
                }
            }
        }
        unset($dumpData['spec_desc']);
        return $dumpData;
    }

/*function getList($cols='*',$filter=array(),$start=0,$limit=-1,$orderType=null){
        if (kernel::servicelist('ome_goods_list_apps'))
        foreach(kernel::servicelist('ome_goods_list_apps') as $object){
            return $object->goods_list($cols,$filter,$start,$limit,$orderType, $this);
        }
    }*/


    function _filter($filter,$tbase=''){
        foreach(kernel::servicelist('ome_goods_filter_apps') as $object){
            if(method_exists($object,'goods_filter')){
                $data = $object->goods_filter($filter, $this);
            }
            return $data;
        }
    }

    function wFilter($words){
        $replace = array(",","+");
        $enStr=preg_replace("/[^chr(128)-chr(256)]+/is"," ",$words);
        $otherStr=preg_replace("/[chr(128)-chr(256)]+/is"," ",$words);
        $words=$enStr.' '.$otherStr;
        $return=str_replace($replace,' ',$words);
        $word=preg_split('/\s+/s',trim($return));
        $GLOBALS['search_array']=$word;
        foreach($word as $k=>$v){
            if($v){
                $goodsId = array();
                foreach($this->getGoodsIdByKeyword(array($v)) as $idv)
                    $goodsId[] = $idv['goods_id'];
                foreach( $this->db->select('SELECT goods_id FROM sdb_ome_products WHERE bn = \''.trim($v).'\' ') as $pidv)
                    $goodsId[] = $pidv['goods_id'];
                $sql[]='(name LIKE \'%'.$word[$k].'%\' or bn like \''.$word[$k].'%\' '.( $goodsId?' or goods_id IN ('.implode(',',$goodsId).') ':'' ).')';
            }
        }
        return implode('and',$sql);
    }
    function getGoodsIdByKeyword($keywords , $searchType = 'tequal'){
        $where = '';
        switch( $searchType ){
            case 'has':
                $where = ' keyword LIKE "%'.implode( '%" AND keyword LIKE "%' ,$keywords ).'%" ';
                //like
                break;
            case 'nohas':
                $where = ' keyword NOT LIKE "%'.implode( '%" AND keyword NOT LIKE "%' ,$keywords ).'%" ';
                // not like
                break;
            case 'tequal':
            default:
                $where = ' keyword in ( "'.implode('","',$keywords).'" ) ';
                break;
        }
        return $this->db->select('SELECT goods_id FROM sdb_om_goods_keywords WHERE '.$where);
    }
    function save(&$goods,$mustUpdate = null){

        if( !$goods['bn'] ) $goods['bn'] = strtoupper(uniqid('g'));

        if( array_key_exists( 'spec',$goods ) ){
            if( $goods['spec'] )
                foreach( $goods['spec'] as $gSpecId => $gSpecOption ){
                    $goods['spec_desc'][$gSpecId] = $gSpecOption['option'];
                }
        } else{
                $goods['spec_desc'] = null;
        }
        $goodsStatus = false;
        if(is_array($goods['product'])){
            foreach( $goods['product'] as $pk => $pv ){
                if( $pv['bn'] == '' ) $goods['product'][$pk]['bn'] = strtoupper(uniqid('p'));
                if( !$pv['uptime'] ) $goods['product'][$pk]['uptime'] = time();
                $goods['product'][$pk]['name'] = $goods['name'];
                if (!$goods['product'][$pk]['barcode'])
                $goods['product'][$pk]['barcode'] = $goods['barcode'];
                if( $pv['status'] != 'false' ) $goodsStatus = true;
            }
        }
        if( !$goodsStatus )
            $goods['status'] = 'false';
        unset($goods['spec']);

      return parent::save($goods,$mustUpdate);
    }

    function unfreez($goods_id, $product_id, $num){
        $oPro = &$this->app->model('products');
        $sdf_pdt = $oPro->dump($product_id, 'freez');
        if($sdf_pdt['freez'] === null || $sdf_pdt['freez'] === ''){
            $sdf_pdt['freez'] = 0;
        }elseif($num < $sdf_pdt['freez']){
            $sdf_pdt['freez'] -= $num;
        }elseif($num >= $sdf_pdt['freez']){
            $sdf_pdt['freez'] = 0;
        }
        $sdf_pdt['product_id'] = $product_id;
        $sdf_pdt['last_modify'] = false;
        $oPro->save($sdf_pdt);
        return true;
    }

    function getFilterByTypeId($p){
        if(!function_exists('goods_filter_of_type')) require(CORE_DIR.'/lib/core/goods.filter_of_type.php');
        return goods_filter_of_type($p , $this);
    }

    function getFilter($p){
        if(!function_exists('goods_get_filter')) require(CORE_DIR.'/lib/core/goods.get_filter.php');
        return goods_get_filter($p , $this);
    }

    function orderBy($id=null){
        $order=array(
            array('label'=>app::get('base')->_('默认'),'sql'=>implode($this->defaultOrder,'')),
            array('label'=>app::get('base')->_('按发布时间 新->旧'),'sql'=>'last_modify desc'),
            array('label'=>app::get('base')->_('按发布时间 旧->新'),'sql'=>'last_modify'),
            array('label'=>app::get('base')->_('按价格 从高到低'),'sql'=>'price desc'),
            array('label'=>app::get('base')->_('按价格 从低到高'),'sql'=>'price'),
            array('label'=>app::get('base')->_('访问周次数'),'sql'=>'view_w_count desc'),
            array('label'=>app::get('base')->_('总访问次数'),'sql'=>'view_count desc'),
            array('label'=>app::get('base')->_('周购买次数'),'sql'=>'buy_count desc'),
            array('label'=>app::get('base')->_('总购买次数'),'sql'=>'buy_w_count desc'),
            array('label'=>app::get('base')->_('评论次数'),'sql'=>'comments_count desc'),
        );
        if($id){
            return $order[$id];
        }else{
            return $order;
        }
    }

    function prepared_import_csv_row($row,$title,&$goodsTmpl,&$mark,&$newObjFlag,&$msg){

        $acti = isset($_GET['acti'])?$_GET['acti']:'goods';
      //error_log(var_export($_GET,1),3,'c:/log/row.txt');  
        $Oimport = kernel::single('ome_io_import_'.$acti);

        if($Oimport){
            $return = $Oimport->prepared_import_csv_row($row,$title,$goodsTmpl,$mark,$newObjFlag,$msg);
            return $return;
        }else{
            trigger_error('导入有误!!!',E_USER_ERROR);
                        }

    }

    function ioSchema2sdf($data,$title,$csvSchema,$key = null){
        $rs = array();
        $subSdf = array();
        foreach( $csvSchema as $schema => $sdf ){
            $sdf = (array)$sdf;
            if( ( !$key && !$sdf[1] ) || ( $key && $sdf[1] == $key ) ){
                eval('$rs["'.implode('"]["',explode('/',$sdf[0])).'"] = $data[$title[$schema]];');
                unset($data[$title[$schema]]);
            /*}else if( ){
                eval('$rs["'.implode('"]["',explode('/',$sdf[0])).'"] = $data[$title[$schema]];');
                unset($data[$title[$schema]]);*/
            }else{
                $subSdf[$sdf[1]] = $sdf[1];
            }
        }
        if(!$key){
            foreach( $subSdf as $k ){
                foreach( $data[$k] as $v ){
                    $rs[$k][] = $this->ioSchema2sdf($v,$title,$csvSchema,$k);
                }
            }
        }
        foreach( $data as $orderk => $orderv ){
            if( substr($orderk,0,4 ) == 'col:' ){
                $rs[ltrim($orderk,'col:')] = $orderv;
            }
        }

        return $rs;

    }


     function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){

        $acti = isset($_GET['acti'])?$_GET['acti']:'goods';

        $Oimport = kernel::single('ome_io_import_'.$acti);

        if($Oimport){
            $return = $Oimport->prepared_import_csv_obj($data,$mark,$goodsTmpl,$msg);
            return $return;
        }else{
            trigger_error('导入有误!!!',E_USER_ERROR);
                    }

    }

    function getProducts($gid, $pid=0){
        $sqlWhere = '';
        if($pid > 0) $sqlWhere = ' AND A.product_id = '.$pid;
        $sql = "SELECT A.*,B.image_default_id FROM sdb_ome_products AS A LEFT JOIN sdb_ome_goods AS B ON A.goods_id=B.goods_id WHERE A.goods_id=".intval($gid).$sqlWhere;
        return $this->db->select($sql);
    }
    function getGoodsIdByBn( $bn , $searchType = 'has') {

        switch($searchType){
            case'nohas':
                $goodsId = $this->db->select('SELECT g.goods_id FROM sdb_ome_goods g LEFT JOIN sdb_ome_products p ON g.goods_id = p.goods_id WHERE g.bn NOT LIKE "%'.$bn.'%" OR p.bn NOT LIKE "%'.$bn.'%"');
                break;
            case'tequal':
                $goodsId = $this->db->select('SELECT g.goods_id FROM sdb_ome_goods g LEFT JOIN sdb_ome_products p ON g.goods_id = p.goods_id WHERE g.bn in( "'.$bn.'") OR p.bn in( "'.$bn.'")');
                break;
            case'has':
            default:
                $goodsId = $this->db->select('SELECT g.goods_id FROM sdb_ome_goods g LEFT JOIN sdb_ome_products p ON g.goods_id = p.goods_id WHERE g.bn LIKE "%'.$bn.'%" OR p.bn LIKE "%'.$bn.'%"');
                break;
            case'head':
                $goodsId = $this->db->select('SELECT g.goods_id FROM sdb_ome_goods g LEFT JOIN sdb_ome_products p ON g.goods_id = p.goods_id WHERE g.bn LIKE "'.$bn.'%" OR p.bn LIKE "'.$bn.'%"');
                break;
            case'foot':
                $goodsId = $this->db->select('SELECT g.goods_id FROM sdb_shopex_goods g LEFT JOIN sdb_shopex_products p ON g.goods_id = p.goods_id WHERE g.bn LIKE "%'.$bn.'" OR p.bn LIKE "%'.$bn.'"');
                break;
        }

        $rs = array();
        if ($goodsId)
        foreach( $goodsId as $key=>$val) {
            if(!in_array($val['goods_id'],$rs)){
                $rs[] = $val['goods_id'];
            }
        }
        return $rs;
    }
    function getGoodsIdByBarcode($barcode){
        $goodsId = $this->db->select('SELECT g.goods_id FROM sdb_ome_goods g LEFT JOIN sdb_ome_products p ON g.goods_id = p.goods_id WHERE p.barcode LIKE "%'.addslashes($barcode).'%" OR g.barcode LIKE "%'.addslashes($barcode).'%"');

        $rs = array();
        if ($goodsId)
        foreach( $goodsId as $key=>$val) {
            if(!in_array($val['goods_id'],$rs)){
                $rs[] = $val['goods_id'];
            }
        }
        return $rs;
    }
    function getMarketableById($gid){
        return $this->db->selectrow('SELECT marketable FROM sdb_ome_goods WHERE goods_id='.$gid);
    }
     function data2local( $data ){
        $title = array();
        if($data)
        foreach( $data as $aTitle ){
            $title[] = $this->charset->utf2local($aTitle);
        }
        return $title;
    }


    function searchOptions(){
        return array(
                'bn'=>app::get('base')->_('货号'),
                'barcode'=>app::get('base')->_('条形码'),
                'name'=>app::get('base')->_('商品名称'),
                'fuzzy_search'=>app::get('base')->_('模糊搜索'),
            );
    }


    function fgetlist_csv(&$data,$filter,$offset){

        $acti = isset($filter['acti'])?$filter['acti']:'goods'; 
        $Oexport = kernel::single('ome_io_export_'.$acti);

        return $Oexport->fgetlist_csv($data,$filter,$offset);
    }

    public function exportName(&$filename,$filter='')
    {
        $acti = isset($_GET['acti'])?$_GET['acti']:'goods'; 
        $Oexport = kernel::single('ome_io_export_'.$acti);

        $filename['name'] = $this->export_name = $Oexport->export_name;

        return $this->export_name;
    }

    function fcount_csv($filter){
        $count = 0;
        if( $filter['_gType'] ){
            return $count;
        }

        //$count = $this->count($filter);

        return 600;
    }

    function export_csv($data){
        $acti = isset($_GET['acti'])?$_GET['acti']:'goods'; 
        $Oexport = kernel::single('ome_io_export_'.$acti);
        echo $Oexport->export_csv($data);
    }

    function io_title( $filter,$ioType='csv' ){
        $acti = isset($_GET['acti'])?$_GET['acti']:'goods'; 
        $Oexport = kernel::single('ome_io_export_'.$acti);
        return $Oexport->io_title($filter,$ioType);
    }



    /*
     * 删除商品
     */
   function pre_recycle($data=null){
       $productObj = &$this->app->model('products');

       $orderItemObj = &$this->app->model('order_items');
       $pkgProductObj = &app::get('omepkg')->model('pkg_product');

       foreach ($data as $goods){
           if($goods['goods_id']){
               $products = $productObj->getList('product_id,store,store_freeze', array('goods_id'=>$goods['goods_id']), 0,-1);
               foreach ($products as $product){
                   $orders = $orderItemObj->getList('order_id', array('product_id'=>$product['product_id']), 0,-1);
                   $pkgProducts = $pkgProductObj->getList('goods_id', array('product_id'=>$product['product_id']), 0,-1);
                   if($orders || $pkgProducts || $product['store'] || $product['store_freeze']){
                       return false;
                   }else{
                       //删除基础档案对应关系
                       $product_id = $product['product_id'];
                       $productObj->db->exec("DELETE FROM sdb_console_foreign_sku WHERE inner_product_id=$product_id");
                   }
               }
           }
       }
       return true;
   }

   function pre_restore(&$data = null){
       $data['need_delete'] = true;
       if($data['product']){
           $bns = array();
           foreach($data['product'] as $p){
               $bns[] = $p['bn'];
           }

           $productObj = &$this->app->model('products');
           $exist_p = $productObj->getList('product_id',array('bn'=>$bns));
           if(is_array($exist_p) && count($exist_p)>0){
               return false;
           }
       }

       return true;
   }

   function checkIsUse(& $goods){
      $pids = array();
      foreach($goods['product'] as $product_id=>$p){
          $pids[] = $product_id;
          $p['is_use'] = 0;
          $goods['product'][$product_id] = $p;
      }
      $rows = $this->db->select('SELECT COUNT(item_id) AS _counts,product_id FROM sdb_ome_order_items WHERE product_id IN('.implode(',',$pids).') GROUP BY product_id ORDER BY product_id');
      foreach($rows as $row){
          if($row['_counts']>0){
              $goods['product'][$row['product_id']]['is_use'] = 1;
          }
      }
   }

   function finish_import_csv(){

       $op_name = kernel::single('desktop_user')->get_login_name();
       $op_id = kernel::single('desktop_user')->get_id();
       $opinfo = array('op_id'=>$op_id,'op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()));
       
	    $goodsdate = $this->g_data;
	    unset($this->g_data);
        $oQueue = &app::get('base')->model('queue');
		 $count = 0;
        $limit = 50;
        $page = 0;
		foreach($goodsdate as $goodSdf){
				if($count < $limit){
						$count ++;
					}else{
						$count = 0;
						$page ++;
					}
					 $goodsSdfs[$page][] = $goodSdf;
		}
		  foreach($goodsSdfs as $v){

            $queueData = array(
                'queue_title'=>$this->g_title,
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => $this->params['mdl']?$this->params['mdl']:'goods',
                    'opinfo'=>$opinfo
                ),
                'worker'=>'ome_goods_import.run',
            );

            $oQueue->save($queueData);

        }

		 app::get('base')->model('queue')->flush();
	}
	function saveGoods(&$goods,$difff=null,$mustUpdate = null){
	    if( !$goods['bn'] ) $goods['bn'] = strtoupper(uniqid('g'));
        if( array_key_exists( 'spec',$goods ) ){
            if( $goods['spec'] )
                foreach( $goods['spec'] as $gSpecId => $gSpecOption ){
                #对商品表字段生成商品规格信息
                $goods['spec_desc'][$gSpecId] = $gSpecOption['option'];
            }
        } else{
            $goods['spec_desc'] = null;
        }
        $goodsStatus = false;
        if(is_array($goods['product'])){
            foreach( $goods['product'] as $pk => $pv ){
                #生成货品与商品的操作时间
                if( !$pv['uptime'] ){
                    $goods['uptime'] = $goods['last_modify'] = $goods['product'][$pk]['uptime'] = time();
                } else{
                    $goods['uptime'] = $goods['last_modify'] = $goods['product'][$pk]['uptime'];
                }
                $goods['product'][$pk]['name'] = $goods['name'];
                if( $pv['status'] != 'false' ) $goodsStatus = true;
            }
        }
        if( !$goodsStatus ){
            $goods['status'] = 'false';
        }
        if(!empty($difff)){
            #删除数据
            $obj_product = &app::get('ome')->model('products');
            foreach($difff as $product_id){
                $obj_product->delete(array('product_id'=>$product_id));
            }
        }
        unset($goods['spec']);
        #如果goods_id不存在,新增数据;如果存在,更新相关数据
        if(empty($goods['goods_id'])){
            return parent::save($goods,$mustUpdate);
        }else{
            #商品表列和数据的键、值对
            $plainData = $this->sdf_to_plain($goods);
            #对商品表进行更新操作，
            if(!$this->db_save($plainData,$mustUpdate)){
                return false;
            }
            #获取货品表的数据
            $_productData = utils::apath($goods,array('product'));
                    $arr_product = $_productData;
                    $obj_product = &$this->app->model('products');
                    #重组product表的数据
                    foreach( $_productData as $key=>$v){
                        $arr_product[$key]['cost'] =  $v['price']['cost']['price'];
                        $arr_product[$key]['mktprice'] = $v['price']['mktprice']['price'];
                        $arr_product[$key]['price'] = $v['price']['price']['price'];
                        if(!empty($v['spec_desc'])){
                            $arr_product[$key]['spec_desc'] = serialize($v['spec_desc']);
                            $arr_product[$key]['spec_info'] = implode('、',$v['spec_desc']['spec_value']);
                        }
                        #把商品goods_id加到货品数据当中去
                        $arr_product[$key]['goods_id'] = $goods['goods_id'];
                        #对货品product_id进行判断，如果存在，添加数据;如果不存在,新增数据
                        if($arr_product[$key]['product_id']){
                            $this->changeDateByType('update',$arr_product[$key]);
                            #更新WMS数据
                            if (app::get('console')->is_installed()) {
                                app::get('console')->model('foreign_sku')->update_status($arr_product[$key]['product_id'],$v['bn']);
                            }
                            
                        }else{
                              $this->changeDateByType('insert',$arr_product[$key]);
                         }
                    }
            return true;
        }
	}
    #新增货品时，检测条形码,不允许重复
    function checkBarcodeById($barcode=null){
        $sql = 'select count(barcode) num from sdb_ome_products where barcode = '."'$barcode'";
        return $this->db->selectRow($sql);
    }

    #根据product_id或者货号bn,检测库存是否存在,如果库存存在，不允许删除货品
    function checkStoreById($product_id=null,$bn=null){
        if($product_id){
            $sql = 'select bn,store from sdb_ome_products where product_id='.$product_id ;
        }elseif($bn){
            $sql = 'select store from sdb_ome_products where bn='."'$bn'" ;
        }
        return $this->db->selectRow($sql);
    }

    #根据货号bn，检测出入库明细，如果存在货号对应数据，则货品不能删除
    function checkIostockByBn($bn = null){
        $sql = 'select count(*) num from sdb_ome_iostock where bn='."'$bn'";
        return $this->db->selectRow($sql);
    }

    #根据货号bn，检测订单信息,如果订单中，使用了对应货号，则该货品不能删除
    function checkOrderByBn($bn = null){
        $sql = 'select count(*) num from sdb_ome_order_items where bn='."'$bn'";
        return $this->db->selectRow($sql);
    }
    #根据货号，检测是否有捆绑商品
    function checkPkgGoods($bn = null){
        $sql = 'select count(*) num from sdb_omepkg_pkg_product where bn='."'$bn'";
        return $this->db->selectRow($sql);
    }
    #根据货号，检测是否有采购记录
    function checkPurchaseByBn($bn = null){
        $sql = 'select count(*) num from sdb_purchase_po_items where bn='."'$bn'";
        return $this->db->selectRow($sql);
    }
    #根据货号,检测是否有盘点记录
    function checkInventoryByBn($bn = null){
        $sql = 'select count(*) num from sdb_taoguaninventory_inventory_items where bn='."'$bn'";
        return $this->db->selectRow($sql);
    }
    
    public function iostockExsit($bns = array()) 
    {
        $sql = 'SELECT bn from sdb_ome_iostock WHERE bn in("'.implode('","',(array)$bns).'") GROUP BY bn';
        $rows = $this->db->select($sql);

        $iostockBn = array();
        foreach ($rows as $key=>$row) {
            $iostockBn[] = $row['bn'];
        }

        return $iostockBn;
    }

	#根据不同商品类型，处理相关数据
	function changeDateByType($type=null,$data=null){
        $pk = $data['product_id'];
        $visibility = "'".$data['visibility']."',";
        $bn = "'".$data['bn']."',";
        $spec_desc = "'".$data['spec_desc']."',";
        $picurl = "'".$data['picurl']."',";
        $barcode = "'".$data['barcode']."',";
        $price = "'".$data['price']."',";
        $weight = "'".$data['weight']."',";
        $unit = "'".$data['unit']."',";
        $uptime = "'".$data['uptime']."',";
        $last_modified = $uptime;
        $name = "'".mysql_escape_string($data['name'])."',";
        $cost = "'".$data['cost']."',";
        $mktprice = "'".$data['mktprice']."',";
        $spec_info = "'".$data['spec_info']."',";
        $goods_id = "'".$data['goods_id']."'";
        if('insert' == $type){
            $sql = 'insert into sdb_ome_products(
                `visibility`,
                `bn`,
                `spec_desc`,
                `picurl`,
                `barcode`,
                `price`,
                `weight`,
                `unit`,
                `uptime`,
                `last_modified`,
                `name`,
                `cost`,
                `mktprice`,
                `spec_info`,
                `goods_id`
        )values('.
                $visibility.
                $bn.
                $spec_desc.
                $picurl.
                $barcode.
                $price.
                $weight.
                $unit.
                $uptime.
                $last_modified.
                $name.
                $cost.
                $mktprice.
                $spec_info.
                $goods_id.')';
        }elseif('update' == $type){
            $sql = "update sdb_ome_products set
                `visibility`=$visibility
                `bn`=$bn
                `spec_desc`=$spec_desc
                `picurl`=$picurl
                `barcode`=$barcode
                `price`=$price
                `weight`= $weight
                `unit`=$unit
                `uptime`=$uptime
                `last_modified`=$last_modified
                `name`=$name
                `cost`=$cost
                `mktprice`=$mktprice
                `spec_info`=$spec_info
                `goods_id`=$goods_id
                where product_id=$pk";
        }
    	 return $this->db->exec($sql);
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_goods') {
            if (isset($params['acti']) && $params['acti'] == 'cost') {
                $type .= '_goodsMananger_allList_template';
            }
            elseif (isset($params['_gType'])) {
                $type .= '_goodsBatProcess_batUpload';
            }
            else {
                $type .= '_goodsMananger_allList';
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
        $type = 'goods';
        if ($logParams['app'] == 'omecsv' && $logParams['ctl'] == 'admin_to_import') {
            if ($params['acti'] == 'cost' && $logParams['act'] == 'cost_import') {
                $type .= '_goodsMananger_costAndWeight_bat';
            }
            else {
                $type .= '_goodsBatProcess_batUpload';
            }
                
        }
        $type .= '_import';
        return $type;
    }
}
