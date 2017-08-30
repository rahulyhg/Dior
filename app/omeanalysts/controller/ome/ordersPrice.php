<?php
/**
 * 客单价查询
 * @author Chris.Zhang
 * @access public
 * @copyright www.shopex.cn 2010.12.30
 */
class omeanalysts_ctl_ome_ordersPrice extends desktop_controller{

    public function __construct($app){
        parent::__construct($app);
        $timeBtn = array(
            'today' => date("Y-m-d"),
            'yesterday' => date("Y-m-d", time()-86400),
            'this_month_from' => date("Y-m-" . 01),
            'this_month_to' => date("Y-m-d"),
            'this_week_from' => date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400),
            'this_week_to' => date("Y-m-d"),
            'sevenday_from' => date("Y-m-d", time()-6*86400),
            'sevenday_to' => date("Y-m-d"),
        );
        $this->pagedata['timeBtn'] = $timeBtn;

    }

    function index(){
        //客单价分布情况crontab的手动调用
        #kernel::single('omeanalysts_crontab_script_ordersPrice')->orderPrice();
        //取所有店铺
        $shopObj = &app::get('ome')->model('shop');
        $shopList = $shopObj->getList('shop_id,name');
        $this->pagedata['shopList']= $shopList;

        if(empty($_POST)){
            $time = time();
            $year = date("Y",$time);
            $date = date("Y-m-d",mktime(0,0,0,1,1,$year));
            $date0 = date("Y-m-d",mktime(0,0,0,12,31,$year));
            $this->pagedata['time_from'] = strtotime($date);
            $this->pagedata['time_to'] = strtotime($date0);
        }else{
            $this->pagedata['time_from'] = strtotime($_POST['time_from']);
            $this->pagedata['time_to'] = strtotime($_POST['time_to']);
            $this->pagedata['shop_id'] = $_POST['ext_type_id'];
        }
        $args['shop_id'] = $_POST['ext_type_id']?$_POST['ext_type_id']:0;
        $this->pagedata['select_type'] = $args['shop_id'];
        $this->pagedata['form_action'] = 'index.php?app=omeanalysts&ctl=ome_ordersPrice&act=index';
        $this->pagedata['path']= '客单价分布情况';
        $this->page('ordersPrice/frame.html');
    }

    //默认取价格区间、价格区间值
    function price_interval_map(){
        $data = $_GET;
        $interval_map = $this->interval_list();
        $price_map = &app::get('omeanalysts')->model('ordersPrice');
        $order_price = $price_map->price_interval($data);
        foreach($order_price as $k => $v){
            if(empty($v)){
                $order_price[$k] = 0;
            }
        }

        $categories = implode(',',$interval_map);
        $volume = implode(',',$order_price);

         $this->pagedata['categories'] = '['.$categories.']';
         $this->pagedata['data']='{
             name: \'客单价分布图\',
             data: ['.$volume.']
         }';

        $this->display('ordersPrice/chart_type_column.html');
    }

    function interval_list(){
        $interval_price = &app::get('omeanalysts')->model('interval');
        $interval_list = $interval_price->getList();
        $interval_map = array();

        foreach($interval_list as $v){
            if(empty($v['from']) && !empty($v['to'])){
                $interval_map[] .= '\''.$v['to'].'以下的\'';
            }elseif(!empty($v['from']) && empty($v['to'])){
                $interval_map[] .= '\''.$v['from'].'以上的\'';
            }else{
                $interval_map[] .= '\''.$v['from'].'至'.$v['to'].'\'';
            }
        }
        return $interval_map;
    }

    function edit(){
        //base_kvstore::instance('omeanalysts_priceInterval')->fetch('priceInterval',$arr);
        $arr = app::get('omeanalysts')->getConf('priceInterval');
        $data = unserialize($arr);
        $this->pagedata['form_action'] = 'index.php?app=omeanalysts&ctl=ome_ordersPrice&act=edit_price_interval';
        $this->pagedata['data'] = $data;
        $this->display('ordersPrice/set.html');
    }

    function edit_price_interval(){
        $this->begin();
        $data = $_POST;
        $interval = &app::get('omeanalysts')->model('interval');
        $arr = array();
        $i = 1;
        $j = 0;
        foreach($data['arfrom'] as $k => $v){
            $arr[$j]['interval_id'] = $i;
            $arr[$j]['from'] = $v;
            $arr[$j]['to'] = $data['arto'][$k];
            $j++;
            $i++;
           
        }
        
        foreach ($arr as $v) {
            $interval->save($v);
        }

        //base_kvstore::instance('omeanalysts_priceInterval')->store('priceInterval',serialize($arr));
        app::get('omeanalysts')->setConf('priceInterval', serialize($arr));
        //base_kvstore::instance('omeanalysts_ordersPrice')->delete('ordersPrice_time');
        app::get('omeanalysts')->setConf('old_time.ordersPrice_time', null);
        
        $this->end(true,app::get('omeanalysts')->_('修改完成'));
    }

}
?>
