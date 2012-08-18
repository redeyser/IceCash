#!/usr/bin/python
# -*- coding: utf8 -*-
"""
Shtrikh FR-K Python interface
=================================================================
Copyright (C) 2010  Dmitry Shamov demmsnt@gmail.com

    You can choose between two licenses when using this package:
    1) GNU GPLv2
    2) PSF license for Python 2.2

    Shtrikh KKM page: http://www.shtrih-m.ru/
        Project page: http://sourceforge.net/projects/pyshtrih/

CHANGELOG 
   0.02 : change kkm.Sale
               bprice = pack('l',int(price*100)+chr(0x0)
               replace to
               bprice = pack('l',int((price*10)*10))+chr(0x0)
               because 17719.85*100 = 1771984.9999999998 and int(17719.85*100) = 1771984
               but     (17719.85*10)*10 = 1771985.0 and int((17719.85*10)*10)  = 1771985

               Add DUMMY class
  0.03    replace all *100 into *10*10 
          add function float2100int
  0.04    >>> int((float('17451.46')*10)*10)
          1745145
          Now work with fload same as string
FOR ICE CASH v1.0.1
"""

VERSION = 0.04
PORT = '/dev/ttyS0' #'COM1'
#Commands
DEBUG = 1
TODO  = 1
OS_CP = 'cp866'

def dbg(*a):
        if DEBUG:
                print unicode(string.join(map(lambda x: str(x),a)),'utf8').encode(OS_CP)

def todo(*a):
        if TODO:
                print unicode(string.join(map(lambda x: str(x),a)),'utf8').encode(OS_CP)

import serial
import string,time
from struct import pack, unpack


def bufStr(*b):
    """Преобразует буфер 16-х значений в строку"""
    result = []
    for x in b: result.append(chr(x))
    return string.join(result,'')

def hexStr(s):
    """Преобразуем в 16-е значения"""
    result = []
    for c in s: result.append(hex(ord(c)))
    return string.join(result,' ')

def float2100int(f,digits=2):
        mask = "%."+str(digits)+'f'
        s    = mask % f
        return int(s.replace('.',''))

#Status constants
OK            = 0
KKM_READY     = 1
KKM_ANSWERING = 2
#FP modes descriptions
FP_MODES_DESCR = { 0:u'Принтер в рабочем режиме.',\
                   1:u'Выдача данных.',\
                   2:u'Открытая смена, 24 часа не кончились.',\
                   3:u'Открытая смена, 24 часа кончились.',\
                   4:u'Закрытая смена.',\
                   5:u'Блокировка по неправильному паролю налогового инспектора.',\
                   6:u'Ожидание подтверждения ввода даты.',\
                   7:u'Разрешение изменения положения десятичной точки.',\
                   8:u'Открытый документ:',\
                   9:u'''Режим разрешения технологического обнуления. В этот режим ККМ переходит по включению питания, если некорректна информация в энергонезависимом ОЗУ ККМ.''',\
                   10:u'Тестовый прогон.',\
                   11:u'Печать полного фис. отчета.',\
                   12:u'Печать отчёта ЭКЛЗ.',\
                   13:u'Работа с фискальным подкладным документом:',\
                   14:u'Печать подкладного документа.',\
                   15:u'Фискальный подкладной документ сформирован.'}

FR_SUBMODES_DESCR = {0:{0:u'Подрежим не предусмотрен'},\
                     1:{0:u'Подрежим не предусмотрен'},\
                     2:{0:u'Подрежим не предусмотрен'},\
                     3:{0:u'Подрежим не предусмотрен'},\
                     4:{0:u'Подрежим не предусмотрен'},\
                     5:{0:u'Подрежим не предусмотрен'},\
                     6:{0:u'Подрежим не предусмотрен'},\
                     7:{0:u'Подрежим не предусмотрен'},\
                     8:{0:u'Продажа',\
                        1:u'Покупка',\
                        2:u'Возврат продажи',\
                        3:u'Возврат покупки'},\
                     9:{0:u'Подрежим не предусмотрен'},\
                     10:{0:u'Подрежим не предусмотрен'},\
                     11:{0:u'Подрежим не предусмотрен'},\
                     12:{0:u'Подрежим не предусмотрен'},\
                     13:{0:u'Продажа (открыт)',\
                        1:u'Покупка (открыт)',\
                        2:u'Возврат продажи (открыт)',\
                        3:u'Возврат покупки (открыт)'},\
                     14:{0:u'Ожидание загрузки.',\
                         1:u'Загрузка и позиционирование.',\
                         2:u'Позиционирование.',\
                         3:u'Печать.',\
                         4:u'Печать закончена.',\
                         5:u'Выброс документа.',\
                         6:u'Ожидание извлечения.'},\
                     15:{0:u'Подрежим не предусмотрен'}}


