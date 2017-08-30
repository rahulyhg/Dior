<?php
class replacesku_order
{
      /*
       * 转换订单规格
       *
       * @param array order_list 订单
       *
       * @return array
       */
      public function transform_sku($order_list)
      {
            $oOrders       = app::get('ome')->model('orders');
            $oOrders_items = app::get('ome')->model('order_items');
            $oOperation_log = app::get('ome')->model('operation_log');
            $other = 0;
            $succ  = 0;
            $fail  = 0;
            $total = count($order_list);
            $memo = '';
            foreach($order_list as $k=>$v){
                $items_list = $oOrders_items->getlist('obj_id,item_id,addon,name,bn,amount,nums,shop_goods_id,	shop_product_id,bn',array('order_id'=>$v['order_id'],'delete'=>'false'));
                if($items_list){
                    $need_tran_sku = $this->trans_sku_count($items_list);
                    $need_tran_sku_num = count($need_tran_sku);
                    $order['order_id'] = $v['order_id'];
                    if($need_tran_sku_num>1){
                          $order['pause']      = 'true';
                          $oOrders->save($order);
                          $other++;
                    }else if($need_tran_sku_num==1){
                      $result = $this->filter_custom_mark($v['order_id'],$need_tran_sku,$memo);

                      if($result){
                           $oOrders->save($result);
                           $delete_item=array('item_id'=>$need_tran_sku[0]['item_id'],'delete' =>'true');
                           $oOrders_items->save($delete_item);
                           $succ++;
                      }else{
                            $order['pause'] = 'true';
                            $oOrders->save($order);
                            $fail++;
                      }
                      $oOperation_log->write_log('order_modify@ome',$v['order_id'],$memo);

                    }else{
                        $other++;
                    }
                }else{
                        $other++;
                }
            }
            $mess = array('total'=>$total,'succ'=>$succ,'fail'=>$fail,'other'=>$other);
            return $mess;
      }

       /*
          * 返回需要转换的订单明细列表
          *
          * @param array order_objects 订单
       *
          * @return array
          */
      public function order_sku_filter($order_objects)
      {
        foreach($order_objects as $key=>$object){
        $order_items = $object['order_items'];
            foreach($order_items as $k=>$v){
            $addon = unserialize($v['addon']);
             foreach($addon['product_attr'] as $k1=>$v1){
                $match_sku = array();
                 if($v1['value']){
                    $match_sku_flag = $this->filter_sku_set($v1['value']);
                    if($match_sku_flag){
                        $need_tran_sku[]= $v;
                    }
                 }
            }
            }
         }
        return $need_tran_sku;
      }


      public function trans_sku_count($order_items)
      {
        $need_tran_sku = array();
        foreach($order_items as $k=>$v){
            $addon = unserialize($v['addon']);
            foreach($addon['product_attr'] as $k1=>$v1){
                $match_sku = array();
                if($v1['value']){
                    $match_sku_flag = $this->filter_sku_set($v1['value']);
                    if($match_sku_flag){
                        $need_tran_sku[]= $v;
                    }
                }
            }
        }
        return $need_tran_sku;
      }
     /*
      * 过滤转换订单备注
      *
      * @param array need_tran_sku 订单
      *
      * @return array
      */

