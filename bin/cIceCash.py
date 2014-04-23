#!/usr/bin/python
# -*- coding: utf8 -*-
# IceCash client v 1.3.003
import string
import skserv
import sys
import time
import os

const_error=1
const_error_connect=2
const_error_wait=3

class IceClient(skserv.SockClient):

    def run(self):
        accept=self.mrecv()
        if accept==skserv.msg_err:
            return 0
        lines = sys.stdin.readlines()
        error=0
        for line in lines:
            self.msend(line);
            error=self.mrecv()
            print error
            if str(error)==skserv.msg_err:
                break
        return 1

def connect(addr):
    global ic
    try:
        ic=IceClient(addr,7171)
    except:
        print const_error_connect 
        sys.exit(const_error_connect)

run=0
times=0
if os.sys.argv.__len__()<2:
    addr='localhost'
else:
    addr=os.sys.argv[1]
print "connect "+addr
while not run:
    if times==3:
        break
    connect(addr)
    run=ic.run()
    if run or times==3:
        break
    times=times+1
    time.sleep(3)

if not run:
    print const_error_wait 
    sys.exit(const_error_wait)