ENQ = chr(0x05)
STX = chr(0x02)
ACK = chr(0x06)
NAK = chr(0x15)

MAX_TRIES = 10 # Кол-во попыток
MIN_TIMEOUT = 0.05

DEFAULT_ADM_PASSWORD = bufStr(0x1e,0x0,0x0,0x0) #Пароль админа по умолчанию = 30
DEFAULT_PASSWORD     = bufStr(0x1,0x0,0x0,0x0)  #Пароль кассира по умолчанию = 1

def LRC(buff):
    """Подсчет CRC"""
    result = 0
    for c in buff:
        result = result ^ ord(c)
    dbg( "LRC",result)
    return chr(result)

def byte2array(b):
        """Convert byte into array"""
        result = []
        for i in range(0,8):
                if b == b >> 1 <<1:
                        result.append(False)
                else:
                        result.append(True)
                b = b >>1
        return result


#Exceptions
class kkmException(Exception):
        def __init__(self, value):
                self.value = value
                self.s = { 0x4e: "Смена превысила 24 часа (Закройте смену с гашением) (ошибка 0x4e)",\
                           0x4f: "Неверный пароль (ошибка 0x4f)",\
                           0x73: "Команда не поддерживается в данном режиме (отмените печать чека или продолжите её или закончилась смена, надо осуществить гашение.) (ошибка 0x73)",
                        }[value]
        def __str__(self):
            return self.s

        def __unicode__(self):
            return unicode(str(self.s),'utf8')
