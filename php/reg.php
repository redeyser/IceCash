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
$isviz=0;
# POST VARIABLES
if ((isset($_POST['cursor']))&&($_POST['cursor'])){
   $cIce->ice_cursor= $_POST['cursor']-1;
   $_SESSION['ice_cursor']=$cIce->ice_cursor;
}

if ((isset($_POST['viz']))&&($_POST['viz'])){
   $viz=$cIce->like_tovar($_POST['code']);
   $isviz=1;
}elseif ((isset($_POST['codetov']))&&($_POST['codetov'])){
   $cIce->add_sale($_POST['code'],0,$_POST['cena']);
}elseif ((isset($_POST['pay']))&&($_POST['pay'])){
   $cIce->close_check($_POST['code'],0);
}elseif ((isset($_POST['pay_kred']))&&($_POST['pay_kred'])){
   $cIce->close_check($_POST['code'],1);
}elseif ((isset($_POST['typecheck']))&&($_POST['typecheck'])){
    $cIce->write_trsc(116,'',0,0,0);
    $cIce->set_typecheck();
}elseif ((isset($_POST['openbox']))&&($_POST['openbox'])){
   $cIce->write_trsc(65,'',0,0,0);
   $cIce->openbox();
}elseif ((isset($_POST['cancel']))&&($_POST['cancel'])){
   $cIce->write_trsc_cancel();
   $cIce->clear();
}elseif ((isset($_POST['X']))&&($_POST['X'])){
   $cIce->ot_X();
}elseif ((isset($_POST['Z']))&&($_POST['Z'])){
   $cIce->ot_Z();
}elseif ((isset($_POST['frk_continue']))&&($_POST['frk_continue'])){
    $cIce->write_trsc(117,'',0,0,0);
    $cIce->frk_continue();
}elseif ((isset($_POST['frk_cancel']))&&($_POST['frk_cancel'])){
   $cIce->write_trsc(118,'',0,0,0);
   $cIce->frk_cancel();
}elseif ((isset($_POST['repeat']))&&($_POST['repeat'])){
   $cIce->write_trsc(119,'',0,0,0);
   $cIce->repeat();
}elseif ((isset($_POST['view']))&&($_POST['view'])){
   $cIce->read_check($_POST['code']);
}elseif ((isset($_POST['remove']))&&($_POST['remove'])){
   $cIce->trsc_remove();
}elseif((isset($_POST['price']))&&($_POST['price'])){
   $price=$_POST['code'];
   $cIce->change_trsc('price',$price);
}elseif ((isset($_POST['count']))&&($_POST['count'])){
   $count=$_POST['code'];
   $cIce->change_trsc('count',$count);
}elseif ((isset($_POST['bonus_type']))&&($_POST['bonus_type'])){
   $cIce->change_bonus_type();

}


if (count($cIce->ice_cur_tovars)>1){
   $viz=$cIce->ice_cur_tovars;
   $isviz=1;
}
if ((isset($_POST['vkbd']))&&($_POST['vkbd'])){
   $_SESSION['vkbd'] = $_SESSION['vkbd'] ? 0 : 1;
}
# --------------------------------
if (!$cIce->ice_tp){
    $type_check='продажа';
}else{
    $type_check='возврат';
}
$zsum=$cIce->calc_zsum();

$c_actions=$cIce->get_actions();
?>

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Регистация продаж</title>
<link rel="stylesheet" type="text/css" href="IceCash.css">
</head>

<body onload="setFocus();">
<? ice_links() ?>

<form name="shk" id="shk" method="post" action=reg.php#code> 
    <input id="code" name="code" class="big" size=3 type="text" value="" autocomlete="off"  onKeyDown="keyr(event);">
    <span class="btn" onclick='keyread(121);' >Клавиши</span>
    <input id="cena" name="cena" type="hidden" value="">
    <input id="count" name="count" type="hidden" value="">
    <input id="price" name="price" type="hidden" value="">
    <input id="remove" name="remove" type="hidden" value="">
    <input id="cancel" name="cancel" type="hidden" value="">
    <input id="openbox" name="openbox" type="hidden" value="">
    <input id="typecheck" name="typecheck" type="hidden" value="">
    <input id="view" name="view" type="hidden" value="">
    <input id="pay" name="pay" type="hidden" value="">
    <input id="pay_kred" name="pay_kred" type="hidden" value="">
    <input id="codetov" name="codetov" type="hidden" value="">
    <input id="viz" name="viz" type="hidden" value="">
    <input id="frk_cancel" name="frk_cancel" type="hidden" value="">
    <input id="frk_continue" name="frk_continue" type="hidden" value="">
    <input id="repeat" name="repeat" type="hidden" value="">
    <input id="X" name="X" type="hidden" value="">
    <input id="Z" name="Z" type="hidden" value="">
    <input id="vkbd" name="vkbd" type="hidden" value="">
    <input id="cursor" name="cursor" type="hidden" value="">
    <input id="bonus_type" name="bonus_type" type="hidden" value="">
