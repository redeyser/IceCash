<?php
# SESSION START
# version 1.3.011
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
$cIce->clear();
# --------------------------------
# INIT OBJECT 
    $cIce->openClient();
    $r=$cIce->Client->_download();
# --------------------------------
?>

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Выгрузка</title>
<link rel="stylesheet" type="text/css" href="IceCash.css">
</head>
<body>
<? ice_links() ?>
<body>
  <header>
    <h1>Выгрузка</h1>
    Выгрузка продаж, загрузка прайса
  </header>
 <hr width=400 align=left> </hr>
<div class="info">
<? 
    echo "Подождите 2-3 минуты...";
?>
</div>

</body>
</html>

