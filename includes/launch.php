<?php
defined('ABSPATH')||exit;
function obra_launch_time($value){
 if(!is_string($value))return false;
 $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,wp_timezone());
 return $date&&$date->format('Y-m-d\TH:i')===$value?$date->getTimestamp():false;
}
function obra_apply_launch($settings){
 $time=obra_launch_time($settings['launch_date']??'');
 if(!empty($settings['enabled'])&&!empty($settings['auto_open'])&&$time!==false&&$time<=time())$settings['enabled']=false;
 return $settings;
}
