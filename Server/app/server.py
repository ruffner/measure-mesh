# Matt Ruffner May 2019
# logger daemon for measure-mesh project
# ee699 final project
#

import time
import serial
import requests

INIT=True
ID=int(str(time.time()).split('.')[0])
PARAMS={'runNumber':ID, 'values':0}
URL='http://127.0.0.1/measure-mesh/Server/app/log.php'

def postValue(val):
    global INIT
    PARAMS['values'] = val
    r=''
    if INIT==False:
        if 'new' in PARAMS:
            PARAMS.pop('new')
        r = requests.get(url=URL, params=PARAMS)
    else:
        PARAMS['new']=1
        r = requests.get(url=URL, params=PARAMS)
        print("created first entry")
        INIT = False
    print(r.content)
    
#TODO: hardcode device filename with udev rule
ser = serial.Serial('/dev/ttyUSB0', 115200)

if ser.is_open:
    print("opened ", ser.name)
else:
    print("could not open ", ser.name)
    
# start logging requests upon serial receive
print("Starting logging loop...")
while(True):
    lines=0
    while lines<6:
        line=ser.readline()
        if not line:
            print("read error")
            break
        else:
            lines=lines+1
            try:
                idx=line.index(bytearray('Voltage:','utf-8'))
            except ValueError:
                continue
            if idx:
                postValue(int(line[idx+8:-2]))


