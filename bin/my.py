#!/usr/bin/python
# -*- coding: utf-8
import string
import copy
import time
import MySQLdb
def mydt2time(d,t):
    struct_time=time.strptime(d+"_"+t,"%Y-%m-%d_%H:%M:%S")
    return time.mktime(struct_time)
def mydt2normdt(d):
    d=str(d)
    a=d.split('-')
    return a[2]+'.'+a[1]+'.'+a[0]
def curdate2my():
    t=time.localtime()
    return str(t.tm_year)+'-'+str(t.tm_mon)+'-'+str(t.tm_mday)
def curtime2my():
    t=time.localtime()
    return str(t.tm_hour)+':'+str(t.tm_min)+':'+str(t.tm_sec)

class table:
    def __init__ (self,dbname):
        self.tablename=dbname
        self.field_list={}
        self.field_value={}
        self.fieldsorder=[]
        self.defaults={'s':'','d':0,'f':0,}

    def addfield(self,fn,ft):
        self.field_list[fn]=ft
        self.fieldsorder.append(fn)

    def number_field(self,field_name):
        return self.fieldsorder.index(field_name)

    def empty (self,v):
        if self.defaults.has_key(v):
            r=self.defaults[v]
        else:
            r=''
        return r

    def set_values (self,fields,valuesorder):
        field_value={}
        i=0
        for k in fields:
            field_value[k]=valuesorder[i]
            i=i+1
        return field_value

    def set_all_values (self,valuesorder):
        field_value=self.field_list.copy()
        i=0
        for k in self.fieldsorder:
            field_value[k]=valuesorder[i]
            i=i+1
        return field_value

    def empty_all_values (self):
        field_value=self.field_list.copy()
        for k,v in field_value.items():
            field_value[k]=self.empty(v)

    def query_select (self,field_list):
        fd=[]
        for v in field_list:
            fd.append("`%s`" % v)
        fields=",".join(fd)
        s="select %s from %s" % (fields,self.tablename)
        return s
    
    def query_all_select (self):
        return self.query_select(self.fieldsorder)

    def query_insert (self,struct):
        fields=",".join(["`%s`" % str(v) for v in struct.keys()])
        st=[]
        for k,v in struct.items():
            if self.field_list[k] in ('s','D','t'):
                v="\'%s\'" % v
            st.append(v)
        values=",".join(["%s" % v for v in st])
        s="insert into %s (%s) values(%s)" % (self.tablename,fields,values)
        return s

    def query_all_insert(self,data):
        i=0
        struct={}
        for k in self.fieldsorder:
            struct[k]=data[i]
            i=i+1
        return self.query_insert(struct)
    
    def query_update (self,struct):
        st=[]
        for k,v in struct.items():
            if self.field_list[k] in ('s','D','t'):
                v="\"%s\"" % v
            st.append("`%s`=%s" % (k,str(v)))
        values=",".join(["%s" % v for v in st])
        s="update %s set %s" % (self.tablename,values)
        return s

    def query_last_id(self):
        return "select last_insert_id()"

class db:
    def __init__ (self,dbname,host,user,password):
        self.dbname=dbname
        self.host=host
        self.user=user
        self.password=password

    def open (self):
        try:
            self.db = MySQLdb.connect(host=self.host, user=self.user, passwd=self.password, db=self.dbname, charset='utf8')
            self.cursor = self.db.cursor()
            return 1
        except:
            return 0 

    def close (self):
        self.cursor.close()
        self.db.close()

    def ping (self):
        try:
            self.run('select 1')
            return True
        except:
            return False
    
    def run (self,query):
        return self.cursor.execute(query)

    def get (self,query):
        r=self.run(query)
        data=self.cursor.fetchall()
        return data

