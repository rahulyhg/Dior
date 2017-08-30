#!/bin/bash
# Name : php-cgi

phpcgiprocess=$(pgrep php)
phpcgino=$(pgrep php|wc -l)
## yes | no
_sendemail="no"

## email
_email=""

## 不要修改如下配置
_failed=""
_service=""

if [ $phpcgino  -lt 1 ];then
        echo "$(date) php-cgi only have $phpcgino downd  ">>/data/xterp_script/taskmgr/logs/phpdown.log
	rm -rf /var/run/xt-taskDaemon.pid
	/data/xterp_script/taskmgr/taskDaemon.php start  >> /data/xterp_script/taskmgr/logs/phpdown.log
	echo "$(date) phptaskmgr ok" >> /data/xterp_script/taskmgr/logs/phpdown.log
else
        echo "$(date) phptaskmgr ok" >> /tmp/xt-chechphp.log
fi
