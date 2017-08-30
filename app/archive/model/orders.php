<?php
class archive_mdl_orders extends dbeav_model{
     var $has_many = array(
        'order_objects' => 'order_objects',
    );
    //是否有导出配置
    var $has_export_cnf = true;

    var $defaultOrder = array('createtime DESC ,order_id DESC');

     function _filter($filter,$tableAlias=null,$baseWhere=null){
        
         $where = " 1 ";

        if (isset($filter['flag']) && $filter['flag'] == '0') {
            return ;
        }
        if ($filter['search_filter'] && $filter['search_filter_value']) {
            switch ($filter['search_filter']) {
                case 'order_bn':
                    $where.=" AND order_bn='".$filter['search_filter_value']."'";
                    break;
                case 'logi_no':

                    $where.=" AND logi_no='".$filter['search_filter_value']."'";
                    
                    break;
                case 'member_name':
                    $member_id = '';
                    $memberObj = &app::get('ome')->model("members");
                    $rows = $memberObj->getList('member_id',array('uname|head'=>$filter['search_filter_value']));

                    $memberId[] = 0;
                    foreach($rows as $row){
                        $memberId[] = $row['member_id'];
                    }
                    $where .= '  AND member_id IN ('.implode(',', $memberId).')';
                    
                    break;
                case 'receive_name':
                    $where.=" AND ship_name='".$filter['search_filter_value']."'";
                    break;
                case 'mobile':
                    $where.=" AND ship_mobile='".$filter['search_filter_value']."'";
                    break;
                case 'tel':
                    $where.=" AND ship_tel='".$filter['search_filter_value']."'";
                    break;
            }
        }

         if ($filter['time_from'] && $filter['time_to']) {
            $time_from = strtotime($filter['time_from']);
            $time_to = strtotime($filter['time_to']);

            //开始时间
            $start_time = strtotime(date("Y-m-1 00:00:00",$time_from));
            $where.=" AND createtime >='".$start_time."'";

            //获取选择时间范围内的最后一天
            if ( date('Ym',$time_to) >= date('Ym') ){
                $end_time = strtotime(date("Y-m-j 23:59:59",time()-24*60*60));
            }else{
                $end_time = strtotime(date('Y-m-t 23:59:59', $time_to));//1351612799
            }
            $where.=" AND createtime <='".$end_time."'";
         }

        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
       
    }

    
    /**
     * 获取订单明细
     * @param   
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function getItemList($order_id,$sort=false)
    {
        if($sort){
            $objectsObj = $this->app->model('order_objects');
            $itemsObj = $this->app->model('order_items');
            $order_objects = $objectsObj->getlist('*',array('order_id'=>$order_id));
            $order_items = array();
            foreach($order_objects as $k=>$v){
                $order_items[$v['obj_type']][$k] = $v;
                foreach($this->db->select("SELECT *,nums AS quantity FROM sdb_archive_order_items WHERE obj_id=".$v['obj_id']." AND item_type='product' ORDER BY item_type") as $it){
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
                foreach($this->db->select("SELECT *,nums AS quantity FROM sdb_archive_order_items WHERE obj_id=".$v['obj_id']." AND item_type<>'product' ORDER BY item_type") as $it){
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
            }
        }else{
            foreach($this->db->select("SELECT *,nums AS quantity FROM sdb_archive_order_items WHERE order_id=".$order_id." ") as $it){
                    $order_items[] = $it;
                }
        }
        
        
        
        return $order_items;
    }

    /*
     * 统计某订单货号生成退款单数
     *
     * @param int $order_id ,varchar $bn
     *
     * @return int
     */
    function Get_refund_count($order_id,$bn,$reship_id='')
    {
        $sql = "SELECT sum(nums) as count FROM sdb_archive_order_items WHERE order_id='".$order_id."' AND bn='".$bn."'";
        $order=$this->db->selectrow($sql);

        $sql = "SELECT sum(i.num) as count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check!='5' AND r.order_id='".$order_id."' AND i.bn='".$bn."'";
        if($reship_id != ''){
            $sql .= ' AND r.reship_id!='.$reship_id;
        }

        $refund = $this->db->selectrow($sql);

        return $order['count']-$refund['count'];
    }

