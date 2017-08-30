<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 * 
 */

class ome_io_import_cost {

    function __construct($app){
        $this->app = $app;
    }

    var $ioSchema = array(
        'csv' => array(
            '*:货号' => array('bn','product'),
            'col:商品名称' => array('name','product'),            
            'col:成本价' => array('price/cost/price','product'),
            'col:重量' => array('weight','product'),            
        ),
    );

    function prepared_import_csv_row($row,$title,&$goodsTmpl,&$mark,&$newObjFlag,&$msg){
        if(substr($row[0],0,1) == '*'){
            $mark = 'title';
            return array_flip($row);
        }else{
        	$mark = 'contents';
            $newObjFlag = true;
        	return $row;
        }
        return null;
    }

    /**
     * 批量导入商品成本价
     *
     * @return void
     * @author 
     **/
    function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = '')
    {
        $gData = $data['contents'];
        $gTitle = $data['title'];
        if (empty($gData) || !is_array($gData[0])) return null;
        $rs = array();
        if(!$gData[0][$gTitle['*:货号']] || empty($gData[0][$gTitle['*:货号']])){
            $msg = array( 'error'=>$gData[0][$gTitle['*:货号']].'货号不能为空！' );
            return false;
        }
        if(!isset($gData[0][$gTitle['col:成本价']]) || $gData[0][$gTitle['col:成本价']] < 0){
            $msg = array( 'error'=>$gData[0][$gTitle['*:货号']].'成本价不能为空！' );
            return false;
        }
        if(!isset($gData[0][$gTitle['col:重量']]) || $gData[0][$gTitle['col:重量']] < 0){

            $msg = array( 'error'=>$gData[0][$gTitle['*:货号']].'重量必须大于等于0！' );
            return false;
        }

        $oGoods = &app::get('ome')->model('goods');
        
        $oProducts = &app::get('ome')->model('products');

        $productId = $oProducts->dump(array('bn|tequal'=>$gData[0][$gTitle['*:货号']]),'product_id,goods_id,bn');
        if(!$productId){
            $msg = array( 'error'=>'货号'.$gData[0][$gTitle['*:货号']].'不存在！' );
            return false;
        }

        $goods = $oGoods->dump(array('goods_id'=>$productId['goods_id']),'*');

        //$update_col['goods_id'] = $productId['goods_id'];
        //$update_col['bn'] = $goods['bn'];
        $update_col['cost'] = $gData[0][$gTitle['col:成本价']];   
        $update_col['weight'] = $gData[0][$gTitle['col:重量']];
        //$update_col['name'] = $gData[0][$gTitle['col:商品名称']];
       
        
        if(isset($goods['spec'])){
            #开启规格的
            $oProducts->update($update_col,array('product_id'=>$productId['product_id']));#更新货品表
        }else{
            #没开启规格的,货品表的成本与商品表都要更新
            $oGoods->update($update_col,array('goods_id'=>$productId['goods_id']));
            $oProducts->update($update_col,array('product_id'=>$productId['product_id']));#更新货品表
        }

        $return['product_id'] = $productId['product_id'];
        $return['bn'] = $productId['bn'];
        $return['price']['cost']['price'] = $gData[0][$gTitle['col:成本价']];   
        $return['weight'] = $gData[0][$gTitle['col:重量']];          
        $return['name'] = $gData[0][$gTitle['col:商品名称']];
        $oGoods->g_title = '商品成本价导入';

        $oGoods->g_data[] = $return;
        $oGoods->params = array('mdl'=>'products','is_update'=>true);
        return $return;
    }

}