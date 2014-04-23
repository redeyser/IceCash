#!/usr/bin/perl
while (<STDIN>){
  chomp($_); 
  $cmd = 'xvkbd -xsendevent -text "'.$_.'\r"';
  print $cmd;
  `$cmd`;
}
