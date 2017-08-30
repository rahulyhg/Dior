<?php
/**
* 接口日志类
* @author chenjun 2013.01.21
* @copyright shopex.cn 
*/
class ome_apilog_request_apilog extends ome_apilog_rpc{
    /**
    * filter解析
    * @access public
    * @param array $filter
    * @return mixed
    */
    public function filter($filter = array()){
        return $filter;
    }

    /**
    * 
    * @access public
    * @param array $filter
    * @return mixed
    */
    public function count($filter = array()){
        $params['filter'] = $this->filter($filter);
        $response = $this->request('tg.api.count',APILOG_URL,$params);
        return $response['data']['count'] != '' ? $response['data']['count'] : 0;
    }

    /**
    * 
    * @access public
    * @param array $filter
    * @param array $offset
    * @param array $limit
    * @param array $orderby
    * @return mixed
    */
    public function getlist($filter = array(),$offset = '',$limit = '',$orderby = array()){
        $params['filter'] = $this->filter($filter);
        $params['offset'] = $offset;
        $params['limit'] = $limit;
        $params['orderby'] = $orderby;
        $response = $this->request('tg.api.query',APILOG_URL,$params);
        return $response['data'] ? $response['data'] : array();
    }

    /**
    * 
    * @access public
    * @param array $filter
    * @return mixed
    */
    public function insert($data = array()){
        if(!isset($data['retry']) || $data['retry'] == ''){
            $data['retry'] = '0';
        }
        $response = $this->request('tg.api.insert',APILOG_URL,$data);
        $result = $response['status'] == 'success' ? true : false ;
        return $result;
    }

    /**
    * 
    * @access public
    * @param array $filter
    * @return mixed
    */
    public function update($data = array(),$filter = array()){
        $params['data'] = $data;
        $params['filter'] = $this->filter($filter);
        $response = $this->request('tg.api.update',APILOG_URL,$params);
        $result = $response['status'] == 'success' ? true : false ;
        return $result;
    }

    /**
    * 
    * @access public
    * @param array $filter
    * @return mixed
    */
    public function detail($filter = array()){
        $params['filter'] = $this->filter($filter);
        $response = $this->request('tg.api.detail',APILOG_URL,$params);
        return $response['data'];
    }

}