<?php

class omeanalysts_mdl_ordersTime extends dbeav_model{

    function orders_time($data = null){
        $db = kernel::database();
        
        $data['to'] = $data['to'] + 86400;
        
        if(empty($data['shop_id'])){
            $sql = "select sum(h1),sum(h2),sum(h3),sum(h4),sum(h5),sum(h6),sum(h7),sum(h8),sum(h9),sum(h10),sum(h11),sum(h12),sum(h13),sum(h14),sum(h15),sum(h16),sum(h17),sum(h18),sum(h19),sum(h20),sum(h21),sum(h22),sum(h23),sum(h24) from sdb_omeanalysts_ordersTime where dates >= ".$data['from']." AND dates <= ".$data['to']."";
        }else{
            $sql = "select sum(h1),sum(h2),sum(h3),sum(h4),sum(h5),sum(h6),sum(h7),sum(h8),sum(h9),sum(h10),sum(h11),sum(h12),sum(h13),sum(h14),sum(h15),sum(h16),sum(h17),sum(h18),sum(h19),sum(h20),sum(h21),sum(h22),sum(h23),sum(h24) from sdb_omeanalysts_ordersTime where dates >= ".$data['from']." AND dates <= ".$data['to']." AND shop_id = '".$data['shop_id']."'";
        }

        $info = $db->selectrow($sql);

        /*$datas = array();
        $datas['one'] = $info['sum(one)']?$info['sum(one)']:0;
        $datas['two']= $info['sum(two)']?$info['sum(two)']:0;
        $datas['three']= $info['sum(three)']?$info['sum(three)']:0;
        $datas['four']= $info['sum(four)']?$info['sum(four)']:0;
        $datas['five']= $info['sum(five)']?$info['sum(five)']:0;
        $datas['six']= $info['sum(six)']?$info['sum(six)']:0;
        $datas['seven']= $info['sum(seven)']?$info['sum(seven)']:0;
        $datas['eight']= $info['sum(eight)']?$info['sum(eight)']:0;
        $datas['nine']= $info['sum(nine)']?$info['sum(nine)']:0;
        $datas['ten']= $info['sum(ten)']?$info['sum(ten)']:0;
        $datas['eleven']= $info['sum(eleven)']?$info['sum(eleven)']:0;
        $datas['twelve']= $info['sum(twelve)']?$info['sum(twelve)']:0;
        $datas['thirteen']= $info['sum(thirteen)']?$info['sum(thirteen)']:0;
        $datas['fourteen']= $info['sum(fourteen)']?$info['sum(fourteen)']:0;
        $datas['fifteen']= $info['sum(fifteen)']?$info['sum(fifteen)']:0;
        $datas['sixteen']= $info['sum(sixteen)']?$info['sum(sixteen)']:0;
        $datas['seventeen']= $info['sum(seventeen)']?$info['sum(seventeen)']:0;
        $datas['eighteen']= $info['sum(eighteen)']?$info['sum(eighteen)']:0;
        $datas['nineteen']= $info['sum(nineteen)']?$info['sum(nineteen)']:0;
        $datas['twenty']= $info['sum(twenty)']?$info['sum(twenty)']:0;
        $datas['twentyOne']= $info['sum(twentyOne)']?$info['sum(twentyOne)']:0;
        $datas['twentyTwo']= $info['sum(twentyTwo)']?$info['sum(twentyTwo)']:0;
        $datas['twentyThree']= $info['sum(twentyThree)']?$info['sum(twentyThree)']:0;
        $datas['twentyFour']= $info['sum(twentyFour)']?$info['sum(twentyFour)']:0;*/

        return $info;

    }

}
?>