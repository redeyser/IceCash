<?php
# SESSION START
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
if ($_POST){
    $cIce->put_sets();
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
<table column=2 width=400>
<?
$i=0;
foreach ($cIce->ice_sets as $k=>$v){
    ++$i;
    if ($i==1){echo "<tr>";}
    echo "<td>";
    html_edit($k,$k,20,$v,'');
    echo "</td>";
    if ($i==2){echo "</tr>";$i=0;}
}
?>
</table>
<input name="bt" type="submit" value="Сохранить"/>
</form>
</body>
</html>
<? mysql_close(); ?>

