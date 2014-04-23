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
#$cIce->global_to_sets();
$id=session_id();
# --------------------------------
# VERIF RULE
# --------------------------------
# MYSQL CONNECT
$conn=ice_mysql_connect();
$time=time();
$d1=datemodify($time,0,'mysql_format');
if ((isset($_POST['do']))&&($_POST['do'])){
#   $cIce->write_trsc(115,'',0,0,0);
   $ot=$cIce->get_ot($_POST['date1'],$_POST['time1'],$_POST['date2'],$_POST['time2'],$_POST['ncheck'],$_POST['type'],$_POST['tov']);
   $text=$_POST['date1'].", ".$_POST['time1']." --> ".$_POST['date2'].", ".$_POST['time2'];
   #$text=$cIce->ice_status;
   $isot=True;
}elseif ((isset($_POST['codetov']))&&($_POST['codetov'])){
}
?>

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Отчеты по продажам</title> <link rel="stylesheet" type="text/css" href="IceCash.css">
</head>

<body onload="setFocus();">
<? ice_links() ?>

<div class="info"> Отчеты по продажам за период <? echo $text; ?></div>

<form name="query" id="query" method="post" action="/ot.php" > 
<table>
<tr> 
<td> Дата начала  <br/>     <input id="date1" name="date1" type="text" value=<? echo "\"$d1\""; ?> > </td>
    <td> Время начала <br/>     <input id="time1" name="time1" type="text" value="09:00:00"> </td>
</tr>
<tr>
    <td> Дата окончания <br/>   <input id="date2" name="date2" type="text" value=<? echo "\"$d1\""; ?>> </td>
    <td> Время окончания <br/>  <input id="time2" name="time2" type="text" value="23:59:00"> </td>
</tr>
<tr>
    <td> Номер чека <br/>   <input id="ncheck" name="ncheck" type="text" value=""> </td>
    <td> Тип транзакции <br/>   <input id="type" name="type" type="text" value=""> </td>
    <td> Код товара <br/>   <input id="tov" name="tov" type="text" value=""> </td>
</tr>
<tr>
<td><input name="bt" type="submit" value="Отправить"/></td>
</tr>    
</table>
<input id="do" name="do" type="hidden" value="1">
<table id="tab" style="font: normal 8pt serif;" rules=all cellpadding=3>
</form>
<tr> <th>Дата</th><th>Время</th><th>Тип</th><th>Номер</th><th>Код</th><th>Товар</th><th>Цена</th><th>Кол</th><th>Стоимость</th></tr>
<?
    $types=array(11=>"продажа",13=>'возврат',37=>'скидка',71=>'дисконт',40=>'оплата',55=>'закрытие чека',56=>"отмена чека",60=>'икс отчет',
                 61=>'зет отчет',65=>'открыт ящик',111=>'рестарт',112=>'сторно',113=>'ред.кол-во',114=>'ред.цена',115=>'отчет',116=>'тип чека',117=>'продолжить',
                 118=>'аннулировать',119=>'копия чека',120=>"Сбой ФР",121=>"Бонус карта");
    $i=0;
    foreach ($ot as $k => $v){
        if (($v['type']==37)||($v['type']==121)){
            $v['ParamS']='******';
        }
        if ($v['type']==55){
            $color="style=\"background-color: #909090;\"";
        }else{$color="";}
        if ($v['type']==56){
            $color="style=\"background-color: #DD9090;\"";
        }
        if ($v['type']==112){
            $color="style=\"background-color: #EEAAAA;\"";
        }
        echo "<tr $color>";
        echo "<tr $color>";
        echo "<td>".$v['date']."</td>";
        echo "<td>".$v['time']."</td>";
        
        echo "<td>".$types[$v['type']]."</td>";
        echo "<td>".$v['ncheck']."</td>";
        echo "<td>".$v['ParamS']."</td>";
        echo "<td>".$v['name']."</td>";
        echo "<td>".$v['ParamF1']."</td>";
        echo "<td>".$v['ParamF2']."</td>";
        echo "<td>".$v['ParamF3']."</td>";
        echo "</tr>";
    }
?>
</table>
<? mysql_close(); ?>
</body>
</html>
