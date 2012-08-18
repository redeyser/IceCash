<?php
# SESSION START
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
$cIce->get_sets();
# --------------------------------
# INIT OBJECT 
$cn=$cIce->connectClient();#connect dIce daemon & connect frk port
# --------------------------------
if ($conn) {$my='mysql_ok';}else{$my='mysql_error';$error=True;}
$cIce->get_status(1);
$cIce->clear();
$cIce->sets_to_global();
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
    echo "[$ip] [$user] [$my] [$cn[0]] [$cn[1]]";
    echo " [".$cIce->ice_status."]";
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

