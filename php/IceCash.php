<?php
  # version 1.3.013
  include_once("funct.php");

  # Costants
  $ice_user_kassir='kassir';
  $ice_user_admin='admin'; 
  $ice_separator="\t";

  $msg_ok='ok';
  $msg_err='err';

function initobj(){
    global $cIce;
    if (!isset( $cIce )){
     $cIce =new cIce(); 
    } 
    return $cIce;
}

#Обект сокетного клиента
class csocket{ 
  var $ip,$port,$socket; 

      function __construct($ip,$port) { 
        $this->ip = $ip; 
        $this->port=$port;
      } 

      function open() { 
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket < 0) {
                return 1; 
            }
            if (!socket_connect($this->socket, $this->ip, $this->port)){
                return 2;
            }
            return 0;
      } 
      function close() { 
        if (isset($this->socket)) {
            socket_close($this->socket);
        }
      }
      function send($msg) { 
            return socket_write($this->socket, $msg, strlen($msg));
      } 
      function recv() { 
            $msg = socket_read($this->socket, 16000);
            return $msg;
      } 

} 

#Объект клиента IceCashiBonus
class cIceBonusClient extends csocket{
   var $nkassa=1;

   function init() {
    global $cIce;
    global $msg_err,$msg_ok;
    $this->idplace=$cIce->ice_sets['idplace'];
    $this->nkassa=$cIce->ice_sets['nkassa'];
    $r=2;$t=0;
    while (($r==2)&&($t<3)){
        if ($this->open()) { return 1; }
        $msg=$this->recv();
        if ($msg == $msg_err){
            $t=$t+1;
            $r=2;
            sleep(4);
        }
        elseif ($msg==$msg_ok){
            $r=0;
        }
    }
    return $r;
   }

   function sendcmd($cmd,$card,$summa){
   #(cmd,card,idp,idkassa,ncheck,idtrsc,summa)
   global $ice_separator,$cIce;
    $idtrsc=$cIce->get_lasttrsc();
    $ncheck=$cIce->get_lastncheck()+1;
     $p=implode($ice_separator,array($cmd,$card,$this->idplace,$this->nkassa,$ncheck,$idtrsc,$summa));
     $this->send($p);
     $msg=$this->recv();
     return $msg;
   }

   function _ping(){
    return  $this->sendcmd( 'ping','0','0' );
   }
   function _info($card){
    return  $this->sendcmd( 'info',$card,'0');
   }
   function _addsum($card,$summa){
    return  $this->sendcmd( 'addsum',$card,$summa);
   }
   function _dedsum($card,$summa){
    return  $this->sendcmd( 'dedsum',$card,$summa);
   }
   function _closesum($card){
    return  $this->sendcmd( 'closesum',$card,0);
   }
}

#Объект клиента IceCash
class cIceClient extends csocket{
   var $nkassa=1;

   function init() {
    global $cIce;
    global $msg_err,$msg_ok;
    $this->nkassa=$cIce->ice_sets['nkassa'];
    $r=2;$t=0;
    while (($r==2)&&($t<3)){
        if ($this->open()) { return 1; }
        $msg=$this->recv();
        if ($msg == $msg_err){
            $t=$t+1;
            $r=2;
            sleep(4);
        }
        elseif ($msg==$msg_ok){
            $r=0;
        }
    }
    return $r;
   }

   function sendcmd($id,$to,$cmd,$param=array()){
   global $ice_separator;
    if (count($param)){
     $p=$ice_separator.implode($ice_separator,$param);
    }else{$p='';}
     $s=$id.$ice_separator.$to.$ice_separator.$cmd.$p;
     $this->send($s);
     #echo $s;
     $msg=$this->recv();
     return $msg;
   }

   function connect(){
    global $cIce;
    if (($cIce->ice_sets['dev'])&&($cIce->ice_sets['speed'])) {
        $err=$this->sendcmd($this->nkassa,'frk','connect', array($cIce->ice_sets['dev'],$cIce->ice_sets['speed']) );
        return $err;
    }else{return 1;}
   }

