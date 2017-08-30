<?php
class console_storefreeze{
    
       
       /**
        * 销售预占.
        * @param
        * @return
        * @access  public
        * @author sunjing@shopex.cn
        */
       function sale_freezeproduct($product_ids=0)
       {
            $productObj = &app::get('ome')->model('products');
            $product_ids = (array)$product_ids;
            if(is_array($product_ids)) $product_ids = implode(',',$product_ids);
            
            $get_order_sql = "SELECT o.order_id,i.product_id,i.nums,i.sendnum,(i.nums-i.sendnum) as freeze FROM sdb_ome_orders AS o
                      LEFT JOIN sdb_ome_order_items AS i ON(o.order_id=i.order_id)
                      WHERE o.ship_status IN('0','2') AND o.status='active' AND o.process_status in ('unconfirmed','splited','splitting') AND i.delete='false' AND i.nums != i.sendnum";
                       
            $get_order_sql .= " AND i.product_id in (".$product_ids.")";

            $orders = kernel::database()->select($get_order_sql);
            $p_freeze = array();
            foreach($orders as $order){
                $freeze = $order['freeze'];
                if($freeze<0) {
                    $freeze = 0;
                }
                if(isset($p_freeze[$order['product_id']])){
                    $p_freeze[$order['product_id']] += $freeze;
                }else{
                    $p_freeze[$order['product_id']] = $freeze;
                }
            }
                                                                                                                                                                                 
            return $p_freeze;
       }


        function sale_freezebranchproduct($product_ids=0)
       {
            $branchProductObj = &app::get('ome')->model('branch_product');
            $product_ids = (array)$product_ids;
            if(is_array($product_ids)) $product_ids = implode(',',$product_ids);
            $sql = 'select a.branch_id,sum(b.number) as total_num,b.product_id,b.bn
            from sdb_ome_delivery as a
                left join sdb_ome_delivery_items as b
                on a.delivery_id=b.delivery_id
            where
                a.status in ("progress","ready","stop") and a.process="false" and type="normal" and a.parent_id=0
            ';
            $sql .= " and b.product_id in(".$product_ids.")";
            $sql .= " group by b.product_id,a.branch_id ";

            $deliverys = kernel::database()->select($sql);
            $sale_freeze = array();
            foreach ( $deliverys as $dr ) {
                $sale_freeze[$dr['product_id']][$dr['branch_id']] = $dr['total_num'];
            }
            
            return $sale_freeze;
       }
       /**
        * 销售预占.
        * @param
        * @return
        * @access  public
        * @author sunjing@shopex.cn
        */
       function branch_freezeproduct($product_ids=0)
       {
            //采购退货
            $db = kernel::database();
            $product_ids = (array)$product_ids;
            if(is_array($product_ids)) $product_ids = implode(',',$product_ids);
            $sql = "select ai.product_id,a.branch_id,sum(ai.num) as _num from sdb_purchase_returned_purchase as a LEFT JOIN sdb_purchase_returned_purchase_items as ai ON a.rp_id = ai.rp_id
                        where a.rp_type in ('eo') AND a.return_status in ('1','4') and ai.product_id in (".$product_ids.")  AND a.check_status in ('2')  group by a.branch_id,ai.product_id";
            //echo $sql."<br>";
            $data = $db->select($sql);
            
            $rs = array();
            foreach($data as $v){
                $rs[$v['product_id']][$v['branch_id']] = $v['_num'];
            }
            
            //

            $stock_sql = "select ai.product_id,a.branch_id,sum(ai.nums) as _num from sdb_taoguaniostockorder_iso as a LEFT JOIN sdb_taoguaniostockorder_iso_items as ai ON a.iso_id = ai.iso_id
                        where a.iso_status in ('1','2') AND a.check_status in ('2') AND a.type_id in('5','7','100','300') and ai.product_id in (".$product_ids.") group by a.branch_id,ai.product_id";

            $stock = $db->select($stock_sql);
            //print_r($stock);
            foreach ($stock as $sv ) {
                if (isset($rs[$sv['product_id']][$sv['branch_id']])) {
                    $rs[$sv['product_id']][$sv['branch_id']] +=$sv['_num'];
                }else{
                    $rs[$sv['product_id']][$sv['branch_id']] = $sv['_num'];
                }
                
            }
            
            //退货预占
            $reship_sql = "SELECT i.product_id,i.branch_id,sum(i.num) as _num FROM sdb_ome_reship as r LEFT JOIN sdb_ome_reship_items as i ON r.reship_id=i.reship_id WHERE r.return_type='change' AND i.return_type='change' AND r.is_check in('1','11') and i.product_id in (".$product_ids.") group by i.branch_id,i.product_id";
            $reship = $db->select($reship_sql);
            foreach ($reship as $rv ) {
                if (isset($rs[$rv['product_id']][$rv['branch_id']])) {
                    $rs[$rv['product_id']][$rv['branch_id']] +=$rv['_num'];
                }else{
                    $rs[$rv['product_id']][$rv['branch_id']] =$rv['_num'];
                }
            }
         
            return $rs;
       }


