#!/usr/bin/env python3
import argparse,json,os,shutil,time,urllib.request
VERSION="0.2.0"
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
def post(url,payload,headers=None):
    h={"Content-Type":"application/json","User-Agent":"mPanel-Agent/"+VERSION}; h.update(headers or {})
    req=urllib.request.Request(url,data=json.dumps(payload).encode(),headers=h,method="POST")
    with urllib.request.urlopen(req,timeout=15) as r: return json.loads(r.read().decode())
p=argparse.ArgumentParser()
p.add_argument("--url",required=True); p.add_argument("--server-id",required=True)
p.add_argument("--pairing-token",default=os.getenv("MPANEL_PAIRING_TOKEN"))
p.add_argument("--token",default=os.getenv("MPANEL_AGENT_TOKEN")); p.add_argument("--interval",type=int,default=30)
a=p.parse_args(); base=a.url.rstrip("/")
token=a.token
if not token:
    if not a.pairing_token: raise SystemExit("Missing pairing token.")
    result=post(base+"/api/agent/pair",{"server_id":int(a.server_id),"pairing_token":a.pairing_token})
    token=result["agent_token"]
endpoint=base+"/api/agent/servers/"+a.server_id+"/heartbeat"
while True:
    try:
        result=post(endpoint,{"cpu_percent":cpu(),"memory_percent":mem(),"disk_percent":disk()},{"Authorization":"Bearer "+token})
        print("[mPanel] heartbeat:",result,flush=True)
    except Exception as e: print("[mPanel] heartbeat failed:",e,flush=True)
    time.sleep(max(5,a.interval))
