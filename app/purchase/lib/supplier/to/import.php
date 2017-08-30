<?php
class purchase_supplier_to_import {

    function run(&$cursor_id,$params){

        $supplierObj = &app::get($params['app'])->model($params['mdl']);
        $brandObj = &app::get('ome')->model('brand');
        $regionsObj = kernel::single('eccommon_regions');
        $supplierSdf = $params['sdfdata'];
        //$supplierObj = &app::get('purchase')->model('supplier');
        $oSBrand = &app::get('purchase')->model("supplier_brand");

        foreach ($supplierSdf as $v){

            $su = array();
            //获取所在地区的ID
            $area_ini = str_replace("：",":",$v[4]);
            $area = explode(":",$area_ini);
            $flag = false;
            if ($area_ini){
                if ( !strstr($area_ini,":") ){
                    $area_name = explode("/",$area_ini);
                    $area1 = $regionsObj->getOneByName(array("local_name"=>$area_name[0]), 'region_id');
                    $area2 = $regionsObj->getOneByName(array("local_name"=>$area_name[1]), 'region_id');
                    if (str_replace(" ","",$area_name[2])){
                        $area3 = $regionsObj->getList('region_id,region_path', array("local_name"=>trim($area_name[2])), 0, -1);
                        if ($area3)
                        foreach ($area3 as $rk=>$rv){
                            if (in_array($rv['region_path'],$area1['region_id']) and in_array($rv['region_path'],$area2['region_id'])){
                                $area3_id = $rv['region_id'];
                                $v[4] = "mainland:".$v[4].":".$area3_id;
                                $flag = true;
                                break;
                            }
                        }
                        if (!$flag and $area3) $v[4] = "mainland:".$v[4].":".$area3[0]['region_id'];
                    }
                }
            }
            $contacter = array();
            $su['bn'] = $v[0];
            $su['name'] = $v[1];
            $su['brief'] = $v[2];
            $su['company'] = $v[3];
            $su['area'] = $v[4];
            $su['addr'] = $v[5];
            $su['zip'] = $v[6];
            $su['telphone'] = $v[7];
            $su['fax'] = $v[8];
            $su['operator'] = $v[9];
            $su['arrive_days'] = intval($v[10]);
            $su['credit_lv'] = $v[11];
            $su['account'] = $v[13];
            $su['bank'] = $v[14];
            $su['memo'] = $v[19];
            if ($v[15] or $v[16] or $v[17] or $v[18]){
                //联系人
                $contacter[0]['name'] = $v[15];
                $contacter[0]['telphone'] = $v[16];
                $contacter[0]['email'] = $v[17];
                $contacter[0]['qqwangwang'] = $v[18];
                $su['contacter'] = serialize($contacter);
            }
            $supplierObj->save($su);

            //供应商品牌关联入库
            if ($v[12]){
                $v[12] = str_replace("，",",",$v[12]);
                if ( !strstr($v[12],",") ){
                    $v[12] .= ",";
                }
                $brand_arr = explode(",",$v[12]);
                if ($brand_arr){
                    foreach ($brand_arr as $sv){
                        if ($sv){
                            //品牌
                            $brand = $brandObj->dump(array("brand_name"=>$sv), 'brand_id');
                            if (!$brand['brand_id']){
                                $brand = array("brand_name"=>$sv);
                                $brandObj->save($brand);
                            }
                            $brand_id = $brand['brand_id'];
                            $supplier_brand = array("supplier_id"=>$su['supplier_id'],"brand_id"=>$brand_id);
                            $oSBrand->save($supplier_brand);
                        }
                    }
                }
            }
        }
        return false;
    }
}
