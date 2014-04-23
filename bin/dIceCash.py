#!/usr/bin/python
# -*- coding: utf-8
# dIceCash v 1.3.014
import string
import kkmdrv
import frk
import skserv
import dbIce
import sys
import my
import os
from subprocess import Popen, PIPE

DEFAULT_IP    = ''
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

    def frk_autoconnect(self):
        return self.frk.parsingCmd('connect',[self.frk_sets['dev'],self.frk_sets['speed']])

    def frk_get_info(self,pvar): 
        if not self.frk.statusRequest():
            r=self.frk.status_long[pvar]
        else:
            r=0
        return r

    def frk_get_allinfo(self): 
        if not self.frk.statusRequest():
            r=""
            for k,v in self.frk.status_long.items():
                r=r+"["+str(k)+"] : ["+str(v)+"]\n"
        else:
            r=0
        return r

    def frk_get_numdoc(self): 
        if not self.frk.statusRequest():
            r=self.frk.status_long['doc_num']
            #f=open("/var/www/IceCash/status.txt",'w')
            #for k,v in self.frk.status_long.items():
            #    f.write("["+str(k)+"] : ["+str(v)+"]\n")
            #f.close()
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
            print "error: arguments<3"
            ct.msend(skserv.msg_err)
            return 1 

        if not self.db.ping():
            if not self.db.open():
                ct.msend(skserv.msg_err)
                return 1

        self.frk_sets=self.db.getsets()
        #print self.frk_sets;

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
            if cmd=='download':
                os.system("killall start.pl")
                os.system("uclient/bin/start.pl&")
                #out,err=Popen('uclient/bin/start.pl',shell=True,stdout=PIPE).communicate()
                result="0"
            if cmd=='test':
                result='dIceCash_ready' 
            if cmd=='frk_off':
                self.frk_off=True
                result='frk_off'
            if cmd=='frk_on':
                self.frk_off=False
                result='frk_on'
            if cmd=='load_actions':
                if len(param):
                    if not (self.db.load_actions(param[0])):
                        result="0"
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
                    if param[1]!='':
                        if not (self.db.unload_trsc(param[0],param[1],id_client)):
                            result="0"
                    else:
                        result="1"

                      
        if obj==const_obj_db:
            # Запросы к базе данных
            if cmd=='price_code_id':
                if len(param):
                    tovar=self.db.find_price_code(param[0])
                    result=skserv.separator.join(["%s" % v for v in tovar])
                    if not result:
                        result='none'
                else:
                    result="none"
            if cmd=='price_code':
                if len(param):
                    tovar=self.db.find_price_shk(param[0])
                    result=skserv.separator.join(["%s" % v for v in tovar])
                    if not result:
                        result='none'
                else:
                    result="none"
            if cmd=='price_codes':
                if len(param):
                    tovars=self.db.find_price_shks(param[0])
                    result=""
                    for tovar in tovars:
                        line=skserv.separator.join(["%s" % v for v in tovar])
                        if line:
                            result=result+"\n"+line
                    if not result:
                        result="none"
                else:
                    result="none"
            if cmd=='price_likes':
                if len(param):
                    tovars=self.db.find_price_likes(param[0])
                    result=""
                    for tovar in tovars:
                        line=skserv.separator.join(["%s" % v for v in tovar])
                        if line:
                            result=result+"\n"+line
                    if not result:
                        result="none"
                else:
                    result="none"

            if cmd=='discount_code':
                if len(param):
                    card  = self.db.find_discount_card(param[0])
                    if len(card):
                        result=skserv.separator.join(["%s" % v for v in card])
                    else:
                        result='none'
            if cmd=='trsc_bonuscard':
                #Запись транзацкии начисления бонусов по карте
                if len(param):
                    ParamF3 = param[1]
                    self.db.add_trsc((id_client,cdate,ctime,121,1,int(self.last_number_check),1,param[0],0,0,0,ParamF3))
                    result='0'
            if cmd=='trsc_discount':
                #Запись транзацкии скидки на чек по карте
                #DOOM 2014-02-13 Так как процент перерасчитывается на самом клиенте, то нужно принимать два параметра карта и скидка
                if len(param):
                    card=self.db.find_discount_card(param[0])
                    ParamF3 = param[1]
                    if len(card):
                        proc=card[5]
                    else:
                        proc=0
                    self.db.add_trsc((id_client,cdate,ctime,37,1,int(self.last_number_check),1,param[0],0,0,proc,ParamF3))
                    card.append(ParamF3)
                    result=skserv.separator.join(["%s" % str(v) for v in card])
            if cmd=='trsc_add':
                #Запись дополнительных транзакций #v1.0.11
                if len(param):
                    trsc_type=int(param[0])
                    ParamS=param[1]
                    try:
                        ParamF1=float(param[2])
                        ParamF2=float(param[3])
                        ParamF3=float(param[4])
                    except:
                        ParamF1=0
                        paramF2=0
                        ParamF3=0
                    self.db.add_trsc((id_client,cdate,ctime,trsc_type,1,int(self.last_number_check)+1,1,ParamS,0,ParamF1,ParamF2,ParamF3))
                    result="1"

        if obj==const_obj_frkr:
            #Запросы Состояния ФРК
            print "frkr "+cmd
            if cmd=='report':
                result=str(self.frk_get_numdoc())
            if cmd=='status':
                result=str(self.frk.status)
            if cmd=='allinfo':
                result=self.frk_get_allinfo()
            if cmd=='info':
                result=str(self.frk_get_info(param[0]))
            if cmd=='autoconnect':
                result=str(self.frk_autoconnect())


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
                    if self.frk_sets['typedev']=='shtrihl':
                        text[2]=text[2][:36]
                        #print "cutcheck"
                    ParamS=param[2]
                    #print "("+self.frk_sets['typedev']+")"
                    #print ParamS
                    try:
                        ParamF1=float(param[1])
                        ParamF2=float(param[0])
                        ParamF3=round(ParamF1*ParamF2,2)
                    except:
                        ParamF1=0
                        ParamF2=0
                        ParamF3=0
                    param[2]=text[2]
            else:
                istrsc=False

            if cmd=='close_check':
		        #Исключаем ошибку ввода
                try:
                    param[0]=str(float(param[0]))
                except:
                    param[0]='0'
                try:
                    param[2]=str(float(param[2]))
                except:
                    param[2]='0'
                #Исключаем комбинированный вид оплаты
                if float(param[1])>0:
                    #Сумма должна быть положительная при отправке на фискальник
                    sum_check=abs(self.fp.summa(float(param[2])))
                    param[1]=str(sum_check)

            #Автоматическая обработка команды ФРК драйвером
            #---------------------------------
            if not self.frk_off:
                r = self.frk.parsingCmd(cmd,param)
            else:
                r = 0
            #---------------------------------

            #Косяк с неправильными ответами от фискальника обходим если тип девайса ASPD
            if (r) and (self.frk_sets['typedev']=='ASPD'):
                r = 0
            if r:
                #print "RX="+str(self.fp.trsc.__len__())
                if self.fp.trsc.__len__():
                    #Ошибка ФРК (пишем в транзакции отмену чека)
                    self.db.add_trsc((id_client,cdate,ctime,56,1,int(self.last_number_check)+1,1,'',0,0,0,0))
                self.db.add_trsc((id_client,cdate,ctime,120,1,int(self.last_number_check)+1,1,'',0,0,0,0))
                self.frk.cancelCheck()
                r=skserv.msg_err
                self.fp.cancel()
            else:

                #Запросы ФРК
                if cmd=='connect':
                    self.frk_get_numdoc()
                    lasttype=self.db.last_trsc_type(id_client)
                    if lasttype in (11,12,13,14,37,71,111,112,113,114,115,116,117,118,119,121):
                        self.db.add_trsc((id_client,cdate,ctime,56,1,int(self.last_number_check)+1,1,'',0,0,0,0))
                        

                #Запись транзакции
                if istrsc:
                    if cmd=='sale':
                        self.fp.add(ParamF3)                    
                    if cmd=='return':
                        ParamF3=-ParamF3
                        ParamF2=-ParamF2
                        self.fp.add(ParamF3)                    
                    if cmd=='close_check':
                        try:
                            param[1]=str(float(param[1]))
                        except:
                            param[1]='0'
                        try:
                            param[2]=str(float(param[2]))
                        except:
                            param[2]='0'
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
                    r=str(r)+skserv.separator+str(ParamF3)
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
print "dIceCash 1.3.008 (2014-02-17)"
try:
    IceCash.start()
except:
    print "icecash:error: start socket server"
    sys.exit(2)

