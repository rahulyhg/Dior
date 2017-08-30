<?php
/**
 * 相关捆绑商品的处理
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_stock_pkg {

    public static $pkg = array();

    public static $pbn = array();

    function __construct($app)
    {
        $this->app = $app;
        base_kvstore::config_persistent(false);
    }

    public function setPkg($pkg) 
    {
        self::$pkg = $pkg;
    }

    public function setPbn($pbn) 
    {
        self::$pbn = $pbn;
    }

    /**
     * @description 捆绑商品写进内存
     * @access public 
     * @param String $pkg_bn 捆绑商品货号
     * @param Array $data  捆绑商品信息
     * @return void
     */
    public function store_pkg($pkg_bn,$data) 
    {
        base_kvstore::instance('inventorydepth/local/pkg')->store($pkg_bn,$data);
    }

    /**
     * @description 从内存中读
     * @access public
     * @param String $pkg_bn 捆绑商品货号
     * @return Array $data  捆绑商品信息
     */
    public function fetch_pkg($pkg_bn) 
    {
        if(isset(self::$pkg[$pkg_bn])) return self::$pkg[$pkg_bn];

        # 读数据库
        $pkgGood = app::get('omepkg')->model('pkg_goods')->select()->columns('*')
                            ->where('pkg_bn=?',$pkg_bn)->instance()->fetch_row();
        if ($pkgGood) {
            $pkgProduct = app::get('omepkg')->model('pkg_product')->select()->columns('*')
                                    ->where('goods_id=?',$pkgGood['goods_id'])->instance()->fetch_all();
            $pkgGood['products'] = $pkgProduct;
        }
        self::$pkg[$pkg_bn] = $pkgGood;

        return self::$pkg[$pkg_bn] ;

        /*
        if ($this->pkg[$pkg_bn]) {
            return $this->pkg[$pkg_bn];
        }

        base_kvstore::instance('inventorydepth/local/pkg')->fetch($pkg_bn,$data);

        $this->pkg[$pkg_bn] = $data;

        return $data;*/
    }

     /**
     * @description 删除捆绑商品
     * @access public
     * @param void
     * @return void
     */
    public function delete_pkg($pkg_bn) 
    {
        base_kvstore::instance('inventorydepth/local/pkg')->delete($pkg_bn);
    }

    public function store_pkg_products($pbn,$data) 
    {
        base_kvstore::instance('inventorydepth/local/pkg/products')->store($pbn,$data);
    }

    /**
     * @description 初始化
     * @access public
     * @param void
     * @return void
     */
    public function init() 
    {
        $pkg = array();

        $pkgGoodModel = app::get('omepkg')->model('pkg_goods');
        $pkgProductModel = app::get('omepkg')->model('pkg_product');
        $offset = 0 ; $limit = 400;
        do {
            $pgkGoods = $pkgGoodModel->getList('*',array(),$offset,$limit);
            if (!$pgkGoods) break;
            
            foreach ($pgkGoods as $key=>$value) {
                $pgkGoods[$key]['products'] = &$products[$value['goods_id']];

                $pp[$value['goods_id']] = $value['pkg_bn'];
            }
            
            $productList = $pkgProductModel->getList('bn,goods_id',array('goods_id'=>array_keys($products)));
            foreach ($productList as $key=>$value) {
                $products[$value['goods_id']][] = $value['bn'];
            }
            unset($productList);
            
            
            foreach ($pgkGoods as $key=>$value) {
                $this->store_pkg($value['pkg_bn'],$value);
            }

            $offset += $limit;
        }while(true);
    }

    public function pre_recycle($rows){
        foreach ($rows as $key=>$value) {
            $this->delete_pkg($value['pkg_bn']);
        }
    }

    /**
     * @description 保存后续操作
     * @access public
     * @param Int $goods_id 捆绑商品ID
     */
    public function after_upsert($goods_id) 
    {
        $pkgGoodModel = app::get('omepkg')->model('pkg_goods');
        $pkgProductModel = app::get('omepkg')->model('pkg_product');

        $pgkGoods = $pkgGoodModel->getList('*',array('goods_id'=>$goods_id),0,1);

        $pkgProducts = $pkgProductModel->getList('bn',array('goods_id'=>$goods_id));

        $pgkGoods[0]['products'] = array_map('current',$pkgProducts);

        $this->store_pkg($pgkGoods[0]['pkg_bn'],$pgkGoods[0]);
    }

    /**
     * @description 涉及到修改的捆绑商品
     * @access public
     * @param void
     * @return void
     */
    public function writeMemory($products = array()) 
    {
        $product_id = array();
        foreach ($products as $key=>$value) {
            $product_id[] = $value['product_id'];
        }

        $pgkGoodModel = app::get('omepkg')->model('pkg_goods');
        $pgkProductModel = app::get('omepkg')->model('pkg_product');

        # 货品波及到的捆绑
        $tmpGoodsId = $pgkProductModel->getList('distinct goods_id',array('product_id'=>$product_id));
        if(!$tmpGoodsId) return false;
        $tmpGoodsId = array_map('current',$tmpGoodsId);

        # 捆绑列表
        $pkgGood = array();
        $tmpPkgGood = $pgkGoodModel->getList('pkg_bn,goods_id',array('goods_id'=>$tmpGoodsId));
        foreach ($tmpPkgGood as $value) {
            $pkgGood[$value['pkg_bn']] = $value;
            $pkgGood[$value['pkg_bn']]['products'] = &$pkgp[$value['goods_id']];
        }
        unset($tmpPkgGood);

        $pbn = $pids = array();
        $pkgProduct = $pgkProductModel->getList('product_id,bn,goods_id,pkgnum',array('goods_id'=>$tmpGoodsId));
        foreach ($pkgProduct as $key=>$value) {
            $pkgp[$value['goods_id']][] = $value;
            
            $pids[] = $value['product_id'];
        }
        
        $new_product_id = array_diff($pids,$product_id);
        if ($new_product_id) {
            $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('product_id'=>$new_product_id));
            
            kernel::single('inventorydepth_stock_products')->writeMemory($products);
        }

        self::$pkg = $pkgGood;

        $this->batch = true;
    }

    public function resetVar() 
    {
        self::$pkg = array();
        $this->batch = false;
        return $this;
    }

}
