#!/usr/bin/env python3
import argparse,json,os,shutil,time,urllib.request,urllib.error,pathlib,subprocess
VERSION="0.4.2"
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
    h={"Content-Type":"application/json","User-Agent":"mPanel-Agent/"+VERSION}
    h.update(headers or {})
    data=json.dumps(payload).encode() if payload is not None else None
    req=urllib.request.Request(url,data=data,headers=h,method=method)
    try:
        with urllib.request.urlopen(req,timeout=20) as r:
            raw=r.read().decode("utf-8","replace")
    except urllib.error.HTTPError as e:
        body=e.read().decode("utf-8","replace").strip()
        detail=f"HTTP {e.code} for {method} {url}"
        if body:
            detail += f": {body[:2000]}"
        raise RuntimeError(detail) from e
    except urllib.error.URLError as e:
        raise RuntimeError(f"Network error for {method} {url}: {e.reason}") from e
    if not raw:
        return {}
    try:
        return json.loads(raw)
    except json.JSONDecodeError as e:
        raise RuntimeError(f"Invalid JSON response from {method} {url}: {raw[:500]}") from e

def save_token(token):
    os.makedirs(os.path.dirname(TOKEN_FILE),exist_ok=True)
    fd=os.open(TOKEN_FILE,os.O_WRONLY|os.O_CREAT|os.O_TRUNC,0o600)
    with os.fdopen(fd,"w") as f: f.write(token)
    os.chmod(TOKEN_FILE,0o600)


def valid_domain(domain):
    import re
    return bool(re.fullmatch(r"(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}", domain))

def run_checked(args):
    result = subprocess.run(
        args,
        check=False,
        capture_output=True,
        text=True,
        timeout=30,
    )
    if result.returncode != 0:
        details = (result.stderr or result.stdout or "").strip()
        command = " ".join(str(part) for part in args)
        message = f"Command '{command}' failed with exit code {result.returncode}"
        if details:
            message += f": {details}"
        else:
            message += " (no stdout/stderr was returned)"
        raise RuntimeError(message)
    return result



def safe_website_path(root, relative):
    root_path=pathlib.Path(root).resolve()
    target=(root_path / str(relative).strip("/")).resolve()
    if target != root_path and root_path not in target.parents:
        raise ValueError("Path escapes website root.")
    return root_path,target


def complete_download_file(job, base, auth):
    p=job.get("payload") or {}
    root=str(p.get("document_root",""))
    relative=str(p.get("path","")).strip("/")
    try:
        domain=root[len("/var/www/")] if root.startswith("/var/www/") else ""
        if not valid_domain(domain) or root != "/var/www/"+domain:
            raise ValueError("Invalid website root.")
        if not relative:
            raise ValueError("A file path is required.")
        root_path,target=safe_website_path(root,relative)
        if not target.exists() or not target.is_file():
            raise ValueError("File not found.")
        if target.stat().st_size > 5 * 1024 * 1024:
            raise ValueError("File is larger than the 5 MB Agent download limit.")
        import base64
        content=base64.b64encode(target.read_bytes()).decode("ascii")
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {
            "status":"completed",
            "result":{"path":relative,"name":target.name,"size":target.stat().st_size,"content_base64":content}
        }, auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                {"status":"failed","error":str(e)},auth)

