<?php
# SESSION START
session_start();
# --------------------------------
# SERVER VARIABLES
$error = $_SESSION['error'];
# --------------------------------
echo "<!DOCTYPE html>";
# --------------------------------
?>

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Ошибка</title>
<link rel="stylesheet" type="text/css" href="IceCash.css">
</head>
<body>
  <header>
    <h2 align=left> Ошибка </h2>
    Произошла ошибка:
  </header>
 <hr width=400 align=left> </hr>
 <div class="warning"><span class="data">
 <? echo "$error"?>
 </span>
 </div>  
</body>
</html>
