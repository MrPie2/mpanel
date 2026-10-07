#!/usr/bin/env python3
import argparse,json,os,shutil,time,urllib.request
VERSION="0.4.0"
TOKEN_FILE="/opt/mpanel-agent/agent-token"

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

def request(url,payload=None,headers=None,method="POST"):
    h={"Content-Type":"application/json","User-Agent":"mPanel-Agent/"+VERSION}; h.update(headers or {})
    data=json.dumps(payload).encode() if payload is not None else None
    req=urllib.request.Request(url,data=data,headers=h,method=method)
    with urllib.request.urlopen(req,timeout=20) as r:
        return json.loads(r.read().decode())

def save_token(token):
    os.makedirs(os.path.dirname(TOKEN_FILE),exist_ok=True)
    fd=os.open(TOKEN_FILE,os.O_WRONLY|os.O_CREAT|os.O_TRUNC,0o600)
    with os.fdopen(fd,"w") as f: f.write(token)
    os.chmod(TOKEN_FILE,0o600)

def load_token():
    try:
        with open(TOKEN_FILE) as f: return f.read().strip()
    except FileNotFoundError: return ""

p=argparse.ArgumentParser()
p.add_argument("--url",required=True); p.add_argument("--server-id",required=True)
p.add_argument("--pairing-token",default=os.getenv("MPANEL_PAIRING_TOKEN"))
p.add_argument("--interval",type=int,default=30)
a=p.parse_args(); base=a.url.rstrip("/")

token=load_token()
if not token:
    if not a.pairing_token: raise SystemExit("Missing pairing token.")
    result=request(base+"/api/agent/pair",{"server_id":int(a.server_id),"pairing_token":a.pairing_token})
    token=result["agent_token"]; save_token(token)
    print("[mPanel] pairing successful; permanent Agent credential saved.",flush=True)

auth={"Authorization":"Bearer "+token}
heartbeat=base+"/api/agent/servers/"+a.server_id+"/heartbeat"
jobs_url=base+"/api/agent/servers/"+a.server_id+"/jobs"

while True:
    try:
        result=request(heartbeat,{"cpu_percent":cpu(),"memory_percent":mem(),"disk_percent":disk()},auth)
        print("[mPanel] heartbeat:",result,flush=True)

        jobs=request(jobs_url,None,auth,"GET").get("jobs",[])
        for job in jobs:
            print("[mPanel] received job",job["id"],job["type"],flush=True)
            # Provisioning operations are intentionally allowlisted and will be
            # implemented individually. Unknown operations are never executed.
            result={"message":"Operation not implemented by this Agent version."}
            request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                    {"status":"failed","result":result,"error":"Unsupported operation: "+job["type"]},auth)
    except Exception as e:
        print("[mPanel] agent cycle failed:",e,flush=True)
    time.sleep(max(5,a.interval))