#commands
class KKM:
        def __init__(self,conn,password=DEFAULT_PASSWORD):
                self.conn     = conn
                self.password = password
                if self.__checkState()!=NAK:
                        buffer=''
                        while self.conn.inWaiting():
                                buffer += self.conn.read()
                        self.conn.write(ACK+ENQ)
                        if self.conn.read(1)!=NAK:
                                raise RuntimeError("NAK expected")

        def __checkState(self):
                """Проверить на готовность"""
                self.conn.write(ENQ)
                repl = self.conn.read(1)
                if not self.conn.isOpen():
                        raise RuntimeError("Serial port closed unexpectly")
                if repl==NAK:
                        return NAK
                if repl==ACK:
                        return ACK
                raise RuntimeError("Unknown answer")

        def __clearAnswer(self):
                """Сбросить ответ если он болтается в ККМ"""
                def oneRound():
                        self.conn.flush()
                        time.sleep(MIN_TIMEOUT*10)
                        self.conn.write(ENQ)
                        a = self.conn.read(1)
                        time.sleep(MIN_TIMEOUT*2)
                        if a==NAK:
                                return 1
                        elif a==ACK:
                                a = self.conn.read(1)
                                time.sleep(MIN_TIMEOUT*2)
                                if a!=STX:
                                        raise RuntimeError("Something wrong")
                                length = ord(self.conn.read(1))
                                time.sleep(MIN_TIMEOUT*2)
                                data = self.conn.read(length+1)
                                self.conn.write(ACK)
                                time.sleep(MIN_TIMEOUT*2)
                                return 2
                        else:
                                raise RuntimeError("Something wrong")
                n=0
                while n<MAX_TRIES and oneRound()!=1:
                        n+=1
                if n>=MAX_TRIES:
                        return 1
                return 0

        def __readAnswer(self):
                """Считать ответ ККМ"""
                a = self.conn.read(1)
                if a==ACK:
                        a = self.conn.read(1)
                        if a==STX:
                         length   = ord(self.conn.read(1))
                         cmd      = self.conn.read(1)
                         errcode  = self.conn.read(1)
                         data     = self.conn.read(length-2)
                         if length-2!=len(data):
                            #print hexStr(data)
                              self.conn.write(NAK)
                              raise RuntimeError("Length (%i) not equal length of data (%i)" % (length, len(data)))
                         rcrc   = self.conn.read(1)
                         mycrc = LRC(chr(length)+cmd+errcode+data)
                         if rcrc!=mycrc:
                                    self.conn.write(NAK)
                                    raise RuntimeError("Wrong crc %i must be %i " % (mycrc,ord(rcrc)))
                         self.conn.write(ACK)
                         self.conn.flush()
                         time.sleep(MIN_TIMEOUT*2)
                         if ord(errcode)!=0:
                                 raise kkmException(ord(errcode))
                         return {'cmd':cmd,'errcode':ord(errcode),'data':data}
                        else:
                                raise RuntimeError("a!=STX %s %s" % (hex(ord(a)),hex(ord(STX))))
                elif a==NAK:
                        return None
                else:
                        raise RuntimeError("a!=ACK %s %s" % (hex(ord(a)),hex(ord(ACK))))



        def __sendCommand(self,cmd,params):
                """Стандартная обработка команды"""
                self.conn.flush()
                data   = chr(cmd)+params
                length = 1+len(params)
                content = chr(length)+data
                crc = LRC(content)
                self.conn.write(STX+content+crc)
                self.conn.flush()
                return OK

        def Beep(self):
                """Гудок"""
                self.__clearAnswer()
                self.__sendCommand(0x13,self.password)
                answer = self.__readAnswer()
                return answer['errcode']

        def shortStatusRequest(self):
                """Request short status info"""
                self.__clearAnswer()
                self.__sendCommand(0x10,self.password)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.LAST_ERROR = errcode
                ba = byte2array(ord(data[2]))
                ba.extend(byte2array(ord(data[1])))
                dbg("ba=",ba)
                result = {
                                  'operator':                  ord(data[0]), \
                                  'flags':                     data[1]+data[2], \
                                  'mode':                      ord(data[3]),\
                                  'submode':                   ord(data[4]),\
                                  'rull_oper_log':             ba[0],\
                                  'rull_check_log':            ba[1],\
                                  'upper_sensor_skid_document':ba[2],\
                                  'lower_sensor_skid_document':ba[3],\
                                  '2decimal_digits':           ba[4],\
                                  'eklz':                      ba[5],\
                                  'optical_sensor_oper_log':   ba[6],\
                                  'optical_sensor_chek_log':   ba[7],\
                                  'thermo_oper_log':           ba[8],\
                                  'thermo_check_log':          ba[9],\
                                  'box_open':                  ba[10],\
                                  'money_box':                 ba[11],\
                                  'eklz_full':                 ba[14],\
                                  'battaryvoltage':            ord(data[6]),\
                                  'powervoltage':              ord(data[7]),\
                                  'errcodefp':                 ord(data[8]),\
                                  'errcodeeklz':               ord(data[9]),\
                                  'rezerv':                    data[10:] }
                return result

        def statusRequest(self):
                """Request status info"""
                self.__clearAnswer()
                self.__sendCommand(0x11,self.password)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                print "data len = ",len(data)
                ba = byte2array(ord(data[12]))
                ba.extend(byte2array(ord(data[11])))
                dbg('len of data',len(data))
                result = {'errcode':                   errcode, \
                          'operator':                  ord(data[0]), \
                          'fr_ver':                    data[1]+data[2],\
                          'fr_build':                  unpack('i',data[3]+data[4]+chr(0x0)+chr(0x0))[0],\
                          'fr_date':                   '%02i.%02i.20%02i' % (ord(data[5]),ord(data[6]),ord(data[7])),\
                          'zal_num':                   ord(data[8]),\
                          'doc_num':                   unpack('i',data[9]+data[10]+chr(0x0)+chr(0x0))[0],\
                          'fr_flags':                  unpack('i',data[11]+data[12]+chr(0x0)+chr(0x0))[0],\
                          'mode':                      ord(data[13]),\
                          'submode':                   ord(data[14]),\
                          'fr_port':                   ord(data[15]),\
                          'fp_ver':                    data[16]+data[17],\
                          'fp_build':                  unpack('i',data[18]+data[19]+chr(0x0)+chr(0x0))[0],\
                          'fp_datep':                  '%02i.%02i.20%02i' % (ord(data[20]),ord(data[21]),ord(data[22])),\
                          'date':                      '%02i.%02i.20%02i' % (ord(data[23]),ord(data[24]),ord(data[25])),\
                          'time':                      '%02i:%02i:%02i' % (ord(data[26]),ord(data[27]),ord(data[28])),\
                          'flags_fp':                  ord(data[29]),\
                          'factory_number':            unpack('i',data[30]+data[31]+data[32]+data[33])[0],\
                          'last_closed_tour':          unpack('i',data[34]+data[35]+chr(0x0)+chr(0x0))[0],\
                          'free_fp_records':           data[36]+data[37],\
                          'reregister_count':          data[38],\
                          'reregister_count_left':     ord(data[39]),\
                          'INN':data[40]+data[41]+data[42]+data[43]+data[44]+data[45],\
                          'rull_oper_log':             ba[0],\
                          'rull_check_log':            ba[1],\
                          'upper_sensor_skid_document':ba[2],\
                          'lower_sensor_skid_document':ba[3],\
                          '2decimal_digits':           ba[4],\
                          'eklz':                      ba[5],\
                          'optical_sensor_oper_log':   ba[6],\
                          'optical_sensor_chek_log':   ba[7],\
                          'thermo_oper_log':           ba[8],\
                          'thermo_check_log':          ba[9],\
                          'box_open':                  ba[10],\
                          'money_box':                 ba[11],\
                          'eklz_full':                 ba[14]}
                return result

        def cashIncome(self,count):
                """Внесение денег"""
                self.__clearAnswer()
                bin_summ = pack('l',float2100int(count)).ljust(5,chr(0x0))
                self.__sendCommand(0x50,self.password+bin_summ)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.DOC_NUM    = unpack('i',data[0]+data[1]+chr(0x0)+chr(0x0))[0]


        def cashOutcome(self,count):
                """Выплата денег"""
                self.__clearAnswer()
                bin_summ = pack('l',float2100int(count)).ljust(5,chr(0x0))
                self.__sendCommand(0x51,self.password+bin_summ)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                self.DOC_NUM    = unpack('i',data[1]+data[2]+chr(0x0)+chr(0x0))[0]

        def openCheck(self,ctype):
            """Команда:     8DH. Длина сообщения: 6 байт.
                     • Пароль оператора (4 байта)
                     • Тип документа (1 байт): 0 – продажа;
                                               1 – покупка;
                                               2 – возврат продажи;
                                               3 – возврат покупки
                Ответ:       8DH. Длина сообщения: 3 байта.
                     • Код ошибки (1 байт)
                     • Порядковый номер оператора (1 байт) 1...30
            """
            self.__clearAnswer()
            if ctype not in range(0,4):
                   raise RuntimeError("Check type may be only 0,1,2,3 value")
            self.__sendCommand(0x8D,self.password+chr(ctype))
            a = self.__readAnswer()
            cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
            self.OP_CODE    = ord(data[0])
            return errcode


        def Sale(self,count,price,text=u"",department=1,taxes=[0,0,0,0][:]):
            """Продажа
                Команда:     80H. Длина сообщения: 60 байт.
                     • Пароль оператора (4 байта)
                     • Количество (5 байт) 0000000000...9999999999
                     • Цена (5 байт) 0000000000...9999999999
                     • Номер отдела (1 байт) 0...16
                     • Налог 1 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 2 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 3 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 4 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Текст (40 байт)
                Ответ:       80H. Длина сообщения: 3 байта.
                     • Код ошибки (1 байт)
                     • Порядковый номер оператора (1 байт) 1...30
            """
            #self.__clearAnswer()
            if count < 0 or count > 9999999999:
                   raise RuntimeError("Count myst be in range 0..9999999999")
            if price <0 or price > 9999999999:
                   raise RuntimeError("Price myst be in range 0..9999999999")
            if not department in range(0,17):
                   raise RuntimeError("Department myst be in range 1..16")
            #if len(text)>40:
            #       raise RuntimeError("Text myst be less than 40 chars")
            if len(taxes)!=4:
                   raise RuntimeError("Count of taxes myst be 4")
            for t in taxes:
                if t not in range(0,4):
                   raise RuntimeError("taxes myst be only 0,1,2,3,4")
            bcount = pack('l',float2100int(count,3))+chr(0x0)
            bprice = pack('l',float2100int(price))+chr(0x0) #если сразу * 100 то ошибка округления
            bdep   = chr(department)
            btaxes = "%s%s%s%s" % tuple(map(lambda x: chr(x), taxes))
