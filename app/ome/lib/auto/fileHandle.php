<?php

/**
 * Class statement_auto_fileHandle
 * 文件处理类
 * @data 2018-05-11 17:48
 * @user august.yao
 */
class ome_auto_fileHandle{

	/**
	 * 解压zip文件
	 * @param $file 文件路径
	 * @param $dir  解压路径
	 */
	public function unzip_file($file, $dir){
		// 实例化对象
		$zip = new ZipArchive();
		// 打开zip文档，如果打开失败返回提示信息
		if ($zip->open($file) !== TRUE) {
			die ("Could not open archive");
		}
		// 将压缩文件解压到指定的目录下
		$zip->extractTo($dir);
		// 关闭zip文档
		$zip->close();
	}

	/**
	 * 解压rar文件到指定目录
	 * @param $filePath： 文件路径
	 * @param $extractTo: 解压路径
	 */
	public function un_rar_pecl($filePath,$extractTo) {
		$rar_file = rar_open($filePath) or die('could not open rar');
		$list     = rar_list($rar_file) or die('could not get list');
		foreach($list as $file) {
			$pattern = '/\".*\"/';
			preg_match($pattern, $file, $matches, PREG_OFFSET_CAPTURE);
			$pathStr = $matches[0][0];
			$pathStr = str_replace("\"",'',$pathStr);
			$entry   = rar_entry_get($rar_file, $pathStr) or die('</br>entry not found');
			$entry->extract($extractTo); // extract to the current dir
		}
		rar_close($rar_file);
	}

	/**
	 * 获取解压的文件
	 * @param $dir 文件路径
	 * @return array
	 */
	public function loopFun($dir){
        // 判断文件是否含有中文
        if(preg_match('/[^\x00-\x80]/',$dir)){
            // 将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
            $dir = iconv("utf-8","gb2312",$dir);
        }
        // 读取目录
		$handle = opendir($dir);
		// 定义用于存储文件名的数组
		$array_file = array();
		while (false !== ($file = readdir($handle))) {
            // 判断文件是否含有中文
            if(preg_match('/[^\x00-\x80]/',$file)){
                // 将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
                $file = iconv("gb2312","utf-8",$file);
            }
			if ($file != "." && $file != "..") {
				$array_file[] = $dir . $file; // 输出文件名
			}
		}
		closedir($handle);
		return $array_file;
	}

