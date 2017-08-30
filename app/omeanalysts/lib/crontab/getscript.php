<?php
class omeanalysts_crontab_getscript{

    function get(){
        $base_path = 'lib/crontab';// ·������ڵ�ǰAPP��,����Ҫ�ṩ��ǰAPP�����
        $script = array(
            $base_path."/goodsamount.php",#��Ʒ��ͳ��
            $base_path."/goodsrma.php",#��Ʒ�ۺ�ͳ��
            $base_path."/ordersPrice.php",#�͵��۷ֲ����
            $base_path."/ordersTime.php",#�µ�ʱ��ֲ����
            $base_path."/rmatype.php",#�ۺ����ͷֲ�ͳ��
            $base_path."/sale.php",#����ͳ��
            $base_path."/storeStatus.php",#���״̬�ۺϷ���
            $base_path."/catSaleStatis.php",#��Ʒ��Ŀ����ͳ��
            //$base_path."/bpStockDetail.php",#���ÿ�ձ���
            //$base_path."/zeroSale.php",#��������Ʒ����
            $base_path."/productSaleRank.php",#��Ʒ��������
        );
        return $script;
    }

}