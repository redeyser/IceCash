#!/usr/bin/python
import serial
import sys
import os

if not len(sys.argv):
    sys.halt(1)

p=sys.argv[1]
ser = serial.Serial(
port=p,
baudrate=9600,
parity=serial.PARITY_NONE,
stopbits=serial.STOPBITS_ONE,
)

ser.open()
x=''
while 1:
    a=ser.read(1)
    if ord(a)==13:
        os.system("xvkbd -display :0.0 -xsendevent -text \"%s\\r\"" % x)
        x=''
    else:
        x=x+a

ser.close()
