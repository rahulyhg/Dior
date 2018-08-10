<?php
class qmwms_queue{
    
    private $_queue;
    
    private $_limit = 1000;
    
    private $_method = array(
        'deliveryOrderConfirm' => 'do_delivery',
        'returnOrderConfirm' => 'do_finish',
    );
    
    public function __construct(&$app)
    {
        $this->_queue = app::get('qmwms')->model('queue');
    }
    
    public function doQueue()
    {
        $datas = $this->_queue->getList('*', array('status'=>0), 0, $this->_limit);
        if(empty($datas)) {
            return true;
        }
        
        $objQm = kernel::single('qmwms_response_qmoms');
        
        foreach($datas as $data) {
            $id = $data['id'];
            $method = $data['api_method'];
            
            $this->begin($id);
            
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
    
}
?>