<?php
class omeanalysts_finder_ome_goods{
	var $detail_basic = '货品详情';

	public function detail_basic($goods_id) {
        $filter = array(
            'goods_id'=>$goods_id,
            'time_from' => $_GET['time_from'],
            'time_to' => $_GET['time_to'],
        );
		$render = app::get('omeanalysts')->render();
		$productObj = &app::get('omeanalysts')->model('ome_products');
		$products = $productObj->getlist('*',$filter);

		$render->pagedata['products'] = $products;
		
		return $render->display('ome/detail_goods.html');
	}
}