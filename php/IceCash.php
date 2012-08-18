<?php
  # IceCash Functions v 1.0.1
  include_once("funct.php");

  # Costants
  $ice_user_kassir='kassir';
  $ice_user_admin='admin'; #ИСПРАВИТЬ на admin

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
            $msg = socket_read($this->socket, 1024);
            return $msg;
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
            sleep(3);
        }
        elseif ($msg==$msg_ok){
            $r=0;
        }
    }
    return $r;
   }

   function sendcmd($id,$to,$cmd,$param=array()){
    if (count($param)){
     $p=';'.implode(';',$param);
    }else{$p='';}
     $s=$id.';'.$to.';'.$cmd.$p;
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
   function _frk_on(){
    return $this->sendcmd( $this->nkassa, 'self', 'frk_on', array() );
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
   function _trsc_discount($id){
    return $this->sendcmd( $this->nkassa, 'db', 'trsc_discount', array($id) );
   }
   function _writesets($autonull,$autobox,$autocut){
    return $this->sendcmd( $this->nkassa, 'frk', 'writesets', array($autonull,$autobox,$autocut));
   }
   function _writesets_text($t12,$t13,$t14){
    return $this->sendcmd( $this->nkassa, 'frk', 'writesets_text', array($t12,$t13,$t14));
   }
}

Class cIce{
  var $ice_cur_count,$ice_trsc, $ice_sets, $ice_tp, $ice_sum,$ice_sum_skid,$ice_nal,$ice_bnal,$ice_skid,$ice_sdacha,$ice_cur_tovar;
  var $ice_number,$ice_status,$discount_card,$ice_cur_tovars,$ice_cursor;
  function sets_to_global(){
    $_SESSION['ice_sets']=$this->ice_sets;
    $_SESSION['ice_trsc']=$this->ice_trsc;
    $_SESSION['ice_tp']=$this->ice_tp;
    $_SESSION['ice_number']=$this->ice_number;
    $_SESSION['ice_cursor']=$this->ice_cursor;
    $_SESSION['discount_card']=$this->discount_card;
  }
  function global_to_sets(){
    if (isset($_SESSION['ice_sets'])){
        $this->ice_sets = $_SESSION['ice_sets'];
        $this->ice_trsc = $_SESSION['ice_trsc'];
        $this->ice_tp = $_SESSION['ice_tp'];
        $this->ice_cursor = $_SESSION['ice_cursor'];
        $this->ice_number = $_SESSION['ice_number'];
    }
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
    return array($cn,$cf);
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
    $this->sets_to_global();
  }
  function __construct() { 
    #$this->clear();
    $this->Client = new cIceClient('127.0.0.1',7171);
    $this->global_to_sets();
    $this->ice_default_count=1;
  } 
  function calc(){
    $this->ice_sum=0;
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

        if ($tr){
            $this->ice_sum=$this->ice_sum+round($price*$count,2);
        }
    }
    if (isset($_SESSION['discount_card'])&&(count($_SESSION['discount_card']))){
        $this->discount_card=$_SESSION['discount_card'];
        $this->ice_skid=$this->discount_card['procent'];
    }else{ $this->ice_skid=0; }
    $this->ice_sum_skid=round($this->ice_sum*$this->ice_skid/100,2);
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
  function close_check($nal,$bnal){
    if (!$_SESSION['ice_mode']){$this->ice_status='error: mode view check';return False;}
    $this->ice_nal=$nal;$this->ice_bnal=$bnal;
    $this->calc();
    if ($nal==0){$nal=$this->ice_sum;}
    $c=count($this->ice_trsc['type']);
    if ($c){
        #open_check #trsc #close_check #result
        $this->ice_status='error: frk';
        $this->openClient();
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
        if ($this->ice_skid){
            $this->Client->_trsc_discount($this->discount_card['number']);
        }
        $r=$this->Client->_close_check($nal,$bnal,$this->ice_skid);
        if ($r=='err'){
            sleep(1);
            $this->_continue();
            sleep(3);
            if ( $cIce->Client->connect() ){ return False; }
            $this->_continue();
            return False;
            #$r=$this->Client->_close_check($nal,$bnal,$this->ice_skid);
            #if ($r=='err'){return False; }
            #$r='R'.$r;
        }
        $this->Client->close();
        $this->ice_status='чек '.$r;
        $a=spliti(';',$r);
        $this->ice_number=$a[0];
        $_SESSION['ice_number']=$this->ice_number;
    }else{
        $this->ice_status='error: empty check';
        return False;
    }
    $this->clear();
    return True;
  }
  function calc_zsum(){
    $z=mysqlqueryOneLine("select idtrsc from trsc where type=61 order by nkassa, idtrsc desc limit 1");
    $id=$z['idtrsc'];
    if (!$id){$id=0;}
    $nal=mysqlqueryOneLine("select sum(ParamF3) as sum from trsc where idtrsc>$id and type=40 and ParamF2=1");
    $bnal=mysqlqueryOneLine("select sum(ParamF3) as sum from trsc where idtrsc>$id and type=40 and ParamF2=2");
    return array($nal['sum'],$bnal['sum']);
  }
  function read_tovar($id){
    return mysqlqueryOneLine("select * from price where id='$id'");
  }
  function like_tovar($tovar){
    return mysqlquery("select id,name,cena from price where lower(name) like('%$tovar%')");
  }
  function find_discount($shk){
    $this->discount_card = mysqlqueryOneLine("select * from discount_card where number='$shk'");
    if ((isset($this->discount_card['number']))&&($this->discount_card['number'])){
        $_SESSION['discount_card']=$this->discount_card;
        $this->ice_status='discount '.$this->discount_card['procent']."%";
    }
    else{$this->ice_status='error: not find code';}
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
            $this->ice_status='view_check';
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
                $this->ice_status='error: not find codetov';
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
                    if ($k=='cena'){
                        #Если в товаре из доп базы цена не нулевая то ее не меняем
                        if (!round($a1[$i][$k],2)){
                            continue;
                        }
                    }
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
    $this->ice_status='type_check';
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
        sleep(2);
        $this->Client->connect();
        $this->Client->close();
        return 1;
    }else{return 0;}
  }
  function frk_continue(){
    if ($this->openClient()==0){
        $this->Client->_continue();
        $this->Client->close();
        $this->ice_status='frk_continue';
        return 1;
    }else{return 0;}
  }
  function frk_cancel(){
    if ($this->openClient()==0){
        $this->Client->_cancel_check();
        $this->Client->close();
        $this->ice_status='cancel_check';
        return 1;
    }else{return 0;}
  }
  function repeat(){
    if ($this->openClient()==0){
        $this->Client->_repeat_check();
        $this->Client->close();
        $this->ice_status='repeat_check';
        return 1;
    }else{return 0;}
  }
  function openbox(){
    if ($this->openClient()==0){
        $this->Client->_open_box();
        $this->Client->close();
        $this->ice_status='openbox';
        return 1;
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
    $this->ice_status='add_sale';
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
                $this->ice_status='error: price_type';
                return 1;
            }
            $this->ice_status='change_price';
        }    
        if ($k=='count'){
           if ($v<=0){$this->ice_status='error: count<=0';return 1;}
           if ( ($this->ice_cur_tovar['real']==0) && ($v - floor($v) >0) )
            {
                $this->ice_status='error: real count ';
                return 1;
            }
            $this->ice_status='change_count';
        }    
        $this->ice_trsc[$k][$c]=$v+0;
        $_SESSION['ice_trsc']=$this->ice_trsc;
    }
  }
  function trsc_remove(){
    $c=$this->ice_cursor;
    #count($this->ice_trsc['type'])-1;
    if ($c>=0){
        array_splice($this->ice_trsc['type'],$c,1);
        array_splice($this->ice_trsc['id'],$c,1);
        array_splice($this->ice_trsc['price'],$c,1);
        array_splice($this->ice_trsc['count'],$c,1);
        array_splice($this->ice_trsc['name'],$c,1);
        $_SESSION['ice_trsc']=$this->ice_trsc;
        $this->ice_status='storno';

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
        #mysqlquery("set names utf8 collate utf8_general_ci");
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
    'start'=>array('/start.php','Старт')
    );
    if (!$p){ $p=array('index','sets','start'); }
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
