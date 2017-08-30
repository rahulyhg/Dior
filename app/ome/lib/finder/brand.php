<?php
class ome_finder_brand{
    
    var $detail_info = '品牌信息';
    function detail_info($brand_id){
     
        $render =  app::get('ome')->render();
        $render->path[] = array('text'=>app::get('base')->_('商品品牌编辑'));
        $objBrand = &$render->app->model('brand');
        $render->pagedata['brandInfo'] = $objBrand->dump($brand_id);
 
        if(empty($render->pagedata['brandInfo']['brand_url'])) $render->pagedata['brandInfo']['brand_url'] = 'http://';

        $brand_type_id = $objBrand->getBrandTypes($brand_id);
        foreach($brand_type_id as $row){
            $aType[$row['type_id']] = $row;
        }

        $render->pagedata['seo']=$seo_info;
        $render->pagedata['brandInfo']['type'] = $aType;
        $render->pagedata['type'] = $objBrand->getDefinedType();//所有的商品类型
        $objGtype = &$render->app->model('goods_type');
        $render->pagedata['gtype']['status'] = $objGtype->checkDefined();

        return $render->fetch('admin/goods/brand/detail.html');
    }

}
