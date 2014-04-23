<?php
#  include_once("mysql");
  // MYSQL STANDART
  $CN_str_point='.';
  $CN_NO_ROLLUP=0;
  $CN_color_minus='#BB2222';
  function mysqlexec($q) {
	$result=array();
  	$res = mysql_query($q) or die(mysql_error());
  	return $res;
  };
  function mysqlquery($q) {
    //echo "$q";
	$result=array();
  	$res = mysql_query($q) or die(mysql_error());
	    while ($row=mysql_fetch_array($res)) { $result[]=$row; }
	    mysql_free_result($res);
  	return $result;
  };
  function mysqlqueryIDarray($q) {
	$result=array();
  	$res = mysql_query($q) or die(mysql_error());
	while ($row=mysql_fetch_array($res)) { $result[$row[0]]=$row[1]; }
	mysql_free_result($res);
  	return $result;
  };
  function mysqlqueryOneLine($q) {
	$result=array();
  	$res = mysql_query($q) or die(mysql_error());
	$result=mysql_fetch_array($res);
	mysql_free_result($res);
//	echo "res=${result['name_f']}";
  	return $result;
  };
  function mysqlresult($q,$delta) {
  	$res = mysql_query($q) or die(mysql_error());
	if (mysql_num_rows($res)==0){return '';}
	if ($res){$result=mysql_result($res,$delta);}else{$result='';}
	mysql_free_result($res);
  	return $result;
  };
