# mPanel Agent v0.1
Telemetry-only Ubuntu/Linux agent.

Install:
sudo bash install.sh

Edit /opt/mpanel-agent/mpanel-agent.env:
MPANEL_URL=https://your-mpanel-domain.example
MPANEL_SERVER_ID=YOUR_SERVER_ID
MPANEL_AGENT_TOKEN=YOUR_TOKEN
MPANEL_INTERVAL=30

Then:
sudo systemctl start mpanel-agent
sudo systemctl status mpanel-agent

The Agent reports CPU, memory and root-disk utilization every 30 seconds. It does not execute commands received from mPanel in v0.1. Keep the token out of source control and protect the environment file.
