<?php
/**
* 
*/
class wms_iostockdata{
   

   /***
    * 获取仓库对应售后仓
    */
   function getDamagedbranch($branch_id){
       $oBranch = app::get('ome')->model('branch');
       $branch_damaged = $oBranch->dump(array('parent_id' => $branch_id, 'type' => 'damaged'), 'branch_id,branch_bn');
       return $branch_damaged;


   }
   /**
   * 根据仓库ID返回仓库信息
   */
    function getBranchByid($branch_id){
        $oBranch = app::get('ome')->model('branch');
        $branch = $oBranch->getlist('type,branch_id,branch_bn',array('branch_id' => $branch_id),0,1);
        $branch = $branch[0];
        return $branch;

    }

    /**
    * 根据出入库编号查询出对应入库数量是否异常并返回
    */
    function getIsoBybn($iso_bn){
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $iso = $oIso->dump(array('iso_bn'=>$iso_bn),'iso_id');
        $iso_id = $iso['iso_id'];
        $sql = 'SELECT nums,normal_num,defective_num,bn FROM sdb_taoguaniostockorder_iso_items WHERE iso_id='.$iso_id.' AND (normal_num>nums OR defective_num>0)';
        $iso_item = $oIso->db->select($sql);
        return $iso_item;
    }

    /**
    * 根据编号查询采购单入库数量是否异常并返回
    *
    */
    function getPoBybn($po_bn){
        $oPo = &app::get('purchase')->model('po');
        $po = $oPo->dump(array('po_bn'=>$po_bn),'po_id');
        $po_id = $po['po_id'];
        $SQL = 'SELECT in_num,num,defective_num,bn FROM sdb_purchase_po_items WHERE po_id='.$po_id.' AND (in_num>num OR defective_num>0)';
        $po_item = $oPo->db->select($SQL);
        return $po_item;
    }

     /**
    * 根据编号查询采购单入库数量是否异常并返回
    *
    */
    function getPurchasereturnBybn($rp_bn){
        $oRp = &app::get('purchase')->model('returned_purchase');
        $rp = $oRp->dump(array('rp_bn'=>$rp_bn),'rp_id');
        $rp_id = $rp['rp_id'];
        $SQL = 'SELECT num,out_num,bn FROM sdb_purchase_returned_purchase_items WHERE rp_id='.$rp_id.' AND (out_num>num)';
        $rp_item = $oRp->db->select($SQL);
        return $rp_item;
    }
}
