<?php
class qmwms_queue{
    
    private $_queue;
    
    private $_ome;
    
    private $_limit = 1000;
    
    private $_huddle = 1500;
    
    private $_method = array(
        'deliveryOrderConfirm' => 'do_delivery',
        'returnOrderConfirm' => 'do_finish',
    );
    
    public function __construct(&$app)
    {
        $this->_queue = app::get('qmwms')->model('queue');
        $this->_ome = app::get('ome');
    }
    
    public function doQueue()
    {   
        if(! $this->canRun()) {
            return true;
        }

        $datas = $this->_queue->getList('*', array('status'=>0), 0, $this->_limit);
        if(empty($datas)) {
            return $this->clearCache();
        }
        
        $objQm = kernel::single('qmwms_response_qmoms');
        
        foreach($datas as $ids) {
            $this->begin($ids['id']);
        }
            
        foreach($datas as $data) {
            $id = $data['id'];
            $method = $data['api_method'];
            
            if(!isset($this->_method[$method])) {
                $this->end($id, 3);
                continue;
            }
            
            try{
                if(! $objQm->{$this->_method[$method]}($data['api_params'])) {
                    $this->end($id, 2);    
                    continue;
                }
                $this->end($id, 3); 
            }catch(Exception $e) {
                $this->end($id, 2, $e->getMessage());
            }
        }
        
        $this->clearCache();
    }
    
    public function begin($id) 
    {
        $this->_queue->update(array('status'=>1), array('id'=>$id));
    }
    
    public function end($id, $status='2', $msg='') 
    {
        if($status == '2') {
            $this->_queue->update(array('status'=>2, 'msg'=>$msg), array('id'=>$id));
        }else{
            $this->_queue->delete(array('id'=>$id));
        }
    }
    
    public function canRun() {
        $run = $this->getCache();
        
        if(empty($run)) {
            return $this->setCache();
        }else{
            if($run == '1') {
                $count = $this->_queue->count(array('status'=>0));
                if($count >= $this->_huddle) {
                    kernel::single("emailsetting_send")->send("jinrong.zhang@d1m.cn;jun.li@d1m.cn", 'QM队列过于臃肿', 'QM队列过于臃肿');
                }
                return false;
            }else{        
                return $this->setCache();
            }
        }
    }
    
    public function clearCache() {
        $this->_ome->setConf('running', 2);
        return false;
    }
    
    public function setCache() {
        $this->_ome->setConf('running', 1);
        return true;
    }
    
    public function getCache() {
        return $this->_ome->getConf('running');
    }
    
}
?>