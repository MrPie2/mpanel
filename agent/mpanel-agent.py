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


def valid_domain(domain):
    import re
    return bool(re.fullmatch(r"(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}", domain))

def complete_create_site(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    root=str(p.get("document_root",""))
    php=str(p.get("php_version",""))
    try:
        if not valid_domain(domain):
            raise ValueError("Invalid domain supplied.")
        if root != "/var/www/"+domain:
            raise ValueError("Invalid document root.")
        if php not in {"8.2","8.3","8.4"}:
            raise ValueError("Unsupported PHP version.")
        nginx_config = "server {\n    listen 80;\n    listen [::]:80;\n    server_name "+domain+" www."+domain+";\n    root "+root+";\n    index index.php index.html;\n\n    location / {\n        try_files $uri $uri/ /index.php?$query_string;\n    }\n\n    location ~ \\.php$ {\n        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:/run/php/php"+php+"-fpm.sock;\n    }\n}\n"
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {
            "status":"completed",
            "result":{
                "website_id":p.get("website_id"),
                "domain":domain,
                "document_root":root,
                "php_version":php,
                "nginx_config":nginx_config,
                "mode":"validated"
            }
        }, auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                {"status":"failed","error":str(e)},auth)

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
            if job["type"] == "create_site":
                complete_create_site(job, base, auth)
            else:
                result={"message":"Operation not implemented by this Agent version."}
                request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                        {"status":"failed","result":result,"error":"Unsupported operation: "+job["type"]},auth)
    except Exception as e:
        print("[mPanel] agent cycle failed:",e,flush=True)
    time.sleep(max(5,a.interval))
