# mPanel Agent v0.4

The Agent is a telemetry and controlled provisioning service for Ubuntu/Linux servers.

## First installation

1. Copy the `agent/` directory to the VPS.
2. Run:

```bash
sudo bash install.sh
```

3. Edit `/opt/mpanel-agent/mpanel-agent.env`:

```text
MPANEL_URL=https://your-mpanel-domain.example
MPANEL_SERVER_ID=YOUR_SERVER_ID
MPANEL_PAIRING_TOKEN=YOUR_ONE_TIME_TOKEN
MPANEL_INTERVAL=30
```

4. Start:

```bash
sudo systemctl start mpanel-agent
sudo systemctl status mpanel-agent
```

The Agent exchanges the one-time pairing token for a permanent Agent credential. The permanent credential is stored locally at `/opt/mpanel-agent/agent-token` with mode 0600.

After successful pairing, the pairing token is no longer needed. It may be removed from `mpanel-agent.env`.

## Heartbeats

The Agent reports CPU, memory and root-disk utilization every 30 seconds.

## Security

The Agent only accepts explicitly allowlisted job types. The current `create_site` operation validates the requested domain, document root and PHP version and returns the generated Nginx configuration for the control plane. It does not yet modify Nginx or the filesystem.

Use HTTPS in production. Never commit pairing or Agent credentials to Git.
