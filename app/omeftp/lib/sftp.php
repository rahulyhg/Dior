<?php
class omeftp_sftp{

	public $ftp_extension = true;
	public $mode = FTP_BINARY;

	 // 是否使用秘钥登陆 
     private $use_pubkey_file= false;

	public function __construct() {
		$this->extension_loaded_ftp();
	}

    public function instance($params,&$msg){
        if($this->conn&&$this->sftp){
            return true;
        }else{
            $res = $this->connect($params,$msg);
            return $res;
        }
    }


	/**
     * 判断php是否安装了FTP扩展
     *
     * @return string
     */
    public function extension_loaded_ftp()
    {
        $this->ftp_extension = extension_loaded('ftp') ? true : false;
        return $this->ftp_extension; 
    }

	/**
     * 连接FTP服务器,并且登录
     *
     * @params array $params ftp服务器配置信息
     */
    public function connect($params,&$msg){
		$methods['hostkey'] = $this->use_pubkey_file ? 'ssh-rsa' : '[]' ; 

        if( !$params['host'] ){
            $msg = app::get('omeftp')->_('FTP地址必填');
            return false;
        }
		

        $params['port'] = $params['port'] ? $params['port'] : 21;

        if( $this->ftp_extension ) {
		//	echo '<pre>';print_r($params);exit;
            $connect = ssh2_connect($params['host'], $params['port']);
            $this->conn = $connect;
        } else {
            $msg = app::get('omeftp')->_('请检查FTP扩展是否开启！');
			return false;
        }

        if( !$connect ) {
            $msg = app::get('importexport')->_('连接FTP失败，请检查FTP地址或FTP端口方法');
            return false;
        }

        if( !$this->_login($params,$msg) ){
            return false; 
        }

        $this->changeDirectory($params['dir'],$msg);

        return true;
    }

    /**
     * 登录到FTP服务器
     *
     * @params array $params FTP用户名和密码
     * @return bool 登录成返回true 登录失败则返回异常错误
     */
    private function _login($params,&$msg){

        if($this->sftp){
            return true;
        }
        if( $this->ftp_extension ) {
			if($this->use_pubkey_file){	
				$flag = ssh2_auth_pubkey_file($this->conn,$params['user'],$params['pubkey_file'],$params['privkey_file'],$params['passphrase']);
			}else{
				$flag = ssh2_auth_password($this->conn, $params['name'],$params['pass']);
			}
        } else {
            $msg = app::get('omeftp')->_('请检查FTP扩展是否开启！');
            return false;
        }
		$this->sftp = @ssh2_sftp($this->conn);
        if (! $this->sftp){
            $msg = app::get('omeftp')->_('登录到FTP失败，请检查用户名和密码');
			return false;
		}

        if( !$flag ) {
            $msg = app::get('omeftp')->_('登录到FTP失败，请检查用户名和密码');
            return false;
        } 

        return true;
    }

	 /**
     * 检查FTP配置
     *
     * @params array $params FTP配置信息参数
     * @return bool  成功返回true 失败则返回 false
     */
    public function check($params){
        $params['timeout'] = 5;//5秒连接失败则检查不通过

        if( !$this->connect($params,$msg) ) 
        {
            trigger_error($msg, E_USER_ERROR); 
            return false;
        }
        $tmpFile = tempnam(ROOT_DIR.'/ftp_ls','omeftpTest.txt');
        file_put_contents($tmpFile,'This is test file');
        $params['remote'] = '/in/omeftpTest.txt';
        $params['local'] = $tmpFile;
        $params['resume'] = 0;
        //检查上传文件
        if( !$this->push($params,$msg) )
        {
            trigger_error($msg, E_USER_ERROR); 
            return false;
        }
        return true;
    }//end function

    /**
     * 更改目录，如果配置为空，则将文件存储到FTP根目录下
     *
     * @params string $dir 目录
     * @return bool 返回true 如果配置的目录不存在则忽略错误
     */
    public function changeDirectory($dir=null,&$msg){

        if( $this->ftp_extension ) {
            @ftp_chdir($this->conn,$dir); //目录错误会返回警告，屏蔽
        } else {
              $msg = app::get('omeftp')->_('请检查FTP扩展是否开启！');
        }

        return true;
    }