#            print 'taxes = ',taxes,'bin=',hexStr(btaxes)
            btext  = text.encode('cp1251').ljust(40,chr(0x0))
#            time.sleep(0.5)
            self.__sendCommand(0x80,self.password+bcount+bprice+bdep+btaxes+btext)
#            time.sleep(1)
            a = self.__readAnswer()
#            time.sleep(0.5)
            cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
            self.OP_CODE    = ord(data[0])
            return errcode

        def returnSale(self,count,price,text=u"",department=1,taxes=[0,0,0,0][:]):
            """Возврат продажи
                Команда:     82H. Длина сообщения: 60 байт.
                     • Пароль оператора (4 байта)
                     • Количество (5 байт) 0000000000...9999999999
                     • Цена (5 байт) 0000000000...9999999999
                     • Номер отдела (1 байт) 0...16
                     • Налог 1 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 2 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 3 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 4 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Текст (40 байт)
                Ответ:       80H. Длина сообщения: 3 байта.
                     • Код ошибки (1 байт)
                     • Порядковый номер оператора (1 байт) 1...30
            """
            #self.__clearAnswer()
            if float2100int(count)*10 < 0 or float2100int(count)*10 > 9999999999:
                   raise RuntimeError("Count myst be in range 0..9999999999")
            if float2100int(price) <0 or float2100int(price) > 9999999999:
                   raise RuntimeError("Price myst be in range 0..9999999999")
            if not department in range(0,17):
                   raise RuntimeError("Department myst be in range 1..16")
            #if len(text)>40:
            #       raise RuntimeError("Text myst be less than 40 chars")
            if len(taxes)!=4:
                   raise RuntimeError("Count of taxes myst be 4")
            for t in taxes:
                if t not in range(0,4):
                   raise RuntimeError("taxes myst be only 0,1,2,3,4")
            bcount = pack('l',float2100int(count,3))+chr(0x0)
            bprice = pack('l',float2100int(price))+chr(0x0)
            bdep   = chr(department)
            btaxes = "%s%s%s%s" % tuple(map(lambda x: chr(x), taxes))
            btext  = text.encode('cp1251').ljust(40,chr(0x0))
            self.__sendCommand(0x82,self.password+bcount+bprice+bdep+btaxes+btext)