def complete_upload_file(job, base, auth):
    p=job.get("payload") or {}
    root=str(p.get("document_root",""))
    relative=str(p.get("path","")).strip("/")
    name=str(p.get("name") or "").strip()
    try:
        domain=root[len("/var/www/"):] if root.startswith("/var/www/") else ""
        if not valid_domain(domain) or root != "/var/www/"+domain:
            raise ValueError("Invalid website root.")
        if not name or "/" in name or "\\" in name or name in {".",".."}:
            raise ValueError("Invalid file name.")
        root_path,target=safe_website_path(root,relative)
        if not target.exists() or not target.is_dir():
            raise ValueError("Destination directory not found.")
        destination=(target/name).resolve()
        if root_path not in destination.parents:
            raise ValueError("Invalid destination.")
        if destination.exists():
            raise ValueError("A file with that name already exists.")
        import base64
        raw=base64.b64decode(str(p.get("content_base64","")), validate=True)
        if len(raw) > 5 * 1024 * 1024:
            raise ValueError("Upload exceeds the 5 MB Agent limit.")
        destination.write_bytes(raw)
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {
            "status":"completed",
            "result":{"operation":"upload","path":str(destination.relative_to(root_path)),"name":name,"size":len(raw)}
        }, auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                {"status":"failed","error":str(e)},auth)

def complete_file_operation(job, base, auth):
    p=job.get("payload") or {}
    root=str(p.get("document_root",""))
    operation=str(p.get("operation",""))
    relative=str(p.get("path","")).strip("/")
    name=str(p.get("name") or "").strip()
    new_name=str(p.get("new_name") or "").strip()
    try:
        domain=root[len("/var/www/"):] if root.startswith("/var/www/") else ""
        if not valid_domain(domain) or root != "/var/www/"+domain:
            raise ValueError("Invalid website root.")
        if operation not in {"create_folder","create_file","rename","delete","write_file"}:
            raise ValueError("Unsupported file operation.")

        root_path,target=safe_website_path(root,relative)
        if operation in {"create_folder","create_file","write_file"}:
            if not name or "/" in name or "\\" in name or name in {".",".."}:
                raise ValueError("Invalid name.")
            parent=target
            if not parent.exists() or not parent.is_dir():
                raise ValueError("Parent directory not found.")
            destination=(parent/name).resolve()
            if root_path not in destination.parents:
                raise ValueError("Invalid destination.")
            if operation=="write_file":
                content=str(p.get("content","")).encode("utf-8")
                if len(content) > 512 * 1024:
                    raise ValueError("Text editor is limited to 512 KB.")
                if not destination.exists() or not destination.is_file():
                    raise ValueError("File not found.")
                destination.write_bytes(content)
            else:
                if destination.exists():
                    raise ValueError("An item with that name already exists.")
                if operation=="create_folder":
                    destination.mkdir()
                else:
                    destination.touch()
            result={"operation":operation,"path":str(destination.relative_to(root_path))}
        elif operation=="rename":
            if not name or "/" in name or name in {".",".."} or not new_name or "/" in new_name or new_name in {".",".."}:
                raise ValueError("Invalid name.")
            source=target/name
            destination=(target/new_name).resolve()
            if root_path not in source.resolve().parents and source.resolve()!=root_path:
                raise ValueError("Invalid source.")
            if root_path not in destination.parents:
                raise ValueError("Invalid destination.")
            if not source.exists():
                raise ValueError("Source item not found.")
            if destination.exists():
                raise ValueError("Destination already exists.")
            source.rename(destination)
            result={"operation":"rename","path":str(destination.relative_to(root_path))}
        else:
            if not relative:
                raise ValueError("The website root cannot be deleted.")
            if not target.exists():
                raise ValueError("Item not found.")
            if target.is_symlink():
                target.unlink()
            elif target.is_dir():
                shutil.rmtree(target)
            else:
                target.unlink()
            result={"operation":"delete","path":relative}

        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {"status":"completed","result":result}, auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {"status":"failed","error":str(e)},auth)

def complete_list_files(job, base, auth):
    p=job.get("payload") or {}
    root=str(p.get("document_root",""))
    relative=str(p.get("path","")).strip("/")
    try:
        if not root.startswith("/var/www/") or not valid_domain(root[len("/var/www/"):]):
            raise ValueError("Invalid website root.")
        target=pathlib.Path(root) / relative
        target=target.resolve()
        root_path=pathlib.Path(root).resolve()
        if target != root_path and root_path not in target.parents:
            raise ValueError("Path escapes website root.")
        if not target.exists() or not target.is_dir():
            raise ValueError("Directory not found.")
        entries=[]
        for item in sorted(target.iterdir(), key=lambda x: (not x.is_dir(), x.name.lower())):
            entries.append({
                "name":item.name,
                "type":"directory" if item.is_dir() else "file",
                "size":item.stat().st_size if item.is_file() else None
            })
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {
            "status":"completed",
            "result":{"path":relative,"entries":entries}
        }, auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                {"status":"failed","error":str(e)},auth)