    /*
    *  根据货号获取对应仓库和ID
    *
    * @param int $order_id ,varchar $bn
    *
    * * return array
    */
     function getBranchCodeByBnAndOd($bn,$orderid)
     {
         $oBranch=app::get('ome')->model('branch');
         $sqlstr = "SELECT s.branch_id,s.delivery_id FROM sdb_archive_delivery as s left join sdb_archive_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_archive_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$orderid' AND sdi.bn='$bn'";

        $branch=$this->db->select($sqlstr);
        
        $branch_ids = array();
        $t_branch = $branch;
        foreach($t_branch as $k=>$v){
            if(!in_array($v['branch_id'],$branch_ids)){
                $branchs = $oBranch->dump($v['branch_id'],'name,branch_id');
                $branch[$k]['branch_name']=$branchs['name'];
                $branch_ids[] = $v['branch_id'];
            }else{
                unset($branch[$k]);
            }
        }
        
        return $branch;
     }

     /*
  * 根据仓库ID，货号订单号获取发货单号以及对应收货相关信息
  * @param int $branch_id,int $order_id
  * return $array
  */
   function Get_delivery($branch_id,$bn,$order_id)
   {
        $sqlstr = "SELECT s.delivery_id,s.delivery_bn,s.ship_name,s.ship_area,s.ship_addr,sdi.bn,sum(sdi.number) as number FROM sdb_archive_delivery as s left join sdb_archive_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_archive_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$order_id' AND sdi.bn='$bn' AND s.branch_id='$branch_id' group by sdi.bn";

        $result=$this->db->selectrow($sqlstr);

        $result['refund'] = $result['number']-$this->Get_refund_num($branch_id,$bn,$order_id);

        return $result;
   }
   /*
    *根据仓库，货号，订单号数量
    */
   function Get_refund_num($branch_id,$bn,$order_id)
   {
       $refund = $this->db->selectrow("SELECT sum(i.num) as count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check!='5' AND r.order_id='".$order_id."' AND i.bn='".$bn."' AND i.branch_id='".$branch_id."' group by i.bn");

       return $refund['count'];

   }

   function order_detail($order_id){
        $order_detail = $this->dump(array('order_id'=>$order_id));
        return $order_detail;
    }

    function countlist($filter=null){
        $filter['flag'] = '1';
        
        $sql ="SELECT COUNT(order_id) AS _count FROM sdb_archive_orders WHERE".$this->_filter($filter);

        $archive = $this->db->selectrow($sql);
        return $archive['_count'];
    }

