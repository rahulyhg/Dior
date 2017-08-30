<?php
define('HTTP_TIME_OUT', -3);

class taoexlib_request_curl{

    public $timeout = 10;
    public $defaultChunk = 4096;
    public $http_ver = '1.1';
    public $hostaddr = null;
    public $proxyHost = null;
    public $proxyPort = null;
    public $default_headers = array(
        'Pragma'=>"no-cache",
        'Cache-Control'=>"no-cache",
        'Connection'=>"close"
        );
    public $is_websocket = false;
    public $logfunc = null;
    public $hostport = null;
    public $callback = null;
    public $responseHeader = null;
    public $responseBody = null;
    private $handles = array();

    function __construct(){
        $this->register_handler(302, array($this, 'handle_redirect'));
        $this->register_handler(301, array($this, 'handle_redirect'));
    }

    public function register_handler($type, $func){
        $this->handles[$type] = $func;
    }

    public function action($action, $url, $headers=null, $data=null){

        $url_info = parse_url($url);
        $request_query = (isset($url_info['path'])?$url_info['path']:'/').(isset($url_info['query'])?'?'.$url_info['query']:'');
        $request_server = $request_host = $url_info['host'];
        $request_port = (isset($url_info['port'])?$url_info['port']:80);

        $out = strtoupper($action).' '.$request_query." HTTP/{$this->http_ver}\r\n";
        $out .= 'Host: '.$request_host.($request_port!=80?(':'.$request_port):'')."\r\n";

        if($data){
            if(is_array($data)){
                $data = http_build_query($data);
                if(!isset($headers['Content-Type'])){
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                }
            }
            $headers['Content-length'] = strlen($data);
        }

        $headers = array_merge($this->default_headers, (array)$headers);

        foreach((array)$headers as $k=>$v){
            $out .= $k.': '.$v."\r\n";
        }
        $out .= "\r\n";
        if($data){
            $out .= $data;
        }
        $data = null;

        $this->responseHeader = array();
        if($this->proxyHost && $this->proxyPort){
            $request_server = $this->proxyHost;
            $request_port = $this->proxyPort;
            $this->log('Using proxy '.$request_server.':'.$request_port.'. ');
        }

        if($this->hostaddr){
            $request_addr = $this->hostaddr;
        }else{
            if(!$this->is_addr($request_server)){
                $this->log('Resolving '.$request_server.'... ',true);
                $request_addr = gethostbyname($request_server);
                $this->log($request_addr);
            }else{
                $request_addr = $request_server;
            }
        }
        if($this->hostport){
            $request_port = $this->hostport;
        }

        $this->log(sprintf('Connecting to %s|%s|:%s... connected.',$request_server,$request_addr,$request_port));
        if($fp = @fsockopen($request_addr,$request_port,$errno, $errstr, $this->timeout)){

            if($this->timeout && function_exists('stream_set_timeout')){
                $this->read_time_left = $this->read_time_total = $this->timeout;
            }else{
                $this->read_time_total = null;
            }

            $sent = fwrite($fp, $out);

            $this->log('HTTP request sent, awaiting response... ',true);
            $this->request_start = $this->microtime();

            $out = null;

            $this->responseBody = '';
            if(HTTP_TIME_OUT === $this->readsocket($fp,512,$status,'fgets')){
                return HTTP_TIME_OUT;
            }

            if(preg_match('/\d{3}/',$status,$match)){
                $this->responseCode = $match[0];
            }

            $this->log($this->responseCode);
            while (!feof($fp)){
                if(HTTP_TIME_OUT === $this->readsocket($fp,512,$raw,'fgets')){
                    return HTTP_TIME_OUT;
                }
                $raw = trim($raw);
                if($raw){
                    if($p = strpos($raw,':')){
                        $this->responseHeader[strtolower(trim(substr($raw,0,$p)))] = trim(substr($raw,$p+1));
                    }
                }else{
                    break;
                }
            }

            if(isset($this->handles[$this->responseCode]) && is_callable($this->handles[$this->responseCode])){
                return call_user_func($this->handles[$this->responseCode], $this, $fp);
            }else{
                return $this->default_handler($fp);
            }
        }else{
            return false;
        }
    }

    private function handle_redirect($self, $fp){
            $this->log(" Redirect \n\t--> ".$this->responseHeader['location']);
            if(isset($this->responseHeader['location'])){
                return $this->action($action,$this->responseHeader['location'],$headers,$callback);
            }else{
                return false;
            }
    }

    private function default_handler($fp){
        $chunkmode = (isset($this->responseHeader['transfer-encoding']) && $this->responseHeader['transfer-encoding']=='chunked');
        if($chunkmode){
            if(HTTP_TIME_OUT === $this->readsocket($fp,30,$chunklen,'fgets')){
                return HTTP_TIME_OUT;
            }
            $chunklen = hexdec(trim($chunklen));
        }elseif(isset($this->responseHeader['content-length'])){
            $chunklen = min($this->defaultChunk,$this->responseHeader['content-length']);
        }else{
            $chunklen = $this->defaultChunk;
        }

        while (!feof($fp) && $chunklen){
            if(HTTP_TIME_OUT ===$this->readsocket($fp,$chunklen,$content)){
                return HTTP_TIME_OUT;
            }
            $readlen = strlen($content);
            while($chunklen!=$readlen){
                if(HTTP_TIME_OUT === $this->readsocket($fp,$chunklen-$readlen,$buffer)){
                    return HTTP_TIME_OUT;
                }
                if(!strlen($buffer)) break;
                $readlen += strlen($buffer);
                $content.=$buffer;
            }

            if($this->callback){
                if(!call_user_func_array($this->callback,array(&$this,&$content))){
                    break;
                }
            }else{
                $this->responseBody.=$content;
            }

            $readed = 0;
            if($chunkmode){
                fread($fp, 2);
                if(HTTP_TIME_OUT === $this->readsocket($fp,30,$chunklen,'fgets')){
                    return HTTP_TIME_OUT;
                }
                $chunklen = hexdec(trim($chunklen));
            }else{
                $readed += strlen($content);
                if(isset($this->responseHeader['content-length']) && $this->responseHeader['content-length'] <= $readed){
                    break;
                }
            }
        }
        fclose($fp);
        if($this->callback){
            return true;
        }else{
            return $this->responseBody;
        }
    }

