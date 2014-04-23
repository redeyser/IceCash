<?php
# SESSION START
# version 1.3.008
session_start();
# --------------------------------
# INCLUDE
include_once("funct.php");
include_once("IceCash.php");
# --------------------------------
# SERVER AUTH 
global $cIce;
global $ip,$user;
$error=False;
$id=session_id();
# --------------------------------
# VERIF RULE
# --------------------------------
# POST VARIABLES
# --------------------------------
# MYSQL CONNECT
$conn=ice_mysql_connect();
$cIce->clear();
$cIce->get_sets();
# --------------------------------
# INIT OBJECT 
$sdt="";
if (array_key_exists("noc",$_GET)){
    $cn="admin";
    $cIce->openClient();
    $cIce->get_status(1);
}else{//CONNECT
$cn=$cIce->connectClient();#connect dIce daemon & connect frk port
$cIce->get_status(1);
if ($cn[0]=="dIce_ok"){
    if ($cn[1]!="frk_ok"){
        $cn=$cIce->connectClient();
        $cIce->get_status(1);
    }
    if ($cn[1]=="frk_ok"){
        $sdt="<br/>";
        $cIce->openClient();
        $_cd=$cIce->Client->_set_date();
        $_ct=$cIce->Client->_set_time();
        #$_cd=$cIce->Client->_get_info('date');
        #$cIce->status_long;
        $sdt="<br/>Синхронизация времени [ $_cd $_ct ]";
        if (array_key_exists("repair",$_GET)){
            $cIce->Client->_continue(); sleep(1);
            $cIce->Client->_continue(); sleep(1);
            $cIce->Client->_continue(); sleep(1);
            $cIce->Client->_cancel_check(); sleep(1);
            $cIce->Client->_cancel_check(); sleep(1);
            $cIce->Client->_cancel_check(); sleep(1);
        }
    }else{$error=True;}
} else {$error=True;}
}//CONNECT
# --------------------------------
if ($conn) {$my='mysql_ok';}else{$my='mysql_error';$error=True;}
if ($cn[1]) {$my='mysql_ok';}else{$my='mysql_error';$error=True;}
//$cIce->sets_to_global();
$cIce->write_trsc(111,'',0,0,0);
$_SESSION['vkbd']=0;

?>

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Старт</title>
<link rel="stylesheet" type="text/css" href="IceCash.css">
</head>
<body>
<? ice_links() ?>
<body>
  <header>
    <h1>Рабочее место кассира</h1>
    Функционал кассира
  </header>
 <hr width=400 align=left> </hr>
<div class="info">
<? 
    echo "[$ip] [$user] [$my] [$cn[0]] [$cn[1]] [$cn[2]]";
    echo " [".$cIce->ice_status."]";
    echo "$sdt";
?>
</div>

<?
  if ($error){
  echo "<div class='warning'>";
  echo "Внимание! Обнаружена ошибка. Работа в режиме регистрации невозможна!";
  echo "</div>";
  }
?>

<table width=400 border="0" cellspacing=3 cellpadding=3>
<tr> <td><a class="link" href="reg.php">Регистрация продаж</a></td></tr>
</table>
</body>
</html>
<? mysql_close(); ?>