def ssl_http_config(domain, root):
    return """server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN www.DOMAIN;
    root ROOT;
    index index.php index.html;

    location /.well-known/acme-challenge/ {
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:PHP_SOCKET;
    }
}
""".replace("DOMAIN", domain).replace("ROOT", root)

def ssl_https_config(domain, root, php_socket):
    return """server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN www.DOMAIN;
    root ROOT;

    location /.well-known/acme-challenge/ {
        try_files $uri =404;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name DOMAIN www.DOMAIN;
    root ROOT;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/DOMAIN/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:PHP_SOCKET;
    }
}
""".replace("DOMAIN", domain).replace("ROOT", root).replace("PHP_SOCKET", php_socket)

def cert_expiry(cert_path):
    out=run_checked(["openssl","x509","-enddate","-noout","-in",cert_path]).stdout.strip()
    value=out.split("=",1)[1]
    import datetime
    dt=datetime.datetime.strptime(value,"%b %d %H:%M:%S %Y %Z").replace(tzinfo=datetime.timezone.utc)
    return dt.isoformat()

def complete_issue_ssl(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    root=str(p.get("document_root",""))
    email=str(p.get("email","")).strip()
    config_path="/etc/nginx/sites-available/"+domain
    enabled_path="/etc/nginx/sites-enabled/"+domain
    backup_path=config_path+".mpanel-http-backup"
    try:
        if not valid_domain(domain) or root != "/var/www/"+domain:
            raise ValueError("Invalid website configuration.")
        if not email:
            raise ValueError("Certificate email is required.")
        php_socket="/run/php/php"+str(p.get("php_version","8.3"))+"-fpm.sock"
        if not os.path.exists(php_socket):
            sockets=list(pathlib.Path("/run/php").glob("php*-fpm.sock"))
            if not sockets:
                raise ValueError("No PHP-FPM socket was found.")
            php_socket=str(sockets[0])

        if not os.path.exists("/usr/bin/certbot"):
            raise ValueError("Certbot is not installed on this server. Install certbot before enabling SSL.")

        root_path=pathlib.Path(root)
        root_path.mkdir(parents=True,exist_ok=True)
        challenge=root_path/".well-known"/"acme-challenge"
        challenge.mkdir(parents=True,exist_ok=True)

        original=pathlib.Path(config_path).read_text() if os.path.exists(config_path) else ""
        pathlib.Path(backup_path).write_text(original)

        pathlib.Path(config_path).write_text(ssl_http_config(domain,root).replace("PHP_SOCKET",php_socket))
        try:
            run_checked(["nginx","-t"])
            run_checked(["systemctl","reload","nginx"])
            run_checked([
                "certbot","certonly","--webroot","-w",root,"-d",domain,
                "--non-interactive","--agree-tos","--email",email,
                "--keep-until-expiring"
            ])
            cert_path="/etc/letsencrypt/live/"+domain+"/fullchain.pem"
            if not os.path.exists(cert_path):
                raise ValueError("Certbot completed but the certificate file was not found.")
            pathlib.Path(config_path).write_text(ssl_https_config(domain,root,php_socket))
            run_checked(["nginx","-t"])
            run_checked(["systemctl","reload","nginx"])
            if os.path.exists(backup_path):
                os.unlink(backup_path)
            import datetime
            now=datetime.datetime.now(datetime.timezone.utc).isoformat()
            expires=cert_expiry(cert_path)
            request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {
                "status":"completed",
                "result":{"ssl_enabled":True,"domain":domain,"issued_at":now,"expires_at":expires,"certificate":cert_path}
            },auth)
        except Exception:
            if original:
                pathlib.Path(config_path).write_text(original)
                try:
                    run_checked(["nginx","-t"]); run_checked(["systemctl","reload","nginx"])
                except Exception:
                    pass
            elif os.path.exists(config_path):
                os.unlink(config_path)
            raise
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)