      public function filter_custom_mark($order_id,$need_tran_sku,&$memo)
      {
           $oOrders = app::get('ome')->model('orders');
           $oProducts = app::get('ome')->model('products');
           $orders = $oOrders->dump($order_id,'custom_mark');
           $oOperation_log = app::get('ome')->model('operation_log');
           $custom_mark = $orders['custom_mark'];
           $need_tran_sku = $need_tran_sku[0];
           $order['order_id'] = $order_id;
           if(!$orders['custom_mark']){
                $memo= '没有对应的转换备注，无法继续转换';
                return false;
           }
           $custom_mark = unserialize($custom_mark);
           $custom_mark1 = array_pop($custom_mark);
          
           $op_content = $custom_mark1['op_content'];

           $data = array();
           $op_content = str_replace(" ","",$op_content);
           $op_content = preg_match('/\[(.*?)\]ok/',$op_content,$data);

           if(empty($data)){
                $memo= '备注无法匹配，请确定约定规则';
                return false;
           }
           $op_content = $data[1];
           if($op_content){
                $op_content = trim($op_content,'&nbsp;');
                $op_content = explode('+',$op_content);
                $items = array();
                $sku_number=0;
                foreach ($op_content as $k2=>$v2){
                     $v2 = explode('*',$v2);
                     $goods = $this->get_goods_id($need_tran_sku['bn']);
                     $goods_id = $goods['goods_id'];

                     if(!$goods_id){
                        $memo= '本地商品库中无此货号'.$need_tran_sku['bn'];
                        return false;
                     }
                     $v2[0]=trim($v2[0]);
                     $productlist = $oProducts->getlist('product_id,bn,goods_id,price,name,spec_desc',array('goods_id'=>$goods_id));

                    $addon = $need_tran_sku['addon'];

                    $addon = unserialize($addon);
                    $new_sku=array();

                    foreach($addon['product_attr'] as $k1=>$v1){
                        
                        $match_sku = array();
                        if($v1['value']){
                         $match_sku_flag = $this->filter_sku_set($v1['value']);
                        
                         if($match_sku_flag){
   
                           $new_sku[]=$v1['label'].$v2[0];//以label+value过滤避免因规格值名称一致
                            
                         }else{
                           $new_sku[]=$v1['label'].$v1['value'];
                         }
                         
                       }
                  }

                 foreach($productlist as $key=>$val)
                 {
                     $spec_desc = $val['spec_desc'];
                     $speck = array();
                     $product_attr = $this->product_attr_array($spec_desc);
                     foreach($product_attr as $pk=>$pv){
                        $speck[] = $pv['label'].$pv['value'];
                     }

                    if(count($speck)==count($new_sku)){
                    $if_sku=$this->array_diffs($speck,$new_sku);

                     if(empty($if_sku)){
                          
                        $products = $val;
                     }
                    }
                  }
                    $price = $products['price'];
                     $items['quantity'] = $v2[1]=='' ? 1:$v2[1];
                     if(!$products){
                        $memo = $v2[0].'此规格商品不存在,打标退出';
                        return false;
                     }

                     $order['order_objects'][$k2]= array(
                         'obj_type'    =>'goods',
                         'goods_id'    =>$products['goods_id'],
                         'bn'          =>$products['bn'],
                         'name'        =>$goods['name'],
                         'price'       =>$price,
                         'amount'      =>$price * $items['quantity'],
                         'quantity'    =>$items['quantity'],
                     );
                     $items['bn']         =  $products['bn'];
                     $items['product_id'] =  $products['product_id'];
                     $items['goods_id']   =  $products['goods_id'];
                     $items['price']      =  $price;
                     $items['name']       =  $goods['name'];
                     $items['amount']     =  $price * $items['quantity'];
                     $items['addon']      =  $this->get_product_attr($products['product_id']);
                     $sku_number+=$items['quantity'];
                     $order['order_objects'][$k2]['order_items'][]=$items;
                }

                if($sku_number!=$need_tran_sku['nums']){
                     $memo= '数量应为'.$need_tran_sku['nums'].'留言里统计的数量和明细数量不一致，须退出';
                     return false;
                }
                $order['is_fail'] = 'false';
                $order['pause']   = 'false';
                $memo='匹配成功,原备注内容'.$custom_mark1['op_content'].'被移除';
                if(empty($custom_mark)){
                    $order['custom_mark'] = '';
                }else{
                    $order['custom_mark'] = serialize($custom_mark);
                }
                 
                return $order;
           }
      }
      /*
      * 获取商品goods_id
      *
      * @param array order_list 订单
      *
      * @return goods_id
      */
      private function get_goods_id($goods_bn)
      {
           $oGoods = app::get('ome')->model('goods');
           $sql = "select goods_id,name from sdb_ome_goods where bn='$goods_bn'";

           $goods = kernel::database()->selectrow($sql);

           return $goods;
      }
     /*
      * 获取商品规格属性
      *
      * @param product_id 订单
      *
      * @return
      */
      private function get_product_attr($product_id)
      {
           $oProducts  = app::get('ome')->model('products');
           $oSpecvalue = app::get('ome')->model('spec_values');
           $oSpec      = app::get('ome')->model('specification');
           $product_info = $oProducts->dump(array('product_id'=>$product_id),'spec_desc');
           $spec_desc = $product_info['spec_desc'];
           $productattr = $this->product_attr_array($spec_desc);

            if ($productattr){
                $product_attr['product_attr'] = $productattr;
                $addon = serialize($product_attr);
           }
           return $addon;
      }
      /*
      * 获取商品规格属性数组
      *
      * @param spec_desc 订单
      *
      * @return
      */
      private function product_attr_array($spec_desc){
        $oSpec      = app::get('ome')->model('specification');
        $oSpecvalue = app::get('ome')->model('spec_values');
          if ($spec_desc)
          foreach ($spec_desc['spec_value_id'] as $sk=>$sv){
                $tmp = array();
                $specval = $oSpecvalue->dump($sv,"spec_value,spec_id");

                $tmp['value'] = $tmp['value'] = $spec_desc['spec_value'][$sk];//取自定义规格值
                $spec = $oSpec->dump($specval['spec_id'],"spec_name");
                $tmp['label'] = $spec['spec_name'];
                $productattr[] = $tmp;
           }
           return $productattr;
      }

    private function array_diffs($array_1, $array_2) {
        $array_2 = array_flip($array_2);
        foreach ($array_1 as $key => $item) {
            if (isset($array_2[$item])) {
                unset($array_1[$key]);
            }
         }

        return $array_1;
    }

    /*
      * 获取是否含有系统设定需过滤值
      *
      * @param sku_value 订单
      *
      * @return
      */
    private function filter_sku_set($sku_value) 
    {
        $sku_set= app::get('ome')->getConf('replacesku.order.sku');
        $sku_set = $sku_set=='' ? '其它颜色' : $sku_set;
        $match_sku_flag = preg_match('/'.$sku_set.'/', $sku_value,$match_sku);
        return $match_sku_flag;
    }
}