    public function set_logger($func){
        $this->logfunc = &$func;
    }

    public function log($str, $nobreak=false){
        if(is_callable($this->logfunc)){
            return call_user_func($this->logfunc, $nobreak?$str:($str."\n"));
        }
    }

    private function is_addr($ip){
        return preg_match('/^[0-9]{1-3}\.[0-9]{1-3}\.[0-9]{1-3}\.[0-9]{1-3}$/',$ip);
    }

    private function microtime(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    private function readsocket($fp,$length,&$content,$func='fread'){
        if(!$this->reset_time_out($fp)){
            return HTTP_TIME_OUT;
        }

        $content = $func($fp,$length);

        if($this->check_time_out($fp)){
            return HTTP_TIME_OUT;
        }else{
            return true;
        }
    }

    private function reset_time_out(&$fp){
        if($this->read_time_total===null){
            return true;
        }elseif($this->read_time_left<0){
            return false;
        }else{
            $this->read_time_left = $this->read_time_total - $this->microtime() + $this->request_start;
            $second = floor($this->read_time_left);
            $microsecond = intval(( $this->read_time_left - $second ) * 1000000);
            stream_set_timeout($fp,$second, $microsecond);
            return true;
        }
    }

    private function check_time_out(&$fp){
        if(function_exists('stream_get_meta_data')){
            $info = stream_get_meta_data($fp);
            return $info['timed_out'];
        }else{
            return false;
        }
    }

    protected function build_url($url){
        $ret = $url['scheme'].'://'.$url['host'];
        if($url['port']!=80){
            $ret.=':'.$url['port'];
        }
        $ret.= $url['path'];
        if($url['query']){
            $ret.='?'.$url['query'];
        }
        return $ret;
    }

}

class taoexlib_request_client extends taoexlib_request_curl{

    var $app_key;
    var $app_secret;
    var $base_url;
    var $sign_params_in_url = true;

    function __construct($base_url, $app_key, $app_secret){
        $this->base_url = rtrim($base_url, '/');
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
    }

    public function action($method, $path, $headers=null, $data=null){
        $url = $this->base_url .'/'. ltrim($path, '/');
        $query = array();
        $url_info = parse_url($url);
        if(isset($url_info['query'])){
            parse_str($url_info['query'], $query);
        }

        if($this->sign_params_in_url || $method=='GET'){
            $request = &$query;
        }else{
            $request = &$data;
        }

        $request['client_id'] = $this->app_key;
        $request['sign_time'] = time();
        $request['sign_method'] = 'md5';
        $request['sign'] = $this->sign($this->app_secret,  $method, $url_info['path'], $headers, $query, $data);

        $url_info['query'] = http_build_query($query);
        $url = $this->build_url($url_info);

        $this->log("url: ". $url);
        return parent::action($method, $url, $headers, $data);
    }

    public function get($path, $data=null, $headers=null){
        return $this->action('GET', $path, $headers, $data);
    }

    public function post($path, $data=null, $headers=null){
        return $this->action('POST', $path, $headers, $data);
    }

    public function put($path, $data=null, $headers=null){
        return $this->action('PUT', $path, $headers, $data);
    }

    public function delete($path, $data=null, $headers=null){
        return $this->action('DELETE', $path, $headers, $data);
    }

    private function sign($secret, $method, $path, $headers, $query, $post){
        $sign = array(
                    $secret,
                    $method,
                    rawurlencode($path),
                    rawurlencode($this->sign_headers($headers)),
                    rawurlencode($this->sign_params($query)),
                    rawurlencode($this->sign_params($post)),
                    $secret
            );
        $sign = implode('&', $sign);
        $this->log("signstr: ". $sign);
        return strtoupper(md5($sign));
    }

    private function sign_headers($headers){
        if(is_array($headers)){
            ksort($headers);
            $ret = array();
            foreach($headers as $k=>$v){
                if ( ($k == 'Authorization') || (substr($k, 0, 6)=='X-Api-') ) {
                    $ret[] = $k.'='.$v;
                }
            }
            return implode('&', $ret);
        }
    }

    private function sign_params($params){
        if(is_array($params)){
            ksort($params);
            $ret = array();
            foreach($params as $k=>$v){
                $ret[] = $k.'='.$v;
            }
            return implode('&', $ret);
        }
    }

    public function notify(){
        include_once(dirname(__FILE__).'/notify.php');
        return new prism_notify($this);
    }

}

class taoexlib_request_websocket extends taoexlib_request_client {

}