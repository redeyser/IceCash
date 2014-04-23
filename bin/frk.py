#!/usr/bin/python
# -*- coding: utf8 -*-
# frk object v 1.2.007
import kkmdrv
import serial
import time

const_error=1
const_cmd={'sale':11,'return':13,'X':60,'Z':61,'close_check':55,'cancel_check':56,}


class frk:

    def __init__(self,password,admin_password,port,speed):
    
        self.password = password
        self.admin_password = admin_password
        self.port = port
        self.speed = speed

        self.a_startus=['ready','check','error']

    def connect(self):
        try:
            self.ser = serial.Serial(self.port, int(self.speed),\
                                 parity=serial.PARITY_NONE,\
                                 stopbits=serial.STOPBITS_ONE,\
                                 timeout=0.7,\
                                 writeTimeout=0.7)
        except:
            return 1

        try:
            self.kkm=kkmdrv.KKM(self.ser,password=self.password)
            err=0
            self.kkm.Beep()
            self._status_ready()
            print "connect frk"
        except:
            err=const_error
            self._status_error()
            self.ser.close()
            print "not connect frk"

        return err

    def disconnect(self):
        try:
            self.ser.close()
            return 0
        except:
            return 1


    def parsingCmd(self,cmd,param):
        if cmd=='connect':
            (self.port,self.speed)=param[0:2]
            self.disconnect()
            err=self.connect()
            return err
        if cmd=='disconnect':
            return self.disconnect()
        if cmd=='status':
            return self.status
        if cmd=='short':
            err=self.shortStatusRequest()
            if not err:
                return self.status_short
            else:
                return err
        if cmd=='long':
            err=self.statusRequest()
            if not err:
                return self.status_long
            else:
                return err
        if cmd=='open_check':
            return self.openCheck(int(param[0]))
        if cmd=='close_check':
            return self.closeCheck(float(param[0]),float(param[1]),float(param[2]))
        if cmd=='cancel_check':
            return self.cancelCheck()
        if cmd=='repeat_check':
            return self.repeatDoc()
        if cmd=='roll':
            return self.roll()
        if cmd=='open_box':
            return self.openbox()
        if cmd=='continue':
            return self.continuePrint()
        if cmd=='sale':
            #text=param[2].decode('utf8')
            text=param[2]
            return self.Sale(float(param[0]),float(param[1]),text)
        if cmd=='return':
            #text=param[2].decode('utf8')
            text=param[2]
            return self.returnSale(float(param[0]),float(param[1]),text)
        if cmd=='X':
            r=int(self.reportWoClose())
            return int(not r)
        if cmd=='Z':
            r=int(self.reportWClose())
            return int(not r)
        if cmd=='cut':
            return self.cutCheck()
        if cmd=='renull':
            return self.renull()
        if cmd=='print':
            text=param[0].decode('utf8')
            #text=param[0]
            return self.printString(text)
        if cmd=='set_date':
            self.setDate()
            return self.acceptSetDate()
        if cmd=='set_time':
            return self.setTime()
        if cmd=='writesets':
            return self.writesets(param[0],param[1],param[2])
        if cmd=='writesets_text':
            return self.writesets_text(param[0],param[1],param[2])


    def _status_ready(self):
        self.status='ready'

    def _status_check(self,t):
        self.status='check'
        self.check_type=t

    def _status_error(self):
        self.status='error'

    def shortStatusRequest(self):
        try:
            self.status_short=self.kkm.shortStatusRequest()
        except:
            err=const_error
            self._status_error()
        return err

    def statusRequest(self):
        try:
            self.status_long=self.kkm.statusRequest()
            err=0
        except:
            err=const_error
            self._status_error()
        return err

    def openCheck(self,t):
        try:
            err=self.kkm.openCheck(t)
            if not err:
                self._status_check(t)
        except:
            err=const_error
            self._status_error()
        return err

    def Sale(self,count,price,text):
        try:
            err=self.kkm.Sale(count,price,text=text)
            if not err:
                self._status_check(0)
        except:
            err=const_error
            self._status_error()
        return err

    def returnSale(self,count,price,text):
        try:
            err=self.kkm.returnSale(count,price,text=text)
            if not err:
                self._status_check(1)
        except:
            err=const_error
            self._status_error()
        return err

    def closeCheck(self,summ,summ2,skid):
        try:
            err=self.kkm.closeCheck(summ,summa2=summ2,sale=skid,text=u"---------------------------------------")
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def cancelCheck(self):
        try:
            err=self.kkm.cancelCheck()
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def reportWoClose(self):
        try:
            err=self.kkm.reportWoClose(self.admin_password)
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def reportWClose(self):
        try:
            err=self.kkm.reportWClose(self.admin_password)
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def cutCheck(self):
        try:
            err=self.kkm.cutCheck(0)
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def continuePrint(self):
        try:
            err=self.kkm.continuePrint()
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def repeatDoc(self):
        try:
            err=self.kkm.repeatDoc()
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def setDate(self):
        t=time.gmtime()
        try:
            err=self.kkm.setDate(t.tm_mday,t.tm_mon,t.tm_year)
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def acceptSetDate(self):
        t=time.localtime()
        try:
            err=self.kkm.acceptSetDate(self.admin_password,t.tm_mday,t.tm_mon,t.tm_year)
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def setTime(self):
        t=time.localtime()
        try:
            err=self.kkm.setTime(self.admin_password,t.tm_hour,t.tm_min,t.tm_sec)
            if not err:
                self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def printString(self,t=u""):
        try:
            self.kkm.printString(check_ribbon=True,control_ribbon=False,text=t)
            self._status_ready()
            err=0
        except:
            err=const_error
            self._status_error()
        return err

    def printShk(self,t=u""):
    #не работает
        try:
            self.kkm.printShk(shk=t)
            self._status_ready()
            err=0
        except:
            err=const_error
            self._status_error()
        return err

    def openbox(self):
        try:
            err=self.kkm.openbox()
            self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def roll(self):
        try:
            err=self.kkm.roll()
            self._status_ready()
        except:
            err=const_error
            self._status_error()
        return err

    def renull(self):
        try:
            self.kkm.renull()
            self._status_ready()
            err=0
        except:
            err=const_error
            self._status_error()
        return err

    def writesets_text(self,text12,text13,text14):
        try:
            self.kkm.setTableValue(self.admin_password,4,12,1,text12.decode('utf8').encode('cp1251').center(30))
            self.kkm.setTableValue(self.admin_password,4,13,1,text13.decode('utf8').encode('cp1251').center(30))
            self.kkm.setTableValue(self.admin_password,4,14,1,text14.decode('utf8').encode('cp1251').center(30))
            err=0
        except:
            err=const_error
            self._status_error()
        return err

    def writesets(self,autonull,openbox,autocut):
        values = [ chr(0x0)+chr(0x0), chr(0x1)+chr(0x0) ] 
        try:
            self.kkm.setTableValue(self.admin_password,1,1,2,values[int(autonull)])
            self.kkm.setTableValue(self.admin_password,1,1,6,values[int(openbox)])
            self.kkm.setTableValue(self.admin_password,1,1,7,values[int(autocut)])
            self._status_ready()
            err=0
        except:
            err=const_error
            self._status_error()
        return err

#D=frk(kkmdrv.DEFAULT_PASSWORD,kkmdrv.DEFAULT_ADM_PASSWORD,'/dev/ttyS0',115200)
#D.connect()
#err=D.printString(u'ZAKRITO')
#print err
#D.cancelCheck()
#D.writesets(1,1,1)
#D.writesets_text('         ООО "Фирма Илва"         ','         ПИВТОЧКА         ','     г.Кемерово Октябрьский 68')
#err=D.setTime()
#print err
#D.openCheck(0)
#D.Sale(2,20,'PIVO ZVERSKOE')
#D.Sale(1,10,'ZAKUSON')
#D.closeCheck(100,10,20)
#D.parsingCmd('open_check',[0])
#D.parsingCmd('sale',[float(1),float(20.5),"PIVASIK"])
#D.parsingCmd('close_check',[float(100),float(10),float(20)])
#D.disconnect()
