<?php
class omeanalysts_mdl_ome_type extends dbeav_model{
    public function get_shop(){//店铺
        $sql = 'SELECT shop_id as type_id,name,relate_id FROM '.
            kernel::database()->prefix.'ome_shop as S LEFT JOIN '.
            kernel::database()->prefix.'omeanalysts_relate as R ON R.relate_key=S.shop_id';
        $row = $this->db->select($sql);
        return $row;
    }

    public function get_dly_corp(){//物流公司
        $logi_ids = array('全部');
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $dlyCorps = $dlyCorpObj->getList('corp_id,branch_id,all_branch,name');
        if($_POST['branch_id'])
        {
            foreach($dlyCorps as $dlyCorp){
                if($dlyCorp['all_branch']=='true' || $dlyCorp['branch_id']==$_POST['branch_id']){
                    $logi_ids[$dlyCorp['corp_id']] = $dlyCorp['name'];
                }
            }
        }else{
            foreach($dlyCorps as $dlyCorp){
                
                $logi_ids[$dlyCorp['corp_id']] = $dlyCorp['name'];
                
            }
        }
        
        

        return $logi_ids;
    }

    public function get_branch(){//仓库
        $branchObj = &app::get('ome')->model('branch');
        $branchs = $branchObj->getList('branch_id,name',array('is_deliv_branch'=>'true'));
        foreach($branchs as $key=>$val){
            $branchs[$key]['type_id'] = $branchs[$key]['branch_id'];
        }
        return $branchs;
    }

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'order_id' => array (),
            ),
            'idColumn' => 'order_id',
        );
        return $schema;
    }

    /**
     * 获取品牌
     *
     * @return void
     * @author 
     **/
    function get_brand()
    {
        $sql = 'SELECT brand_id as type_id,brand_name as name FROM '.kernel::database()->prefix.'ome_brand';
        $row = $this->db->select($sql);
        return $row;
    }

    /**
     * 获取商品类型
     *
     * @return void
     * @author 
     **/
    function get_gtype()
    {
        $sql = 'SELECT type_id,name FROM '.kernel::database()->prefix.'ome_goods_type';
        $row = $this->db->select($sql);
        return $row;
    }

    /**
     * 获取售后类型
     *
     * @return void
     * @author 
     **/
    function get_return_type()
    {
        $data[0]['type_id'] = 'return';
        $data[0]['name'] = '退货';
        $data[1]['type_id'] = 'change';
        $data[1]['name'] = '换货';
        //$data[2]['type_id'] = 'refund';
        //$data[2]['name'] = '退款';                                 
        return $data;
    }

    /**
     * 获取售后服务类型
     *
     * @return void
     * @author 
     **/
    function get_problem_type()
    {
        $Oreturn_problem = app::get('ome')->model('return_product_problem');
        $catlist = $Oreturn_problem->getList('problem_id as type_id,problem_name as name');
        return $catlist;
    } 
   function getProductType(){
       $product_type[0]['type_id'] = 'normal';
       $product_type[0]['name'] = '普通商品';
       $product_type[1]['type_id'] = 'pkg';
       $product_type[1]['name'] = '捆绑商品'; 
       return $product_type;
   }

}