#  function verifbit($valbit,$n) {
#        $val = bindec($valbit);
#        $bit='0'x16;
#        
#  }
  
  

  // DATE MODIFY
  function datemodify($t,$m,$mode) {
	$date_time_array = getdate($t);
	$hours = $date_time_array['hours'];
	$minutes = $date_time_array['minutes'];
	$seconds = $date_time_array['seconds'];
	$month = $date_time_array['mon'];
	$day = $date_time_array['mday'];
	$year = $date_time_array['year'];
 	switch ($mode) {
           case 'addmonth':{
             $month+=$m;
    	     $timestamp = mktime($hours,$minutes,$seconds,$month,$day,$year);
    	     return $timestamp;
           break;}      
           case 'endofmonth':{
             $month+=1;
    	     $timestamp = mktime($hours,$minutes,$seconds,$month,1,$year);
	     $timestamp = $timestamp - (24*60*60);
    	     return $timestamp;
           break;}      
	   case 'setday':{
             $day=$m;
    	     $timestamp = mktime($hours,$minutes,$seconds,$month,$day,$year);
    	     return $timestamp;
           break;}      
	   case 'mysql_format':{
	     $format=$year."-".date('m',$t)."-".date('d',$t);
	     return $format;
           break;}      
	   case 'mysql_format_YM':{
	     $format=$year."-".date('m',$t);
	     return $format;
           break;}      
	}
	return 0;
  }
  ### MAKE ARRAY FROM TWO STRING-SEPARATE
  function arr_make($s,$k,$v){
    $r=array();
    $keys = spliti($s,$k);
    $values = spliti($s,$v);
    for ($i=0;$i<count($keys);$i++){
	$r[$keys[$i]] = $values[$i];
    }
    return $r;
  }
  ### IS CIFRA
  function isnumber($n){
   return preg_match("/^[+-]?\d+\.?\d*?$/",$n);
   }
  ### HTLM CODE PASTE
  function html_th($fd){
    foreach ($fd as $key => $val){ echo "<th>$val</th>"; }
  }

  function html_table($title,$fd,$vars){
    global $CN_str_point,$CN_color_minus;
    $col=count($fd);
    echo "<tr> <th colspan=$col> $title </th> </tr>";
    html_th($fd);
    $rollup=array_fill(0, $col-1, '');

    foreach ($vars as $key => $val) {
  	echo "<tr>";
	for ($i=0;$i<$col;$i++)
    	    { 
	      if(preg_match("/^[+-]?\d+\.?\d*?$/",$val[$i])) { 
		$str= str_replace('.',$CN_str_point,$val[$i]); 
		if ($str<0) {$color="<font color=$CN_color_minus>";$end_color="</font>";} 
		else {$color="";$end_color="";}
		$rollup[$i]+=$val[$i];
	      }
	      else {
		$str=$val[$i];
		$color="";$end_color="";
	      }
	      echo "<td>$color".$str.$end_color."</td>"; 
	    }
    }
    echo "<tr>";
    for ($i=0;$i<$col;$i++){
  	 echo "<th>${rollup[$i]}</th>"; 
    }
    echo "</tr>";
  }

  function html_table_field($title,$fd,$vars,$rollups=''){
    global $CN_str_point,$CN_color_minus,$CN_NO_ROLLUP;
    $col=count($fd);
    echo "<tr> <th colspan=$col> $title </th> </tr>";
    html_th($fd);
    $rollup=array_fill(0, $col-1, '');
    $rows=0;
    foreach ($vars as $key => $val) {
  	echo "<tr>";$i=0;
	foreach ($fd as $k => $v)
    	  { 
	    if (array_key_exists($k,$val)) {
	      if(preg_match("/^[+-]?\d+\.?\d*?$/",$val[$k])) { 
		$str= str_replace('.',$CN_str_point,$val[$k]); 
		if ($str<0) {$color="<font color=$CN_color_minus>";$end_color="</font>";} 
		else {$color="";$end_color="";}
		$rollup[$i]+=$val[$k];
	      }
	      else {
		$str=$val[$k];
		$color="";$end_color="";
	      }
	    }
	    else {$str="";$color="";$end_color="";}
	      echo "<td>$color".$str.$end_color."</td>"; 
	   ++$i;
	  }
	++$rows;
    }
    
    if (!$CN_NO_ROLLUP){
    echo "<tr>";
    for ($i=0;$i<$col;$i++){
        if ($rollups!='')
        {
         if ($rollups[$i]==0) { echo "<th></th>"; }
         if ($rollups[$i]==1) { echo "<th>${rollup[$i]}</th>";  }
         if ($rollups[$i]==2) { $rollup[$i]=floor(($rollup[$i]/$rows)*100)/100; echo "<th>${rollup[$i]}</th>";  }
        }
        else
        {
  	 echo "<th>${rollup[$i]}</th>"; 
  	}
    }
    echo "</tr>";}
    return $rollup;
  }

  function html_table_field_a($title,$fd,$vars,$a_href,$a_ID,$a_target,$a_text,$a_custom){
    global $CN_str_point,$CN_color_minus,$CN_NO_ROLLUP;
    $col=count($fd);
    if ($a_ID){++$col;}
    echo "<tr> <th colspan=$col> $title </th> </tr>";
    html_th($fd);echo "<th></th>";
    $rollup=array_fill(0, $col-1, '');

    foreach ($vars as $key => $val) {
  	echo "<tr>";$i=0;
	foreach ($fd as $k => $v)
    	  { 
	    if (array_key_exists($k,$val)) {
	      if(preg_match("/^[+-]?\d+\.?\d*?$/",$val[$k])) { 
		$str= str_replace('.',$CN_str_point,$val[$k]); 
		if ($str<0) {$color="<font color=$CN_color_minus>";$end_color="</font>";} 
		else {$color="";$end_color="";}
		$rollup[$i]+=$val[$k];
	      }
	      else {
		$str=$val[$k];
		$color="";$end_color="";
	      }
	    }
	    else {$str="";$color="";$end_color="";}
	      echo "<td>$color".$str.$end_color."</td>"; 
	   ++$i;
	  }
	if ($a_ID) {
           if (!is_array($a_ID)){
		echo "<td><a $a_target href=$a_href?ID=${val[$a_ID]}$a_custom>$a_text</a></td>";
	   }else{
		$gets='';$i=1;
		foreach($a_ID as $idv){
		  $gets.="$idv=${val[$idv]}";
		  if ($i<count($a_ID)){$gets.="&";}
		  $i++;
		}
		echo "<td><a $a_target href=$a_href?$gets$a_custom>$a_text</a></td>";
	   }
	}
    }
    if (!$CN_NO_ROLLUP){
    echo "<tr>";
    for ($i=0;$i<$col;$i++){
  	 echo "<th>${rollup[$i]}</th>"; 
    }
    echo "</tr>";}
  }

  function html_select($title,$vars,$id_curr){
	echo "<select name='$title' size=1>";
	foreach ($vars as $key => $val){
	  if (!is_array($val)){ $id=$val;$nm=$val;}
	  else{$id = $val[0]; $nm = $val[1];}
  	  if ($id==$id_curr){$selected='selected';}else{$selected='';}
	  echo "<Option $selected value=$id>$nm</Option>";
	}
	echo "</select>";
  }
  function html_selectID($title,$vars,$id_curr){
	echo "<select name='$title' size=1>";
	foreach ($vars as $key => $val){
	  if (!is_array($val)){ $id=$key;$nm=$val;}
  	  if ($id==$id_curr){$selected='selected';}else{$selected='';}
	  echo "<Option $selected value=$id>$nm</Option>";
	}
	echo "</select>";
  }

  function html_edit($title,$key,$size,$val,$custom){
    echo "$title <br/>";
    echo "<input $custom name=$key size=$size type=\"text\" value=\"$val\"/> <br/>";
  }

  function html_edit_m($fields,$sizes,$data,$custom){
    $i=0;
    foreach ( $fields as $key=>$title ){
     html_edit($title,$key,$sizes[$i],$data[$key],$custom);
     $i++;
    }
  }

  function create_query_update_text($table,$data,$fd,$where){
  global $_POST;
	$r="update $table set ";
        $x='';
	foreach ($_POST as $key => $val){
	  if ((array_key_exists($key,$data))&&(in_array($key,$fd))&&($data[$key]!=$_POST[$key]))
	  { if (isnumber($val)){$r.=$x."$key=$val";}
	  else
	  { $r.=$x."$key=\"$val\""; }
	   $x=",";
          }
	}
     if (!$x) {return $x;}
    $r .= " where $where";
    return $r;
  }

  function create_query_insert_text($table,$data,$fd){
  global $_POST;
        $fields=implode(',',$fd);
        $x='';$r=array();$t=0;
	foreach ($fd as $field){
          if (array_key_exists($field,$_POST)) {$t=1;}
          $v=array_key_exists($field,$_POST)?$_POST[$field]:$data[$field];
          #echo "$field:${_POST[$field]}:${data[$field]}\n<br/>";
	  if (!isnumber($v)){$v="\"$v\"";}
	  $r[]=$v;
	}
     if (!$t) {return $t;}
     $values=implode(',',$r);
     $r="insert into $table ($fields) values($values)";
    return $r;
  }
  

#--------------------------------------------------------------------------------
function readpost_multidata ($post_fd){
global $_POST,$POST_MULTIDATA;
 $max=0;
 foreach( $_POST as $key=>$val){
   $m=strpos($key,"_");
   if ($m){$m=substr($key,$m+1);if ($m>$max){$max=$m;} };
 }
 $POST_MULTIDATA = array();
 foreach( $post_fd as $fd){
  for ($i=1;$i<=$max;$i++){
    $t=$fd."_".$i;
    if (array_key_exists($t,$_POST)) {
      $POST_MULTIDATA[$i][$fd]=$_POST[$t];
    }
  }
 }
 return $max;
}

?>
