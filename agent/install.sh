#!/usr/bin/env bash
set -euo pipefail

INSTALL_DIR="/opt/mpanel-agent"
SERVICE="/etc/systemd/system/mpanel-agent.service"
RENEW_SERVICE="/etc/systemd/system/mpanel-ssl-renew.service"
RENEW_TIMER="/etc/systemd/system/mpanel-ssl-renew.timer"

if [[ "\${EUID}" -ne 0 ]]; then
    echo "Run as root: sudo bash install.sh"
    exit 1
fi

required_commands=(python3 git nginx)
for command in "\${required_commands[@]}"; do
    if ! command -v "\$command" >/dev/null 2>&1; then
        echo "Missing required dependency: \$command"
        echo "Install it with: sudo apt install \$command"
        exit 1
    fi
done

SOURCE_DIR="\$(cd "\$(dirname "\$0")" && pwd)"
mkdir -p "\$INSTALL_DIR"
install -m 0755 "\$SOURCE_DIR/mpanel-agent.py" "\$INSTALL_DIR/mpanel-agent.py"

if [[ ! -f "\$INSTALL_DIR/mpanel-agent.env" ]]; then
    cat > "\$INSTALL_DIR/mpanel-agent.env" <<'EOF'
MPANEL_URL=
MPANEL_SERVER_ID=
MPANEL_PAIRING_TOKEN=
MPANEL_INTERVAL=30
EOF
fi
chmod 0600 "\$INSTALL_DIR/mpanel-agent.env"

cat > "\$SERVICE" <<'EOF'
[Unit]
Description=mPanel Server Agent
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
EnvironmentFile=/opt/mpanel-agent/mpanel-agent.env
ExecStart=/usr/bin/python3 /opt/mpanel-agent/mpanel-agent.py --url \${MPANEL_URL} --server-id \${MPANEL_SERVER_ID} --pairing-token \${MPANEL_PAIRING_TOKEN} --interval \${MPANEL_INTERVAL}
Restart=always
RestartSec=5
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/opt/mpanel-agent /var/www /etc/nginx/sites-available /etc/nginx/sites-enabled /etc/letsencrypt /var/lib/letsencrypt /var/log/letsencrypt /run/nginx.pid

[Install]
WantedBy=multi-user.target
EOF

cat > "\$RENEW_SERVICE" <<'EOF'
[Unit]
Description=mPanel Let's Encrypt certificate renewal
After=network-online.target
Wants=network-online.target

[Service]
Type=oneshot
ExecStart=/bin/sh -c 'if command -v certbot >/dev/null 2>&1; then certbot renew --quiet --deploy-hook "/bin/systemctl reload nginx"; fi'
EOF

cat > "\$RENEW_TIMER" <<'EOF'
[Unit]
Description=mPanel Let's Encrypt certificate renewal check

[Timer]
OnBootSec=15min
OnUnitActiveSec=12h
Persistent=true

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable mpanel-agent
systemctl enable mpanel-ssl-renew.timer

echo
echo "mPanel Agent installed successfully."
echo
echo "Configuration:"
echo "  \$INSTALL_DIR/mpanel-agent.env"
echo
echo "Edit that file with your mPanel URL, Server ID and pairing token."
echo
echo "Then run:"
echo "  sudo systemctl start mpanel-agent"
echo "  sudo systemctl status mpanel-agent"
echo "  sudo journalctl -u mpanel-agent -f"
