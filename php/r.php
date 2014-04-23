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
$cIce->global_to_sets();
$id=session_id();
# --------------------------------
# VERIF RULE
if (($ip!="127.0.0.1")&&(!ice_user_isadmin())){
    ice_error("Управление кассовыми операциями возможно только с локального компьютера.");exit; 
}
# --------------------------------
# MYSQL CONNECT
$conn=ice_mysql_connect();
if ((isset($_POST['codetov']))&&($_POST['codetov'])){
   $cIce->add_sale($_POST['code'],0,$_POST['cena']);
}

elseif (isset($_POST['code'])) {
    if (substr($_POST['code'],0,3)==$cIce->ice_sets['bonuscard']){
        $cIce->find_bonuscard($_POST['code']);
    }elseif ( $cIce->add_sale($_POST['code'],1,0) ) {
        //($cIce->find_price($_POST['code'])){
    }elseif($cIce->find_discount($_POST['code'])){    }
}
if (count($cIce->ice_cur_tovars)>1){
   $viz=$cIce->ice_cur_tovars;
   $isviz=1;
}
# --------------------------------
if (!$cIce->ice_tp){
    $type_check='продажа';
}else{
    $type_check='возврат';
}
$zsum=$cIce->calc_zsum();

$c_actions=$cIce->get_actions();

 
     
    if ($isviz==1){

        $c=count($viz);
        if ($c>20){$c=20;}
        echo "<input id=\"maxcount\" name=\"maxcount\" type=\"hidden\" value=$c>";
        echo '<input id="isviz" name="isviz" type="hidden" value=1>';
        echo '<input id="firstcur" name="firstcur" type="hidden" value=1>';

    }else{ 
        echo '<input id="maxcount" name="maxcount" type="hidden" value='.count($cIce->ice_trsc['type']).'>';
        echo '<input id="isviz" name="isviz" type="hidden" value=0>';
        echo '<input id="firstcur" name="firstcur" type="hidden" value='.count($cIce->ice_trsc['type']).'>';
        }
    /*
       
     
        echo '<script type="text/javascript">'."\n";
        echo "function setvar(){\n";
        echo "var isviz=0;\n";
        echo "var maxcount=".count($cIce->ice_trsc['type']).";\n";
        echo "var cursor=".($cIce->ice_cursor+1).";\n";
        echo 'alert("setvar2");}';
        echo "</script>";*/
    
    
 if ($isviz==1){
    $cIce->ice_cursor=0; 
 if (count($viz)){
    echo "<table id='tab0' class='tab' cols=1 rules=all cellpadding=3>";
    echo "<tr><th>Код</th><th>Наименование</th><th>Цена</th></tr>";
    $i=0;
    foreach ($viz as $k => $v){
       if ($i==0){ $colorc="class=\"active\"";}else{$colorc="";}
       $i=$i+1;
       #echo "<tr $colorc><td><a href=\"\" class=\"link\" onclick=\"addsale(".$v['id'].",".$v['cena'].");\">".$v['id']."</a></td><td>".$v['name']."</td><td>".$v['cena']."</td></tr>";
       echo "<tr $colorc OnClick='ActiveLine($i);'><td>".$v['id']."</td><td>".$v['name']."</td><td>".$v['cena']."</td></tr>";
       if ($i>=20){break;}
    }
    echo "</table>";
 }else{
  echo "Не найдено (вводите маленькими буквами)";  
 }}else{
    $cIce->ice_cursor=count($cIce->ice_trsc['type'])-1;
 }


?>
<table id="tab" class="tab" cols=4 rules=all cellpadding=3>
<tr> <th>Наименование товара</th><th>Кол</th><th>Цена</th><th>Сумма</th></tr>
<?
    $i=0;
    $c=count($cIce->ice_trsc['type']);
    for ($i=0;$i<$c;$i++){
        if ($i==$cIce->ice_cursor) { $colorc="class=\"active\"";}else{$colorc="";}
        $j=$j+1;
        echo "<tr $colorc OnClick='ActiveLine($j);'>";
        echo "<td>".$cIce->ice_trsc['name'][$i]."</td>";
        echo "<td>".$cIce->ice_trsc['count'][$i]."</td>";
        echo "<td>".$cIce->ice_trsc['price'][$i]."</td>";
        $sum=$cIce->trsc_sum($i);
        echo "<td>".$sum."</td>";
        echo "</tr>";
    }
    $cIce->calc();
    if ($cIce->ice_skid){
        $pr=$cIce->discount_card['text']." ".$cIce->discount_card['procent']."%";
        echo "<tr>";
        echo "<th>Скидка по карте $pr</th>"."<th colspan=3>".$cIce->ice_sum_skid."</th>";
        echo "</tr>";
    }
    if (count($cIce->bonus_card)){
        $pr="[N".$cIce->bonus_card[0]."] ".$cIce->bonus_card[2]." ".$cIce->bonus_card[3]." ".$cIce->bonus_card[4];
        if ($cIce->bonus_type==1){
            $style="style='background-color: #B66;color: #000;'";
            $btn="Зачислить";
            $sum=$cIce->ice_sum_skid;
        }else{
            $style="style='background-color: #6B6;color: #000;'";
            $btn="Снять";
            $sum=$cIce->sum4nach;
        }
        echo "<tr $style>";
        echo "<td>$pr <span class='btn' onclick='keyread(120);' >$btn </span> </td>"."<th colspan=2>".$cIce->bonus_card[9]."</th><td>".$sum." </td>"; 
        echo "</tr>";
    }
        echo "<tr>";
        echo "<th>Итого </th>"."<th id=\"itog\" class=\"big\" colspan=3>".$cIce->ice_sum."</th>";
        echo "</tr>";
?>
</table>
<div id="info" class="info"> <? echo "[".$cIce->ice_status."] </br> [".$type_check."] [".$cIce->ice_number."] {в кассе: ".$zsum[0]."/".$zsum[1]."}"; ?> </div>