       /**
    *根据所有有差异货品
    **/
    public function get_all_diff(){
        $productObj = &app::get('ome')->model('products');
        $rs = $productObj->getList('product_id');
        $db = kernel::database();
        $count = count($rs);
        $limit = 10000;
        $page = 0;
        $data = $diff = $product_bn=$total_freeze = array();
        for($page;$page < ($count / $limit);$page++){
            $sql = "select product_id,store_freeze,bn from sdb_ome_products order by product_id limit ".($page * $limit).",".$limit;
            
            $data = $db->select($sql);
            
            
          
            foreach($data as $product){
                $product_ids[] = $product['product_id'];
                $total_freeze[$product['product_id']] = $product['store_freeze'];
                $product_bn[$product['product_id']] = $product['bn'];
            }

            $sale_freeze = 0;
            $sale_freeze = $this->sale_freezeproduct($product_ids);
           
            $salestock_freeze = $this->sale_freezebranchproduct($product_ids);

            $outstock_freeze = $this->branch_freezeproduct($product_ids);
           

            //当前仓库总库存
            $local_branchfreeze = $this->get_branchproductFreeze($product_ids);
            $branch_freeze = array();
            //比较货品冻结
            foreach ( $product_ids as $product_id ) {
                $outstock = 0;
                $branchstock = 0;
                if ($outstock_freeze[$product_id]) {
                    foreach ( $outstock_freeze[$product_id] as $freeze ) {
                        $outstock+=$freeze;
                        
                        $branchstock+=$freeze;
                    }
                }
              
                if ($salestock_freeze[$product_id]) {
                    foreach ($salestock_freeze[$product_id] as $salefreeze ) {
                        $branchstock+=$salefreeze;
                    }
                }
                
                $real_product_freeze = $sale_freeze[$product_id]+$outstock; //货品总冻结

                $pro_total_freeze = $total_freeze[$product_id]/1;
                $real_branch_freeze = $branchstock;
                //real_product_freeze real_branch_freeze local_branch_freeze
               $real_local_branchfreeze = $local_branchfreeze[$product_id] ? $local_branchfreeze[$product_id] :0; 
               

                if (($real_product_freeze!=$pro_total_freeze)  || $real_local_branchfreeze!=$real_branch_freeze || $real_local_branchfreeze>$pro_total_freeze) {
                    $diff[$product_id] = array(
                        'bn'=>$product_bn[$product_id],
                        'local_product_store_freeze' =>$pro_total_freeze,
                        'real_product_freeze'  =>$real_product_freeze,
                        'real_branch_freeze'   =>$real_branch_freeze,
                        'local_branch_freeze'  =>$local_branchfreeze[$product_id],
                    
                    );

                }
            }

        }
        return $diff;
    }

    
    /**
     * 仓库货品总冻结.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_branchproductFreeze($product_ids=0)
    {
        $db = kernel::database();
        $product_ids = (array)$product_ids;
        if(is_array($product_ids)) $product_ids = implode(',',$product_ids);
        $sql="SELECT product_id,sum(store_freeze) as total_store_freeze FROM sdb_ome_branch_product WHERE product_id in(".$product_ids.") group by product_id";

        $branch_product = $db->select($sql);
        $branch_freeze = array();
        if ($branch_product) {
            foreach ( $branch_product as $product ) {
                $branch_freeze[$product['product_id']] = $product['total_store_freeze'];
            }
            return $branch_freeze;
        }
        
    }

    
    /**
     * 修正冻结库存.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function fix_freeze_store($product_id)
    {
        
        $db = kernel::database();
        $real_product_freeze = 0;
        $shop_real_data = $this->sale_freezeproduct($product_id);
    
        $shop_real_data = $shop_real_data[$product_id] ? $shop_real_data[$product_id] : '0';

        $branch_freeze = $this->branch_freezeproduct($product_id);
        
        $branch_freeze = $branch_freeze[$product_id] ? $branch_freeze[$product_id] : '0';

        $sale_freeze = $this->sale_freezebranchproduct($product_id);
        
        $sale_freeze = $sale_freeze[$product_id] ? $sale_freeze[$product_id] : '0';

        $real_product_freeze = $shop_real_data;
        $branch_profreeze = array();

        foreach ( $sale_freeze as $sk=> $shopfree ) {
            $branch_profreeze[$sk] = $shopfree;
            
        }

        foreach ($branch_freeze  as $bk=> $brfree ) {

            $real_product_freeze+=$brfree;
            if (isset($branch_profreeze[$bk])) {
                $branch_profreeze[$bk]+=$brfree;
            }else{
                $branch_profreeze[$bk] = $brfree;
            }
            
        }
      
        $up_sql = "UPDATE sdb_ome_branch_product set store_freeze=0 WHERE product_id=".$product_id;
     

        $db->exec($up_sql);
        if ($branch_profreeze) {

            foreach ($branch_profreeze as $bk=>$bran ) {
                if($bran>0){
                    $up_sql = "UPDATE sdb_ome_branch_product set store_freeze=".$bran." WHERE branch_id=".$bk." AND product_id=".$product_id;
                    
                    $db->exec($up_sql);
                    
                }
                
            }
        }

        if ($real_product_freeze>0) {
            $pro_sql = "update sdb_ome_products set store_freeze=".$real_product_freeze." WHERE product_id=".$product_id;
            
            $db->exec($pro_sql);
        }else{
            $pro_sql = "update sdb_ome_products set store_freeze=0 WHERE product_id=".$product_id;
            
            $db->exec($pro_sql);
        }
        
        return 'success';
    }
}