#            time.sleep(0.8)
            a = self.__readAnswer()
            cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
            self.OP_CODE    = ord(data[0])
            return errcode

        def closeCheck(self,summa,text=u"",summa2=0,summa3=0,summa4=0,sale=0,taxes=[0,0,0,0][:]):
            """
                Команда:     85H. Длина сообщения: 71 байт.
                     • Пароль оператора (4 байта)
                     • Сумма наличных (5 байт) 0000000000...9999999999
                     • Сумма типа оплаты 2 (5 байт) 0000000000...9999999999
                     • Сумма типа оплаты 3 (5 байт) 0000000000...9999999999
                     • Сумма типа оплаты 4 (5 байт) 0000000000...9999999999
                     • Скидка/Надбавка(в случае отрицательного значения) в % на чек от 0 до 99,99
                       % (2 байта со знаком) -9999...9999
                     • Налог 1 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 2 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 3 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Налог 4 (1 байт) «0» – нет, «1»...«4» – налоговая группа
                     • Текст (40 байт)
                Ответ:       85H. Длина сообщения: 8 байт.
                     • Код ошибки (1 байт)
                     • Порядковый номер оператора (1 байт) 1...30
                     • Сдача (5 байт) 0000000000...9999999999
            """
            self.__clearAnswer()
            if float2100int(summa) <0 or float2100int(summa) > 9999999999:
                   raise RuntimeError("Summa myst be in range 0..9999999999")
            if float2100int(summa2) <0 or float2100int(summa2) > 9999999999:
                   raise RuntimeError("Summa2 myst be in range 0..9999999999")
            if float2100int(summa3) <0 or float2100int(summa3) > 9999999999:
                   raise RuntimeError("Summa3 myst be in range 0..9999999999")
            if float2100int(summa4) <0 or float2100int(summa4) > 9999999999:
                   raise RuntimeError("Summa4 myst be in range 0..9999999999")
            if float2100int(sale) <-9999 or float2100int(sale) > 9999:
                   raise RuntimeError("Sale myst be in range -9999..9999")
            if len(text)>40:
                   raise RuntimeError("Text myst be less than 40 chars")
            if len(taxes)!=4:
                   raise RuntimeError("Count of taxes myst be 4")
            for t in taxes:
                if t not in range(0,4):
                   raise RuntimeError("taxes myst be only 0,1,2,3,4")

            bsumma  = pack('l',float2100int(summa))+chr(0x0)
            bsumma2 = pack('l',float2100int(summa2))+chr(0x0)
            bsumma3 = pack('l',float2100int(summa3))+chr(0x0)
            bsumma4 = pack('l',float2100int(summa4))+chr(0x0)
            bsale   = pack('h',float2100int(sale))
            btaxes = "%s%s%s%s" % tuple(map(lambda x: chr(x), taxes))
            btext  = text.encode('cp1251').ljust(40,chr(0x0))
            self.__sendCommand(0x85,self.password+bsumma+bsumma2+bsumma3+bsumma4+bsale+btaxes+btext)
            time.sleep(0.1) # DOOM 
            a = self.__readAnswer()
            cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
            self.OP_CODE    = ord(data[0])
            #Сдачу я не считаю....
            #time.sleep(0.5) # Тут не успевает иногда
            return errcode

        def reportWoClose(self,admpass):
                """Отчет без гашения"""
                self.__clearAnswer()
                self.__sendCommand(0x40,admpass)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return 1

        def reportWClose(self,admpass):
                """Отчет с гашением"""
                self.__clearAnswer()
                self.__sendCommand(0x41,admpass)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return 1

        def cutCheck(self,cutType):
                """Отрезка чека
                   Команда:     25H. Длина сообщения: 6 байт.
                        • Пароль оператора (4 байта)
                        • Тип отрезки (1 байт) «0» – полная, «1» – неполная
                   Ответ:       25H. Длина сообщения: 3 байта.
                        • Код ошибки (1 байт)
                        • Порядковый номер оператора (1 байт) 1...30
                """
                #self.__clearAnswer()
                if cutType!=0 and cutType!=1:
                   raise RuntimeError("cutType myst be only 0 or 1 ")
                self.__sendCommand(0x25,self.password+chr(cutType))
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return errcode

        def continuePrint(self):
                """Продолжение печати
                Команда:    B0H. Длина сообщения: 5 байт.
                        • Пароль оператора, администратора или системного администратора (4 байта)
                   Ответ:      B0H. Длина сообщения: 3 байта.
                        • Код ошибки (1 байт)
                        • Порядковый номер оператора (1 байт) 1...30
                """
                self.__clearAnswer()
                self.__sendCommand(0xB0,self.password)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return errcode

        def repeatDoc(self):
                """Команда:    8CH. Длина сообщения: 5 байт.
                     • Пароль оператора (4 байта)
                Ответ:      8CH. Длина сообщения: 3 байта.
                     • Код ошибки (1 байт)
                     • Порядковый номер оператора (1 байт) 1...30
                     Команда выводит на печать копию последнего закрытого документа
                                 продажи, покупки, возврата продажи и возврата покупки.
                """
                #self.__clearAnswer()
                self.__sendCommand(0x8C,self.password)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return errcode

        def cancelCheck(self):
                """Команда:    88H. Длина сообщения: 5 байт.
                     • Пароль оператора (4 байта)
                Ответ:      88H. Длина сообщения: 3 байта.
                     • Код ошибки (1 байт)
                     • Порядковый номер оператора (1 байт) 1...30
                """
                self.__clearAnswer()
                self.__sendCommand(0x88,self.password)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return errcode

        def setDate(self,admpass,day,month,year):
                """Установка даты
                Команда:     22H. Длина сообщения: 8 байт.
                     • Пароль системного администратора (4 байта)
                     • Дата (3 байта) ДД-ММ-ГГ
                Ответ:       22H. Длина сообщения: 2 байта.
                     • Код ошибки (1 байт)
                """
                self.__clearAnswer()
                self.__sendCommand(0x22,admpass+chr(day)+chr(month)+chr(year-2000))
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                return errcode

        def acceptSetDate(self,admpass,day,month,year):
                """Установка даты (бред какой-то)
                Команда:     23H. Длина сообщения: 8 байт.
                     • Пароль системного администратора (4 байта)
                     • Дата (3 байта) ДД-ММ-ГГ
                Ответ:       23H. Длина сообщения: 2 байта.
                     • Код ошибки (1 байт)
                """
                self.__clearAnswer()
                self.__sendCommand(0x23,admpass+chr(day)+chr(month)+chr(year-2000))
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                return errcode

        def setTime(self,admpass,hour,minutes,secs):
                """Установка даты
                Команда:    21H. Длина сообщения: 8 байт.
                             • Пароль системного администратора (4 байта)
                             • Время (3 байта) ЧЧ-ММ-СС
                        Ответ:      21H. Длина сообщения: 2 байта.
                             • Код ошибки (1 байт)
                """
                self.__clearAnswer()
                self.__sendCommand(0x21,admpass+chr(hour)+chr(minutes)+chr(secs))
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                return errcode



        def setTableValue(self,admpass,table,row,field,value):
                """Записать значение в таблицу, ряд, поле
                поля бывают бинарные и строковые, поэтому value
                делаем в исходном виде
                """
                #self.__clearAnswer()
                drow    = pack('l',row).ljust(2,chr(0x0))[:2]
                self.__sendCommand(0x1e,admpass+chr(table)+drow+chr(field)+value)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                return  errcode
        def printString(self,check_ribbon=True,control_ribbon=False,text=u""):
                """Печать строки без ограничения на 40 символов"""
                t = text
                while len(t)>0:
                        self._printString(check_ribbon=check_ribbon,control_ribbon=check_ribbon,text=t[:39])
                        t = t[39:]
        def _printString(self,check_ribbon=True,control_ribbon=False,text=u""):
                """Напечатать строку"""
                #self.__clearAnswer()
                flag = 0
                if check_ribbon:
                        flag = flag | 1
                if control_ribbon:
                        flag = flag | 2
                if len(text)>40:
                        raise RuntimeError("Length of string myst be less or equal 40 chars")
                s = text.encode('cp1251').ljust(40,chr(0x0))
