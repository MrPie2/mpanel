#!/usr/bin/env bash
set -euo pipefail
INSTALL_DIR="/opt/mpanel-agent"
SERVICE="/etc/systemd/system/mpanel-agent.service"
if [[ "$"+"{EUID}" -ne 0 ]]; then echo "Run as root: sudo bash install.sh"; exit 1; fi
command -v python3 >/dev/null || { echo "python3 is required."; exit 1; }
mkdir -p "$INSTALL_DIR"
install -m 0755 "$(dirname "$0")/mpanel-agent.py" "$INSTALL_DIR/mpanel-agent.py"
cat > "$INSTALL_DIR/mpanel-agent.env" <<'EOF'
MPANEL_URL=
MPANEL_SERVER_ID=
MPANEL_AGENT_TOKEN=
MPANEL_INTERVAL=30
EOF
chmod 0600 "$INSTALL_DIR/mpanel-agent.env"
cat > "$SERVICE" <<'EOF'
[Unit]
Description=mPanel Server Agent
After=network-online.target
Wants=network-online.target
[Service]
Type=simple
EnvironmentFile=/opt/mpanel-agent/mpanel-agent.env
ExecStart=/usr/bin/python3 /opt/mpanel-agent/mpanel-agent.py --url $"+"{MPANEL_URL} --server-id $"+"{MPANEL_SERVER_ID} --token $"+"{MPANEL_AGENT_TOKEN} --interval $"+"{MPANEL_INTERVAL}
Restart=always
RestartSec=5
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable mpanel-agent
echo "Installed. Edit $INSTALL_DIR/mpanel-agent.env then run: systemctl start mpanel-agent"