   function scanner_restart(){
    global $cIce;
    if ($cIce->ice_sets['scanner']){
        return  $this->sendcmd( $this->nkassa, 'scanner', 'restart', array($cIce->ice_sets['scanner']) );
    }else {return 'err';}
   }
   function _get_info($pvar){
    return  $this->sendcmd( $this->nkassa, 'frkr', 'info',array($pvar) );
   }
   function _get_info_all(){
    return  $this->sendcmd( $this->nkassa, 'frkr', 'allinfo' );
   }
   function number_check(){
    return  $this->sendcmd( $this->nkassa, 'frkr', 'report' );
   }
   function status(){
    return $this->sendcmd( $this->nkassa, 'frkr', 'status' );
   }
   function _open_check($type){
    return $this->sendcmd( $this->nkassa, 'frk', 'open_check', array($type) );
   }
   function _close_check($nal,$bnal,$skid){
    return $this->sendcmd( $this->nkassa, 'frk', 'close_check', array($nal,$bnal,$skid) );
   }
   function _cancel_check(){
    return $this->sendcmd( $this->nkassa, 'frk', 'cancel_check', array() );
   }
   function _repeat_check(){
    return $this->sendcmd( $this->nkassa, 'frk', 'repeat_check', array() );
   }
   function _continue(){
    return $this->sendcmd( $this->nkassa, 'frk', 'continue', array() );
   }
   function _cut(){
    return $this->sendcmd( $this->nkassa, 'frk', 'cut', array() );
   }
   function _X(){
    return $this->sendcmd( $this->nkassa, 'frk', 'X', array() );
   }
   function _Z(){
    return $this->sendcmd( $this->nkassa, 'frk', 'Z', array() );
   }
   function _roll(){
    return $this->sendcmd( $this->nkassa, 'frk', 'roll', array() );
   }
   function _open_box(){
    return $this->sendcmd( $this->nkassa, 'frk', 'open_box', array() );
   }
   function _set_date(){
    return $this->sendcmd( $this->nkassa, 'frk', 'set_date', array() );
   }
   function _set_time(){
    return $this->sendcmd( $this->nkassa, 'frk', 'set_time', array() );
   }
   function _sale($count,$price,$idtov){
    return $this->sendcmd( $this->nkassa, 'frk', 'sale', array($count,$price,$idtov) );
   }
   function _return($count,$price,$idtov){
    return $this->sendcmd( $this->nkassa, 'frk', 'return', array($count,$price,$idtov) );
   }
   function _print($text){
    return $this->sendcmd( $this->nkassa, 'frk', 'print', array($text) );
   }
   function _download(){
    return $this->sendcmd( $this->nkassa, 'self', 'download', array() );
   }
   function _frk_on(){
    return $this->sendcmd( $this->nkassa, 'self', 'frk_on', array() );
   }
   function _closeweb(){
    return $this->sendcmd( $this->nkassa, 'self', 'closeweb', array() );
   }
   function _frk_off(){
    return $this->sendcmd( $this->nkassa, 'self', 'frk_off', array() );
   }
   function _price_code($id){
    return $this->sendcmd( $this->nkassa, 'db', 'price_code', array($id) );
   }
   function _discount_code($id){
    return $this->sendcmd( $this->nkassa, 'db', 'discount_code', array($id) );
   }
   function _trsc_discount($id,$summa){
    return $this->sendcmd( $this->nkassa, 'db', 'trsc_discount', array($id,$summa) );
   }
   function _trsc_bonuscard($id,$summa){
    return $this->sendcmd( $this->nkassa, 'db', 'trsc_bonuscard', array($id,$summa) );
   }
   function _writesets($autonull,$autobox,$autocut){
    return $this->sendcmd( $this->nkassa, 'frk', 'writesets', array($autonull,$autobox,$autocut));
   }
   function _writesets_text($t12,$t13,$t14){
    return $this->sendcmd( $this->nkassa, 'frk', 'writesets_text', array($t12,$t13,$t14));
   }
   function _trsc_add($type,$id,$f1,$f2,$f3){
    return $this->sendcmd( $this->nkassa, 'db', 'trsc_add', array($type,$id,$f1,$f2,$f3) );
   }
}