</form>
<span class="big" id="sdacha">Сдача: <? echo $cIce->ice_sdacha  ?></span>
<? if (!$_SESSION['vkbd']){echo "<!--";} ?>
<table cols=4 width=400px>
 <tr>
  <td class="btn" onclick='keyread(111);' >Отмена </td>
  <td class="btn" onclick='keyread(45);' >Оплата</td>
  <td class="btn" onclick='keyread(46);' >Кредит</td>
  <td class="btn" onclick='keyread(122);' >X</td>
  <td class="btn" onclick='keyread(123);' >Z </td>
 </tr>
 <tr>
  <td class="btn" onclick='keyread(33);'  >Возврат</td>
  <td class="btn" onclick='keyread(113);' >Копия</td>
  <td class="btn" onclick='keyread(106);' >Сторно</td>
  <td class="btn" onclick='keyread(107);' >Изм.кол</td>
  <td class="btn" onclick='keyread(109);' >Изм.цен</td>
 </tr>
<tr>
  <td class="btn" onclick='keyread(36);' >КодТов.</td>
  <td class="btn" onclick='keyread(35);' >ПоискТов.</td>
  <td class="btn" onclick='keyread(34);' >Ящик</td>
  <td class="btn" onclick='keyread(119);' >Допечат</td>
  <td class="btn" onclick='keyread(118);' >Аннулир</td>
 </tr>
</table>
<? if (!$_SESSION['vkbd']){echo "-->";} ?>
<div id="refresh">
<?
 if ($isviz==1){
 if (count($viz)){
    echo "<table id='tab0' class='tab' cols=1 rules=all cellpadding=3>";
    echo "<tr><th>Код</th><th>Наименование</th><th>Цена</th></tr>";
    $i=0;
    foreach ($viz as $k => $v){
       if ($i==0){ $colorc="class=\"active\"";}else{$colorc="";}
       $i=$i+1;
       #echo "<tr $colorc><td><a href=\"\" class=\"link\" onclick=\"addsale(".$v['id'].",".$v['cena'].");\">".$v['id']."</a></td><td>".$v['name']."</td><td>".$v['cena']."</td></tr>";
       echo "<tr $colorc><td>".$v['id']."</td><td>".$v['name']."</td><td>".$v['cena']."</td></tr>";
       if ($i>=20){break;}
    }
    echo "</table>";
    echo '<input id="isviz" name="isviz" type="hidden" value="'.$isviz.'">';
    echo '<input id="maxcount" name="maxcount" type="hidden" value="'.count($viz).'">';
    echo '<input id="firstcur" name="firstcur" type="hidden" value="1">';
 }else{
    echo '<input id="isviz" name="isviz" type="hidden" value="'.$isviz.'">';
    echo '<input id="maxcount" name="maxcount" type="hidden" value="'.count($cIce->ice_trsc['type']).'">';
    echo '<input id="firstcur" name="firstcur" type="hidden" value="0">';
  echo "Не найдено (вводите маленькими буквами)";  
 }}else{
    echo '<input id="isviz" name="isviz" type="hidden" value="'.$isviz.'">';
    echo '<input id="maxcount" name="maxcount" type="hidden" value="'.count($cIce->ice_trsc['type']).'">';
    echo '<input id="firstcur" name="firstcur" type="hidden" value="0">';
 }