def complete_disable_ssl(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    root=str(p.get("document_root",""))
    config_path="/etc/nginx/sites-available/"+domain
    try:
        if not valid_domain(domain) or root != "/var/www/"+domain:
            raise ValueError("Invalid website configuration.")
        php_sockets=list(pathlib.Path("/run/php").glob("php*-fpm.sock"))
        php_socket=str(php_sockets[0]) if php_sockets else "/run/php/php8.3-fpm.sock"
        pathlib.Path(config_path).write_text(ssl_http_config(domain,root).replace("PHP_SOCKET",php_socket))
        run_checked(["nginx","-t"])
        run_checked(["systemctl","reload","nginx"])
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{
            "status":"completed","result":{"ssl_enabled":False,"domain":domain}
        },auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)

def mysql_identifier(value, max_len):
    import re
    if not re.fullmatch(r"[A-Za-z0-9_]+", value) or len(value) > max_len:
        raise ValueError("Invalid MySQL identifier.")
    return "`" + value.replace("`","``") + "`"

def mysql_string(value):
    return "'" + str(value).replace("\\","\\\\").replace("'","\\'") + "'"

def mysql_command(sql):
    if not shutil.which("mysql"):
        raise ValueError("The mysql client is not installed on this server.")
    return run_checked(["mysql","--protocol=socket","-Nse",sql])

def complete_create_database(job, base, auth):
    p=job.get("payload") or {}
    name=str(p.get("database_name",""))
    username=str(p.get("username",""))
    password=str(p.get("password",""))
    try:
        db=mysql_identifier(name,64)
        mysql_identifier(username,32)
        if len(password) < 12 or len(password) > 128:
            raise ValueError("Database password must be between 12 and 128 characters.")
        sql=("CREATE DATABASE IF NOT EXISTS "+db+" CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
             "CREATE USER IF NOT EXISTS "+mysql_string(username)+"@'localhost' IDENTIFIED BY "+mysql_string(password)+";"
             "ALTER USER "+mysql_string(username)+"@'localhost' IDENTIFIED BY "+mysql_string(password)+";"
             "GRANT ALL PRIVILEGES ON "+db+".* TO "+mysql_string(username)+"@'localhost';"
             "FLUSH PRIVILEGES;")
        mysql_command(sql)
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"completed","result":{"operation":"create_database","database_name":name,"username":username}},auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)

def complete_delete_database(job, base, auth):
    p=job.get("payload") or {}
    name=str(p.get("database_name",""))
    username=str(p.get("username",""))
    try:
        db=mysql_identifier(name,64)
        mysql_identifier(username,32)
        mysql_command("DROP DATABASE IF EXISTS "+db+"; DROP USER IF EXISTS "+mysql_string(username)+"@'localhost'; FLUSH PRIVILEGES;")
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"completed","result":{"operation":"delete_database","database_name":name,"username":username}},auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)
def dns_zone_path(domain):
    if not valid_domain(domain):
        raise ValueError("Invalid DNS domain.")
    return pathlib.Path("/etc/bind/zones")/(domain+".db")


def dns_escape(value):
    return str(value).replace("\\", "\\\\").replace('"', '\\"')