Class cIce{
  var $ice_cur_count,$ice_trsc, $ice_sets, $ice_tp, $ice_sum,$ice_sum_skid,$ice_nal,$ice_bnal,$ice_skid,$ice_sdacha,$ice_cur_tovar;
  var $ice_number,$ice_status,$discount_card,$bonus_card,$ice_cur_tovars,$ice_cursor;
  function sets_to_global(){
    $_SESSION['ice_sets']=$this->ice_sets;
    $_SESSION['ice_trsc']=$this->ice_trsc;
    $_SESSION['ice_tp']=$this->ice_tp;
    $_SESSION['ice_number']=$this->ice_number;
    $_SESSION['ice_cursor']=$this->ice_cursor;
    $_SESSION['discount_card']=$this->discount_card;
    $_SESSION['bonus_card']=$this->bonus_card;
    $_SESSION['bonus_type']=$this->bonus_type;
    //echo "<br/> GLOBAL=".$_SESSION['ice_sets']['idplace']."<br/>";
  }
  function global_to_sets(){
    //echo "<br/> READ FROM GLOBAL=".$_SESSION['ice_sets']['idplace']."<br/>";
    if (isset($_SESSION['ice_sets'])){
        $this->ice_sets = $_SESSION['ice_sets'];
        $this->ice_trsc = $_SESSION['ice_trsc'];
        $this->ice_tp = $_SESSION['ice_tp'];
        $this->ice_cursor = $_SESSION['ice_cursor'];
        $this->ice_number = $_SESSION['ice_number'];
        $this->bonus_card = $_SESSION['bonus_card'];
        $this->bonus_type = $_SESSION['bonus_type'];
    //echo "<br/> GLOBALIS=".$_SESSION['ice_sets']['idplace']."<br/>";
    }
  }
  function openBonusClient(){
    return $this->BonusClient->init();
  }
  function openClient(){
    return $this->Client->init();
  }
  function connectClient(){
    $err=$this->openClient();
    if (!$err) {
        $cn='dIce_ok';
        $this->Client->scanner_restart();
        $err=1;$i=0;
        while (($err)&&($i<3)){
            $i=$i+1;
            $err=$this->Client->connect();
            if ($err){$cf='frk_error';}
            else {$cf='frk_ok';}
        }
        $this->Client->close();

    }
    elseif ($err==1) {$cn='dIce_notfind';}
    elseif ($err==2) {$cn='dIce_wait';}
    $err=$this->openBonusClient();
    if (!$err) {
        $cb='dIceBonus_ok';}
    else
    { $cb='dIceBonus_error'; }
    return array($cn,$cf,$cb);
  }
  function clear(){
    $this->ice_trsc=array('type'=>array(),'id'=>array(),'count'=>array(),'price'=>array(),'name'=>array());
    $this->ice_cur_count=0;
    $this->ice_sum=0;
    $this->ice_skid=0;
    $this->ice_sum_skid=0;
    $this->ice_nal=0;
    $this->ice_bnal=0;
    $this->ice_tp=0;
    $this->ice_cursor=0;
    $_SESSION['ice_mode']=1;
    $this->discount_card=array();
    $this->bonus_card=array();
    $this->bonus_type=0;
    $this->sets_to_global();
  }
  function __construct() { 
    #$this->clear();
    $this->Client = new cIceClient('127.0.0.1',7171);
    $this->global_to_sets();
    $this->ice_default_count=1;
    $this->BonusClient = new cIceBonusClient($this->ice_sets['bonusserver'],$this->ice_sets['bonusport']);
  } 
  function calc(){
    $this->ice_sum=0;
    $this->ice_sum_section=array(0,0,0);
    $c=count($this->ice_trsc['type']);
    for ($i=0;$i<$c;$i++){
        $type=$this->ice_trsc['type'][$i];
        if (($type == 'sale')||($type == 'return')){
            $tr=True;
            $price=$this->ice_trsc['price'][$i];
            $count=$this->ice_trsc['count'][$i];
        }
        else{
            $tr=False;
        }
        # DOOM 2013-11-22 Суммируем по секциям, Скидка делается только на товар 1ой секции!
        if ($tr){
            $tovar=$this->read_tovar($this->ice_trsc['id'][$i]);
            $section=$tovar['section'];
            $this->ice_sum_section[$section]=$this->ice_sum_section[$section]+round($price*$count,2);
            $this->ice_sum=$this->ice_sum+round($price*$count,2);
        }
    }
    #Если есть бонусная карта
    if (isset($_SESSION['bonus_card'])&&(count($_SESSION['bonus_card']))){
        $this->bonus_card=$_SESSION['bonus_card'];
        #Списание с карты, скидка с чека
        if ($this->bonus_type==1){
            $sum4skid=round($this->ice_sum*$this->bonus_card[10],0);
            if ($this->bonus_card[9]>=$sum4skid){
                $this->ice_sum_skid=$sum4skid;
            }else{
                $this->ice_sum_skid=$this->bonus_card[9];
            }
            #Перерассчитываем процент скидки по отношению к общей сумме
            $this->ice_skid=round($this->ice_sum_skid/$this->ice_sum*100,2);
            $sum4skid=round($this->ice_skid/100*$this->ice_sum,2);
            $this->ice_sum_skid=$sum4skid;
        }
        # Зачисление бонусов на карту с первой секции и кроме акционного товара
        if ($this->bonus_type==0){
            $this->sum4nach=round(($this->ice_sum_section[1]-$this->sum_action)*$this->bonus_card[11],2);
        }
    }
    #Или если есть дисконтная карта
    elseif(isset($_SESSION['discount_card'])&&(count($_SESSION['discount_card']))){
        $this->discount_card=$_SESSION['discount_card'];
        $this->ice_skid=$this->discount_card['procent'];
        #РАСЧЕТ ПРОЦЕНТА СКИДКИ И ПЕРЕРАСЧЕТ СКИДКИ ----------
        if ($this->ice_skid>0){ 
            #Расчитываем скидку на первую секцию округленную
            $this->ice_sum_skid=round($this->ice_skid * $this->ice_sum_section[1] / 100,0);
            #Перерассчитываем процент скидки по отношению к общей сумме
            $this->ice_skid=round($this->ice_sum_skid/$this->ice_sum*100,2);
            #Еще раз расчитываем скидку с новым процентом
            $this->ice_sum_skid=round($this->ice_skid*$this->ice_sum/100,2);
        }else{ $this->ice_skid=0; }
        #-----------------------------------------------------
    }else{ $this->ice_skid=0; }

    $this->ice_sum_wo_skid=$this->ice_sum;
    $this->ice_sum=$this->ice_sum-$this->ice_sum_skid;
    if (!$this->ice_bnal){
        if ($this->ice_nal==0){
            $this->ice_nal=$this->ice_sum;
        }
        $this->ice_sdacha=round($this->ice_nal-$this->ice_sum,2);
    }else{ $this->ice_sdacha=$this->ice_nal; }
    return $this->ice_sum;
  }

  function open_check(){
    $this->clear();
  }

  function sum_count_0($_idt){
    $c=count($this->ice_trsc['type']);
    $r=0;

    if ($c){
        for ($i=0;$i<$c;$i++){
            $price=$this->ice_trsc['price'][$i];
            $idtov=$this->ice_trsc['id'][$i];
            if (($_idt==$idtov)&&($pricei==0)){ $r=$r+$this->ice_trsc['count'][$i]; }
        }
    }
    return $r;
  }

  function sum_count_wo_0($_idt){
    $c=count($this->ice_trsc['type']);
    $r=0;

    if ($c){
        for ($i=0;$i<$c;$i++){
            $price=$this->ice_trsc['price'][$i];
            $idtov=$this->ice_trsc['id'][$i];
            if (($_idt==$idtov)&&($price>0)){ $r=$r+$this->ice_trsc['count'][$i]; }
        }
    }
    return $r;
  }
  function find_in_check($_idt,$_price,$_count){
    $c=count($this->ice_trsc['type']);
    $r=-1;
    if ($c){
        for ($i=0;$i<$c;$i++){
            $price=$this->ice_trsc['price'][$i];
            $count=$this->ice_trsc['count'][$i];
            $idtov=$this->ice_trsc['id'][$i];
            if ($_count==-1){$count=-1;}
            if ($_price==-1){$price=-1;}
            if (($_idt==$idtov)&&($_price==$price)&&($_count==$count)){
                $r=$i;break;
            }
        }
    }
    return $r;
  }
  function close_check($nal,$bnal){
    global $msg_err;
    if (isset($_SESSION['ice_bnal_old'])) {
        if ($_SESSION['ice_bnal_old']) {
            $nal=0;$bnal=$_SESSION['ice_bnal_old'];
        }
    }
    $_SESSION['ice_bnal_old']=$bnal;
    if (!$_SESSION['ice_mode']){$this->ice_status='ошибка: режим просмотра чека';return False;}
    $this->ice_nal=$nal;$this->ice_bnal=$bnal;
    $this->calc();
    
    #open_check #trsc #close_check #result
    $this->ice_status='error: frk';
    if ($this->openClient()!=0){
        $this->ice_status='error: wait';
        return False;
    };
    #------------------ Начало обработки чека -----------------
    $this->sum_action=0;#Для акционного товара найденного в чеке
    #------------------ Акционные товары -----------------
    if (($bnal==0)&&($nal==0)){$nal=$this->ice_sum;}
    if ($bnal) {$nal=0;}
    $c=count($this->ice_trsc['type']);
    if ($c){
        #Проверяем акции
        $this->get_actions();
        foreach ($this->ice_actions as $k => $v){
            $sum0 = $this->sum_count_0($v['idt']); //Позиций с аукционным товаром по  нулевой цене
            $sumc = $this->sum_count_wo_0($v['idt']); //Позиций с аукционным, но не нулевым товаром 
            if (($sum0>0)&&($sumc==0)){ //Если только нулевые значения, то это подарок, его считать ошибкой
                    $this->ice_status='ошибка: пустая продажа ';
                    return False;
            }
            if ($sumc>0){ // Найдена акция в чеке
                $a=$this->find_in_check($v['idt'],-1,-1);
                $t=$this->find_in_check($v['idt'],0,-1);
                $c1=$this->ice_trsc['count'][$t]*1;
                $cp=$v['countplus'];
                $m=floor($sumc/$v['count']);
                $c2=$v['countplus']*$m;
                #DOOM 2014-04-17
                #Накапливаем сумму на которую приобретается акционные позиции. То есть сумма денег на которые были куплены товары по акции
                $this->sum_action=$v['count']*$m*$this->ice_trsc['price'][$a]+$this->sum_action;

                if (($t>=0)&&($c1!=$c2)){ // find ++
                    $this->ice_trsc['count'][$t]=$c2; // change if hacking
                    $this->ice_status='внимание: изменено количество в акции '.$c1.">>".$c2;
                    $_SESSION['ice_trsc']=$this->ice_trsc;
                    return False;
                }elseif (($t<0)&&($c2>0)){
                    if ($this->ice_tp==0){$tp='sale';}else{$tp='return';}
                    $this->add_trsc($tp,$v['idt'],$c2,0,$this->ice_trsc['name'][$a]);
                    $this->ice_status='внимание: добавлена акция';
                    return False;
                }
            }
        }
    
    $this->calc();

    #-----------------  Обработка бонусной карты  -------------
        if (count($this->bonus_card)){
            $card=$this->bonus_card[1];
            $this->Client->_print("-----------------------");
            $this->Client->_print("бонусная карта №".$this->bonus_card[0]);
            $this->Client->_print("Остаток:".$this->bonus_card[9]);
            if ($this->openBonusClient()==0){
                if ($this->bonus_type==0){
                    $r=$this->BonusClient->_addsum($card,$this->sum4nach);
                    $this->Client->_print("Начислено:".$this->sum4nach);
                    $it=round($this->bonus_card[9]+$this->sum4nach,2);
                    $this->Client->_print("Итого:".$it);
                    $this->Client->_trsc_bonuscard($card,$this->sum4nach);
                }
                if ($this->bonus_type==1){
                    #Проводим на сервере скидку
                    $r=$this->BonusClient->_dedsum($card,$this->ice_sum_skid);
                    #Если скидка не совпала, то отмена операции
                    if (-$r!=$this->ice_sum_skid){
                        $this->ice_status='ошибка: скидка не совпадает с сервером';return False;
                    }
                    $this->Client->_trsc_discount($card,-$r);
                    $this->Client->_print("Списано:".$r);
                    $it=round($this->bonus_card[9]+$r,2);
                    $this->Client->_print("Итого:".$it);
                }
            }else{
                $this->ice_status='ошибка: нет соединения с сервером бонусов';return False;
            }
            $this->Client->_print("-----------------------");
        }
    #---------------- Конец обработки бонусных карт  ------------

        #---------------- Проверяем акции по сумме чека ----------
        $sumcheck=$this->ice_sum;
        $mact=$this->get_mactions();

        foreach ($this->ice_mactions as $k => $v){
                    #$this->ice_status='MACT='.$v['count'];
                    #return False;
            if ($v['count']<=$sumcheck) { // сумма чека попадает под акцию
                $cp=$v['countadd'];
                $m=floor($sumcheck/$v['count']);
                $c2=$v['countadd']*$m;
                $t=$this->find_in_check($v['idtadd'],0,-1);
                $a=$this->read_tovar($v['idtadd']);

                if (($t>=0)&&($c2!=$this->ice_trsc['count'][$t])){ // find ++
                    $this->ice_trsc['count'][$t]=$c2; // change if hacking
                    $this->ice_status='внимание: изменено количество в акции '.$c1.">>".$c2;
                    $_SESSION['ice_trsc']=$this->ice_trsc;
                    return False;
                }elseif ($t<0){
                    if ($this->ice_tp==0){$tp='sale';}else{$tp='return';}
                    $this->add_trsc($tp,$v['idtadd'],$c2,0,$a['name']);
                    $this->ice_status='внимание: добавлена акция';
                    return False;
                }
            }
        }
        $this->Client->_print($this->ice_sets['textcheck']);
        if ($this->Client->_open_check($this->ice_tp)=='err'){return False;}
        //if ($this->ice_sets['autobox']){ DOOM
            $this->Client->_open_box();
        //}
        for ($i=0;$i<$c;$i++){
            $price=$this->ice_trsc['price'][$i];
            $count=$this->ice_trsc['count'][$i];
            $idtov=$this->ice_trsc['id'][$i];
            if ($this->ice_tp == 0){
                if ($this->Client->_sale($count,$price,$idtov)=='err'){return False;}
            }
            if ($this->ice_tp == 2){
                if ($this->Client->_return($count,$price,$idtov)=='err'){return False;}
            }
        }
        #Обработка дисконтной карты
        if ((!count($this->bonus_card))&&($this->ice_skid)){ 
            $this->Client->_trsc_discount($this->discount_card['number'],$this->ice_sum_skid); 
        }
        $r=$this->Client->_close_check($nal,$bnal,$this->ice_skid);
        // FOR TYPE ASPD
        if ($this->ice_sets['typedev']!='ASPD'){
        if ($r=='err'){
            sleep(1);
            $this->Client->_continue();
            sleep(1);
            $this->Client->_continue();
            sleep(3);
            if ( $this->Client->connect() ){ return False; }
            $this->Client->_cancel_check();
            return False;
            #$r=$this->Client->_close_check($nal,$bnal,$this->ice_skid);
            #if ($r=='err'){return False; }
            #$r='R'.$r;
        }}else{ if ($r=='err'){$r='0;0';}}
        if (count($this->bonus_card)){
            $this->BonusClient->_closesum($card);
        }
        $this->Client->close();
        $_SESSION['ice_bnal_old']=0;
        $this->ice_status='чек '.$r;
        $a=spliti(";",$r);
        $this->ice_number=$a[0];
        $_SESSION['ice_number']=$this->ice_number;
    }else{
        $this->ice_status='ошибка: пустой чек';
        return False;
    }
    $this->clear();
    return True;
  }
  
  function get_lasttrsc(){
    $q=mysqlqueryOneLine("select idtrsc from trsc where nkassa=".$this->ice_sets['nkassa']." order by nkassa, idtrsc desc limit 1");
    if (count(q)){
        return $q['idtrsc'];}
    else{ return 0;}
  }
  
  function get_lastncheck(){
    $q=mysqlqueryOneLine("select ncheck from trsc where nkassa=".$this->ice_sets['nkassa']." order by nkassa, idtrsc desc limit 1");
    if (count(q)){
        return $q['ncheck'];}
    else{ return 0;}
  }

  function calc_zsum(){
    $q=mysqlqueryOneLine("select idtrsc from trsc where type=61 and nkassa=".$this->ice_sets['nkassa']." order by nkassa, idtrsc desc limit 1");
    $id=$q['idtrsc'];
    if (!$id){$id=0;}
    $nal=mysqlqueryOneLine("select sum(ParamF3) as sum from trsc where idtrsc>$id and type=40 and ParamF2=1 and nkassa=".$this->ice_sets['nkassa']);
    $bnal=mysqlqueryOneLine("select sum(ParamF3) as sum from trsc where idtrsc>$id and type=40 and ParamF2=2 and nkassa=".$this->ice_sets['nkassa']);
    return array($nal['sum'],$bnal['sum']);
  }
  function read_tovar($id){
    return mysqlqueryOneLine("select * from price where id='$id'");
  }
  function get_ot($date1,$time1,$date2,$time2,$ncheck,$type,$tov){
    if (!$date1){
        $date1='2012-10-01';
    }
    if (!$date2){
        $date2=$date1;
    }
    if (!$time1){
        $time1='08:00:00';
    }
    if (!$time2){
        $time2=$time1;
    }
    if ($ncheck){
        $if_check=" and ncheck=$ncheck";
    }else { $if_check=''; }
    if ($type){
        $if_type=" and type=$type";
    }else { $if_type=''; }
    if ($tov){
        $if_tov=" and params='$tov'";
    }else { $if_tov=''; }
    $this->ice_status="select * from trsc where date>='$date1' and date<='$date2' and  time>='$time1' and  time<='$time2' $if_check $if_type $if_tov";
    return mysqlquery("select date,time,ncheck,type,ParamS,ParamF1,ParamF2,ParamF3,(select name from price where id=params) as name from trsc where date>='$date1' and date<='$date2' and  time>='$time1' and  time<='$time2' $if_check $if_type $if_tov");
  }
  function like_tovar($tovar){
    return mysqlquery("select id,name,cena from price where lower(name) like('%$tovar%')");
  }
  function find_discount($shk){
    $this->discount_card = mysqlqueryOneLine("select * from discount_card where number='$shk'");
    if ((isset($this->discount_card['number']))&&($this->discount_card['number'])){
        $_SESSION['discount_card']=$this->discount_card;
        $this->ice_status='дисконт '.$this->discount_card['procent']."%";
    }
    else{$this->ice_status='';}
  }
  function find_bonuscard($card){
    global $ice_separator;
        if ($this->openBonusClient()==0){
            $info=$this->BonusClient->_info($card);
           $a=spliti($ice_separator,$info);
           $this->bonus_card=$a;
           $_SESSION['bonus_card']=$this->bonus_card;
           $this->ice_status='бонусная карта #'.$a[0];
           $this->ice_bonustype=0;
           $_SESSION['bonus_type']=0;
        }else{$this->ice_status='ошибка: сервер бонусов не отвечает';}
  }
  function change_bonus_type(){
    if ($this->bonus_type==0){
        $this->bonus_type=1;
    }else{
        $this->bonus_type=0;
    }
    $_SESSION['bonus_type']=$this->bonus_type;
    $this->ice_status='действие бонусной карты: '.$this->bonus_type;
  }
  function read_check($ncheck){
    $a=mysqlquery("select * from trsc where ncheck=$ncheck");
    if (count($a)){
        for ($i=0;$i<count($a);$i++){
            if ($a[$i]['type']==56){
                array_splice($a,$i);
            }
        }
    }
    if (count($a)){ 
        $this->clear();
        for ($i=0;$i<count($a);$i++){
            $this->ice_default_count=$a[$i]['ParamF2']*1;
            $this->add_sale($a[$i]['ParamS'],0,$a[$i]['ParamF1']);
        }
            $this->ice_number=$ncheck;
            $_SESSION['ice_number']=$this->ice_number;
            $_SESSION['ice_mode']=0;
            $this->ice_status='просмотр чека '.' date:'.$a[0]['date'].' time:'.$a[0]['time'];
            $this->ice_default_count=1;
        return True;
    }else{
        return False;
    }
  }
  function print_check(){
   if ($this->openClient()==0){
    $this->Client->_print("Копия чека номер".$this->ice_number);
    $c=count($this->ice_trsc['type']);
    for ($i=0;$i<$c-1;$i++){
        $this->Client->_print($this->ice_trsc['name'][$i]."    ".$this->ice_trsc['count'][$i]." X ".$this->ice_trsc['price'][$i]." = ".$this->trsc_sum($i));
    }
   }
  }
  function find_price($shk){
      $this->ice_status='ошибка: код товара';
      if (!$shk){return False;}
      $count=1;
      if ($this->ice_sets['scale_prefix']){
        $prefix=spliti(';',$this->ice_sets['scale_prefix']);
      }
      else{
        $prefix=array();
      }
      $isscale=False;
      #Проверяем является ли штрихкод весовым
      foreach ($prefix as $pref){
        if ( preg_match("/^$pref/",$shk) ){
            preg_match("/^$pref(\d{5})(\d{5}).*?/", $shk,$id);
            $c1=substr($id[2],0,2);$c2=substr($id[2],2);
            $idtov=$id[1];$count=$c1+$c2/1000;
            $isscale=True;break;
        }
      }

      if ($isscale){
        $a=mysqlquery("select * from price where id='$idtov'");
      }else{

	    $a=mysqlquery("select * from price where shk='$shk'");
        $a1=mysqlquery("select * from price_shk where shk='$shk'");
        if ((!count($a))||(count($a1))){
            #Если штрихкод не найден в основной базе или есть такойже в доп базе
            #$a1=mysqlquery("select * from price_shk where shk='$shk'");
            if (!count($a1)){
                $this->ice_status='ошибка: не найден код товара';
                return False;
            }
            #Товары по искомому штрихкоду в доп базе
            #Дополняем информацией из основной
            #$a=array();
            for ($i=0;$i<count($a1);$i++){
                $idtov=$a1[$i]['id'];
                $a2=mysqlqueryOneLine("select * from price where id='$idtov'");
                $fields=array('cena','shk','name');
                foreach ($fields as $k){
                    #if ($k=='cena'){
                        #Если в товаре из доп базы цена не нулевая то ее не меняем
                        #if (!round($a1[$i][$k],2)){
                        #    continue;
                        #}
                    #}
                    $a2[$k]=$a1[$i][$k];
                }
                $a[]=$a2;
            }
      }
    }
     if (count($a)){
      $this->ice_cur_tovars=$a;
      $this->ice_cur_tovar=$a[0];
      $this->ice_cur_count=$count;
      return True;}
     else {return False;}
  }
  function get_mactions() {
      $this->ice_mactions=array();
	  $this->ice_mactions=mysqlquery("select * from actions where idp=0 and idt=0");
      return count($this->ice_mactions);
  }
  function get_actions() {
      //echo ":".$this->ice_sets['idplace'];
      $this->ice_actions=array();
	  $this->ice_actions=mysqlquery("select * from actions where idp=".$this->ice_sets['idplace']);
      #echo "select * from actions where idp=".$this->ice_sets['idplace'];
      return count($this->ice_actions);
  }
  function get_sets() {
	  $a=mysqlquery("select name,value from sets");
      $this->ice_sets=array();
      foreach ($a as $k=>$v){
        $key=$v[0];$value=$v[1];
        $this->ice_sets[$key]=$value;
      }
      # Отключение ФРК (если в настройках не указан дэвайс)
        if ($this->openClient()==0){
            if (!$this->ice_sets['typedev']){
                $this->Client->_frk_off();
            }else{
                $this->Client->_frk_on();
            }
            $this->Client->close();
        }
      $this->sets_to_global();
      //echo "get sets;idp=".$this->ice_sets['idplace'];
  }
  function put_sets() {
      foreach ($this->ice_sets as $k=>$v){
	    mysqlexec("update sets set value=\"$v\" where name=\"$k\"");
      }
        if ($this->openClient()==0){
            $this->Client->_writesets(1,$this->ice_sets['autobox'],$this->ice_sets['autocut']);
            $this->Client->_writesets_text($this->ice_sets['firma'],$this->ice_sets['placename'],'Касса №'.$this->ice_sets['nkassa']);
            $this->Client->close();
            return 1;
        }
    return 0;
  }
  function set_typecheck(){
    if ($this->ice_tp==2){$this->ice_tp=0;}
    else{$this->ice_tp=2;}
    $_SESSION['ice_tp']=$this->ice_tp;
    $this->ice_status='тип чека';
  }
  function ot_Z(){
    if ($this->openClient()==0){
        $this->Client->_Z();
        $this->ice_status='Z';
        sleep(6);
        $this->Client->connect();
        sleep(2);
        $this->Client->connect();
        $this->Client->close();
        return 1;
    }else{return 0;}
  }
  function ot_X(){
    if ($this->openClient()==0){
        $this->Client->_X();
        $this->ice_status='X';
        sleep(6);
        $this->Client->connect();
        //sleep(2);
        //$this->Client->connect();
        $this->Client->close();
        return 1;
    }else{return 0;}
  }
  function frk_continue(){
    if ($this->openClient()==0){
        $this->Client->_continue();
        $this->Client->close();
        $this->ice_status='продолжить печать';
        return 1;
    }else{return 0;}
  }
  function frk_cancel(){
    if ($this->openClient()==0){
        $this->Client->_cancel_check();
        $this->Client->close();
        $this->ice_status='аннулировать чек';
        return 1;
    }else{return 0;}
  }
  function repeat(){
    if ($this->openClient()==0){
        $this->Client->_repeat_check();
        $this->Client->close();
        $this->ice_status='копия чека';
        return 1;
    }else{return 0;}
  }
  function openbox(){
    if ($this->openClient()==0){
        $this->Client->_open_box();
        $this->Client->close();
        $this->ice_status='открыть ящик';
        return 1;
    }else{return 0;}
  }
  function get_info($pvar){
    if ($this->openClient()==0){
        $r=$this->Client->_get_info($pvar); 
        $this->Client->close();
        return $r;
    }else{return 0;}
  }
  function get_status($full){
    if ($this->openClient()==0){
        if ($full){ $this->ice_number=$this->Client->number_check(); }
        $this->ice_status=$this->Client->status();
        $this->Client->close();
        return 1;
        $_SESSION['ice_number']=$this->ice_number;
    }else{return 0;}
  }
  function write_trsc_cancel(){
    if ($this->openClient()==0){
        $c=count($this->ice_trsc['type']);
        for ($i=0;$i<$c;$i++){
            $price=$this->ice_trsc['price'][$i];
            $count=$this->ice_trsc['count'][$i];
            $idtov=$this->ice_trsc['id'][$i];
            if ($this->ice_tp == 0){
                $type=11;
            }
            if ($this->ice_tp == 2){
                $type=13;
            }
            $this->Client->_trsc_add($type,$idtov,$price,$count,$count*$price);
        }
        $this->Client->_trsc_add(56,'',0,0,0);
        $this->Client->close();
     }
    }
  function write_trsc($type,$id,$f1,$f2,$f3){
    if ($this->openClient()==0){
        $this->Client->_trsc_add($type,$id,$f1,$f2,$f3);
        $this->Client->close();
     }
  }
  function add_trsc($type,$id,$count,$price,$name){
    $this->ice_trsc['type'][]=$type;
    $this->ice_trsc['id'][]=$id;
    $this->ice_trsc['count'][]=$count;
    $this->ice_trsc['price'][]=round($price,2);
    $this->ice_trsc['name'][]=$name;
    $_SESSION['ice_trsc']=$this->ice_trsc;
  }
  function add_sale($shk,$type,$cena) {
    if ( $type ){
        if ( !$this->find_price($shk) ){
            return False;
        }
        if (count($this->ice_cur_tovars)>1){
            return False;
        }
    }else{
        $t=$this->read_tovar($shk);
        if ( !$t['id'] ) {
            return False;
        }else{ $this->ice_cur_tovar=$t;$this->ice_cur_count=$this->ice_default_count; }
    }
    if (isset($cena)&&($cena)){$this->ice_cur_tovar['cena']=$cena;}
    $this->add_trsc('sale',$this->ice_cur_tovar['id'],$this->ice_cur_count,$this->ice_cur_tovar['cena'],$this->ice_cur_tovar['name']);
    $this->ice_status='добавлена позиция';
    $this->ice_cursor=count($this->ice_trsc['type'])-1;
    $_SESSION['ice_cursor']=$this->ice_cursor;
  }
  function trsc_sum($i) {
    return round($this->ice_trsc['count'][$i] * $this->ice_trsc['price'][$i],2);
  }
  function change_trsc($k,$v){
    $c=$this->ice_cursor;
    if ($c>=0){
        $this->ice_cur_tovar = $this->read_tovar($this->ice_trsc['id'][$c]);
        if ($k=='price'){
            if (!( ($this->ice_cur_tovar['type']==1) && ($this->ice_cur_tovar['minprice']<=$v) &&
                  (($this->ice_cur_tovar['maxprice']==0)||($this->ice_cur_tovar['maxprice']>=$v))  ))
            {
                $this->ice_status='ошибка: тип цены не меняется';
                return 1;
            }
            $this->ice_status='изменена цена';
            if ($this->openClient()==0){
                $this->Client->_trsc_add(114,$this->ice_trsc['id'][$c],$this->ice_trsc['count'][$c],$v,0);
                $this->Client->close();
            }
        }    
        if ($k=='count'){
           if ($v<=0){$this->ice_status='ошибка: количество<=0';return 1;}
           if ( ($this->ice_cur_tovar['real']==0) && ($v - floor($v) >0) )
            {
                $this->ice_status='ошибка: не весовой товар ';
                return 1;
            }
            $this->ice_status='изменено количество';
            if ($this->openClient()==0){
                $this->Client->_trsc_add(113,$this->ice_trsc['id'][$c],$this->ice_trsc['count'][$c],$v,0);
                $this->Client->close();
            }
        }    
        $this->ice_trsc[$k][$c]=$v+0;
        $_SESSION['ice_trsc']=$this->ice_trsc;
    }
  }
  function trsc_remove(){
    $c=$this->ice_cursor;
    #count($this->ice_trsc['type'])-1;
    if ($c>=0){
        if ($this->openClient()==0){
            $this->Client->_trsc_add(112,$this->ice_trsc['id'][$c],$this->ice_trsc['price'][$c],$this->ice_trsc['count'][$c],0);
            $this->Client->close();
        }
        array_splice($this->ice_trsc['type'],$c,1);
        array_splice($this->ice_trsc['id'],$c,1);
        array_splice($this->ice_trsc['price'],$c,1);
        array_splice($this->ice_trsc['count'],$c,1);
        array_splice($this->ice_trsc['name'],$c,1);
        $_SESSION['ice_trsc']=$this->ice_trsc;
        $this->ice_status='сторно';

        $this->ice_cursor=count($this->ice_trsc['type'])-1;
        $_SESSION['ice_cursor']=$this->ice_cursor;
    }
  }
  
}

  function ice_error($error,$to='') {
     if ($to!='') { $x="<br/> Вернитесь <a href=\"$to\">на рекомендуемую страницу </a> или";}else {$x='';}
     $_SESSION['error'] = $error."$x<br/> перейдите <a href=\"index.html\">на стартовую страницу</a>"; 
     header("Location: error.php");
  }

  function ice_mysql_connect() {
	$hostname = "localhost";
	$username = "icecash";
	$password = "icecash1024";
	$dbName = "IceCash";
    if (!mysql_connect($hostname,$username,$password)){
        return 0;    
    }
	if (!mysql_select_db($dbName)) {
        return 0;
    }else{
        mysqlquery("set names utf8 collate utf8_general_ci;");
        #mysqlquery("set charset latin1");
        return 1;
    }
  }
  
  function ice_get_user() {
    global $ip,$user;
    $user = $_SERVER['PHP_AUTH_USER'];
    $ip = $_SERVER['REMOTE_ADDR'];
  }

 function ice_user_isadmin() {
    global $ip,$user,$ice_user_admin;
    ice_get_user();
    if ($user == $ice_user_admin) {return True;} else {return False;}
  }

  function ice_links($p='') {
    $links=array(
    'index'=>array('/index.html','На главную'),
    'sets'=>array('/sets.php','Настройки'), 
    'start'=>array('/start.php','Старт'),
    'ot'=>array('/ot.php','Отчеты'),
    'exchange'=>array('/exchange.php','Выгрузка')
    );
    if (!$p){ $p=array('index','sets','start','ot','exchange'); }
    echo '  <table width=400>  <tr>';
    foreach ($p as $key=>$val){
     echo "<td class=\"small\">";
     echo "<a class=\"link\" href=\"".$links[$val][0]."\">".$links[$val][1]."</a></td>";
   }
   echo "<td class='small'><a class='link' href=\"/help.html\" target=\"blank\">Помощь</a></td>";
   echo "</tr></table>";
   echo "<hr width=400 align=left></hr>";
 }
 initobj();
 ice_get_user();
?>
