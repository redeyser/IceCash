#!/usr/bin/perl
use Socket;

our $R_SIZE=4096;

sub readsets($){
  $pathset=shift;
  our $pswd,$ipserver,$skport;
  open(FS,"<",$pathset);
  while (<FS>) {
	if (/^ipserver=(.*?)$/){$ipserver=$1;}
	if (/^skport=(.*?)$/){$skport=$1;}
	if (/^pswd=(.*?)$/){$pswd=$1;}
  }
 close(FS);
}

sub skopen(){
our $skopen=0;
  $RemoteHost = $ipserver;
  $RemotePort = $skport;
  socket(Server,PF_INET,SOCK_STREAM,getprotobyname("tcp"));
  if (! ($internet_addr = inet_aton($RemoteHost))) {print "not found skserver\n";return 0;}
  $paddr = sockaddr_in($RemotePort,$internet_addr);
  if (! (connect ( Server, $paddr))) { print "error connect socket\n";return 0;}
   select ((select(Server),$|=1)[0]); 
  $skopen=1;
  return 1;
}

sub get($$){
my $file=shift;
my $to=shift;
our $R_SIZE;
$head="cmd=get;file=$file\n";
  if ($skopen){
    if (!open(RFILE,">",$to)){print "cant create this file [".$to."]\n";return 0;exit;}
    print Server $head;
    binmode(RFILE);
    binmode(Server);
    while( ($size=read(Server,$data,$R_SIZE))!=0 ) {
	print RFILE $data;
	print STDOUT ".";
    }
    print STDOUT "get.ok.\n";
    close(RFILE);
  }
  return 1;
}

sub put($$){
my $file=shift;
my $to=shift;
our $R_SIZE;
$head="cmd=put;file=$to\n";
  if ($skopen){
    if (!open(RFILE,"<",$file)){print "cant open this file [".$file."]\n";return 0;exit;}
    print Server $head;
    binmode(RFILE);
    binmode(Server);
    while( ($size=read(RFILE,$data,$R_SIZE))!=0 ) {
	print Server $data;
	print STDOUT ".";
    }
    print STDOUT "put.ok.\n";
    close(RFILE);
  }
  return 1;
}


sub skclose(){
  if ($skopen){
  close (Server);
  shutdown (Server,1);
  }
}

 readsets('.client_ft');
 skopen();
 print "open socket $ipserver:$skport\n";
 if ($ARGV[0] eq 'get') {
    get($ARGV[1],$ARGV[2]);
 }
 if ($ARGV[0] eq 'put') {
    put($ARGV[1],$ARGV[2]);
 }
 print "close socket\n";
 skclose();
 
