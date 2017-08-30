<?php
class wms_common{

    /**
     *
     * 根据地区及物流公司id获取实际运费
     * @param int $area_id 地区ID
     * @param int $logi_id 物流公司ID
     * @param double $weight 重量
     * @return double
     */
    function getDeliveryFreight($area_id=0,$logi_id=0,$weight=0){

        if($logi_id && $logi_id>0){
            $dlyCorpObj = &app::get('ome')->model('dly_corp');
            $corp  = $dlyCorpObj->dump($logi_id);//物流公司信息
        }
        if($corp['setting']=='1'){
            $firstunit = $corp['firstunit'];
            $continueunit = $corp['continueunit'];
            $firstprice = $corp['firstprice'];
            $continueprice = $corp['continueprice'];
            $dt_expressions = $corp['dt_expressions'];
        }else{
            //物流预算费用计算
            if($area_id && $area_id>0){
                $regionObj = kernel::single('eccommon_regions');
                $region = $regionObj->getOneById($area_id);
                $regionIds = explode(',', $region['region_path']);
                foreach($regionIds as $key=>$val){
                    if($regionIds[$key] == '' || empty($regionIds[$key])){
                        unset($regionIds[$key]);
                    }
                }
            }
            $regionIds = implode(',',$regionIds);
            #物流公式设置明细表
            $sql = 'SELECT firstunit,continueunit,firstprice,continueprice,dt_expressions,dt_useexp FROM sdb_ome_dly_corp_items WHERE corp_id='.$logi_id.' AND region_id in ('.$regionIds.') ORDER BY region_id DESC';

            $corp_items = kernel::database()->selectrow($sql);
            $firstunit = $corp_items['firstunit'];
            $continueunit = $corp_items['continueunit'];
            $firstprice = $corp_items['firstprice'];
            $continueprice = $corp_items['continueprice'];
            $dt_expressions = $corp_items['dt_expressions'];
        }

        if($dt_expressions && $dt_expressions != ''){

            $price = utils::cal_fee($dt_expressions, $weight, 0,$firstprice,$continueprice); //TODO 生成快递费用
        }else{
            if($continueunit>0){
                $continue_price = (($weight-$firstunit)/$continueunit)*$continueprice;
            }else{
                $continue_price = 0;
            }
            $price = $firstprice+$continue_price;
        }
    	return $price ? $price : 0.00;
    }
}