def dns_record_line(record):
    rtype=str(record.get("type","")).upper()
    name=str(record.get("name","")).strip()
    value=str(record.get("value","")).strip()
    ttl=int(record.get("ttl",3600))
    priority=record.get("priority")
    if rtype not in {"A","AAAA","CNAME","MX","TXT","NS","CAA","SRV","SVCB","HTTPS"}:
        raise ValueError("Unsupported DNS record type.")
    if not name or any(x in name for x in ["\n","\r"," ","\t"]):
        raise ValueError("Invalid DNS record name.")
    if not value or any(x in value for x in ["\n","\r"]):
        raise ValueError("Invalid DNS record value.")
    if ttl < 60 or ttl > 86400:
        raise ValueError("DNS TTL must be between 60 and 86400.")
    if rtype in {"MX","SRV"}:
        if priority is None:
            raise ValueError(rtype+" priority is required.")
        priority=int(priority)
        if priority < 0 or priority > 65535:
            raise ValueError("Invalid DNS priority.")
        value=str(priority)+" "+value
    if rtype == "TXT":
        value='"'+dns_escape(value)+'"'
    return f"{name} {ttl} IN {rtype} {value}"


def ensure_dns_zone(domain):
    if not valid_domain(domain):
        raise ValueError("Invalid DNS domain.")
    if not shutil.which("named-checkzone"):
        raise ValueError("BIND9 is not installed. Install bind9 and bind9-utils, then retry.")
    zones=pathlib.Path("/etc/bind/zones")
    zones.mkdir(parents=True,exist_ok=True)
    zone=zones/(domain+".db")
    local_conf=pathlib.Path("/etc/bind/named.conf.local")
    if not local_conf.exists():
        raise ValueError("BIND9 configuration /etc/bind/named.conf.local was not found.")
    config='zone "'+domain+'" { type master; file "'+str(zone)+'"; };'
    conf=local_conf.read_text(encoding="utf-8")
    if config not in conf:
        with local_conf.open("a",encoding="utf-8") as handle:
            handle.write("\n\n// Managed by mPanel\n"+config+"\n")
    if not zone.exists():
        serial=int(time.strftime("%Y%m%d%H"))
        initial=(
            "$TTL 3600\n"
            "@ IN SOA ns1."+domain+". hostmaster."+domain+". (\n"
            "  "+str(serial)+" ; serial\n"
            "  3600 ; refresh\n"
            "  900 ; retry\n"
            "  1209600 ; expire\n"
            "  300 ; negative cache\n"
            ")\n"
            "@ IN NS ns1."+domain+".\n"
            "@ IN NS ns2."+domain+".\n"
        )
        zone.write_text(initial,encoding="utf-8")
    return zone


def write_zone_safely(domain, zone, lines):
    original=zone.read_text(encoding="utf-8")
    serial=int(time.strftime("%Y%m%d%H"))
    updated=[]
    serial_replaced=False
    for line in lines:
        if not serial_replaced and "; serial" in line:
            import re
            match=re.search(r"(\d+)\s*; serial",line)
            if match:
                serial=max(serial,int(match.group(1))+1)
            updated.append("  "+str(serial)+" ; serial")
            serial_replaced=True
        else:
            updated.append(line)
    if not serial_replaced:
        updated=lines
    temp=zone.with_suffix(".db.mpanel-tmp")
    temp.write_text("\n".join(updated)+"\n",encoding="utf-8")
    try:
        run_checked(["named-checkzone",domain,str(temp)])
        run_checked(["named-checkconf"])
        os.replace(temp,zone)
        run_checked(["systemctl","reload","bind9"])
    except Exception:
        temp.unlink(missing_ok=True)
        zone.write_text(original,encoding="utf-8")
        raise


def complete_dns_upsert(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    try:
        zone=ensure_dns_zone(domain)
        line=dns_record_line(p)
        records=zone.read_text(encoding="utf-8").splitlines()
        if line not in records:
            records.append(line)
        write_zone_safely(domain,zone,records)
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"completed","result":{"operation":"dns_upsert","record_id":p.get("record_id"),"domain":domain}},auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)


