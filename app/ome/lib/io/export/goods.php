<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_io_export_goods {

    function __construct($app){
        $this->app = $app;
        $this->charset = kernel::single('base_charset');
        $this->goods = &$this->app->model('goods');
        $this->io = kernel::single('desktop_io_type_csv');
        $this->export_name = '商品列表';
    }

    function fgetlist_csv(&$data,$filter,$offset){
        if( $filter['_gType'] ){
            $title = array();
            if(!$data['title'])$data['title'] = array();
            //$data['title'][''.$filter['_gType']] = '"'.implode('","',$io->data2local( $this->goods->io_title(array('type_id'=>$filter['_gType']))) ).'"';
            //$data['content']['goods'][] =  '"'.implode('","',$io->data2local( $this->goods->io_title(array('type_id'=>$filter['_gType']))) ).'"';
            $data['title']['goods'] = '"'.implode('","',$this->io->data2local( $this->goods->io_title(array('type_id'=>$filter['_gType']))) ).'"';
            return false;
        }

        $this->is_title = false;
        // 获取所有商品类型
        if ($offset == 0) {
            $oGtype = &$this->app->model('goods_type');
            if(isset($filter['type_id'])){
                $this->_oGtypeList = $oGtype->getList('type_id,name',array('type_id'=>$filter['type_id']));
            }else{
                $this->_oGtypeList = $oGtype->getList('type_id,name');
            }
            $this->_offset = 0;
            $this->is_title = true;
        }

        $types_count = count($this->_oGtypeList);

        do {
            $type = $this->_oGtypeList[0];
            if (empty($type)) {
                $data['records'] = count($data['content']['main'])-$types_count;
                return false;
            }
            
            $filter['type_id'] = $type['type_id'];
            $rs = $this->getCsvData($data,$filter,$this->_offset);
            if($rs === false) {
                 array_shift($this->_oGtypeList);
                 $this->_offset = 0;
                 continue;
            }else{
                $this->_offset++;
            }
        }while(true);

        return true;
    }
    
    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function getCsvData(&$data,$filter,$offset) 
    {
        $limit = 200;
        $subSdf = array(
            'product'=>array(
                '*',
            ),
        );

        $oGtype = &$this->app->model('goods_type');
        $bObj = &$this->app->model('brand');
        if(!$goodsList = $this->goods->getList('goods_id',$filter,$offset*$limit,$limit))return false;


        foreach( $goodsList as $aFilter ){
            $aGoods = $this->goods->dump( $aFilter['goods_id'],'*',$subSdf );
            $brand_detail = $bObj->dump(array('brand_id'=>$aGoods['brand']['brand_id']));
            $aGoods = array_merge($aGoods, array('brand'=>$brand_detail));
            if( !$aGoods )continue;

            //过滤简介中的特殊字符导致换行
            $aGoods['brief'] = str_replace('&nbsp;', '', $aGoods['brief']);
            $aGoods['brief'] = str_replace(array("\r\n","\r","\n"), '', $aGoods['brief']);
            $aGoods['brief'] = str_replace(',', '', $aGoods['brief']);

            if( !$this->goods->csvExportGtype[$aGoods['type']['type_id']] ){
                $this->goods->csvExportGtype[$aGoods['type']['type_id']] = $oGtype->dump($aGoods['type']['type_id'],'*');
                if ($this->is_title && empty($this->title_flag)) {
                    $data['content']['main'][] = '"' . implode('","', $this->io->data2local($this->goods->io_title($aGoods['type']))) . '"';
                    $this->title_flag = true;
                }
            }
            $csvData = $this->sdf2Csv($aGoods);
            $data['content']['main'] = array_merge((array)$data['content']['main'],(array)$csvData);
        }

        $data['name'] = 'goods'.date('YmdHis');
        return true;
    }

    function sdf2csv( $sdfdata ){
        $rs = array();

        $conTmp = array();
        if ($this->goods->io_title( $sdfdata['type'] ))
        foreach( $this->goods->io_title( $sdfdata['type'] ) as $titleCol ){
            $conTmp[$titleCol] = '';
        }
        $gcontent = $conTmp;

        $this->goods->oSchema['csv'][$sdfdata['type']['type_id']]['col:销售价'] = 'price';
        $sdfdata['type']['name'] = $this->goods->csvExportGtype[$sdfdata['type']['type_id']]['name'];
        if ($this->goods->oSchema['csv'][$sdfdata['type']['type_id']])
        foreach( $this->goods->oSchema['csv'][$sdfdata['type']['type_id']] as $title => $sdfpath ){
            if( !is_array($sdfpath) ){
                $tSdfCol = utils::apath($sdfdata,explode('/',$sdfpath));
                $gcontent[$title] = (is_array($tSdfCol)?$tSdfCol:$this->charset->utf2local($tSdfCol));
            }else{
                $gcontent[$title] = '';
            }
            if( substr($title,0,6) == 'props:' ){
                if( !$gcontent && $gcontent[$title]['value'] !== 0 ){
                    $gcontent[$title] = '';
                }else{
                    $k = explode('_',$sdfpath);
                    $k = $k[1];
                    if( $this->goods->csvExportGtype[$sdfdata['type']['type_id']]['props'][$k]['options'] ){
                        $gcontent[$title] = $this->charset->utf2local( $this->goods->csvExportGtype[$sdfdata['type']['type_id']]['props'][$k]['options'][$gcontent[$title]['value']] );
                    }else{
                        $gcontent[$title] = $this->charset->utf2local( $gcontent[$title]['value'] );
                    }
                }
            }
        }

        $cat = array();
        $oCat = &$this->app->model('goods_cat');
        $tcat = $oCat->dump($sdfdata['category']['cat_id'],'cat_path');
        if ($oCat->getList('cat_name'))
        foreach( $oCat->getList('cat_name') as $acat ){
            if( $acat ) $cat[] = iconv('UTF-8','GB2312',$acat['cat_name']);
        }
        $this->goods->oSchema['csv'][$sdfdata['type']['type_id']]['col:销售价'] = array('price/price/price','product');

        //$this->goods->oSchema['csv'][$sdfdata['type']['type_id']]['col:成本价'] = array('price/cost/price','product');
        if( !$sdfdata['spec'] ){
            if ($sdfdata['product'])
            $product = current( $sdfdata['product'] );
            foreach( $this->goods->oSchema['csv'][$sdfdata['type']['type_id']] as $title => $sdfpath ){
                if( is_array($sdfpath) && $sdfpath[1] == 'product' ){
                    $tSdfCol = $this->charset->utf2local(utils::apath($product,explode('/',(!is_array($sdfpath)?$sdfpath:$sdfpath[0]))));
                    $gcontent[$title] = $tSdfCol;
                }
            }
            $gcontent['col:规格'] = '-';
//            $gcontent['col:可视状态'] = strtoupper($gcontent['col:可视状态'])=='TRUE' ? $this->charset->utf2local('显示') : $this->charset->utf2local('隐藏');
            $gcontent['col:商品名称']=iconv("UTF-8","GBK//TRANSLIT",$product['name'] );
            $rs[0] = '"'.implode('","',$gcontent).'"';

        }else{

            $spec = array();
            if ($sdfdata['spec'])
            foreach( $sdfdata['spec'] as $aSpec ){
                $spec[] = $aSpec['spec_name'];
            }
            $gcontent['col:规格'] = $this->charset->utf2local( implode('|',$spec) );
            $gcontent['col:图片地址'] = $sdfdata['picurl'];
//            $gcontent['col:可视状态'] = strtoupper($gcontent['visibility'])=='TRUE' ?  $this->charset->utf2local('显示' ) :  $this->charset->utf2local('隐藏');
            $oSpec = &$this->app->model('spec_values');

            $rs[0] = '"'.implode('","',$gcontent).'"';
            if ($sdfdata['product'])
            foreach( $sdfdata['product'] as $row => $aSdfdata ){
                $content = $gcontent;
                foreach( $this->goods->oSchema['csv'][$sdfdata['type']['type_id']] as $title => $sdfpath ){
                    $content[$title] = $this->charset->utf2local(utils::apath($aSdfdata,explode('/',(!is_array($sdfpath)?$sdfpath:$sdfpath[0]))));
                }
                $specValue = array();

                $spec_value_id = $aSdfdata['spec_desc']['spec_value_id'];
               /*  foreach( $spec_value_id as $k=>$v){
                    $spec_valuelist = $oSpec->getList('spec_value',array('spec_value_id'=>$v));
                    $spec_value = $spec_valuelist[0]['spec_value'];
                    if(!$spec_valuelist){
                        $spec_value = $aSdfdata['spec_desc']['spec_value'][$k];
                    }
                    
                    $specValue[] = $this->charset->utf2local($spec_value);
                } */
                #和商品详情那个地方读取规格保持一致
                foreach($aSdfdata['spec_desc']['spec_value'] as $_val){
                    if(isset($_val)){
                        $_speValue .= $_val.'|';
                    }
                }
                if($_speValue){
                    #去除最后一个竖线
                    $_speValue = substr($_speValue,0,strlen($_speValue)-1);
                }
                $content['col:规格'] = $this->charset->utf2local($_speValue);
                $_speValue = null;
                //$content['col:规格'] = implode('|',$specValue);
                $content['bn:商品编号'] = $gcontent['bn:商品编号'];
                //$content['col:上架'] = $content['col:上架'] == 'true'?'Y':'N';
                $content['*:商品类型'] = $this->charset->utf2local($sdfdata['type']['name']);
//                $content['col:可视状态'] = strtoupper($aSdfdata['visibility'])=='TRUE' ?  $this->charset->utf2local('显示' ) :  $this->charset->utf2local('隐藏');
                $rs[$row] = '"'.implode('","',$content).'"';
            }

        }
        return $rs;
    }

     function io_title( $filter,$ioType='csv' ){
        $title = array();
        switch( $ioType ){
            case 'csv':
            default:
                $oGtype = $this->app->model('goods_type');
                if( $this->goods->csvExportGtype[$filter['type_id']] )
                    $gType = $this->goods->csvExportGtype[$filter['type_id']];
                else
                    $gType = $oGtype->dump($filter['type_id'],'*');
                $title = array(
                    '*:商品类型',
                    'bn:商品编号',
                    'barcode:条形码',
                    'ibn:货号',
                    //'col:分类',
                    'col:品牌',
                    //'col:市场价',
                    'col:成本价',
                    'col:销售价',
                    'col:商品名称',
                    //'col:上架',
                    'col:规格',
                );
                $this->goods->oSchema['csv'][$filter['type_id']] = array(
                    '*:商品类型' => 'type/name',
                    'bn:商品编号' => 'bn',
                    'barcode:条形码'=>'barcode',
                    'ibn:货号' => array('bn','product'),
                    //'col:分类' => 'category/cat_name',
                    'col:品牌' => 'brand/brand_name',
                    //'col:市场价' => array('price/mktprice/price','product'),
                    'col:销售价' => array('price/price/price','product'),
                    'col:成本价' => array('price/cost/price','product'),    
                    'col:商品名称' => 'name',
                    //'col:上架' => 'status',
                    'col:规格' => 'spec',
                    'col:图片地址'=>'picurl',
                );
//               // $oMlv = $this->app->model('member_lv');
//                foreach( $oMlv->getList() as $mlv ){
//                    $title[] = 'price:'.$mlv['name'];
////                    $this->oSchema['csv'][$filter['type_id']]['price:'.$mlv['name']] = 'price/';
//                }
                $title = array_merge($title,array(
                   'col:图片地址',
                   'col:商品简介',
                    //'col:详细介绍',
                    'col:重量',
                    'col:单位',
                    //'col:可视状态',
                    'col:商品状态',//新增、更新
                ));
                $this->goods->oSchema['csv'][$filter['type_id']] = array_merge(
                    $this->goods->oSchema['csv'][$filter['type_id']],
                    array(
                        'col:商品简介' => 'brief',
                        //'col:详细介绍' => 'description',
                        'col:重量' => 'weight',
                        'col:单位' => 'unit',
//                        'col:可视状态' => 'visibility',
                    )
                );
                if ($gType['props'])
                foreach( (array)$gType['props'] as $propsK => $props ){
                    $title[] = 'props:'.$props['name'];
                    $this->goods->oSchema['csv'][$filter['type_id']]['props:'.$props['name']] = 'props/p_'.$propsK;
                }
                break;
        }
        $this->goods->ioTitle['csv'][$filter['type_id']] = $title;
        return $title;
    }

    function export_csv($data){
        $output = array();
        $output[] = $data['title']['goods'];
        foreach( $data['content']['goods'] as $k => $val ){
            $output[] = implode("\n",(array)$val);
        }
        return implode("\n",$output);
    }

}