<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_io_export_cost {
    
    function __construct($app){
       $this->app = $app;
       $this->charset = kernel::single('base_charset');
       $this->goods = &$this->app->model('goods');
       $this->product = &$this->app->model('products');
       $this->export_name = '商品成本价';
    }

    function fgetlist_csv(&$data,$filter,$offset){
        //@ini_set('memory_limit','64M');
        $limit = 40;
        $orderBy = 'product_id desc';
        $offset = 0;

        do{
            if(!$goodsList = $this->product->getList('goods_id,name,bn,cost,weight',$filter,$offset*$limit,$limit,$orderBy)){
                $data['name'] = 'cost'.date('YmdHis');
                $data['records'] = count($data['content']['main'])-1;
                return false;
            }
            
            $csv_title = $this->io_title();

            if($offset ==0){
                $title = array();
                foreach( $csv_title as $k => $v ){
                    $title[] = $this->charset->utf2local($v);
                }
                $data['content']['main'][] = '"'.implode('","',$title).'"';
            }

            foreach( $goodsList as $aFilter ){
                foreach ($this->goods->oSchema['csv']['main'] as $kk => $v) {
                    if($v=='name'){
                        $goodsRow[$kk] = iconv("UTF-8", "GBK//TRANSLIT", $aFilter[$v]);//$this->charset->utf2local($aFilter[$v]);
                    }else{
                        $goodsRow[$kk] = $this->charset->utf2local($aFilter[$v]);
                    }                                
                }
                $data['content']['main'][] = '"'.implode('","',$goodsRow).'"';
            }
            $offset++;
        }while(true);
        
        return true;
    }

    function io_title( $filter,$ioType='csv' ){  
        $title = array();
        switch( $ioType ){
            case 'csv':
            default:
            $this->goods->oSchema['csv']['main'] = array(
                '*:货号'=>'bn',
                'col:商品名称'=>'name',
                'col:成本价'=>'cost',
                'col:重量'=>'weight',
            );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->goods->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }

    function export_csv($data){
        $output = array();

        $output[] = $data['title']['goods']."\n".implode("\n",(array)$data['content']['goods']);
        return implode("\n",$output);
    }

}  