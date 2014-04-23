<?php
# SESSION START
# version 1.3.004
session_start();
# --------------------------------
# INCLUDE
include_once("funct.php");
include_once("IceCash.php");
# --------------------------------
# SERVER AUTH 
global $ip,$user;
global $cIce;
# --------------------------------
# VERIF RULE
if (!ice_user_isadmin()) { ice_error("Вы не администратор. Доступ запрещен.");exit; }
# --------------------------------
# POST VARIABLES
foreach ($_POST as $k=>$v){
    $cIce->ice_sets[$k]=$v;
}

# --------------------------------
# MYSQL CONNECT
$conn=ice_mysql_connect();
# --------------------------------
# INIT OBJECT 
#$cIce->connectClient();#connect dIce daemon & connect frk port
# --------------------------------
$_SESSION['ice_sets']['idplace']=2;
if ($_POST['save']){
    $cIce->put_sets();
}
if ($_POST['test']){
    $cIce->OpenClient();
    $cIce->Client->_print($_POST['text']);
    $cIce->Client->_roll();
}
if ($_POST['info']){
    $cIce->OpenClient();
    $res=$cIce->Client->_get_info_all();
}

$cIce->get_sets();
if ($conn) {$my='mysql_IceCash';}else{$my='mysql_error';}

?>

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Настройки</title>
<link rel="stylesheet" type="text/css" href="IceCash.css">
</head>
<body>
<? ice_links() ?>
  <header>
    <h1>Настройки IceCash</h1>
    Основные настройки работы РМК
  </header>
 <hr width=400 align=left> </hr>
<div class="info">
<? echo "[$ip] [$user] [$my]";  ?>
</div>
<form id="sets" method="post" action="sets.php">
<input name="save" type="hidden" value="1"/>
<table column=2 width=400>
<?
$i=0;
$frks=array("","shtrihm","shtrihl",'ASPD');
foreach ($cIce->ice_sets as $k=>$v){
    ++$i;
    if ($i==1){echo "<tr>";}
    echo "<td>";
    if ($k=='typedev'){
     echo "$k<br/>";
     html_select($k,$frks,$v);    
    }else{
    html_edit($k,$k,20,$v,'');}
    echo "</td>";
    if ($i==2){echo "</tr>";$i=0;}
}
?>
</table>
<input name="bt" type="submit" value="Сохранить"/>
</form>
<form id="test" method="post" action="sets.php">
Текст для теста <br/>
<textarea rows="10" cols="45" name="text"></textarea>
<input name="bt2" type="submit" value="Печать"/>
<input name="test" type="hidden" value="1"/>
</form>
<form id="info" method="post" action="sets.php">
<input name="info" type="hidden" value="1"/>
<input name="bt3" type="submit" value="Статус"/>
</form>
<?
if ($_POST['info']){
    echo "Статус ФРК: <br/><pre>";
    echo "$res";
    echo "</pre>";
}
?>
</body>
</html>
<? mysql_close(); ?>

