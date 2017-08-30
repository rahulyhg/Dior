<?php
/**
 * rabbitmq 访问对像
 */

class taskmgr_connecter_rabbitmq_pecl extends taskmgr_connecter_abstract implements taskmgr_connecter_interface{

    //连接配置信息
    protected $_amqp_config = null;
    //交换机名
    protected $_amqp_exchange_name = null;
    //对列名
    protected $_amqp_queue_name = null; 
    //rabbitmq 连接
    protected $_amqp_connect = null;
    //rabbitmq 通道
    protected $_amqp_channel = null;
    //rabbitmq 交换机
    protected $_amqp_exchange = null;
    //rabbitmq 队列
    protected $_amqp_queue = null;
    //已定义的发布对列
    protected $_publishQueue = array();

    public function __destruct(){
        //销毁类对象的时候断开mq连接
        $this->disconnect();
    }

    /**
     * 初始化数据访问对像
     *
     * @param string $task 任务标识
     * @return void
     */
    public function load($task, $config) {
    
        
        $config['ROUTER'] = sprintf($config['ROUTER'], strtolower($task));
    
        $exchangeName = sprintf('ERP_TASK_%s_EXCHANGE', strtoupper($task));
        $queueName =sprintf('ERP_TASK_%s_QUEUE', strtoupper($task));
    
        $this->connect(array('config' => $config, 'exchangeName' => $exchangeName, 'queueName' => $queueName));
        $this->length();
    }
    
    /**
     * 连接 rabbitmq 服务器 
     *  
     * @param $config Array 连接参数 
     * @param $exchangeName String 交换机名称 
     * @param $queueName String 对列名 
     */
    public function connect($cfg) {

        //分解参数
        $config         = $cfg['config'];
        $exchangeName   = $cfg['exchangeName'];
        $queueName      = $cfg['queueName'];

        if (!$this->_validCfg($config)) {

            return false;
        }

        $exchangeName = trim($exchangeName);
        if (empty($exchangeName)) {

            return false;
        }

        $queueName = trim($queueName);
        if (empty($queueName)) {

            return false;
        }
       
        //断开原有链接
        $this->disconnect();

        //保存参数
        $this->_amqp_config = $config;
        $this->_amqp_exchange_name = $exchangeName;
        $this->_amqp_queue_name = $queueName;
    }

    /**
     * 断开amqp连接 
     *  
     * @param void 
     * @retrun void 
     */
    public function disconnect() {

        if (is_object($this->_amqp_connect)) {

            $this->_amqp_connect->disconnect();
            unset($this->_amqp_connect);
            $this->_amqp_connect = null;
        }

        unset($this->_amqp_channel);
        $this->_amqp_channel = null;

        unset($this->_amqp_exchange);
        $this->_amqp_exchange = null;

        unset($this->_amqp_queue);
        $this->_amqp_queue = null;
    }

    /**
     * 向对列提交信息 
     *  
     * @param $message String 信息内容体 
     * @param $router String  
     * @return boolean 
     */
    public function publish($message, $router) {

        if ($this->_amqpExchange()) {

            if (!isset($this->_publishQueue[$this->_amqp_queue_name])) {

                $this->declareQueue($this->_amqp_queue_name, $this->_amqp_config['ROUTER']);
            }
            return $this->_amqp_exchange->publish($message, $router);
        } else {

            return false;
        }
    }

    /**
     * 获取队列信息 
     *  
     * @param void 
     * @return mixed 
     */
    public function get() {

        if ($this->_amqpQueue()) {

            return $this->_amqp_queue->get(AMQP_AUTOACK);
        } else {

            return null;
        }
    }

    /**
     * 使用 block 模式 
     *  
     * @param void 
     * @return void 
     */
    public function consume($function) {

        if ($this->_amqpQueue()) {
            
            return $this->_amqp_queue->consume($function);
        } else {
            
            return null;
        }
    }

    /**
     * 获取队列信息条数 
     *  
     * @param void 
     * @return integer 
     */
    public function length() {

        if ($this->_amqpQueue()) {
            
            return $this->_amqp_queue->declare();
        } else {

            return 0;
        }
    }

