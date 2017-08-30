<?php
class migrate extends PHPUnit_Framework_TestCase
{
    function setUp() {
        //本测试用例正对于单仓库使用
        
    }
    
    public function testMigrate(){
        $path = ROOT_DIR."/app/ome/testcase/";
        $log = $path."migrate.log";
        error_log("Migrate begin.......:\n",3,$log);
        $handle = fopen($path.'zyc.csv','rb');
        if($handle){
            $oGoods = &app::get('ome')->model('goods');
            $oSupplier = &app::get('purchase')->model('supplier');
            $oBranch_pos = &app::get('ome')->model('branch_pos');
            $oBranch_product = &app::get('ome')->model('branch_product');
            $oBranch_product_pos = &app::get('ome')->model('branch_product_pos');
            $oSupplier_goods = &app::get('purchase')->model('supplier_goods');
            $db = kernel::database();
            
            $db->beginTransaction();
            
            $i = 0;
            
            fgets($handle); //get first line title
            while(!feof($handle)){
                $i ++;
                $buffer = fgets($handle,4096);
                if(trim($buffer)){
                    $data = explode(",",$buffer);
                    if(count($data) != 13){
                        error_log("Wrong data:\n".print_r($data,true)."\n\n\n",3,$log);
                    }else{
                        $bn = trim($data[0]);
                        $barcode = '';
                        $goods_name = trim($data[1]);
                        $unit = trim($data[3]);
                        $store = trim($data[4])?trim($data[4]):0;
                        $cost = trim($data[7]);
                        $supplier_name = trim($data[9]);
                        $pos = trim($data[10]);
                        $alert_store = trim($data[11]);
                        $price = floatval(trim($data[12]));
                    
                        if(!$goods_name){
                            error_log("Goods name empty, bn:\n".$bn."\n\n\n",3,$log);
                            continue;
                        }
                        
                        if(!$bn){
                            error_log("Goods bn empty, name:\n".$goods_name."\n\n\n",3,$log);
                            continue;
                        }
                    
                        if($db->selectrow("SELECT goods_id FROM sdb_ome_goods WHERE bn='".$bn."'")){
                            error_log("Duplicate code:\n".$bn."\n\n\n",3,$log);
                            continue;
                        }
                    
                        //添加商品
                        $goods = array(
                            'name' => $goods_name,
                            'bn' => $bn,
                            'unit' => $unit,
                            'category' => array('cat_id'=>0),
                            'type' => array('type_id'=>1),
                            'status' => 'true',
                            'product' => array(
                                array(
                                    'status' => 'true',
                                    'price' => array(
                                            'price' => Array(
                                                'price' => $price,
                                            ),
                                            'cost' => array(
                                                'price' => $cost,
                                            ),
                                            'mktprice' => array(
                                                'price' => 0,
                                            ),
                                        ),
                                    'bn' => $bn,
                                    'barcode' => $barcode,
                                    'store' => $store,
                                    'store_freeze' => 0,
                                    'weight' => 0.00,
                                    'alert_store' => $alert_store,
                                    'default' => 1,
                                ),
                            ),
                        );
                        $oGoods->save($goods);
                        $goods_id = $goods['goods_id'];
                        $product_id = $goods['product'][0]['product_id'];
                        
                        //添加供应商并获取供应商id
                        if($supplier_name){
                            if($supplier=$db->selectrow("SELECT supplier_id FROM sdb_purchase_supplier WHERE name='".$supplier_name."'")){
                                $supplier_id = $supplier['supplier_id'];
                            }else{
                                $supplier = array(
                                    'name' => $supplier_name,
                                    'bn' => 'supplier'.$i,
                                );
                                $oSupplier->save($supplier);
                                $supplier_id = $supplier['supplier_id'];
                            }
                            
                            //添加供应商和商品的关联表
                            $supplier_goods = array(
                                'supplier_id' => $supplier_id,
                                'goods_id' => $goods_id,
                            );
                            $oSupplier_goods->save($supplier_goods);
                        }
                        
                        
                        
                        if($pos){
                            //添加货位并获取货位id
                            if($branch_pos=$db->selectrow("SELECT pos_id FROM sdb_ome_branch_pos WHERE store_position='".$pos."'")){
                                $pos_id = $branch_pos['pos_id'];
                            }else{
                                $branch_pos = array(
                                    'store_position' => $pos,
                                    'branch_id' => 1,
                                    'stock_threshold' => $alert_store,
                                );
                                $oBranch_pos->save($branch_pos);
                                $pos_id = $branch_pos['pos_id'];
                            }
                            
                            //填写货品的仓库库存和货位库存
                            $branch_product = array(
                                'branch_id' => 1,
                                'product_id' => $product_id,
                                //'store' => $store,
                                'store_freeze' => 0,
                                'last_modified' => time(),
                                'arrive_store' => 0,
                            );
                            $oBranch_product->save($branch_product);
                            
                            $branch_product_pos = array(
                                'product_id' => $product_id,
                                'pos_id' => $pos_id,
                                //'store' => $store,
                                'default_pos' => 'true',
                                'create_time' => time(),
                                'branch_id' => 1,
                            );
                            $oBranch_product_pos->save($branch_product_pos);
                            $oBranch_product_pos->change_store(1, $product_id, $branch_product_pos['pos_id'],$store);
                        }else{
                            error_log($goods_name." is not in the branch, because pos is not set\n\n\n",3,$log);
                        }
                    }
                }
            }
            $db->commit();
            fclose($handle);
            error_log("Migrate end!\n",3,$log);
        }else{
            error_log("Can not open zyc.csv!",3,$log);
        }
    }
}
