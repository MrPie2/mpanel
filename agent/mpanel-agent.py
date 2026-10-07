#!/usr/bin/env python3
import argparse,json,os,shutil,time,urllib.request
VERSION="0.1.0"
def cpu():
    def r():
        with open("/proc/stat") as f: v=list(map(int,f.readline().split()[1:]))
        return sum(v),v[3]+(v[4] if len(v)>4 else 0)
    a=r(); time.sleep(.15); b=r(); td=b[0]-a[0]; idle=b[1]-a[1]
    return round(max(0,min(100,100*(1-idle/td))),2) if td else 0
def mem():
    d={}
    with open("/proc/meminfo") as f:
        for line in f:
            k,v=line.split(":",1); d[k]=int(v.strip().split()[0])
    return round((1-d.get("MemAvailable",0)/d.get("MemTotal",1))*100,2)
def disk():
    u=shutil.disk_usage("/"); return round(u.used/u.total*100,2)
def beat(url,token,payload):
    req=urllib.request.Request(url,data=json.dumps(payload).encode(),headers={"Authorization":"Bearer "+token,"Content-Type":"application/json","User-Agent":"mPanel-Agent/"+VERSION},method="POST")
    with urllib.request.urlopen(req,timeout=15) as r: return r.status,r.read().decode()
p=argparse.ArgumentParser()
p.add_argument("--url",required=True); p.add_argument("--server-id",required=True); p.add_argument("--token",default=os.getenv("MPANEL_AGENT_TOKEN")); p.add_argument("--interval",type=int,default=30)
a=p.parse_args()
if not a.token: raise SystemExit("Missing Agent token.")
endpoint=a.url.rstrip("/")+"/api/agent/servers/"+a.server_id+"/heartbeat"
while True:
    try: print("[mPanel] heartbeat",beat(endpoint,a.token,{"cpu_percent":cpu(),"memory_percent":mem(),"disk_percent":disk()}),flush=True)
    except Exception as e: print("[mPanel] heartbeat failed:",e,flush=True)
    time.sleep(max(5,a.interval))