    /**
     *
     */
    public function ack($tagId) {

        if ($this->_amqpQueue()) {
            
            $this->_amqp_queue->ack($tagId);
        } else {

            return null;
        }
    }

    /**
     *
     */
    public function nack($tagId) {

        if ($this->_amqpQueue()) {

            $this->_amqp_queue->nack($tagId);
        } else {

            return null;
        }
    }

    /**
     * 创建AMQP连接 
     *  
     * @param void 
     * @return boolean 
     */
    protected function _amqpConnect() {

        if (is_object($this->_amqp_connect)) {

            if (!$this->_amqp_connect->isConnected()) {

                //重新连接
                return $this->_amqp_connect->reconnect();
            } else {

                return true;
            }
        } else {

            $this->_amqp_connect = new AMQPConnection();
            $this->_amqp_connect->setHost($this->_amqp_config['HOST']);
            $this->_amqp_connect->setPort($this->_amqp_config['PORT']);
            $this->_amqp_connect->setLogin($this->_amqp_config['USER']);
            $this->_amqp_connect->setPassword($this->_amqp_config['PASSWD']);
            $this->_amqp_connect->setVhost($this->_amqp_config['VHOST']);
            return $this->_amqp_connect->connect();
        }
    }

    /** 
     * 创建 amqp channel 
     * @param void 
     * @return Boolean 
     */
    protected function _amqpChannel() {

        if ($this->_amqpConnect()) {

            if (is_object($this->_amqp_channel) && $this->_amqp_channel->isConnected()) {

                return true;
            } else {

                unset($this->_amqp_channel);
                $this->_amqp_channel = new AMQPChannel($this->_amqp_connect);
                return $this->_amqp_channel->isConnected(); 
            }
        } else {

            return false;
        }
    }

    /**
     * 创建 amqp exchange 
     *  
     * @param void 
     * @returb Boolean 
     */
    protected function _amqpExchange() {

        if ($this->_amqpChannel()) {

            if (!is_object($this->_amqp_exchange)) {

                $this->_amqp_exchange = new AMQPExchange($this->_amqp_channel);
                $this->_amqp_exchange->setName($this->_amqp_exchange_name);
                $this->_amqp_exchange->setType(AMQP_EX_TYPE_TOPIC);
                $this->_amqp_exchange->setFlags(AMQP_DURABLE);
                $this->_amqp_exchange->declare();
            }

            return true;
        } else {

            return false;
        }
    }

    /**
     * 创建队列 
     *  
     * @param void 
     * @return Booelean
     */

    protected function _amqpQueue() {

        if ($this->_amqpExchange()) {

            if (!is_object($this->_amqp_queue)) {
                
                $this->_amqp_queue = new AMQPQueue($this->_amqp_channel);
                $this->_amqp_queue->setName($this->_amqp_queue_name);
                $this->_amqp_queue->setFlags(AMQP_DURABLE);
                $this->_amqp_queue->declare();

                //$this->_amqp_queue->bind($this->_amqp_exchange_name, 'tg.ecos_ome.#');  # 只收淘管订单
            }

            return true;
        } else {

            return false;
        }
    }

    /**
     * rabbitmq 连接参数检查 
     *  
     * @param $config Array 要检查的参数数组 
     * @return Boolean 
     */
    protected function _validCfg($config) {

        //先只做简单检查 ，后期可能需对参数做完整检查
        if (!is_array($config) || empty($config)) {

            return false;
        } else {

            return true;
        }
    }

    /**
     * 定义一分发对列
     *
     * @param $queueName String 对列名
     * @param $routerKey String 路由
     * @return void
     */
    public function declareQueue($queuName, $routerKey ='#') {

        $this->_publishQueue[$queuName] = new AMQPQueue($this->_amqp_channel);
        $this->_publishQueue[$queuName]->setName($queuName);
        $this->_publishQueue[$queuName]->setFlags(AMQP_DURABLE);
        $this->_publishQueue[$queuName]->declare();

        $this->_publishQueue[$queuName]->bind($this->_amqp_exchange_name, $routerKey);
    }

}