#!/usr/bin/python
# -*- coding: utf-8
# IceCash v 1.0.1
import string
import kkmdrv
import frk
import skserv
import dbIce
import sys
import my
import os

DEFAULT_IP    = '127.0.0.1'
DEFAULT_PORT  = 7171
DEFAULT_DEV   = '/dev/ttyS0'
DEFAULT_SPEED = 115200

const_obj_self='self'
const_obj_frk='frk'
const_obj_scan='scanner'
const_obj_db='db'
const_obj_frkr='frkr'

class fp:
    def __init__(self):
        self.trsc=[]
    def summa(self,skid):
        summ=0
        for v in self.trsc:
            summ=summ+v
        if skid:
            summ=summ-round(summ*(skid/100),2)
        return summ
    def add(self,summ):
        self.trsc.append(summ)
    def cancel(self):
        self.trsc=[]
    def discount(self,proc):
        summ=self.summa(0)
        summ=round(summ*(proc/100),2)
        return summ
    def close(self,skid):
        summ=self.summa(skid)
        self.cancel()
        return summ
    def count(self):
        return len(self.trsc)

class dIceCash(skserv.SockSrv):

    def init_frk(self):
        self.frk=frk.frk(kkmdrv.DEFAULT_PASSWORD,kkmdrv.DEFAULT_ADM_PASSWORD,DEFAULT_DEV,DEFAULT_SPEED)
        self.frk_get_numdoc()

    def frk_get_numdoc(self): 
        if not self.frk.statusRequest():
            r=self.frk.status_long['doc_num']
        else:
            r=0
        self.last_number_check=int(r)
        return r

    def init_db(self):
        self.db = dbIce.db()
        self.fp=fp()
        self.frk_off=False

    def process(self,ct,arr):
        if len(arr)<3:
            ct.msend(skserv.msg_err)
            return 1 

        if not self.db.ping():
            if not self.db.open():
                ct.msend(skserv.msg_err)
                return 1

        (id_client,obj,cmd) = arr[0:3]
        param = arr[3:]
        print 'icecash:getmsg:id=%s;object=%s;cmd=%s' % (id_client,obj,cmd)
        lp=len(param)-3
        for i in range (len(param),lp):
            param[i]='0'
            
        #DEFAULTS
        result = skserv.msg_err
        ParamS=''
        ParamF1=0
        ParamF2=0
        ParamF3=0
        cdate=my.curdate2my()
        ctime=my.curtime2my()

        if obj==const_obj_scan:
            # Запросы к сканеру
            if cmd=='restart':
                os.system("killall serialtokbd.py")
                os.system("su kassir -c \"./serialtokbd.py %s &\"" % param[0])
                result='0'

        if obj==const_obj_self:
            # Собственные запросы 
            if cmd=='test':
                result='dIceCash_ready' 
            if cmd=='frk_off':
                self.frk_off=True
                result='frk_off'
            if cmd=='frk_on':
                self.frk_off=False
                result='frk_on'
            if cmd=='load_discount':
                if len(param):
                    if not (self.db.load_discount(param[0])):
                        result="0"
            if cmd=='load_price':
                if len(param):
                    if not (self.db.load_price(param[0])):
                        result="0"
            if cmd=='unload_trsc':
                if len(param)==2:
                    if not (self.db.unload_trsc(param[0],param[1],id_client)):
                        result="0"

                      
        if obj==const_obj_db:
            # Запросы к базе данных
            if cmd=='price_code':
                if len(param):
                    tovar=self.db.find_price_shk(param[0])
                    result=";".join(["%s" % str(v) for v in tovar])
            if cmd=='discount_code':
                if len(param):
                    card=self.db.find_discount_card(param[0])
                    if len(card):
                        result=";".join(["%s" % str(v) for v in card])
            if cmd=='trsc_discount':
                #Запись транзацкии скидки на чек по карте
                if len(param):
                    card=self.db.find_discount_card(param[0])
                    if len(card):
                        ParamF3=self.fp.discount(float(card[5]))
                        self.db.add_trsc((id_client,cdate,ctime,37,1,int(self.last_number_check)+1,1,card[0],0,0,card[5],ParamF3))
                        card.append(ParamF3)
                        result=";".join(["%s" % str(v) for v in card])

        if obj==const_obj_frkr:
            #Запросы Состояния ФРК
            if cmd=='report':
                result=str(self.frk_get_numdoc())
            if cmd=='status':
                result=str(self.frk.status)


        if obj==const_obj_frk:
            #Запросы ФРК
            if frk.const_cmd.has_key(cmd):
                #Формируем параметры транзакции
                istrsc=True
                if cmd=='sale' or cmd=='return':
                    #Поиск товара в базе данных
                    text=self.db.find_price_code(param[2])
                    if not len(text):
                      ct.msend(result)
                      return 0

                    if not param[1]:
                        #Если параметр цены пустой, то берем из базы
                        param[1]=text[3]
                    ParamS=param[2]
                    ParamF1=float(param[1])
                    ParamF2=float(param[0])
                    ParamF3=round(ParamF1*ParamF2,2)
                    param[2]=text[2]
            else:
                istrsc=False

            if cmd=='close_check':
                #Исключаем комбинированный вид оплаты
                if float(param[1])>0:
                    param[1]=str(self.fp.summa(float(param[2])))
                    print "param1=",param[1]

            #Автоматическая обработка команды ФРК драйвером
            #---------------------------------
            if not self.frk_off:
                r = self.frk.parsingCmd(cmd,param)
            else:
                r = 0
            #---------------------------------

            if r:
                if self.fp.count():
                    #Ошибка ФРК (пишем в транзакции отмену чека)
                    self.db.add_trsc((id_client,cdate,ctime,56,1,int(self.last_number_check)+1,1,'',0,0,0,0))
                self.frk.cancelCheck()
                r=skserv.msg_err
                self.fp.cancel()
            else:

                #Запросы ФРК
                if cmd=='connect':
                    self.frk_get_numdoc()

                #Запись транзакции
                if istrsc:
                    if cmd=='sale':
                        self.fp.add(ParamF3)                    
                    if cmd=='return':
                        ParamF3=-ParamF3
                        ParamF2=-ParamF2
                        self.fp.add(ParamF3)                    
                    if cmd=='close_check':
                        ParamF3=self.fp.close(float(param[2]))
                        #Запись транзацкии оплаты
                        if float(param[1])>0:
                            typepay=2
                        else:
                            typepay=1
                        self.db.add_trsc((id_client,cdate,ctime,40,1,int(self.last_number_check)+1,1,'',0,param[0],typepay,ParamF3))

                    t=(id_client,
                      cdate,
                      ctime,
                      frk.const_cmd[cmd],
                      1,
                      int(self.last_number_check)+1,
                      1,
                      ParamS,
                      0,
                      ParamF1,
                      ParamF2,
                      ParamF3,)

                    self.db.add_trsc(t)

                if cmd=='close_check':
                    #Узнаём номер чека
                    r=self.frk_get_numdoc()
                    r=str(r)+';'+str(ParamF3)
                    #self.frk.openbox()

            result=str(r)

        ct.msend(result)





# daemon IceCash start ------------------------- #
IceCash = dIceCash(DEFAULT_IP,DEFAULT_PORT,1)
IceCash.init_frk()
IceCash.init_db()

if not IceCash.db.open():
    print "icecash:error: connect database IceCash"
    sys.exit(1)

try:
    IceCash.start()
except:
    print "icecash:error: start socket server"
    sys.exit(2)