	/**
	 * 删除目录
	 * @param $dir
	 * @return bool
	 */
	public function del_dir($dir) {
		// 先删除目录下的文件：
		$dh = opendir($dir);
		while ($file = readdir($dh)) {
			if($file != "." && $file != "..") {
				$fullPath = $dir . "/" . $file;
				if(!is_dir($fullPath)) {
					unlink($fullPath);
				} else {
					$this->del_dir($fullPath);
				}
			}
		}
		closedir($dh);
		// 删除当前文件夹：
		if(rmdir($dir)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 指令删除文件
	 * @param $dir 目录
	 */
	public function del_dir_sh($dir){
		exec('rd /s /q ' . $dir);
	}

	/**
	 * 记录日志
	 * @param $fileDir 文件地址
	 * @param $msg 日志信息
	 */
	public function write_log($fileDir, $msg){
		$logFile = fopen($fileDir, 'a+');
		fwrite($logFile, $msg . ' ' . date('Y-m-d H:i:s', time()) . "\n");
		fclose($logFile);
	}

	/**
	 * 下载文件
	 * @param $down_url 下载地址
     * @param $filename 保存文件地址
	 * @return bool 是否下载成功
	 */
	public function downFile($down_url, $filename){

        $errMsg = ''; // 错误信息
        // 打开文件
		$fp = fopen($filename,'wb');
        // 初始化curl句柄
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $down_url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		$options = array(
			CURLOPT_URL     => $down_url,
			CURLOPT_FILE    => $fp,
			CURLOPT_HEADER  => 0,
			CURLOPT_TIMEOUT => 60
		);
		curl_setopt_array($ch, $options);
		$res = curl_exec($ch); // 执行
        // 判断是否下载成功
        if(!$res){
            $errMsg = curl_error($ch);
        }
        curl_close($ch);// 关闭句柄
		fclose($fp); // 关闭

        return $errMsg ? $errMsg : 'true';
	}

	/**
	 * 解压压缩包文件
	 * @param $filename 压缩包文件
	 * @param $path 文件存放地址
     * @param $fileLog log文件
	 */
	public function get_zip($filename, $path, $fileLog){

        // 判断文件是否存在
		if(!file_exists($filename)){
            $this->write_log($fileLog, "文件 $filename 不存在！");return;
		}

		// 将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
        $path     = iconv("utf-8","gb2312",$path);
		$filename = iconv("utf-8","gb2312",$filename);

		// 打开压缩包
		$resource = zip_open($filename);
		// 遍历读取压缩包里面的一个个文件
		while ($dir_resource = zip_read($resource)) {
			if (zip_entry_open($resource,$dir_resource)) {
				$file_name = $path . zip_entry_name($dir_resource);
				$file_path = substr($file_name,0,strrpos($file_name, "/"));
				if(!is_dir($file_path)){
                    $u_mask = umask(0);	// 处理umask情况
                    mkdir($file_path,0777,true);   // 创建解压目录
                    umask($u_mask);
				}
				if(!is_dir($file_name)){
					$file_size = zip_entry_filesize($dir_resource);
					if($file_size < ( 1024 * 1024 * 6)){
						$file_content = zip_entry_read($dir_resource,$file_size);
						file_put_contents($file_name,$file_content);
					}else{
						$this->write_log($fileLog,'此文件已被跳过，原因：文件过大->' . iconv("gb2312","utf-8",$file_name));
					}
				}
				zip_entry_close($dir_resource);
			}
		}
		// 关闭压缩包
		zip_close($resource);
		unlink($filename);
	}

    /**
     * 处理excel文件
     */
    public function save_csv_data($zipDir, $fileLog, $pay_type, $fileName){

        // 获取解压后的文件
        $array_file = $this->loopFun($zipDir);
        if(empty($array_file)){
            $this->write_log($fileLog, '处理成功-暂无待处理数据');return;
        }
        // excel文件处理
        $oIo = kernel::servicelist('omecsv_io');
        $balance_import_account = kernel::single('ome_balance_to_import');
        foreach ($array_file as $k => $v) {
            // 获取文件信息
            $pathInfo = pathinfo($v);
            // 获取文件处理类
            $oImportType = false;
            foreach( $oIo as $aIo ){
                if( $aIo->io_type_name == $pathInfo['extension']){
                    $oImportType = $aIo;
                    break;
                }
            }
            // 跳过txt、汇总、业务明细文件
            if($pathInfo['extension'] == 'txt' || strpos($pathInfo['filename'],'汇总') || strpos($pathInfo['filename'],'业务明细')){
                // 判断文件是否含有中文
                if(preg_match('/[^\x00-\x80]/',$v)){
                    $vv = iconv("utf-8","gb2312",$v);
                }
                unlink($vv);    // 删除文件
                continue;
            }
            // 判断文件格式
            if( !$oImportType ){
                $this->write_log($fileLog,$pathInfo['basename'] . '：处理失败-文件格式错误 ');continue;
            }
            // 数据内容
            $contents = array();
            try {
                // 判断文件是否含有中文
                if(preg_match('/[^\x00-\x80]/',$v)){
                    $v = iconv("utf-8","gb2312",$v);
                }
                // 条数限制
                $sheetInfo = $oImportType->listWorksheetInfo($v);
                if ((int)$sheetInfo['totalRows'] > $oImportType->limitRow ) {
                    $this->write_log($fileLog,$pathInfo['basename'] . '：处理失败-导入数据量过大，请减至' . $oImportType->limitRow . '单以下 ');continue;
                }
                $oImportType->fgethandle($v,$contents);
            } catch (Exception $e) {
                $this->write_log($fileLog,$pathInfo['basename'] . '：处理失败-' . $e->getMessage());continue;
            }

            // 插入数据库
            $balance_import_account->pay_type = $pay_type;
            $re = $balance_import_account->do_paymens_bill($contents,$msg);

            if($re){
                $backupDir = ROOT_DIR . '/data/bill_data/backup/aliPay_data/'; // 备份目录
                $this->csv_backup($v, $backupDir, $fileName);
                $this->write_log($fileLog,$pathInfo['basename'] . '：处理成功 ');
            }else{
                $this->write_log($fileLog,$pathInfo['basename'] . '：处理失败' . $msg);
            }
        }
    }

    /**
     * 处理微信账单数据
     * @param $data 数据
     * @param $fileLog log文件
     * @param $pay_type 来源 weixin：微信，alipay：支付宝
     */
    public function save_data_WeChat($data, $fileLog, $pay_type, $bill_date, $isXcx = false){

        $balance_import_account = kernel::single('ome_balance_to_import');
        // 插入数据库
        $balance_import_account->pay_type = $pay_type;
        $re = $balance_import_account->do_paymens_bill($data,$msg);
        // 处理信息
        $res_msg = $isXcx ? '微信小程序对账单[' . $bill_date . ']数据' : '微信对账单[' . $bill_date . ']数据';

        if($re){
            $this->write_log($fileLog, $res_msg . '处理成功');
        }else{
            $this->write_log($fileLog, $res_msg . '处理失败：' . $msg);
        }
    }

    /**
     * 备份对账文件
     */
    public function csv_backup($file, $backup_dir, $newFile){
        // 判断目录是否存在
        if (!file_exists($backup_dir)) {
            $u_mask = umask(0);	// 处理umask情况
            mkdir($backup_dir,0777,true);   // 创建备份目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
		// 获取文件名
        $fileTemp    = pathinfo($file);
        $newFileName = $fileTemp['basename'] ? $fileTemp['basename'] : $newFile . 'csv';
        copy($file, $backup_dir . $newFileName); // 拷贝到新目录
        unlink($file); // 删除旧目录下的文件
    }
}