def complete_dns_delete(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    try:
        zone=dns_zone_path(domain)
        if not zone.exists():
            raise ValueError("DNS zone is not managed by mPanel on this server.")
        target=dns_record_line(p)
        lines=zone.read_text(encoding="utf-8").splitlines()
        if target not in lines:
            raise ValueError("DNS record was not found in the zone; no change was made.")
        lines.remove(target)
        write_zone_safely(domain,zone,lines)
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"completed","result":{"operation":"dns_delete","record_id":p.get("record_id"),"domain":domain}},auth)
    except Exception as e:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)

def valid_git_repository(url):
    import re
    return bool(re.fullmatch(r"https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(?:\.git)?", url))

def complete_git_deploy(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    root=str(p.get("deploy_path",""))
    repo=str(p.get("repository_url","")).strip()
    branch=str(p.get("branch","main")).strip()
    temp_root=pathlib.Path("/tmp/mpanel-git-"+str(job["id"]))
    try:
        if not valid_domain(domain) or root != "/var/www/"+domain:
            raise ValueError("Invalid website deployment path.")
        if not valid_git_repository(repo):
            raise ValueError("Only public GitHub HTTPS repositories are supported.")
        if not branch or any(ch in branch for ch in ["\n","\r"," ","'","\""]):
            raise ValueError("Invalid Git branch.")
        if temp_root.exists():
            shutil.rmtree(temp_root)
        temp_root.parent.mkdir(parents=True,exist_ok=True)
        run_checked(["git","clone","--depth","1","--branch",branch,repo,str(temp_root)])
        commit=run_checked(["git","-C",str(temp_root),"rev-parse","HEAD"]).stdout.strip()
        root_path=pathlib.Path(root)
        root_path.mkdir(parents=True,exist_ok=True)
        preserved=root_path/".well-known"
        preserved_backup=None
        if preserved.exists():
            preserved_backup=temp_root.parent/(temp_root.name+"-well-known")
            shutil.copytree(preserved,preserved_backup)
        for item in root_path.iterdir():
            if item.name == ".well-known":
                continue
            if item.is_dir() and not item.is_symlink():
                shutil.rmtree(item)
            else:
                item.unlink()
        for item in temp_root.iterdir():
            if item.name == ".git":
                continue
            target=root_path/item.name
            if item.is_dir():
                shutil.copytree(item,target,symlinks=False)
            else:
                shutil.copy2(item,target)
        if preserved_backup and preserved_backup.exists() and not preserved.exists():
            shutil.copytree(preserved_backup,preserved)
        shutil.rmtree(temp_root,ignore_errors=True)
        if preserved_backup:
            shutil.rmtree(preserved_backup,ignore_errors=True)
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{
            "status":"completed",
            "result":{"operation":"git_deploy","commit":commit,"branch":branch,"repository":repo}
        },auth)
    except Exception as e:
        shutil.rmtree(temp_root,ignore_errors=True)
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",{"status":"failed","error":str(e)},auth)

