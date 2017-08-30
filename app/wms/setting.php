<?php
$setting = array( 
'wms.delivery.check'=>array('type'=>SET_T_INT,'default'=>5,'desc'=>'发货校验商品数量设置'),
'wms.delivery.weight'=>array('type'=>SET_T_ENUM,'default'=>'on','options'=>array('on'=>'开启','off'=>'关闭'),'desc'=>'逐单发货是否称重'),
'wms.delivery.weightwarn'=>array('type'=>SET_T_ENUM,'default'=>'off','options'=>array('on'=>'开启','off'=>'关闭'),'desc'=>'逐单发货是否称重报警'),
'wms.delivery.minWeight'=>array('type'=>SET_T_TXT,'default'=>'10','desc'=>'最小包裹重量'),
'wms.delivery.maxWeight'=>array('type'=>SET_T_TXT,'default'=>'30000','desc'=>'最大包裹重量'),
'wms.delivery.cfg.radio'=>array('type'=>SET_T_INT,'default'=>1,'desc'=>'打印模式:经典打印、按品类打印'),
'wms.groupCalibration.intervalTime'=>array('type'=>SET_T_INT,'default'=>2,'desc'=>'分组校验时间间隔设置(分钟)'),
'wms.groupDelivery.intervalTime'=>array('type'=>SET_T_INT,'default'=>2,'desc'=>'分组发货时间间隔设置(分钟)'),
'wms.product.serial.delivery'=>array('type'=>SET_T_BOOL,'default'=>'false','desc'=>'唯一码发货回传'),
);
?>