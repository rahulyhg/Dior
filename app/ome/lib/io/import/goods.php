<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_io_import_goods {

    function __construct($app){
       $this->app = $app;
    }

    function prepared_import_csv_row($row,$title,&$goodsTmpl,&$mark,&$newObjFlag,&$msg){

        if (empty($row)){
            if($this->delwarning) $msg['warning'] = $this->delwarning['warning'];
            
            if(isset($this->same_bn)){
                if(count($this->same_bn) > 10){
                    for($i=0;$i<10;$i++){
                        $temp[] = current($this->same_bn);
                        next($this->same_bn);
                    }
                    $more = "...";
                }else{
                    $more = "";
                }
                $this->error_msg[] = "文件中以下商品货号重复：".implode(",",$this->same_bn).$more;
            }
            if(isset($this->same_barcode)){
                if(count($this->same_barcode) > 10){
                    for($i=0;$i<10;$i++){
                        $temp[] = current($this->same_barcode);
                        next($this->same_barcode);
                    }
                    $more = "...";
                }else{
                    $more = "";
                }
                $this->error_msg[] = "文件中以下商品条形码重复：".implode(",",$this->same_barcode).$more;
            }
            
            if(isset($this->same_good_bn)){
                $good_bns = $this->FetchRepeatMemberInArray($this->same_good_bn);
                if(!empty($good_bns)){
                    if(count($good_bns) > 10){
                        for($i=0;$i<10;$i++){
                            $temp[] = current($good_bns);
                            next($good_bns);
                        }
                        $more = "...";
                    }else{
                        $more = "";
                    }
                    $this->error_msg[] = "文件中以下商品编号重复：".implode(",",$good_bns).$more;
                }
            }
            if(!empty($this->error_msg)){
                unset($this->bn_data);
                $msg['error'] .= implode("\\n",$this->error_msg);
            }
            if($msg){
                
                return false;
            }
           
        }

        if( substr($row[0],0,1) == '*' ){
            $mark = 'title';
            $newObjFlag = true;



            return array_flip($row);

        }else{
            //必填选项判断
            if(isset($this->good_nums)){
                $this->good_nums ++;
                if($this->good_nums > 5000){
                    $msg['error'] = "导入的商品数据量过大，请减少到5000单以下";
                    return false;
                }
            }else{
                $this->good_nums = 0;
            }

            $mark = 'contents';

            /*
             * 对信息不全的行进行检查
             *
             */
            if( empty($row[$title['ibn:货号']]) && empty($row[$title['col:规格']]) ){
                if(empty($row[0])){
                    $this->error_msg[] = "第".($this->good_nums+2)."行  商品类型不能为空";
                }
                $this->error_msg[] = "第".($this->good_nums+2)."行  货号和规格不能同时为空";
                
                $newObjFlag = true;
            
            }
            /*
             * 有规格的
             * 商品状态为更新（默认为新增）时，必须含有商品编号
             * 商品状态为新增（默认为新增）时，必须含有商品名称
             */
            $oGType = &app::get('ome')->model('goods_type');
            $oGoods = &app::get('ome')->model('goods');
            if( empty($row[$title['ibn:货号']]) && !empty($row[$title['col:规格']]) ){

                if(empty($row[0])){
                    $this->error_msg[] = "第".($this->good_nums+2)."行  商品类型不能为空";
                }else{
                   $goodsTmpl['gtype'] = $oGType->dump(array('name'=>trim($row[0])),'*','default');
                   if( !$goodsTmpl['gtype'] ){
                       $this->error_msg[] = "第".($this->good_nums+2).'行  商品类型:'.trim( $row[0] ).' 不存在';
                    }
                }
                if($row[$title['col:商品状态']]!='更新'){
                    if(empty($row[$title['col:商品名称']])){
                        $this->error_msg[] = "第".($this->good_nums+2)."行  新增商品必须带有商品名称";
                    }

                }else{
                    if(empty($row[$title['bn:商品编号']])){
                        $this->error_msg[] = "第".($this->good_nums+2)."行  更新商品必须带有商品编号";
                        
                    }
                }
                $newObjFlag = true;
                if(!empty($row[1])){
                    $this->same_good_bn[] = $row[1];
                }

            }
            /*
             * 无规格的
             * 商品状态为更新（默认为新增）时，必须含有商品编号
             * 商品状态为新增（默认为新增）时，必须含有商品名称
             */
            if( !empty($row[$title['ibn:货号']]) && empty($row[$title['col:规格']]) ){
                //判断商品类型
                if(empty($row[0])){
                    $this->error_msg[] = "第".($this->good_nums+2)."行  商品类型不能为空";
                }else{
                    $goodsTmpl = array();
                    $goodsTmpl['gtype'] = $oGType->dump(array('name'=>trim($row[0])),'*','default');
                    if( !$goodsTmpl['gtype'] ){
                       $this->error_msg[] = "第".($this->good_nums+2).'行  商品类型:'.trim( $row[0] ).' 不存在';
                    }
                }
                if($row[$title['col:商品状态']]!='更新'){
                    if(empty($row[$title['col:商品名称']])){
                        $this->error_msg[] = "第".($this->good_nums+2)."行  新增商品必须带有商品名称";

                    }
                     
                }else{
                    if(empty($row[$title['bn:商品编号']])){
                        $this->error_msg[] = "第".($this->good_nums+2)."行  更新商品必须带有商品编号";
                        
                    }
                }
                $newObjFlag = true;
                if(!empty($row[1])){
                    $this->same_good_bn[] = $row[1];
                }
                
            }

            if (!empty($row[$title['col:重量']]) && !is_numeric($row[$title['col:重量']])){
                $this->error_msg[] = "第".($this->good_nums+2)."行  重量必须为大于等于0的数字";

            }
            if (!empty($row[$title['col:成本价']]) && !is_numeric($row[$title['col:成本价']])){
                $this->error_msg[] = "第".($this->good_nums+2)."行  成本价必须为大于等于0的数字";  

            }
            if (!empty($row[$title['col:销售价']]) && !is_numeric($row[$title['col:销售价']])){
                $this->error_msg[] = "第".($this->good_nums+2)."行  销售价必须为大于等于0的数字";  

            }
            if ($this->bn_data)
            foreach ($this->bn_data as $v){
                if ($v[2] && $v[2] == $row[2]){
                   $this->same_barcode[] = $row[2];
                   $this->flag = true;
                }
                if ($v[3] && $v[3] == $row[3]){
                   $this->same_bn[] = $row[3];
                   $this->flag = true;
                }
            }
            $this->bn_data[] = $row;
            return $row;
        }
        return null;
    }

    /**
     * 批量导入商品
     *
     * @return void
     * @author 
     **/
    function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = '')
    {
        if(isset($this->num)){
            $this->num++;
        }else{
            $this->num = 1;
        }
        $gData = &$data['contents'];
        $gTitle = $data['title'];
        //判断传进来的数据是否包含商品信息，不包含则直接返回空
        if (empty($gData) || !is_array($gData[0])) return null;
        $rs = array();
        //id
        $oGType = &app::get('ome')->model('goods_type');
        $oGoods = &app::get('ome')->model('goods');
        //通过商品编号获取id
        $goodsId = $oGoods->dump(array('bn|tequal'=>$gData[0][$gTitle['bn:商品编号']],'_bn_search'=>'tequal'),'goods_id');

        foreach( $gTitle as $colk => $colv ){

            if( (substr( $colk,0,6 ) == 'price:' || in_array( $colk , array('col:市场价','col:销售价') ) ) && $gData[0][$gTitle[$colk]] !== 0 && !$gData[0][$gTitle[$colk]] ){
                unset($gData[0][$gTitle[$colk]]);
            }
        }

        //品牌
        $oBrand = &app::get('ome')->model('brand');
        if( !$gData[0][$gTitle['col:品牌']] ){
            $brandId = array('brand_id'=>null);
        }else{
            $brandId = $oBrand->dump(array('brand_name'=>$gData[0][$gTitle['col:品牌']]),'brand_id');
            if( !$brandId['brand_id'] )
                $this->error_msg[] = '第'.$this->num.'行  品牌：'.$gData[0][$gTitle['col:品牌']].'不存在';
        }
        $gData[0][$gTitle['col:品牌']] = trim($brandId['brand_id'] ? $brandId['brand_id'] : null);
        $rs = $gData[0];

        //只有一个货品的时候
        if( count( $gData ) == 1 ){
            $this->handle_good_data($gData,$gTitle,$goodsId,$rs);
        }else{
            $this->handle_goods_data($gData,$gTitle,$goodsId,$rs);
        }
        $return =  $oGoods->ioSchema2sdf( $rs,$gTitle, $oGoods->ioSchema['csv'] );
        if ($return['product'])
        foreach( $return['product'] as $pk => $pv ){
            $return['product'][$pk]['name'] = $return['name'];
            if (!$return['product'][$pk]['weight'])
            $return['product'][$pk]['weight'] = $rs[$gTitle['col:重量']] ? $rs[$gTitle['col:重量']] : 0;
        }
        $return['unit'] = $return['unit'] ? $return['unit'] : null;
        $return['brand']['brand_id'] = $return['brand']['brand_id'] ? $return['brand']['brand_id'] : null;
        $goodsTmpl['gtype'] = $oGType->dump(array('name'=>trim($gData[0][$gTitle['*:商品类型']])),'*','default');
        $return['type']['type_id'] = intval( $goodsTmpl['gtype']['type_id'] );
        $oGoods->g_title = '商品导入';
        $oGoods->g_data[] = $return;
        return $return;
    }
    /**
     * 无规格商品信息处理
     * 
     */
    public function handle_good_data($gData,$gTitle,$goodsId,&$rs){
        
        if (isset($gTitle['col:销售价'])) {
            $gData[0][$gTitle['col:销售价']] = $gData[0][$gTitle['col:销售价']] ? $gData[0][$gTitle['col:销售价']] : 0;
        }
        if (isset($gTitle['col:成本价'])) {
            $gData[0][$gTitle['col:成本价']] = $gData[0][$gTitle['col:成本价']] ? $gData[0][$gTitle['col:成本价']] : 0;
        }
        if (isset($gTitle['col:重量'])) {
            $gData[0][$gTitle['col:重量']] = $gData[0][$gTitle['col:重量']] ? $gData[0][$gTitle['col:重量']] : 0;
        }
        unset($rs[$gTitle['col:规格']] );
        $rs['product'][0] = $gData[0];
        //商品简介长度判断
        if (strlen($gData[0][$gTitle['col:商品简介']])>250){
            $this->error_msg[] = '第'.$this->num.'行  商品简介过长';
        }
        $gData[0][$gTitle['ibn:货号']] = trim($gData[0][$gTitle['ibn:货号']]);
        $gData[0][$gTitle['barcode:条形码']] = trim($gData[0][$gTitle['barcode:条形码']]);
        //检查普通商品货号是否在捆绑货品中已存在
        $checkBn = kernel::single('omepkg_ome_product')->checkProductByBn($gData[0][$gTitle['ibn:货号']]);
        if($checkBn){
            $this->error_msg[] = '第'.$this->num.'行  货号：'.$gData[0][$gTitle['ibn:货号']].'在捆绑商品中已存在';
        }
        $oPro = &app::get('ome')->model('products');

        $bnProId = $oPro->dump( array('bn'=>$gData[0][$gTitle['ibn:货号']],'_bn_search'=>'tequal'),'product_id,goods_id' );
        if(!empty($gData[0][$gTitle['barcode:条形码']])){
            $bcProId = $oPro->dump( array('barcode'=>$gData[0][$gTitle['barcode:条形码']],'_barcode_search'=>'tequal'),'product_id,goods_id' );
        }

        //更新商品信息
        if($gData[0][$gTitle['col:商品状态']]=='更新'){
            if(empty($goodsId['goods_id']) && !empty($gData[0][$gTitle['bn:商品编号']])){
                $this->error_msg[] = '第'.$this->num.'行  商品编号：'.$gData[0][$gTitle['bn:商品编号']].'的商品不存在';
                
            }
            if(empty($bnProId['goods_id']) || $bnProId['goods_id']!=$goodsId['goods_id']){
                $this->error_msg[] = '第'.$this->num.'行  货号：'.$gData[0][$gTitle['ibn:货号']].'在要更新的商品中不存在';
            }
            if(!empty($bcProId) && $bcProId['goods_id']!=$goodsId['goods_id']){
                $this->error_msg[] = '第'.$this->num.'行  条形码:'.$gData[0][$gTitle['barcode:条形码']].'已经存在';
            }
            $rs['product'][0]['barcode'] = $gData[0][$gTitle['barcode:条形码']];
            if($goodsId['goods_id'] ){
                $rs['col:goods_id'] = $goodsId['goods_id'];
            }
            if( $bnProId['product_id'] ){
                $rs['product'][0]['col:product_id'] = $bnProId['product_id'];
            }

        }else{
        //添加商品信息
            if(!empty($goodsId['goods_id'])){
                $this->error_msg[] = '第'.$this->num.'行  商品编号：'.$gData[0][$gTitle['bn:商品编号']].'已经存在';
            }
            if($bnProId['goods_id']){
                $this->error_msg[] = '第'.$this->num.'行  货号：'.$gData[0][$gTitle['ibn:货号']].'已经存在';
            }
            if(!empty($bcProId) && !empty($gData[0][$gTitle['barcode:条形码']])){
                $this->error_msg[] = '第'.$this->num.'行  条形码:'.$gData[0][$gTitle['barcode:条形码']].'已经存在';
            }
            $rs['product'][0]['barcode'] = $gData[0][$gTitle['barcode:条形码']];
            $rs['product'][0]['product_bn'] = $gData[0][$gTitle['ibn:货号']];
        }
    }
    /**
     * 包含规格商品信息处理
     * 
     */
    public function handle_goods_data($gData,$gTitle,$goodsId,&$rs){
        
        reset($gData);
        $oSpec = &app::get('ome')->model('specification');
        $oPro = &app::get('ome')->model('products');
        //判断规格值
        foreach( explode('|',$gData[0][$gTitle['col:规格']] ) as $speck => $specName ){
            $spec[$speck] = array(
                'spec_name' => $specName,
                'option' => array(),
            );
        }
        //商品简介长度判断
        if (strlen($gData[0][$gTitle['col:商品简介']])>250){
            $this->error_msg[] = '第'.$this->num.'行  商品简介过长';
        }
        if($gData[0][$gTitle['col:商品状态']]=='更新'){
            if(empty($goodsId['goods_id'])){
                $this->error_msg[] = '第'.$this->num.'行  商品编号：'.$gData[0][$gTitle['bn:商品编号']].'在要更新的商品中不存在';
            }
        }else{
            if(!empty($goodsId['goods_id'])){
                $this->error_msg[] = '第'.$this->num.'行  商品编号：'.$gData[0][$gTitle['bn:商品编号']].'已经存在';
            }
        }
        // 获取所有已经存在的货品
        $oldProductList = $oPro->getList('product_id,bn',array('goods_id'=>$goodsId['goods_id']));
        $oldBnkArr = array();
        foreach ($oldProductList as $key => $value) {
            $oldBnkArr[$value['bn']] = $value['product_id'];
        }
        unset($oldProductList);
        while( ( $aPro = next($gData) ) ){
            $this->num++;
            //必填项判断
            if (!$aPro[$gTitle['ibn:货号']]){
                 $this->error_msg[] = '第'.$this->num.'行  货号不能为空' ;
            }
            // 销售价判断
            if (isset($gTitle['col:销售价'])) {
                $aPro[$gTitle['col:销售价']] = $aPro[$gTitle['col:销售价']] ? $aPro[$gTitle['col:销售价']] : 0;
            }
            if (isset($gTitle['col:成本价'])) {
                $aPro[$gTitle['col:成本价']] = $aPro[$gTitle['col:成本价']] ? $aPro[$gTitle['col:成本价']] : 0;
            }
            if (isset($gTitle['col:重量'])) {
                $aPro[$gTitle['col:重量']] = $aPro[$gTitle['col:重量']] ? $aPro[$gTitle['col:重量']] : 0;
            }
            $aPro[$gTitle['ibn:货号']] = trim($aPro[$gTitle['ibn:货号']]);
            $aPro[$gTitle['barcode:条形码']] = trim($aPro[$gTitle['barcode:条形码']]);
            $aProk = key( $gData );
            //检查普通商品货号是否在捆绑货品中已存在
            $checkBn = kernel::single('omepkg_ome_product')->checkProductByBn($aPro[$gTitle['ibn:货号']]);
            if($checkBn){
                $this->error_msg[] = '第'.$this->num.'行  货号:'.$aPro[$gTitle['ibn:货号']].' 在捆绑商品中已存在';
            }
            $bnProId = $oPro->dump( array('bn'=>$aPro[$gTitle['ibn:货号']],'_bn_search'=>'tequal'),'product_id,goods_id' );
            $bcProId = $oPro->dump( array('barcode'=>$aPro[$gTitle['barcode:条形码']],'_barcode_search'=>'tequal'),'product_id,goods_id' );
            
            if($gData[0][$gTitle['col:商品状态']]=='更新'){
                if($aPro[$gTitle['col:商品状态']]!='新增'){
                    if(!empty($aPro[$gTitle['ibn:货号']]) && $bnProId['goods_id']!=$goodsId['goods_id']){
                        $this->error_msg[] = '第'.$this->num.'行  货号：'.$aPro[$gTitle['ibn:货号']].'在要更新的商品中不存在';
                    }
                    if( !empty($bcProId) &&  $bnProId['product_id'] != $bcProId['product_id'] ){
                        $this->error_msg[] = '第'.$this->num.'行  条形码:'.$aPro[$gTitle['barcode:条形码']].'已经存在';
                    }
                }else{
                    if(!empty($bnProId['goods_id'])){
                        $this->error_msg[] = '第'.$this->num.'行  货号：'.$aPro[$gTitle['ibn:货号']].'已经存在';
                    }
                    if(!empty($bcProId['goods_id'])){
                        $this->error_msg[] = '第'.$this->num.'行  条形码:'.$aPro[$gTitle['barcode:条形码']].'已经存在';
                    }
                }
                $aPro['col:product_id'] = $bnProId['product_id'];
                if($goodsId['goods_id'] ){
                    $rs['col:goods_id'] = $goodsId['goods_id'];
                }
            }else{
                if(!empty($bnProId['goods_id'])){
                    $this->error_msg[] = '第'.$this->num.'行  货号：'.$aPro[$gTitle['ibn:货号']].'已经存在';
                }
                if(!empty($bcProId) && !empty($aPro[$gTitle['barcode:条形码']])){
                    $this->error_msg[] = '第'.$this->num.'行  条形码:'.$aPro[$gTitle['barcode:条形码']].'已经存在';
                }
            }
            $rs['product'][$aProk] = $aPro;
            foreach( explode('|',$aPro[$gTitle['col:规格']]) as $specvk => $specv ){
                $spec[$specvk]['option'][$specv] = $specv;
            }

            $newBnKArr[$aPro[$gTitle['ibn:货号']]] = $aPro[$gTitle['ibn:货号']];
        }

        // 比较是否有删除操作
        if($gData[0][$gTitle['col:商品状态']]=='更新') {
            if (count($gData) > 1) {
                $intersect = array_intersect_key($oldBnkArr, $newBnKArr);
                if ($intersect != $oldBnkArr) {
                    $this->error_msg[] = '商品编号:' . $gData[0][$gTitle['bn:商品编号']] . '未包含现有货品';
                }
            }
        }
        if ($spec){
            foreach($spec as $sk => $aSpec){
                $specIdList = $oSpec->getSpecIdByAll($aSpec);

                if ($specIdList){
                    foreach( $specIdList as $sv ){
                        if( array_key_exists($sv['spec_id'],$goodsTmpl['gtype']['spec'] ) ){
                            $spec[$sk]['spec_id'] = $sv['spec_id'];
                        }
                    }
                }
                if( !$spec[$sk]['spec_id'] ){
                    $spec[$sk]['spec_id'] = $specIdList[0]['spec_id'];
                }
                if( !$spec[$sk]['spec_id'] ){
                    $spec_option = implode(',',$aSpec['option']);
                    $this->error_msg[] = '商品编号:'.$gData[0][$gTitle["bn:商品编号"]].'规格:'.$aSpec['spec_name'].':'.$spec_option.'出现错误请检查';
                }
                $spec[$sk]['option'] = $oSpec->getSpecValuesByAll($spec[$sk]);
            }
        }
        $pItem = 0;
        if ($rs['product']){
            foreach( $rs['product'] as $prok => $prov ){
                if( !($pItem++) )$rs['product'][$prok]['col:default'] = 1;
                $proSpec = explode('|',$prov[$gTitle['col:规格']]);
                $rs['product'][$prok]['col:spec_info'] = implode(',',$proSpec);
                $rs['product'][$prok]['col:barcode'] = $prov[$gTitle['barcode:条形码']];
                $rs['product'][$prok]['col:weight'] = $prov[$gTitle['col:重量']];
                $rs['product'][$prok]['col:picurl'] = $prov[$gTitle['col:图片地址']];
                $rs['product'][$prok]['col:cost'] = $prov[$gTitle['col:成本价']];
                $rs['product'][$prok]['col:unit'] = $prov[$gTitle['col:单位']];
                if($prov['col:product_id']) $rs['product'][$prok]['col:product_id'] = $prov['col:product_id'];
                if ($proSpec)
                foreach( $proSpec as $aProSpeck => $aProSpec ){
                    $rs['product'][$prok]['col:spec_desc']['spec_value'][$spec[$aProSpeck]['spec_id']] = $spec[$aProSpeck]['option'][$aProSpec]['spec_value'];
                    $rs['product'][$prok]['col:spec_desc']['spec_private_value_id'][$spec[$aProSpeck]['spec_id']] = $spec[$aProSpeck]['option'][$aProSpec]['private_spec_value_id'];
                    $rs['product'][$prok]['col:spec_desc']['spec_value_id'][$spec[$aProSpeck]['spec_id']] = $spec[$aProSpeck]['option'][$aProSpec]['spec_value_id'];
                }
            }
        }
        unset( $rs[$gTitle['col:规格']] );
        if ($spec)
        foreach( $spec as $sk => $sv ){
            foreach( $sv['option'] as $psk => $psv ){
                $rs[$gTitle['col:规格']][$sv['spec_id']]['option'][$psv['private_spec_value_id']] = $psv;
            }
        }
    }
    /*
     * 获取数组中重复的数据
     */
    function FetchRepeatMemberInArray($array) {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique ( $array );
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc ( $array, $unique_arr );
        return $repeat_arr;
    } 
    
    
}