def complete_create_site(job, base, auth):
    p=job.get("payload") or {}
    domain=str(p.get("domain","")).lower().strip()
    root=str(p.get("document_root",""))
    php=str(p.get("php_version",""))
    config_dir=pathlib.Path("/etc/nginx/sites-available")
    enabled_dir=pathlib.Path("/etc/nginx/sites-enabled")
    config_path=config_dir/domain
    enabled_path=enabled_dir/domain
    created_config=False
    created_link=False

    try:
        if not valid_domain(domain):
            raise ValueError("Invalid domain supplied.")
        if root != "/var/www/"+domain:
            raise ValueError("Invalid document root.")
        if php not in {"8.2","8.3","8.4"}:
            raise ValueError("Unsupported PHP version.")

        php_socket="/run/php/php"+php+"-fpm.sock"
        if not os.path.exists(php_socket):
            raise ValueError("PHP-FPM socket not found for PHP "+php+".")

        root_path=pathlib.Path(root)
        root_path.mkdir(parents=True, exist_ok=True)
        index_path=root_path/"index.html"
        if not index_path.exists():
            index_path.write_text(
                "<!doctype html><html><head><meta charset=\"utf-8\"><title>"+domain+
                "</title></head><body><h1>"+domain+
                "</h1><p>Website provisioned by mPanel.</p></body></html>\n",
                encoding="utf-8"
            )

        nginx_config = """server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN www.DOMAIN;
    root ROOT;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:SOCKET;
    }
}
""".replace("DOMAIN", domain).replace("ROOT", root).replace("SOCKET", php_socket)

        config_dir.mkdir(parents=True, exist_ok=True)
        enabled_dir.mkdir(parents=True, exist_ok=True)

        # Never overwrite a manually managed or unrelated site configuration.
        if os.path.lexists(config_path):
            existing=config_path.read_text(encoding="utf-8") if config_path.is_file() and not config_path.is_symlink() else ""
            if existing != nginx_config:
                raise ValueError("Nginx site configuration already exists and differs from mPanel's expected configuration; refusing to overwrite it.")
        else:
            temp_path=config_dir/("."+domain+".mpanel-tmp")
            temp_path.write_text(nginx_config, encoding="utf-8")
            os.replace(temp_path, config_path)
            created_config=True

        if os.path.lexists(enabled_path):
            if not os.path.islink(enabled_path) or os.path.realpath(enabled_path) != str(config_path):
                raise ValueError("Existing Nginx site link conflicts with the requested site.")
        else:
            os.symlink(str(config_path), enabled_path)
            created_link=True

        try:
            run_checked(["nginx","-t"])
            run_checked(["systemctl","reload","nginx"])
        except Exception:
            if created_link and os.path.islink(enabled_path):
                os.unlink(enabled_path)
            if created_config and config_path.exists():
                os.unlink(config_path)
            raise

    except Exception as e:
        error=str(e)
        try:
            request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                    {"status":"failed","error":error},auth)
        except Exception as report_error:
            print("[mPanel] could not report create_site failure:",report_error,flush=True)
        print("[mPanel] create_site job failed:",error,flush=True)
        return

    # Do not mark a successful provision failed just because the completion API
    # is temporarily unavailable. A stale claimed job will be retried later.
    try:
        request(base+"/api/agent/jobs/"+str(job["id"])+"/complete", {
            "status":"completed",
            "result":{
                "website_id":p.get("website_id"),
                "domain":domain,
                "document_root":root,
                "php_version":php,
                "config_path":str(config_path),
                "mode":"provisioned"
            }
        }, auth)
    except Exception as report_error:
        print("[mPanel] site provisioned but completion could not be reported; it will be retried: "+str(report_error),flush=True)

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
            elif job["type"] == "git_deploy":
                complete_git_deploy(job, base, auth)
            elif job["type"] == "dns_upsert":
                complete_dns_upsert(job, base, auth)
            elif job["type"] == "dns_delete":
                complete_dns_delete(job, base, auth)
            elif job["type"] == "create_database":
                complete_create_database(job, base, auth)
            elif job["type"] == "delete_database":
                complete_delete_database(job, base, auth)
            elif job["type"] == "issue_ssl":
                complete_issue_ssl(job, base, auth)
            elif job["type"] == "disable_ssl":
                complete_disable_ssl(job, base, auth)
            elif job["type"] == "list_files":
                complete_list_files(job, base, auth)
            elif job["type"] == "file_operation":
                complete_file_operation(job, base, auth)
            elif job["type"] == "download_file":
                complete_download_file(job, base, auth)
            elif job["type"] == "upload_file":
                complete_upload_file(job, base, auth)
            else:
                result={"message":"Operation not implemented by this Agent version."}
                request(base+"/api/agent/jobs/"+str(job["id"])+"/complete",
                        {"status":"failed","result":result,"error":"Unsupported operation: "+job["type"]},auth)
    except Exception as e:
        print("[mPanel] agent cycle failed:",e,flush=True)
    time.sleep(max(5,a.interval))