    /**
     * 将本地文件上传到FTP
     *
     * @params array $params 参数 array('local'=>'本地文件路径','remote'=>'远程文件路径','resume'=>'文件指针位置')
     * @params string $msg 
     *
     * @return bool
     */
    public function push($params, &$msg){

		$sftp = $this->sftp;
		$remote_file = '/TO_AX/'.$params['remote'];
        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w');

        if (! $stream){
            $msg = app::get('omeftp')->_('不能创建文件！');
			return false;
		}

        $data_to_send = @file_get_contents($params['local']);
        if ($data_to_send === false){
             $msg = app::get('omeftp')->_('文件内容错误！');
			 return false;
		}

        $size = @fwrite($stream, $data_to_send);
        if ($size === false||$size===0){
            $msg = app::get('omeftp')->_('不能上传数据！');
			return false;
		}
        @fclose($stream);

		return true;
    }

    /**
     * FTP中文件下载到本地
     *
     * @params array $params 参数 array('local'=>'本地文件路径','remote'=>'远程文件路径','resume'=>'文件指针位置')
     * @params string $msg 
     * @return bool 
     */
    public function pull( $params, &$msg){
		$sftp = $this->sftp;
		$remote_file = $params['remote'];
		error_log(var_export(PHP_EOl.$remote_file.'    '.date('Y-m-d H:i:s',time()).'        '.$params['local'],true),3,__FILE__.'_log.txt');

        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'r');
        if (! $stream){
            $msg = app::get('omeftp')->_('无法打开远程文件！');
			return false;
		}
        $size = $this->getFileSize($remote_file);           
        $contents = '';
        $read = 0;
        $len = $size;
        while ($read < $len && ($buf = fread($stream, $len - $read))) {
          $read += strlen($buf);
          $contents .= $buf;
        }       
        $res = file_put_contents ($params['local'], $contents);
        @fclose($stream);

		if($contents){
			if($res<=0||$res===false){
				$msg = app::get('omeftp')->_('备份文件失败！');
				return false;
			}
		}
		return true;
    }

	 public function getFileSize($file){
      $sftp = $this->sftp;
      return filesize("ssh2.sftp://$sftp$file");
    }

    /**
     * 获取FTP文件大小
     *
     */
    public function size($filename){
        if( $this->ftp_extension ) {
            return ftp_size($this->conn,$filename);
        } else {
             $msg = app::get('omeftp')->_('请检查FTP扩展是否开启！');
			 return false;
        }
    }

    /**
     * 删除FTP文件
     *
     */
    public function delete($filename){
        $sftp = $this->sftp;
		unlink("ssh2.sftp://$sftp$filename");
		return true;
    }

	/**
     * 获取文件列表
     *
     */
    public function get_list($dir){
        $sftp = $this->sftp;
        $dir = "ssh2.sftp://$sftp$dir"; 
        $tempArray = array();
		$handle = opendir($dir);
          // List all the files
        while (false !== ($file = readdir($handle))) {
			if (substr("$file", 0, 1) != "."){
				if(is_dir($file)){
//                $tempArray[$file] = $this->scanFilesystem("$dir/$file");
				} else {
					$tempArray[]=$file;
				}
             }
         }
         closedir($handle);
         return $tempArray;
    }

    public function exec($cmd){
        if (!($stream = ssh2_exec($this->conn, $cmd))) { 
            throw new Exception('SSH command failed'); 
        } 
        stream_set_blocking($stream, true); 
        $data = ""; 
        while ($buf = fread($stream, 4096)) { 
            $data .= $buf; 
        } 
        fclose($stream); 
        return $data;
    }
    
    public function disconnect() { 
        $this->exec('echo "EXITING" && exit;'); 
        unset($this->conn); 
    }

}