#                time.sleep(0.2)
                self.__sendCommand(0x17,self.password+bufStr(flag)+s)
#                time.sleep(0.5)
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return errcode

        def printShk(self,shk):
                """Напечатать shk"""
                #self.__clearAnswer()
                self.__sendCommand(0xC2,self.password+shk)
                a = self.__readAnswer()
                print "printshk=",a
                return 0

        def openbox(self):
                """Открыть ящик"""
                #self.__clearAnswer()
                self.__sendCommand(0x28,self.password+chr(0))
                a = self.__readAnswer()
                return a['errcode']

        def renull(self):
                """Технологическое обнуление"""
                self.__clearAnswer()
                self.__sendCommand(0x16,'')
                a = self.__readAnswer()
                print "renull=",a
                return 0

        def Run(self,check_ribbon=True,control_ribbon=True,doc_ribbon=False,row_count=1):
                """Прогон"""
                self.__clearAnswer()
                flag = 0
                if check_ribbon:
                        flag = flag | 1
                if control_ribbon:
                        flag = flag | 2
                if doc_ribbon:
                        flag = flag | 4
                if row_count not in range(1,255):
                        raise RuntimeError("Line count myst be in 1..255 range")

                self.__sendCommand(0x29,self.password+bufStr(flag,row_count))
                a = self.__readAnswer()
                cmd,errcode,data = (a['cmd'],a['errcode'],a['data'])
                self.OP_CODE    = ord(data[0])
                return errcode


