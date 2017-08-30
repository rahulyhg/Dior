#!/bin/bash

phpExec=$1;
selfpath=$(cd "$(dirname "$0")"; pwd)
app_dir="$selfpath/../../app"

function checkprocess(){
    if (ps aux|grep -v grep|grep "$1" )
    then
        echo "active"
    else
        #echo "miss"
        #echo $1
        $phpExec $2 $1 &
    fi
}

function execscript(){
    script=`checkprocess "$selfpath/script.php"`
    for filename in $script
    do
       checkprocess "$app_dir/$filename"
    done
}

execscript