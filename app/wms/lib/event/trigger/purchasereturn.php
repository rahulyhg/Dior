<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_trigger_purchasereturn extends wms_event_trigger_stockoutabstract{

    function getStockOutData($data){
        $oRp = &app::get('purchase')->model('returned_purchase');
        $iostockdataObj = kernel::single('wms_iostockdata');
        $rp = $oRp->dump($data['rp_id'],'rp_bn,branch_id');
        $branch_id = $rp['branch_id'];
        $branch_detail = $iostockdataObj->getBranchByid($branch_id);
        $outdata = array(
            'io_type' => 'PURCHASE_RETURN',
            'io_source'=>'selfwms',
            'io_bn'=>$rp['rp_bn'],
            'branch_id'=>$branch_id,
            'branch_bn'=>$branch_detail['branch_bn'],
            'memo' =>$returndata['memo'],
            'io_status' => 'FINISH',
        );
        $item = array();
        foreach($data['items'] as $products){
            $item[] = array(
                'bn'=>$products['bn'],
                'num'=>$products['nums'],
            ); 
        }
        $outdata['items'] = $item;
        return $outdata;
    } 
    
    

}

?>