?>
<table id="tab" class="tab" cols=4 rules=all cellpadding=3>
<tr> <th>Наименование товара</th><th>Кол</th><th>Цена</th><th>Сумма</th></tr>
<?
    $i=0;
    $c=count($cIce->ice_trsc['type']);
    for ($i=0;$i<$c;$i++){
        if ($i==$cIce->ice_cursor) { $colorc="class=\"active\"";}else{$colorc="";}
        $j=$i+1;
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
            $sum=$cIce->ice_sum_skid;
            $btn="Зачислить";
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
</div>
<div style="mark"> 
    <? 
    echo "<br/>";
    if (isset($cIce->ice_actions)){
    foreach ($cIce->ice_actions as $k => $v){
     if (($cIce->sum_count_wo_0($v['idt']*1)) >= ($v['count']*1)) {
      echo "<div class='warning'>Найдена акция: ".$v['name']."</div>\n";
    }}
    }
    ?>
    <? 
    echo "<br/>";
    if (isset($cIce->ice_mactions)){
    foreach ($cIce->ice_mactions as $k => $v){
     if ($cIce->ice_sum>= ($v['count']*1)) {
      echo "<div class='warning'>Бонус на сумму чека: ".$v['name']."</div>\n";
    }}
    }
    ?>
</div>
<? mysql_close();?>
</body>
</html>

<script type="text/javascript">
    cursor=<?echo $cIce->ice_cursor+1;?>;
    setvar();
    function ActiveLine(num){
        setvar();
           if (isviz==0){
            tab=document.getElementById("tab");
           }else{
            tab=document.getElementById("tab0");
           }
            tab.rows[cursor].className="";
            cursor=num;
            tab.rows[cursor].className="active";
                if (isviz==0){
                    curs=document.getElementById("cursor");
                    curs.value=cursor;
                }
    } 
    function setvar(){
            isviz=document.getElementById("isviz").value;
            maxcount=document.getElementById("maxcount").value;
            firstcur=document.getElementById("firstcur");
            if (firstcur.value>0){
                cursor=parseInt(firstcur.value);
                firstcur.value=0;
                curs=document.getElementById("cursor");
                curs.value=cursor;
            }
            //cursor=document.getElementById("cursor").value;
    }

    function calcs(){
        itog=document.getElementById("itog").innerHTML*1;
        sum=document.getElementById("code").value*1;
        if (sum<itog){
            sum=itog;
            document.getElementById("code").value=itog;
        }
        sdacha=Math.round((sum-itog)*100)/100;
        if (sum>6000){
            alert("слишком большая сумма наличности");
            return 0;
        } 
        document.getElementById("sdacha").innerHTML="Сдача: "+sdacha;
        return 1;
    }
    function clearcode(){
        document.getElementById("code").value="";

    }
    function setFocus(){
        document.getElementById("code").focus();
    }
    function addsale(kod,cena){
        inp=document.getElementById("code");
        inp.value=kod;
        inp=document.getElementById("codetov");
        if (cena){
            document.getElementById("cena").value=cena;
        }
        inp.value="1";
        document.forms["shk"].submit();
        return False;
    }
    function keyread(key){
        if (key==111){
            if (confirm ('Отменить чек?')){
                input_cancel=document.getElementById("cancel");
                input_cancel.value="1";
                document.forms["shk"].submit();
            }    
        }
        if (key==112){
                num=document.getElementById("code");
                if (num.value==""){
                    num.value=1;
                }
                input_view=document.getElementById("view");
                input_view.value="1";
                document.forms["shk"].submit();
        }
        if (key==113){
            if (confirm ('Сделать копию последнего чека?')){
                input_cancel=document.getElementById("repeat");
                input_cancel.value="1";
                document.forms["shk"].submit();
            }    
        }
        if (key==114){
            clearcode();
        }
        if (key==121){
                input_cancel=document.getElementById("vkbd");
                input_cancel.value="1";
                document.forms["shk"].submit();
        } 
        if (key==122){
            if (confirm ('Снять X отчет (без гашения)?')){
                input_cancel=document.getElementById("X");
                input_cancel.value="1";
                document.forms["shk"].submit();
            }    
        }
        if (key==118){
            if (confirm ('ФРК: Аннулировать чек?')){
                input_cancel=document.getElementById("frk_cancel");
                input_cancel.value="1";
                document.forms["shk"].submit();
            }    
        }if (key==119){
            if (confirm ('ФРК: Продолжить печать?')){
                input_cancel=document.getElementById("frk_continue");
                input_cancel.value="1";
                document.forms["shk"].submit();
            }    
        }if (key==120){
                input_bt=document.getElementById("bonus_type");
                input_bt.value="1";
                document.forms["shk"].submit();
        }
        if (key==123){
            if (confirm ('Снять Z отчет (с гашением)?')){
                input_cancel=document.getElementById("Z");
                input_cancel.value="1";
                document.forms["shk"].submit();
            }    
        }
        if (key==106){
            input_remove=document.getElementById("remove");
            input_remove.value="1";
            document.forms["shk"].submit();
        }
        if (key==107){
            sh=document.getElementById("code");
            input_count=document.getElementById("count");
            input_count.value="1";
            txt=sh.value;
            sh.value=txt.replace(",",".");
            document.forms["shk"].submit();
        }
        if (key==109){
            input_price=document.getElementById("price");
            input_price.value="1";
            document.forms["shk"].submit();
        }
        if (key==13){
            code=document.getElementById("code").value;
            document.getElementById("code").value="";

            xhttp=new XMLHttpRequest();
            xhttp.onreadystatechange=function(){
                document.getElementById('refresh').innerHTML=xhttp.responseText;
            }
            xhttp.open('POST','r.php',true);
            xhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
            var str='code='+code;
            xhttp.send(str);
        }
        if (key==36){
            code=document.getElementById("code").value;
            document.getElementById("code").value="";

            xhttp=new XMLHttpRequest();
            xhttp.onreadystatechange=function(){
                document.getElementById('refresh').innerHTML=xhttp.responseText;
            }
            xhttp.open('POST','r.php',true);
            xhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
            var str='codetov=1&code='+code;
            xhttp.send(str);
        }
        if (key==34){
            inp=document.getElementById("openbox");
            inp.value="1";
            document.forms["shk"].submit();
        }
        if (key==35){
            inp=document.getElementById("viz");
            inp.value="1";
            curs=document.getElementById("cursor");
            curs.value=1;
            document.forms["shk"].submit();
        }if (key==33){
            if (confirm ('Вы хотите сменить тип чека?')){
                inp=document.getElementById("typecheck");
                inp.value="1";
                document.forms["shk"].submit();
            }
        }
        if (key==46){
            if (confirm ('Оплата кредтной картой?')){
                inp=document.getElementById("pay_kred");
                inp.value="1";
                document.forms["shk"].submit();
            }
        }
        if (key==38){

          if (cursor>1){
           if (isviz==0){
            tab=document.getElementById("tab");
           }else{
            tab=document.getElementById("tab0");

           }
          
                tab.rows[cursor].className="";
                cursor=cursor-1;
                tab.rows[cursor].className="active";
                if (isviz==0){
                    curs=document.getElementById("cursor");
                    curs.value=cursor;
                }
          }
        }
        if (key==40){
          if (cursor<maxcount){
           if (isviz==0){
            tab=document.getElementById("tab");
           }else{
            tab=document.getElementById("tab0");
           }
          
                tab.rows[cursor].className="";
                cursor=cursor+1;
                tab.rows[cursor].className="active";
                if (isviz==0){
                    curs=document.getElementById("cursor");
                    curs.value=cursor;
                }
          } 
         }
        if (key==45){
           if (isviz==0){
            cl=calcs();
            if (cl){
                sh=document.getElementById("code");
                inp=document.getElementById("pay");
                inp.value="1";
                txt=sh.value;
                sh.value=txt.replace(",",".");
                calcs();
                document.forms["shk"].submit();
            }
           }else{
            tab=document.getElementById("tab0");
            kod=tab.rows[cursor].cells[0].innerHTML;
            cena=tab.rows[cursor].cells[2].innerHTML;
            addsale(kod,cena);
           }
        }

        setFocus();
}

    function keyr(e)
    {
        var key = e.keyCode;
        //alert("key="+key);
        var keys = { 
                     13 : true,
                     38 : true,
                     40 : true,
                     45 : true, 
                     46 : true, 
                     33 : true,
                     34 : true, 
                     35 : true, 
                     36 : true,
                     106: true,
                     107: true,
                     109: true,
                     111: true,
                     //112: true,
                     113: true,
                     114: true,
                     120: true,
                     121: true,
                     122: true,
                     118: true,
                     119: true,
                     123: true };
        if (key in keys){
            setvar();
            e.stopPropagation();
            e.preventDefault();
            keyread(key);
        }
    }
    document.onclick=setFocus;
</script>
