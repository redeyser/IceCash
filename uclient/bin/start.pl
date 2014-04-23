#!/usr/bin/perl
# uclient 2.0.5 for IceCash 1.0.1
use Socket;

my $pathcIce = '/var/www/IceCash/bin/cIceCash.py';
my $pathwork = '/var/www/IceCash/bin/uclient';
my $pathset  = $pathwork.'/sets/sets.ini';
my $pathsetR = $pathwork.'/sets/setsR.ini';
my $pathex="/var/www/IceCash/bin/download";
my $path_beerpoint="/home/beerpoint";

chdir("$pathwork/bin");
sub skopen(){
our $skopen=0;
  $RemoteHost = $ipserver;
  $RemotePort = $skport;
  print "skserver:$ipserver:$skport\n";
  socket(Server,PF_INET,SOCK_STREAM,getprotobyname("tcp"));
  if (! ($internet_addr = inet_aton($RemoteHost))) {print "not found skserver\n";return 0;}
  $paddr = sockaddr_in($RemotePort,$internet_addr);
  if (! (connect ( Server, $paddr))) { print "error connect socket\n";return 0;}
   select ((select(Server),$|=1)[0]); 
  $skopen=1;
  return 1;
}

sub sksend($){
my $mess=shift;
  if ($skopen){
    print Server $mess;
    $answer = <Server>;
  }
return $answer;
}

sub skclose(){
  if ($skopen){
  print Server "message=exit\n";
  $answer = <Server>;
  print $answer;
  close (Server);
  shutdown (Server,1);
  }
}

sub ftp_do($$$){
  $do=shift;
  $f1=shift;
  $f2=shift;
  $cmd="./client_ft.pl $do $f1 $f2";print `$cmd`;
}

sub getpos(){
 our $LastZ;
 chomp($LastZ);
 $a="uclient/temp/pos${IDK}.rep";
 `rm $a 2>/dev/null`;
 $cmd="echo \'$IDK\tself\tunload_trsc\t$a\t$LastZ\' | $pathcIce";
 `$cmd`;
 if (-e "../../$a"){print "ok\n";return 1;}else{print "!\n";return 0;}
}

sub readtxt($){
my $f=shift;
open (FT,"<",$f);
$x=<FT>;
close(FT);
return $x;
}

sub readsets(){
  our $ID,$IDK,$incP,$price,$version,$ipscale,$ftpport,$skport,$ipserver,$user,$pswd;
  open(FS,"<",$pathset);
  while (<FS>) {
	if (/^ID=(.*?)$/){$ID=$1;}
	if (/^IDK=(.*?)$/){$IDK=$1;}
	if (/^price=(.*?)$/){$price=$1;}
	if (/^ipscale=(.*?)$/){$ipscale=$1;}
	if (/^ipserver=(.*?)$/){$ipserver=$1;}
	if (/^skport=(.*?)$/){$skport=$1;}
  }
 close(FS);
  open(FS,"<",$pathsetR);
  while (<FS>) {
	if (/^incP=(.*?)$/){$incP=$1;}
	if (/^version=(.*?)$/){$version=$1;}
  }
 close(FS);
}

sub writesets(){
  open(FS,">",$pathsetR);
	print FS "incP=$incP\n";
	print FS "version=$version\n";
  close(FS);
}

print "beerpoint client version 2.0 (for IceCash)\n";
print "read sets\n";
readsets();

print "$ID $IDK $version $price:$incP\n";
print "get price\n";
ftp_do('get',"$path_beerpoint/price/".$price.'.txt',$price.'.txt');

$incP2 = readtxt($price.'.txt');
if ($incP2 != $incP) {
	print "get new price number $incP2\n";
	ftp_do('get',"$path_beerpoint/price/".$price.'.zip',$price.'.zip');
	
	$cmd="rm ../temp/*"; `$cmd`;
	$cmd="unzip -L $price\.zip -d ../temp/";
	`$cmd`;
	if ( -e "../temp/pos1.spr" ){
	  print "price downloaded \n";
	  $incP=$incP2;chomp($incP);
	  #print `iconv -f cp1251 ../temp/pos1.spr >../../dancy/pos1.spr`;
	  #$cmd="cp ../temp/pos1.spr $pathex/pos${IDK}.spr";`$cmd`;
	  #sleep(30);
      $cmd="echo \'1\tself\tload_price\tuclient/temp/pos1.spr\' | $pathcIce";
	  print "load price: ".`$cmd`;
      $cmd="echo \'1\tself\tload_discount\tuclient/temp/card.txt\' | $pathcIce";
	  print "load discount card: ".`$cmd`;
  	  writesets();
	}
}

$cmd='rm '.$price.'.*';`$cmd`;

print "GET LASTZ\n";
$lastf="LastZ_${ID}_$IDK";
print "$lastf\n";
ftp_do('get',"$path_beerpoint/pos/".$lastf,$lastf);
print "recieved\n";
  if ( -e $lastf ){
	$LastZ = readtxt($lastf);
	`rm $lastf`;
	print "getpos\n";
	if(!getpos()){print "upload error\n"}else{
	  $zipf="${ID}_${IDK}.zip";
 	  $repf="../temp/pos${IDK}.rep";
	  $repf2="${ID}_${IDK}.rep";
	  `rm $repf2 2>/dev/null`;
	  `mv $repf $repf2`;
	  `rm $zipf 2>/dev/null`;
	  `zip $zipf $repf2`;
	  ftp_do('put',$zipf,"$path_beerpoint/pos/".$zipf);	   
	  `rm $repf2`;
	  `rm $zipf`;
	  print "connect sk:";
	  skopen();
	  print "ok\n";
	  if ($skopen){
	    print "pos sended:";
	    $answer = sksend("ID=${ID};IDK=${IDK};message=pos_sended\n");
	    print $answer;
	    skclose();
	  }
	}
  }

print "load actions\n";
ftp_do('get',"$path_beerpoint/price/actions.txt",'actions.txt');

$cmd="echo \'1\tself\tload_actions\tuclient/bin/actions.txt\' | $pathcIce";
print "load actions: ".`$cmd`;

print "checking current version\n";
ftp_do('get',"$path_beerpoint/upgrade/IceCash.txt",'IceCash.txt');
$V2 = readtxt('IceCash.txt');
chomp($V2);
if ($V2 ne $version) {
	print "get new version $V2\n";
	ftp_do('get',"$path_beerpoint/upgrade/IceCash.zip",'IceCash.zip');
    print "upgrade...\n";
    `mkdir -p ../temp/upgrade && unzip -o IceCash.zip -d ../temp/upgrade`;
    chdir("$pathwork/temp/upgrade");
    `./install.sh`;
    chdir("$pathwork/bin");
    `rm -R ../temp/upgrade`;
    `rm IceCash.txt`;
    `rm IceCash.zip`;
    $version=$V2;
    writesets();
}