    public function getexportdetail($fields,$filter,$offset=0,$limit=1,$has_title=false)
    {
        //获取订单号信息
        $orders = $this->db->select("SELECT order_id,order_bn FROM sdb_archive_orders WHERE order_id in(".implode(',', $filter['order_id']).")");
        $aOrder = array();
        if($orders){
            foreach($orders as $order){
                $aOrder[$order['order_id']] = $order['order_bn'];
            }
        }

        $pkgLib = kernel::single('archive_service_objtype_pkg');
        $row_num = 1;
        foreach($filter['order_id'] as $oid){
            $objects = $this->db->select("SELECT * FROM sdb_archive_order_objects WHERE order_id =".$oid);
            if ($objects){
                foreach ($objects as $obj){
                    if (strtolower($obj['obj_type']) == 'pkg'){
                        $item_data = $pkgLib->process($obj);
                        if ($item_data){
                            foreach ($item_data as $itemv){
                                $orderObjRow = array();
                                $orderObjRow['*:订单号']   = mb_convert_encoding($aOrder[$obj['order_id']], 'GBK', 'UTF-8');
                                $orderObjRow['*:商品货号'] = mb_convert_encoding("\t".$itemv['bn'], 'GBK', 'UTF-8');
                                $orderObjRow['*:商品名称'] = mb_convert_encoding("\t".str_replace("\n"," ",$itemv['name']), 'GBK', 'UTF-8');
                                $orderObjRow['*:购买单位'] = mb_convert_encoding($itemv['unit']);
                                $orderObjRow['*:商品规格'] = $itemv['spec_info'] ? mb_convert_encoding(str_replace("\n"," ",$itemv['spec_info']), 'GBK', 'UTF-8'):"-";
                                $orderObjRow['*:购买数量'] = $itemv['nums'];
                                $orderObjRow['*:商品原价'] = $itemv['price'];
                                $orderObjRow['*:销售价'] = $itemv['sale_price'] / $itemv['nums'];
                                $orderObjRow['*:商品优惠金额'] = $itemv['pmt_price'];

                                $data[$row_num] = implode(',', $orderObjRow );
                                $row_num++;
                            }
                        }
                    }else {
                        $aOrder['order_items'] = $this->db->select("SELECT * FROM sdb_archive_order_items WHERE obj_id=".$obj['obj_id']." AND order_id =".$obj['order_id']);
                        $aOrder['order_items'] = ome_order_func::add_items_colum($aOrder['order_items']);
                        $orderRow = array();
                        $orderObjRow = array();
                        $k = 0;
                        if ($aOrder['order_items'])
                        foreach( $aOrder['order_items'] as $itemk => $itemv ){
                            $addon = unserialize($itemv['addon']);
                            $spec_info = null;
                            if(!empty($addon)){
                                foreach($addon as $val){
                                    foreach ($val as $v){
                                        $spec_info[] = $v['value'];
                                    }
                                }
                            }
                            $_typeName = app::get('ome')->model('orders')->getTypeName($itemv['product_id']);
                            $orderObjRow = array();
                            $orderObjRow['*:订单号']   = mb_convert_encoding($aOrder[$obj['order_id']], 'GBK', 'UTF-8');
                            $orderObjRow['*:商品货号'] = mb_convert_encoding("\t".$itemv['bn'], 'GBK', 'UTF-8');
                            $orderObjRow['*:商品名称'] = mb_convert_encoding("\t".str_replace("\n"," ",$itemv['name']), 'GBK', 'UTF-8');
                            $orderObjRow['*:购买单位'] = mb_convert_encoding($itemv['unit']);
                            $orderObjRow['*:商品规格'] = $spec_info ? mb_convert_encoding(implode('||', $spec_info), 'GBK', 'UTF-8'):'-';
                            $orderObjRow['*:购买数量'] = $itemv['nums'];
                            $orderObjRow['*:商品原价'] = $itemv['price'];
                            $orderObjRow['*:销售价'] = $itemv['sale_price'] / $itemv['nums'];
                            $orderObjRow['*:商品优惠金额'] = $itemv['pmt_price'];
                            $orderObjRow['*:商品类型'] = mb_convert_encoding($_typeName['type_name'], 'GBK', 'UTF-8');
                            $orderObjRow['*:商品品牌'] = mb_convert_encoding($_typeName['brand_name'], 'GBK', 'UTF-8');
                            

                            $data[$row_num] = implode(',', $orderObjRow );
                            $row_num++;
                        }
                    }
                }
            }
        }

        //明细标题处理
        if($data && $has_title){
            $title = array(
                '*:订单号',
                '*:商品货号',
                '*:商品名称',
                '*:购买单位',
                '*:商品规格',
                '*:购买数量',
                '*:商品原价',
                '*:销售价',
                '*:商品优惠金额',
                '*:商品类型',
                '*:商品品牌',
            );

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $data[0] = implode(',', $title);
        }

        ksort($data);
        return $data;
    }

    /**
     * 订单暂停
     */
    function pauseOrder($order_id, $must_update = 'false'){

       
    }

     function renewOrder($order_id){
     }

     
}

?>