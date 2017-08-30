<?php
/**
 * 相关货品的处理
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_stock_products {

    public static $products = array();

    public static $branch_products = array();

    public static $branches = array();

    function __construct($app)
    {
        $this->app = $app;
        # base_kvstore::config_persistent(false);
    }

    public function set_products($products) 
    {
        self::$products = $products;
    }

    public function set_branch_products($branch_products) 
    {
        self::$branch_products = $branch_products;
    }

    public function set_branches($branches) 
    {
        self::$branches = $branches;
    }

    /**
     * 计算库存时，将货品相关信息记内存
     *
     * @return void
     * @author 
     **/
    public function store_products($product_bn,$data)
    {
        base_kvstore::instance('inventorydepth/local/products')->store('product-'.$product_bn,$data);
    }

    /**
     * @description 从内存中删除货品
     * @access public
     * @param void
     * @return void
     */
    public function delete_products($product_bn) 
    {
        base_kvstore::instance('inventorydepth/local/products')->delete('product-'.$product_bn);
    }

    /**
     * 从内存中读货品
     *
     * @return void
     * @author 
     **/
    public function fetch_products($product_bn)
    {
        if (self::$products[$product_bn]) {
            return self::$products[$product_bn];
        }

        //base_kvstore::instance('inventorydepth/local/products')->fetch('product-'.$product_bn,$data);

        # 如果没有命中
        if (!$data) {
            $data = app::get('ome')->model('products')->select()->columns('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified')
                    ->where('bn=?',$product_bn)->instance()->fetch_row();
        }
        
        self::$products[$product_bn] = $data;
        return $data;
    }

    /**
     * 库存对应货品关系，记内存
     *
     * @return void
     * @author 
     **/
    public function store_branch_products($branch_id,$product_id,$data)
    {
        base_kvstore::instance('inventorydepth/local/branch/products')->store('branch-product-'.$branch_id.'-'.$product_id,$data);
    }

     /**
     * @description 删除仓库商品
     * @access public
     * @param void
     * @return void
     */
    public function delete_branch_products($branch_id,$product_id) 
    {
        base_kvstore::instance('inventorydepth/local/branch/products')->delete('branch-product-'.$branch_id.'-'.$product_id);
    }

    /**
     * 从内存中读仓库对应货品关系
     *
     * @return void
     * @author 
     **/
    public function fetch_branch_products($branch_bn,$product_bn)
    {
        if (self::$branch_products[$branch_bn][$product_bn]) {
            return self::$branch_products[$branch_bn][$product_bn];
        }
        
        if ($this->batch) { return false; }

        $branch = $this->fetch_branch($branch_bn);

        $branch_id = $branch['branch_id'];

        $product = $this->fetch_products($product_bn);
        $product_id = $product['product_id'];
        
        /*
        $query = false;
        if((int)$this->read_store_lastmodify > (int)$product['max_store_lastmodify']) $query = true;
        if ($query === false) {
            base_kvstore::instance('inventorydepth/local/branch/products')->fetch('branch-product-'.$branch_id.'-'.$product_id,$data);
        }*/

        # 如果没有命中
        if (!$data) {
            if ($product_id && $branch_id) {        
                $data = app::get('ome')->model('branch_product')->select()->columns('branch_id,product_id,store,store_freeze,last_modified,arrive_store,safe_store,is_locked')
                        ->where('branch_id=?',$branch_id)
                        ->where('product_id=?',$product_id)->instance()->fetch_row();
            }
        }

        self::$branch_products[$branch_bn][$product_bn] = $data;

        return $data;
    }

    /**
     * @description 仓库编号与ID的对应关系
     * @access public
     * @param void
     * @return void
     */
    public function store_branch($branch_bn,$data) 
    {
        base_kvstore::instance('inventorydepth/local/branch')->store('branch-'.$branch_bn,$data);
    }

    /**
     * @description 读取仓库信息
     * @access public
     * @param void
     * @return void
     */
    public function fetch_branch($branch_bn) 
    {
        if (self::$branches[$branch_bn]) {
            return self::$branches[$branch_bn];
        }

        //base_kvstore::instance('inventorydepth/local/branch')->fetch('branch-'.$branch_bn,$data);
        if (!$data) {
            $data = app::get('ome')->model('branch')->select()->columns('branch_id,branch_bn')->where('branch_bn=?',$branch_bn)->instance()->fetch_row();
        }
    
        self::$branches[$branch_bn] = $data;
        return $data;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function init($filter=array())
    {
        # 货品
        $product_ids = array();

        $pkgGoodModel = app::get('omepkg')->model('pkg_goods');
        $pkgProductModel = app::get('omepkg')->model('pkg_product');
        $productsModel = app::get('ome')->model('products');
        $offset = 0 ; $limit = 1000;
        do {
            $products = $productsModel->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',$filter,$offset,$limit);

            if (!$products) break;
            
            foreach ($products as $key => $product) {

                $this->store_products($product['bn'],$product);

                $product_ids[$product['product_id']] = $product['bn'];
            }

            $offset += $limit;
        } while (true);
        unset($products);

        if(!$product_ids) return false;

        # 仓库货品
        $bpModel = app::get('ome')->model('branch_product');
        $offset = 0; $limit = 1000;
        do {
            $branch_products = $bpModel->getList('branch_id,product_id,store,store_freeze,last_modified,arrive_store,safe_store,is_locked',array('product_id'=>array_keys($product_ids)),$offset,$limit);

            if (!$branch_products) break;

            foreach ($branch_products as $key => $branch_product) {
                $this->store_branch_products($branch_product['branch_id'],$branch_product['product_id'],$branch_product);
            }

            $offset += $limit;
        } while (true);
        unset($branch_products);

        return $product_ids;
    }

    /**
     * 删除货品的后续操作
     *
     * @return void
     * @author 
     **/
    public function recycle($filter)
    {
        $skuModel = $this->app->model('shop_skus');
        // 仓库
        $branches = app::get('ome')->model('branch')->getList('branch_id,branch_bn');
        $products = $this->app->model('products')->getList('bn,product_id',$filter);
        foreach ($products as $key => $product) {
           $skuModel->update(array('mapping'=>'0'),array('shop_product_bn'=>$product['bn']));
           $this->delete_products($product['bn']);
           
           foreach ($branches as $key => $branch) {
               $this->delete_branch_products($branch['branch_id'],$product['product_id']);
           }
        }
    }

    /**
     * @description 保存商品后的操作
     * @access public
     * @param void
     * @return void
     */
    public function after_save($goods) 
    {
        $productModel = $this->app->model('products');
        foreach ($goods['product'] as $product) {
            $p = $this->fetch_products($product['bn']);
            if (!$p) {
                $p = $productModel->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('product_id'=>$product['product_id']),0,1);
                $this->store_products($product['bn'],$p[0]);
            }
        
        }
    }

    /**
     * @description 仓库保存后扩展
     * @access public
     * @param void
     * @return void
     */
    public function after_branch_save($branch) 
    {
        $b = $this->fetch_branch($branch['branch_bn']);
        if (!$b) {
            $b = array(
                'branch_bn' => $branch['branch_bn'],
                'attr' => $branch['attr'],
                'branch_id' => $branch['branch_id'],
            );
            $this->store_branch($branch['branch_bn'],$b);
        }
    }

    /**
     * @description 只执行一次
     * @access public
     * @param void
     * @return void
     */
     /*
    public function init_branches() 
    {
        $branches = app::get('ome')->model('branch')->getList('branch_id,branch_bn,attr');
        foreach ($branches as $key => $branch) {
            $this->store_branch($branch['branch_bn'],$branch);
        }
    }*/


    public function writeMemory($products) 
    {
        $product_ids = array();
        foreach ($products as $key=>$product) {
            self::$products[$product['bn']] = $product;
            
            $product_ids[$product['product_id']] = $product['bn'];
        }
        
        $branches = app::get('ome')->model('branch')->select()->columns('branch_id,branch_bn,attr')->instance()->fetch_all();
        foreach ($branches as $key=>$branch) {
            self::$branches[$branch['branch_bn']] = $branch;

            $branch_ids[$branch['branch_id']] = $branch['branch_bn'];
        }

        # 仓库货品
        $bpModel = app::get('ome')->model('branch_product');
        $branch_products = $bpModel->getList('*',array('product_id'=>array_keys($product_ids)));
        foreach ($branch_products as $key=>$bp) {
            $product_bn = $product_ids[$bp['product_id']];
            $branch_bn = $branch_ids[$bp['branch_id']];

            self::$branch_products[$branch_bn][$product_bn] = $bp;
        }

        $this->batch = true;
    }
    
    public function resetVar() 
    {
        self::$products = self::$branches = self::$branch_products = array();
        $this->batch = false;
        return $this;
    }
}
