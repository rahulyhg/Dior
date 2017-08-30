<?php
class omeanalysts_mdl_ome_products extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '货品销售情况';

    var $stockcost_enabled = false;

    public function __construct(){

        parent::__construct();

        if(app::get('tgstockcost')->is_installed()){

            $setting_stockcost_cost = app::get("ome")->getConf("tgstockcost.cost");

            if(!$setting_stockcost_cost){
                $this->stockcost_enabled = false;
            }else{
                $this->stockcost_enabled = true;
            }
        }
        
    }

    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v){
            if(isset($v['searchtype']) && $v['searchtype']){
                $columns[$k] = $v['label'];
            }
        }

        return $columns;
    }

    public function get_products($filter=null){

        if(isset($filter['time_to']) && $filter['time_to']){
            $filter['time_to'] = $filter['time_to'].' 23:59:59';
        }

        $dayNum = intval((strtotime($filter['time_to'])-strtotime($filter['time_from'])+1)/86400);
        $dayNum = $dayNum ? $dayNum : 1;

        $sql = 'SELECT sum(SI.cost_amount) as cost_amount,sum(SI.nums) as sale_num,sum(SI.sales_amount) as sale_amount,sum(SI.gross_sales) AS gross_sales FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id WHERE '.$this->_filter($filter);
        $salestat = $this->db->selectrow($sql);

        $data['sale_amount']      = $salestat['sale_amount'];
        $data['salenums']         = $salestat['sale_num'];
        $data['day_amounts']      = bcdiv($data['sale_amount'], $dayNum,3);
        $data['day_nums']         = bcdiv($data['salenums'], $dayNum,3);

        // 店铺
        $rwhere = '';
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $rwhere .= ' and sa.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }

        if (isset($filter['brand_id']) && $filter['brand_id']) {
            $rwhere .= ' and g.brand_id=\''.$filter['brand_id'].'\'';
        }

        if (isset($filter['goods_type_id']) && $filter['goods_type_id']) {
            $rwhere .= ' and g.type_id=\''.$filter['goods_type_id'].'\'';
        }

        if (isset($filter['obj_type']) && $filter['obj_type'] == 'pkg') {
            $rwhere .= ' and p.product_id=0';
        }

        $sql = "select sum(sai.num*sai.price) as reship_total_amount,sum(sai.num) as total_reship_num 
                from sdb_sales_aftersale_items sai 
                left join sdb_sales_aftersale sa on sai.aftersale_id = sa.aftersale_id 
                left join sdb_ome_products p on sai.product_id = p.product_id
                left join sdb_ome_goods g on p.goods_id = g.goods_id LEFT JOIN sdb_ome_sales as s ON s.order_id=sa.order_id
                where sai.return_type='return' and s.ship_time >= ".strtotime($filter['time_from'])." and s.ship_time <= ".strtotime($filter['time_to']).$rwhere;
        $reshipstat = $this->db->selectrow($sql);

        $data['reship_nums']      = $reshipstat['total_reship_num'];
        $data['reship_amounts']   = $reshipstat['reship_total_amount'];

        $data['gross_sales']      = $salestat['gross_sales'] - $data['reship_amounts'];
        $data['gross_sales_rate'] = $data['sale_amount'] ? bcdiv($data['gross_sales'], $data['sale_amount'],3) : 0;

        return $data;
    }

    /*
    public function get_products($filter=null){

        if(isset($filter['time_to']) && $filter['time_to']){
            $filter['time_to'] = $filter['time_to'].' 23:59:59';
        }

        $sql = 'SELECT SI.bn,SI.cost,SI.name,sum(SI.cost_amount) as cost_amount,SI.product_id,sum(SI.nums) as sale_num,sum(SI.sales_amount) as sale_amount FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id WHERE '.$this->_filter($filter).' GROUP BY SI.bn';

        $rows = $this->db->select($sql);

        $dayNum = intval((strtotime($filter['time_to'])-strtotime($filter['time_from'])+1)/86400);

        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $rwhere = ' and sa.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }else{
            $rwhere = '';
        }

        $sale_amount = $salenums = $reship_nums = $reship_amounts = 0;
        foreach($rows as $key=>$value){

            $sql = "select sum(sai.num*sai.price) as reship_total_amount,sum(sai.num) as total_reship_num from sdb_sales_aftersale_items sai left join sdb_sales_aftersale sa on sai.aftersale_id = sa.aftersale_id where sai.bn = '".addslashes($value['bn'])."' and sai.return_type='return' and sa.aftersale_time >= ".strtotime($filter['time_from'])." and sa.aftersale_time < ".strtotime($filter['time_to']).$rwhere;

            $row = $this->db->select($sql);

            if($this->stockcost_enabled){
                //$aftersale_sql = "select cost as aftersale_cost_amount from sdb_ome_products where bn='".addslashes($value['bn'])."'";

                $aftersale['aftersale_cost_amount'] = $value['cost'];//$this->db->selectrow($aftersale_sql);//退货商品成本和
            }else{
                $aftersale['aftersale_cost_amount'] = 0;
            }

            $aftersale['aftersale_cost_amount'] = ($aftersale['aftersale_cost_amount'] * $row[0]['total_reship_num']);

            $rows[$key]['day_num'] = $dayNum ? $rows[$key]['sale_num']/$dayNum : 0;
            $rows[$key]['day_amount'] = $dayNum?strval($rows[$key]['sale_amount']/$dayNum):0;

            $rows[$key]['total_cost_amount'] = $rows[$key]['cost_amount'] - $aftersale['aftersale_cost_amount'];//总成本 = 销售成本-售后商品成本之和

            $rows[$key]['gross_sales'] = $rows[$key]['sale_amount'] - $row[0]['reship_total_amount']- $rows[$key]['total_cost_amount'];//销售毛利 = 销售额-退货总额-总成本

            //$rows[$key]['gross_sales_rate'] = round($rows[$key]['gross_sales']/$rows[$key]['sale_amount'],2);//销售毛利率 = 销售毛利/销售额
            $sale_amount += $rows[$key]['sale_amount'];
            $salenums += $rows[$key]['sale_num'];
            $day_amounts += $rows[$key]['day_amount'];
            $day_nums += $rows[$key]['day_num'];
            $gross_sales += $rows[$key]['gross_sales'];
            $reship_nums += $row[0]['total_reship_num'];
            $reship_amounts += $row[0]['reship_total_amount'];
        }
        $data['sale_amount'] = $sale_amount;
        $data['salenums'] = $salenums;
        $data['day_amounts'] = $day_amounts;
        $data['day_nums'] = $day_nums;
        $data['gross_sales'] = $gross_sales;
        $data['gross_sales_rate'] = $gross_sales/$sale_amount;
        $data['reship_nums'] = $reship_nums;
        $data['reship_amounts'] = $reship_amounts;

        return $data;
    }*/

    public function count($filter=null){

        if(isset($filter['time_to']) && $filter['time_to']){
            $filter['time_to'] = $filter['time_to'].' 23:59:59';
        }

        $row = $this->db->select('SELECT count(*) as _count FROM (SELECT SI.product_id FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id WHERE '.$this->_filter($filter).' GROUP BY SI.bn ) as tb');

        return intval($row[0]['_count']);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $Obrand = &app::get('ome')->model('brand');
        $Ogytpe = &app::get('ome')->model('goods_type');
        $pObj   = &app::get('ome')->model("products");
        $oSpec  = &app::get('ome')->model('specification');
        $oSpecvalue  = &app::get('ome')->model('spec_values');

        if(isset($filter['time_to']) && $filter['time_to']){
            $filter['time_to'] = $filter['time_to'].' 23:59:59';
        }
        
        $sales_sql = 'SELECT P.name,G.bn as goods_bn,G.type_id,G.brand_id as brand,SI.product_id,SI.cost as aftersale_cost_amount,SI.spec_name as goods_specinfo, SI.bn,sum(SI.cost_amount) as cost_amount,sum(SI.nums) as sale_num,sum(SI.sales_amount) as sale_amount,S.order_id 
                FROM sdb_ome_sales_items SI 
                LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id 
                LEFT JOIN sdb_ome_products AS P ON SI.product_id=P.product_id
                LEFT JOIN sdb_ome_goods as G ON G.goods_id=P.goods_id
                WHERE '.$this->_filter($filter);
        if((!$filter['_sale_amount_search'])&&(!$filter['_sale_num_search'])){
            $sales_sql .= ' GROUP BY SI.bn';
        }
        $rows = $this->db->selectLimit($sales_sql,$limit,$offset);

        //$this->tidy_data($rows, $cols);

        $dayNum = intval((strtotime($filter['time_to'])-strtotime($filter['time_from'])+1)/86400);

        if($rows){

            /* $bns = array();
            foreach ($rows as $row) {
                $bns[] = $row['bn'];
            } */
            //$productSql = "SELECT P.name,G.bn as goods_bn,G.type_id,G.brand_id as brand,P.spec_desc,P.bn from sdb_ome_products as P LEFT JOIN sdb_ome_goods as G ON P.goods_id=G.goods_id WHERE P.bn in ('" . join($bns,"','") . "')";
          
            //$productinfos = $this->db->select($productSql);
          
            /* foreach ($productinfos as $k => $v) {
                $product_info[$v['bn']] = $v;
                $pro_bns[] = $v['bn'];
            } */
            #一次读取所有商品品牌
            $arr_brand = $Obrand->getList('brand_id,brand_name',array());
            #一次读取所有商品类型
            $arr_type = $Ogytpe->getList('type_id,name',array());
            
            $all_brand = $all_type = array();
            
            foreach($arr_brand as $v){
                $all_brand[$v['brand_id']] = $v['brand_name'];
            }
            foreach($arr_type as $v){
                $all_type[$v['type_id']] = $v['name'];
            }
            
           
            foreach($rows as $key=>$val){
                
               /*  if(empty($rows[$key]['goods_specinfo'])){

                    $spec_desc = $product_info[$rows[$key]['bn']];
                    $productattr = '';
                    if ($spec_desc['spec_value_id'])
                    foreach ($spec_desc['spec_value_id'] as $sk=>$sv){
                         $specval = $oSpecvalue->dump($sv,"spec_value,spec_id");
                         $spec = $oSpec->dump($specval['spec_id'],"spec_name");
                         $productattr .= $spec['spec_name'].':'.$specval['spec_value'].';';
                    }
                    $rows[$key]['goods_specinfo'] = $productattr;
                } */

                if($rows[$key]['product_id'] ){
/*                  $rows[$key]['obj_type'] = 'normal';
                    $brand = $Obrand->getList('brand_name',array('brand_id'=>$product_info[$val['bn']]['brand']));
                    $gtype = $Ogytpe->getList('name',array('type_id'=>$product_info[$val['bn']]['type_id']));
                    $rows[$key]['type_id'] = $gtype[0]['name'];
                    $rows[$key]['name'] = $product_info[$val['bn']]['name'];
                    $rows[$key]['brand'] = $brand[0]['brand_name'];
                    $rows[$key]['goods_bn'] = $product_info[$val['bn']]['goods_bn']; */
                    
                    $rows[$key]['obj_type'] = 'normal';
                    $rows[$key]['type_id'] = $all_type[$val['type_id']]?$all_type[$val['type_id']]:'-';
                    //$rows[$key]['name'] = $rows['name'];
                    $rows[$key]['brand'] = $all_brand[$val['brand']]?$all_brand[$val['brand']]:'-';
                    //$rows[$key]['goods_bn'] = $rows['goods_bn'];
                }else{
                   
                    $pkgObj = kernel::single('omepkg_ome_product');
                    $pkg_info = $pkgObj->getProductByBn($val['bn']);
                            
                           
                    if(!empty($pkg_info)){
                        $rows[$key]['obj_type'] = 'pkg';
                        $rows[$key]['type_id'] = '捆绑商品';
                        $rows[$key]['brand'] = '-';
                        $rows[$key]['goods_bn'] = '-';
                        $rows[$key]['name'] = $pkg_info['name'];
                    }
                    if(!$pkg_info || empty($pkg_info)){
                        $rows[$key]['obj_type'] = 'normal';
                        $rows[$key]['type_id'] = '系统不存在此货号';
                        $rows[$key]['brand'] = '-';
                        $rows[$key]['goods_bn'] = '-';
                    }

                }

                $rows[$key]['day_num'] = $dayNum?round($rows[$key]['sale_num']/$dayNum,2):0;
                $rows[$key]['day_amount'] = $dayNum?strval($rows[$key]['sale_amount']/$dayNum):0;
                $rows[$key]['order_id'] = $rows[$key]['order_id'] ? $rows[$key]['order_id'] : 0;

                $sql = "select sum(sai.num*sai.price) as reship_total_amount,sum(sai.num) as total_reship_num from sdb_sales_aftersale_items sai left join sdb_sales_aftersale sa on sai.aftersale_id = sa.aftersale_id  LEFT JOIN sdb_ome_sales as S on S.order_id=sa.order_id where ".$this->rfilter($filter)." and sai.bn = '".addslashes($val['bn'])."'";
    
                $row = $this->db->select($sql);

                $rows[$key]['reship_total_amount'] = $row[0]['reship_total_amount']?$row[0]['reship_total_amount']:0;

                if($this->stockcost_enabled){

                    /* $aftersale_sql = "select cost as aftersale_cost_amount from sdb_ome_products where bn='".addslashes($val['bn'])."'";

                    $aftersale = $this->db->selectrow($aftersale_sql);//退货商品成本和 */
                    $aftersale['aftersale_cost_amount'] = $val['aftersale_cost_amount'];//$product_info[$val['bn']]['aftersale_cost_amount'];

                }else{

                    $aftersale['aftersale_cost_amount'] = 0;
                    
                }

                $aftersale['aftersale_cost_amount'] = ($aftersale['aftersale_cost_amount'] * $row[0]['total_reship_num']);

                $rows[$key]['name'] = $rows[$key]['name'];

                $rows[$key]['reship_num'] = intval($row[0]['total_reship_num']);//退货数

                $reship_ratio = $rows[$key]['sale_num']?round($rows[$key]['reship_num']/$rows[$key]['sale_num'],2):0;//退货率

                $rows[$key]['reship_ratio'] = ($reship_ratio*100)."%";

                $rows[$key]['agv_cost_amount'] = round($rows[$key]['cost_amount']/$rows[$key]['sale_num'],2);//平均成本

                $rows[$key]['total_cost_amount'] = round($rows[$key]['cost_amount'] - $aftersale['aftersale_cost_amount'],2);//总成本 = 销售成本-售后商品成本之和
//5509.780 -  4795.000;
                $rows[$key]['gross_sales'] = $rows[$key]['sale_amount'] - $rows[$key]['reship_total_amount']-$rows[$key]['total_cost_amount'];//销售毛利 = 销售额-退货总额-总成本

                $rows[$key]['agv_gross_sales'] = round($rows[$key]['gross_sales']/$rows[$key]['sale_num'],2);//销售平均毛利 = 销售毛利/销售量

                $gross_sales_rate = ($rows[$key]['sale_amount']>0) ? round($rows[$key]['gross_sales']/$rows[$key]['sale_amount'],2) : 0;//销售毛利率 = 销售毛利/销售额

                $rows[$key]['gross_sales_rate'] = ($gross_sales_rate*100)."%";
                $rows[$key]['sale_price'] = strval($rows[$key]['sale_amount']/$rows[$key]['sale_num']);//销售单价 = 商品销售之和/销售量

                #$rows[$key]['total_gross_sales'] = $rows[$key]['sale_amount'] - $row[$key]['reship_total_amount'] - $rows[$key]['total_cost_amount'];//总毛利 = 销售额-退货总额-总成本

                #$total_gross_sales_rate = round(($rows[$key]['total_gross_sales'] / ($rows[$key]['sale_amount'] - $row[$key]['reship_total_amount'])),2);//总毛利率 = 总毛利/（销售额-退货金额）

               #$rows[$key]['total_gross_sales_rate'] = ($total_gross_sales_rate*100)."%";
            }

             $createtime = time();
             //对数组排序
             if($orderType){

                foreach($rows as $k=>$data){
                    $type_id[$k] = $data['type_id'];
                    $brand[$k] = $data['brand'];
                    $goods_bn[$k] = $data['goods_bn'];
                    $bn[$k] = $data['bn'];
                    $name[$k] = $data['name'];
                    $goods_specinfo[$k] = $data['goods_specinfo'];
                    $sale_price[$k] = $data['sale_price'];
                    $sale_num[$k] = $data['sale_num'];
                    $sale_amount[$k] = $data['sale_amount'];
                    $day_amount[$k] = $data['day_amount'];
                    $day_num[$k] = $data['day_num'];
                    $reship_num[$k] = $data['reship_num'];
                    $reship_ratio[$k] = $data['reship_ratio'];
                    $reship_total_amount[$k] = $data['reship_total_amount'];
                    $agv_cost_amount[$k] = $data['agv_cost_amount'];
                    $cost_amount[$k] = $data['cost_amount'];
                    $agv_gross_sales[$k] = $data['agv_gross_sales'];
                    $gross_sales[$k] = $data['gross_sales'];
                    $gross_sales_rate[$k] = $data['gross_sales_rate'];
                    #$total_cost_amount[$k] = $data['total_cost_amount'];
                    #$total_gross_sales[$k] = $data['total_gross_sales'];
                    #$total_gross_sales_rate[$k] = $data['total_gross_sales_rate'];
                }

                if(is_string($orderType)){
                    $arr = explode(" ", $orderType);
                    if(strtolower($arr[1]) == 'desc'){
                        array_multisort(${$arr[0]},SORT_DESC,$rows);
                    }
                    else{
                        array_multisort(${$arr[0]},SORT_ASC,$rows);
                    }
                }
             }
        }

        return $rows;
    }

    public function rfilter($filter){

        $where = array(1);
        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' sa.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }

        if(isset($filter['time_from']) && $filter['time_from']){

            $where[] = ' S.ship_time >='.strtotime($filter['time_from']);

        }

        if(isset($filter['time_to']) && $filter['time_to']){

            $where[] = ' S.ship_time <'.strtotime($filter['time_to']);
        }

        $where[] = ' sai.return_type = "return"';

        return implode($where,' AND ');
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $config = app::get('eccommon')->getConf('analysis_config');

        $filter['order_status'] = $config['filter']['order_status'];
        $where = array(1);
        $itemsid = array();

        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' S.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }

        #货号
        if(isset($filter['bn']) && $filter['bn']){
            $where[] = ' SI.bn LIKE \''.addslashes($filter['bn']).'%\'';
            $_SESSION['bn'] = $filter['bn'];
        }else{
            unset($_SESSION['bn']);
        }


        if(isset($filter['order_status']) && $filter['order_status']){
            switch($filter['order_status']){
                case 'createorder':
                    $time_filter = 'order_create_time';
                break;
                case 'confirmed':
                    $time_filter = 'order_check_time';
                break;
                case 'pay':
                    $time_filter = 'paytime';
                break;
                case 'ship':
                    $time_filter = 'ship_time';
                break;
            }

            if(isset($filter['time_from']) && $filter['time_from']){
                $time_from = ' S.'.$time_filter.' >='.strtotime($filter['time_from']);
                $where[] = $time_from;
                $ftime = $time_from;
            }

            if(isset($filter['time_to']) && $filter['time_to']){

                $time_to = ' S.'.$time_filter.' <'.strtotime($filter['time_to']);
                $where[] = $time_to;
                $ftime .= ' AND '.$time_to;
            }

        }else{

            $config['filter']['order_status'] = 'ship';
            app::get('eccommon')->setConf('analysis_config', $config);

            $time_filter = 'ship_time';
        }

        if((isset($filter['brand_id']) && $filter['brand_id'])||(isset($filter['goods_type_id']) && $filter['goods_type_id'])||(isset($filter['goods_bn'])&& $filter['goods_bn'])){

            $sql = "select saleitem.item_id,goods.brand_id,goods.type_id,goods.bn as goods_bn from sdb_ome_sales_items as saleitem left join sdb_ome_sales as S on saleitem.sale_id = S.sale_id left join sdb_ome_products as products on saleitem.bn=products.bn left join sdb_ome_goods as goods on products.goods_id=goods.goods_id
            where ".$ftime;

            #品牌
            if(isset($filter['brand_id']) && $filter['brand_id']){

                $sql .= ' AND goods.brand_id = '.addslashes($filter['brand_id']);
            
                unset($filter['brand_id']);
            }

            #商品编码
            if(isset($filter['goods_bn']) && $filter['goods_bn']){
                $sql .= ' AND goods.bn LIKE \''.addslashes($filter['goods_bn']).'%\'';

                $_SESSION['goods_bn'] = $filter['goods_bn'];
                unset($filter['goods_bn']);

            }else{
                unset($_SESSION['goods_bn']);
            }

            #商品类型
            if(isset($filter['goods_type_id']) && $filter['goods_type_id']){
                $sql .= ' AND goods.type_id =\''.addslashes($filter['goods_type_id']).'\'';
                unset($filter['goods_type_id']);
            }
            
            $query = $this->db->select($sql);

            if ($query) {
                foreach($query as $qu){
                    $saleitem_ids[] = $qu['item_id'];
                }
                $where[] = " SI.item_id IN (".implode(',',$saleitem_ids).")";
            }else{
                $where[] = " 1=0 ";
            }

        }
        #类型
        if($filter['obj_type'] == 'normal'){
            $where[] = " SI.product_id <> 0";
        }elseif($filter['obj_type'] == 'pkg'){
            $where[] = " SI.product_id=0";
        }else{
            unset($filter['obj_type']);
        }

        #查询销售额
        if(isset($filter['_sale_amount_search']) && is_numeric($filter['sale_amount'])){
            switch ($filter['_sale_amount_search']){
                case 'than': $_sql =   ' group by SI.bn HAVING  sale_amount >'.$filter['sale_amount'];break;
                case 'lthan': $_sql =  ' group by SI.bn HAVING  sale_amount <'.$filter['sale_amount'];break;
                case 'nequal': $_sql = ' group by SI.bn HAVING  sale_amount ='.$filter['sale_amount'];break;
                case 'sthan': $_sql =  ' group by SI.bn HAVING  sale_amount <='.$filter['sale_amount'];break;
                case 'bthan': $_sql =  ' group by SI.bn HAVING  sale_amount >='.$filter['sale_amount'];break;
                case 'between':
                    if($filter['sale_amount_from'] && $filter['sale_amount_to'] ){
                        $_sql = ' group by SI.bn HAVING  (sale_amount  >='.$filter['sale_amount_from'].' and sale_amount < '.$filter['sale_amount_to'].')';
                    }else{
                        $_sql = '';
                    }
                    break;
            }
        }
        #查询销售量
        if(isset($filter['_sale_num_search']) && is_numeric($filter['sale_num'])){
            if(isset($filter['_sale_amount_search']) && is_numeric($filter['sale_amount'])){
                $_sql = $_sql.' and ';
            }else{
                $_sql = ' group by SI.bn HAVING ';
            }
            switch ($filter['_sale_num_search']){
                case 'than': $_sql =   $_sql.' sale_num >'.$filter['sale_num'];break;
                case 'lthan': $_sql =  $_sql.' sale_num <'.$filter['sale_num'];break;
                case 'nequal': $_sql = $_sql.' sale_num ='.$filter['sale_num'];break;
                case 'sthan': $_sql =  $_sql.' sale_num <='.$filter['sale_num'];break;
                case 'bthan': $_sql =  $_sql.' sale_num >='.$filter['sale_num'];break;
                case 'between':
                    if($filter['sale_num_from'] && $filter['sale_num_to'] ){
                        $_sql = $_sql.'(sale_num  >='.$filter['sale_num_from'].' and sale_num < '.$filter['sale_num_to'].')';
                    }else{
                        $_sql = '';
                    }
                 break;
             }
         }
        if($where){
            $basefilter = implode($where,' AND ');    
            if($_sql){
                $basefilter = $basefilter.' '.$_sql;
                return $basefilter;
            }else{
                return $basefilter;
            }
        }
    }


    public function exportName(&$data){
        $data['name'] = $_POST['time_from'].'到'.$_POST['time_to'].'货品销售情况';
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        set_time_limit(0);
        sleep(5);
        if(isset($_SESSION['bn'])){
            $filter['bn'] = $_SESSION['bn'];
        }

        if(isset($_SESSION['goods_bn'])){
            $filter['goods_bn'] = $_SESSION['goods_bn'];
        }

         @ini_set('memory_limit','1024M');
        if( !$data['title'] ){
            $title = array();
            foreach( $this->io_title() as $k => $v ){
                $title[] = $v;
            }

            $data['title']['products'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');
        }

        $limit = 100;
        
        $productssale = $this->getList('*',$filter,$offset*$limit,$limit);
       
        if(!$productssale) return false;
        
        foreach ($productssale as $k => $aFilter) {

            foreach( $this->oSchema['csv']['main'] as $kk => $vv ){
                if( $vv == 'obj_type' ){
                    $obj_type = ($aFilter[$vv] == 'pkg')?'捆绑商品':'普通商品';
                    $productRow[$kk] = $obj_type;
                }else{
                   $productRow[$kk] = $aFilter[$vv];
                }
            }

            $data['content']['products'][] = mb_convert_encoding('"'.implode('","',$productRow).'"', 'GBK', 'UTF-8');
            
        }

        return true;

    }

    function export_csv($data,$exportType = 1 ){

        $output = array();

        $output[] = $data['title']['products']."\n".implode("\n",(array)$data['content']['products']);

        echo implode("\n",$output);
    }

    public function io_title( $filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:商品类型'=>'type_id',
                    '*:类型'=>'obj_type',
                    '*:品牌'=>'brand',
                    '*:商品编码'=>'goods_bn',
                    '*:货号'=>'bn',
                    '*:货品名称'=>'name',
                    '*:商品规格'=>'goods_specinfo',
                    '*:销售单价'=>'sale_price',
                    '*:销售量'=>'sale_num',
                    '*:销售额'=>'sale_amount',
                    '*:日均销售额'=>'day_amount',
                    '*:日均销售量'=>'day_num',
                    '*:退货量'=>'reship_num',
                    '*:退货率'=>'reship_ratio',
                    '*:退货总额'=>'reship_total_amount',
                    '*:平均成本'=>'agv_cost_amount',
                    '*:销售成本'=>'cost_amount',
                    '*:销售平均毛利'=>'agv_gross_sales',
                    '*:销售毛利'=>'gross_sales',
                    '*:销售毛利率'=>'gross_sales_rate',
                    #'*:总成本'=>'total_cost_amount',
                    #'*:总毛利'=>'total_gross_sales',
                    #'*:总毛利率'=>'total_gross_sales_rate',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'type_id' => array (
                    'type' => 'table:goods_type@ome',
                    'pkey' => true,
                    'label' => '商品类型',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>1,
                    'orderby' => true,
                    'realtype' => 'varchar(200)',
                ),
                'brand' => array (
                    'type' => 'table:brand@ome',
                    'pkey' => true,
                    'label' => '品牌',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>2,
                    'orderby' => true,
                    'realtype' => 'varchar(200)',
                ),
                'goods_bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '商品编号',
                    'width' => 120,
                    'searchtype' => 'has',
                    'editable' => true,
                    'filtertype' => 'bool',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>3,
                    'orderby' => true,
                    'realtype' => 'varchar(50)',
                ),
                'bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '货号',
                    'width' => 120,
                    'searchtype' => 'has',
                    'editable' => true,
                    'filtertype' => 'bool',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'orderby' => true,
                    'order'=>4,
                    'realtype' => 'varchar(50)',
                ),
                'name' => array (
                    'type' => 'varchar(200)',
                    'pkey' => true,
                    'label' => '货品名称',
                    'width' => 310,
                    #'searchtype' => 'has',
                    'editable' => false,
                    'in_list' => true,
                    'orderby' => true,
                    'default_in_list' => true,
                    'order'=>5,
                    'realtype' => 'varchar(200)',
                ),
                'goods_specinfo' => array (
                    'type' => 'table:goods_type@ome',
                    'pkey' => true,
                    'label' => '商品规格',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>6,
                    'realtype' => 'varchar(200)',
                ),
                'sale_price' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售单价',
                    'width' => 110,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'orderby' => true,
                    'order'=>7,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'sale_num' => array (
                    'type' => 'number',
                    'label' => '销售量',
                    'width' => 75,
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'orderby' => true,
                    'order'=>8,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'sale_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售额',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    'filtertype' => 'number',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>9,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'day_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '日均销售额',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>10,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'day_num' => array (
                    'type' => 'number',
                    'label' => '日均销售量',
                    'width' => 75,
                    'orderby' => true,
                    'editable' => true,
                    //'filtertype' => 'normal',
                    //'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'order'=>11,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'reship_num' => array (
                    'type' => 'varchar(200)',
                    'default' => 1,
                    'required' => true,
                    'label' => '退货量',
                    'orderby' => true,
                    'width' => 110,
                    'editable' => true,
                    //'filtertype' => 'yes',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>12,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'reship_ratio' => array (
                    'type' => 'varchar(200)',
                    'label' => '退货率',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    //'filtertype' => 'time',
                    //'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>13,
                    'realtype' => 'varchar(50)',
                ),
                'reship_total_amount' => array (
                    'type' => 'money',
                    'default' => 1,
                    'required' => true,
                    'label' => '退货总额',
                    'orderby' => true,
                    'width' => 110,
                    'editable' => true,
                   // 'filtertype' => 'yes',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>14,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'agv_cost_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '平均成本',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>15,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'cost_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售成本',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>16,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'agv_gross_sales' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售平均毛利',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                   // 'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>17,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'gross_sales' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售毛利',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>18,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'gross_sales_rate' => array (
                    'type' => 'varchar(200)',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售毛利率',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>19,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'total_cost_amount' => array (
                    'type' => 'money',
                    'default' => 1,
                    'required' => true,
                    'label' => '总成本',
                    'orderby' => true,
                    'width' => 110,
                    'editable' => true,
                    //'filtertype' => 'yes',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>20,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'total_gross_sales' => array (
                    'type' => 'money',
                    'default' => 1,
                    'required' => true,
                    'label' => '总毛利',
                    'orderby' => true,
                    'width' => 110,
                    'editable' => true,
                   // 'filtertype' => 'yes',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>21,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'total_gross_sales_rate' => array (
                    'type' => 'varchar(200)',
                    'default' => 0,
                    'required' => true,
                    'label' => '总毛利率',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    //'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>22,
                    'realtype' => 'mediumint(8) unsigned',
                ),
            ),
            'idColumn' => 'bn',
            'in_list' => array (
                0 => 'type_id',
                1 => 'brand',
                2 => 'goods_bn',
                3 => 'bn',
                4 => 'name',
                5 => 'goods_specinfo',
                6 => 'sale_price',
                7 => 'sale_num',
                8 => 'sale_amount',
                9 => 'day_amount',
                10 => 'day_num',
                11 => 'reship_num',
                12 => 'reship_ratio',
                13 => 'reship_total_amount',
                14 => 'agv_cost_amount',
                15 => 'cost_amount',
                16 => 'agv_gross_sales',
                17 => 'gross_sales',
                18 => 'gross_sales_rate',
                //19 => 'total_cost_amount',
                //20 => 'total_gross_sales',
                //21 => 'total_gross_sales_rate',
            ),
            'default_in_list' => array (
                0 => 'type_id',
                1 => 'brand',
                2 => 'goods_bn',
                3 => 'bn',
                4 => 'name',
                5 => 'goods_specinfo',
                6 => 'sale_price',
                7 => 'sale_num',
                8 => 'sale_amount',
                9 => 'day_amount',
                10 => 'day_num',
                11 => 'reship_num',
                12 => 'reship_ratio',
                13 => 'reship_total_amount',
                14 => 'agv_cost_amount',
                15 => 'cost_amount',
                16 => 'agv_gross_sales',
                17 => 'gross_sales',
                18 => 'gross_sales_rate',
                //19 => 'total_cost_amount',
                //20 => 'total_gross_sales',
                //21 => 'total_gross_sales_rate',
            ),
        );
        return $schema;
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_salesReport_posSales';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_salesReport_posSales';
        }
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $productssale = $this->getList('*',$filter,$start,$end);
        if(!$productssale) return false;
        
        foreach ($productssale as $k => $aFilter) {
            $aFilter['obj_type'] = ($aFilter['obj_type'] == 'pkg')?'捆绑商品':'普通商品';

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($aFilter[$col])){
                    $aFilter[$col] = mb_convert_encoding($aFilter[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $aFilter[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
            
        }

        return $data;
    }
}