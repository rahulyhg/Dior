<?php
class ome_mdl_branch_product_pos extends dbeav_model{
    var $export_flag = false;
    /*
     * 更新货位库存
     */
    function change_store($branch_id, $product_id, $pos_id, $num, $operator='='){
        $now = time();
        $store = "";
        switch($operator){
            case "+":
                $store = "store=IFNULL(store,0)+".$num;
                break;
            case "-":
                $store = " store=IF((CAST(store AS SIGNED)-$num)>0,store-$num,0) ";
                break;
            case "=":
            default:
                $store = "store=".$num;
                break;
        }
        $sql = 'UPDATE sdb_ome_branch_product_pos SET '.$store.' WHERE product_id='.$product_id.' AND branch_id='.$branch_id.' AND pos_id='.$pos_id;
        $rs = $this->db->exec($sql);
        if ($rs) return $this->count_store($product_id,$branch_id);
        return false;
    }

    /**
     * 默认货位库存不够减情况处理
     * @access public
     * @param int $pos_id 货位ID
     * @param int $branch_id 仓库 ID
     * @param int $product_id 货品ID
     * @param int $number 发货数量
     *
     */
    function update_default_pos($branch_id,$product_id,$pos_id,$number){
        //获取默认货位库存值
        $default_pos = $this->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id,'pos_id'=>$pos_id), 'store');
        $remain_pos = $default_pos['store'] - $number;
        if ($remain_pos < 0){
            $this->change_store($branch_id, $product_id, $pos_id, $number, '-');
            $remain_pos = abs($remain_pos);
            //--剩余的库存从其他货位随机消减
            $orderby = 'store DESC';
            $branch_product_pos_list = $this->getList('store,pos_id', array('branch_id'=>$branch_id,'product_id'=>$product_id,'default_pos'=>'false','store|than'=>'0'), 0, -1, $orderby);
            if ($branch_product_pos_list){
                foreach ($branch_product_pos_list as $other_pos){
                    $this->change_store($branch_id, $product_id, $other_pos['pos_id'], $remain_pos, '-');
                    if ($other_pos['store'] >= $remain_pos){
                        break;
                    }else{
                        $remain_pos -= $other_pos['store'];
                    }
                }
            }
        }else{
            $this->change_store($branch_id, $product_id, $pos_id, $number, '-');
        }
    }

    /*
     * 统计所有此商品库存
     */
    function count_store($product_id,$branch_id=0){
        $this->app->model('branch_product')->count_store($product_id,$branch_id);
        $this->app->model('products')->count_store($product_id);
        return true;
    }

    /*
    *
    * 根据仓库号和货号检查此是否已和某商品关联
    * ss备注：此方法已经没有地方调用
    */
    function get_branch_pos($product_id,$pos_id){
        $branch_pos = $this->db->selectrow('SELECT count(*) as count FROM sdb_ome_branch_product AS p
        left join sdb_ome_branch_product_pos AS s on p.product_id=s.product_id
        WHERE p.product_id='.$product_id.' AND s.pos_id=\''.$pos_id.'\'');
        return $branch_pos['count'];
    }

    // ss备注：此方法已经没有地方调用
    function get_branch_pos_exist($product_id,$branch_id){
        $branch_pos = $this->db->selectrow('SELECT count(*) as count FROM sdb_ome_branch_product AS s
        WHERE s.product_id='.$product_id.' AND s.branch_id='.$branch_id);

        return $branch_pos['count'];
    }

    function get_bps_nums($pid) {
        $branch_pos = $this->db->selectrow('SELECT count(*) as count FROM sdb_ome_branch_product_pos
        WHERE product_id='.$pid);
        return $branch_pos['count'];
    }

    function get_product_pos($product_id,$branch_id) {
        $sql = 'SELECT bp.store_position FROM
                (SELECT bpp.*
                        FROM (
                            SELECT pos_id,product_id
                            FROM sdb_ome_branch_product_pos WHERE branch_id = '.$branch_id.'
                            ORDER BY create_time DESC
                        )bpp
                    GROUP BY bpp.product_id
                 ) bb
                 LEFT JOIN sdb_ome_branch_pos bp
                    ON bp.pos_id = bb.pos_id
                    WHERE bb.product_id = '.$product_id;
         $branch_pos = $this->db->selectrow($sql);
         return $branch_pos['store_position'];
    }

    /*
     * 将某产品和仓库和货号关联
     *
     */
    function create_branch_pos($product_id,$branch_id,$pos_id){
        $oBranch_product = &$this->app->model("branch_product");
        $product = $oBranch_product->dump(array('product_id'=>$product_id,'branch_id'=>$branch_id));

        $product_sdf = array(
            'branch_id'=>$branch_id,
            'product_id'=>$product_id
        );
        $pos_sdf=array(
            'operator' => kernel::single('desktop_user')->get_name(),
            'product_id'=>$product_id,
            'pos_id'=>$pos_id,
            'branch_id'=>$branch_id,
            'create_time'=>time()
        );
        $bpp = &$this->app->model('branch_product_pos')->dump(array('product_id'=>$product_id,'pos_id'=>$pos_id), 'pp_id');
        if($bpp){
            $pos_sdf['pp_id'] = $bpp['pp_id'];
        }
        if(empty($product)){
            $pos_sdf['default_pos']='true';
        }

        $product = $oBranch_product->save($product_sdf);
        if($product){
            $this->save($pos_sdf);
        }
        $default = false;
        $pos = $this->get_pos($product_id,$branch_id);
        if ($pos)
        foreach ($pos as $v){
            if ($v['default_pos'] == 'true') {
                $default = true;
            }
        }
        if(count($pos)==1 || (!empty($product) && $default==false)){
            $this->db->exec('UPDATE sdb_ome_branch_product_pos SET default_pos=true WHERE pos_id='.$pos[0]['pos_id']);
        }
        return $product;
    }

    function del_branch_product_pos($product_id, $pos_id) {
          $this->db->exec('DELETE FROM sdb_ome_branch_product_pos WHERE product_id='.$product_id.' AND pos_id=\''.$pos_id.'\'');
    }


    /*
     * 重置货位
     * ss备注：已经没用
     */
    function reset_branch_pos($product_id,$branch_id,$pos_id){
        $this->change_store($branch_id, $product_id, $pos_id, 0);
        $this->db->exec('DELETE FROM sdb_ome_branch_product_pos WHERE product_id='.$product_id.' AND pos_id=\''.$pos_id.'\'');
        $pos = $this->get_pos($product_id,$branch_id);
        if(count($pos)==0){
            $this->db->exec('DELETE FROM sdb_ome_branch_product WHERE product_id='.$product_id.' AND branch_id='.$branch_id.'');

        }
        if(count($pos)>=1){
            $this->db->exec('UPDATE sdb_ome_branch_product_pos SET default_pos=true WHERE pos_id='.$pos[0]['pos_id']);
        }
        $this->count_store($product_id,$branch_id);
    }
    /*
     * 获取某产品已经放置货位列表
     * @param int $product_id $branch_id
     * return array
     */
    function get_pos($product_id,$branch_id){
        //$branch = $this->db->select("SELECT p.*,s.store_position FROM sdb_ome_branch_product_pos as p left join sdb_ome_branch_pos as s on p.pos_id=s.pos_id left join sdb_ome_branch as b on s.branch_id=b.branch_id WHERE p.product_id='$product_id' AND b.branch_id='$branch_id'");
        $branch = $this->db->select("SELECT p.*,s.store_position FROM sdb_ome_branch_product_pos as p
         left join sdb_ome_branch_pos as s on p.pos_id=s.pos_id
         WHERE p.product_id='$product_id' AND p.branch_id='$branch_id' AND s.store_position!=''");
        return $branch;
    }

    /*
    *获取未和产品建立关联货位列表
    *$param int $product_id $branch_id
    * ss备注：ome中已经不使用此方法
    */
    function get_unassign_pos($branch_id){

        //$pos = $this->db->select("SELECT p.pos_id,p.store_position FROM sdb_ome_branch_pos as p LEFT JOIN sdb_ome_branch_product_pos as s on p.pos_id=s.pos_id WHERE p.branch_id='$branch_id' group by p.pos_id having(count(s.pos_id)=0)");
        $sql = "SELECT bp.pos_id,bp.store_position FROM `sdb_ome_branch_pos` bp
                LEFT JOIN `sdb_ome_branch_product_pos` bpp on bp.pos_id=bpp.pos_id
                WHERE bpp.pos_id is NULL and bp.branch_id='$branch_id' ";
        $pos = $this->db->selectRow($sql);
        return array($pos);
    }

    /*
     * 获取未与仓库、货品建立关联的货位
     *  ss备注：此方法已经不使用，可以删除
     */
    function getPosByBranchProduct($branch_id=null, $product_id=null, $pos_name=null){
        if ($pos_name){
            $wheresql = "and bpos.store_position regexp '$pos_name'";
            $field = "bpos.pos_id,bpos.store_position";
        }else{
            $field = "bpos.pos_id";
        }
        $sql = " SELECT $field FROM `sdb_ome_branch_pos` bpos
                 WHERE bpos.`branch_id`='$branch_id' $wheresql and bpos.`pos_id` not in (SELECT bps.pos_id FROM `sdb_ome_branch_product_pos` bps
                 LEFT JOIN `sdb_ome_branch_product` bp on bps.product_id=bp.product_id and bp.branch_id='$branch_id' and bp.product_id='$product_id') ";
        $pos = $this->db->select($sql);
        return $pos;
    }

    /*
     * 根据名称获取货位
     * ss备注：此方法已经不使用，可以删除
     */
    function getPosByName($branch_id=null, $pos_name=null){
        if ($pos_name){
            $sql = " SELECT bpos.pos_id,bpos.store_position FROM `sdb_ome_branch_pos` bpos
                     WHERE bpos.`branch_id`='$branch_id' ";
            $sql .= " AND bpos.store_position regexp '$pos_name'";
            $pos = $this->db->select($sql);
        }
        return $pos;
    }

    function finder_list($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
            if(!$cols){
                $cols = $this->defaultCols;
            }
            if(!empty($this->appendCols)){
                $cols.=','.$this->appendCols;
            }

            $col_tmp = explode(",",$cols);
            foreach($col_tmp as $k=>$v){
                $tmp = explode(" ",$v);
                if(!is_numeric($tmp[0])){
                   $col_tmp[$k] = 'bpp.'.$v;
                }
            }
            $col_tmp[] = 'p.barcode,p.name,p.spec_info,bp.store_position,bpp.branch_id,bpp.product_id';
            $cols = implode(",",$col_tmp);

            $orderType = $orderby?$orderby:$this->defaultOrder;

            $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product_pos AS bpp
                    LEFT JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id)
                    LEFT JOIN sdb_ome_products AS p ON(bpp.product_id=p.product_id)
                    WHERE '.$this->_filter($filter,'bpp');
            if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
            $data = $this->db->selectLimit($sql,$limit,$offset);

            return $data;
    }

    function finder_count($filter=null){
        if ($_GET['act'] != 'index'){
            return parent::count($filter);
        }else {
            $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_branch_product_pos AS bpp
                    LEFT JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id)
                    LEFT JOIN sdb_ome_products AS p ON(bpp.product_id=p.product_id)
                    WHERE '.$this->_filter($filter,'bpp');
            $row = $this->db->select($sql);
            return intval($row[0]['_count']);
        }
    }

  /*
     * 导出模板标题
     */
    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'branch_pos':
                $this->oSchema['csv'][$filter] = array(
                    '*:货位名称' => 'pos_name',
                    '*:所属仓库' => 'branch_name',
                );
                break;
            case 'export_branch_pos':
                $this->oSchema['csv'][$filter] = array(
                    '*:货位名称' => 'pos_name',
                    '*:货品名称' => 'product_name',
                    '*:条形码' => 'barcode',
                    '*:货号' => 'product_bn',
                    '*:所属仓库' => 'branch_name',
                );
                break;
        }
        #新增导出列
        if($this->export_flag){
            $title = array(
                    '*:规格'=>'spec_info'
            );
            $this->oSchema['csv']['export_branch_pos'] = array_merge($this->oSchema['csv']['export_branch_pos'],$title);
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     /*
      * 导出货位记录
      */
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
         $this->export_flag = true;
        if( !$data['title']['export_branch_pos'] ){
            $title = array();
            foreach( $this->io_title('export_branch_pos') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['export_branch_pos'] = '"'.implode('","',$title).'"';
        }
//        $limit = 100;

        $sql = 'SELECT bp.store_position,p.name as product_name,p.bn as product_bn,p.spec_info,p.barcode,b.name branch_name
                FROM sdb_ome_branch_product_pos AS bpp
                LEFT JOIN sdb_ome_branch_pos AS bp ON(bp.pos_id=bpp.pos_id)
                LEFT JOIN sdb_ome_products AS p ON(bpp.product_id=p.product_id)
                LEFT JOIN sdb_ome_branch b ON(bp.branch_id=b.branch_id)
                WHERE '.$this->_filter($filter,'bpp');

        //$sql .= ' GROUP BY bpp.pos_id ';
        $sql .= ' ORDER BY bpp.pos_id desc ';
        $list = $this->db->select($sql);
        if (!$list) return false;
        foreach( $list as $val ){
            $pRow = array();
            $detail['pos_name'] = $val['store_position'];
            $detail['product_name'] = $val['product_name'];
            $detail['barcode'] = $val['barcode'];
            $detail['product_bn'] = $val['product_bn'];
            $detail['branch_name'] = $val['branch_name'];
            $detail['spec_info'] = $val['spec_info'];
            foreach( $this->oSchema['csv']['export_branch_pos'] as $k => $v ){
                $pRow[$k] =  utils::apath( $detail,explode('/',$v)  );
            }
            $data['content']['export_branch_pos'][] = '"'.implode('","',$pRow).'"';
        }
        $data['name'] = '货位表'.date("Ymd",time());

        return false;
    }
    function export_csv($data,$exportType = 1 ){
        $output = array();
        //if( $exportType == 2 ){
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        //}
        return implode("\n",$output);
    }

    /*
     * CSV导入
     */
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        base_kvstore::instance('ome_branch_product_pos')->fetch('branch_product_pos-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,'');
        $pTitle = array_flip( $this->io_title('branch_pos') );
        $pSchema = $this->oSchema['csv']['export_branch_pos'];
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;

        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();

        foreach ($aP['branch_product_pos']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }

        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'货位导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => 'branch_product_pos'
                ),
                'status' => 'hibernate',
                'worker'=>'ome_branch_product_pos_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    //CSV导入业务处理
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){

        if (empty($row)){
            if ($this->branch_error){
                $temp = $this->branch_error;
                $temp = array_unique($temp);
                sort($temp);
                $msg['error'] .= '\n系统中不存在的仓库：';
                $msg['error'] .= implode(',', $temp);
                unset($temp);
                unset($this->branch_error);
                base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,'');
                return false;
            }
            if ($this->branch_pos_error){
                $temp = $this->branch_pos_error;
                $temp = array_unique($temp);
                sort($temp);
                $msg['error'] .= '\n系统中不存在的货位：';
                $msg['error'] .= implode(',', $temp);
                unset($temp);
                unset($this->branch_pos_error);
                base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $mark = false;
        $re = base_kvstore::instance('ome_branch_product_pos')->fetch('branch_product_pos-'.$this->ioObj->cacheTime,$fileData);

        if( !$re ) $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{
            $row[3] = trim($row[3]);
            if ($row[4] and $row[0]){
                //判断仓库是否存在
                $branch_pos_obj = &app::get('ome')->model('branch_pos');
                $pobj = &app::get('ome')->model('products');
                $branch = app::get('ome')->model('branch')->dump(array('name'=>$row[4]),'branch_id');
                $row[2] = trim($row[2]);
                if(!empty($row[2]) && $row[2]!=''){
                    $pFilter['barcode'] = $row[2];
                }else{
                    $pFilter['bn'] = $row[3];
                }
                $products = $pobj->dump($pFilter, 'product_id');
                $branch_pos = $branch_pos_obj->dump(array('store_position'=>trim($row[0]), 'branch_id'=>$branch['branch_id']), 'pos_id');
                if(!$branch){
                    $this->branch_error = isset($this->branch_error)?array_merge($this->branch_error,array($row[4])):array($row[4]);
                }
                if(!$branch_pos){
                    $this->branch_pos_error = isset($this->branch_pos_error)?array_merge($this->branch_pos_error,array($row[0])):array($row[0]);
                }

                $branch_product_pos = app::get('ome')->model('branch_product_pos')->dump(array('product_id'=>$products['product_id'],'pos_id'=>$branch_pos['pos_id']),'pp_id');
                if($branch_product_pos){
                    $row['pp_id'] = $branch_product_pos['pp_id'];
                }
            }
            $fileData['branch_product_pos']['contents'][] = $row;
            base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,$fileData);
        }
        return null;
    }


    function searchOptions(){
        return array(
                'store_position'=>app::get('ome')->_('货位'),
                'barcode'=>app::get('ome')->_('条形码'),
                'bn'=>app::get('ome')->_('货号'),
                'product_name'=>app::get('ome')->_('商品名称'),
            );
    }

    function _filter($filter,$tableAlias=null,$baseWhere=null){

        if(isset($filter['store_position'])){
            $where .= " bp.store_position='".addslashes($filter['store_position'])."'";
            unset($filter['store_position']);
        }
        if(isset($filter['barcode'])){
            $where .= " p.barcode='".addslashes($filter['barcode'])."'";
            unset($filter['barcode']);
        }
        if(isset($filter['bn'])){
            $where_info = app::get('ome')->model('products')->select()->columns('product_id')->where('bn=?',$filter['bn'])->instance()->fetch_row();
            if($where_info['product_id']){
                $where .= " bpp.product_id =".$where_info['product_id'];
                unset($filter['bn']);
            }
        }
        if(isset($filter['product_name'])){
            $product = app::get('ome')->model('products')->getList('product_id',array('`name`|head'=>$filter['product_name']));

            if($product){
                $product_id = array();
               foreach($product as $product){
                $product_id[] = $product['product_id'];
               }
               $product_id = implode(',',$product_id);
                $where .= " bpp.product_id in (".$product_id.")";

                unset($filter['product_name']);
                unset($product);
            }
        }
        if(isset($filter['branch_id']) && $filter['branch_id']==''){
            unset($filter['branch_id']);
        }
        $sWhere = parent::_filter($filter,$tableAlias,$baseWhere);
        if(!empty($where)){
            $sWhere .= " AND ".$where;
        }

        return $sWhere;
    }

    function pre_recycle(&$rows){
        $is_super = kernel::single('desktop_user')->is_super();
        if(!$is_super){
            $oBranch = &app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
        }

        if($branch_ids){
            foreach ($rows as $key => $val){
                if(!in_array($val['branch_id'], $branch_ids)){
                    unset($rows[$key]);
                }
            }
        }
        return true;
    }
    #获取货号在仓库的所有货位
    function getBranchPrducAllPos($branch_id = null,$product_id=null){
        $sql = '
                select 
                   store_position 
                from  sdb_ome_branch_pos pos 
                left join sdb_ome_branch_product_pos branch 
                on branch.pos_id=pos.pos_id where branch.branch_id='.$branch_id.' and branch.product_id='.$product_id;
        $rows = $this->db->select($sql);
        if($rows){
            return $rows;
        }
        return false;
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product_pos') {
            $type .= '_dailyManager_posTidy';
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product_pos') {
            $type .= '_dailyManager_posTidy';
        }
        $type .= '_import';
        return $type;
    }
}