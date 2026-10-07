# mPanel Agent

The mPanel Agent is the privileged automation layer that allows the mPanel Laravel control plane to manage an Ubuntu/Linux server.

Current Agent version: **0.4.1**

## Local Ubuntu installation

For the first mPanel test, the control panel and Agent can run on the same Ubuntu machine. No VPS or public IP is required.

### 1. Clone or update mPanel

\`\`\`bash
git clone https://github.com/MrPie2/mpanel.git
cd mpanel
\`\`\`

If the repository is already present:

\`\`\`bash
cd mpanel
git pull
\`\`\`

### 2. Install the Agent

\`\`\`bash
cd agent
sudo bash install.sh
\`\`\`

The installer checks for the core dependencies:

- Python 3
- Git
- Nginx
- systemd

Feature-specific software such as PHP-FPM, MariaDB/MySQL, BIND9 and Certbot is only required when those mPanel features are tested.

The Agent is installed under:

\`\`\`text
/opt/mpanel-agent/
├── mpanel-agent.py
├── mpanel-agent.env
└── agent-token
\`\`\`

The permanent Agent token is created after successful pairing and stored with mode \`0600\`.

### 3. Run mPanel locally

From the Laravel project root:

\`\`\`bash
php artisan migrate
php artisan db:seed
php artisan serve
\`\`\`

Open:

\`\`\`text
http://127.0.0.1:8000
\`\`\`

The development seed account is:

\`\`\`text
Email:    test@example.com
Password: password
\`\`\`

Do not use the seed password for a production installation.

### 4. Register the Ubuntu server

In mPanel:

**Servers → Add Server**

Create a server entry for the Ubuntu machine. mPanel will generate a one-time pairing token.

For a same-machine test, the control plane URL is:

\`\`\`text
http://127.0.0.1:8000
\`\`\`

### 5. Configure the Agent

Edit:

\`\`\`bash
sudo nano /opt/mpanel-agent/mpanel-agent.env
\`\`\`

Set:

\`\`\`text
MPANEL_URL=http://127.0.0.1:8000
MPANEL_SERVER_ID=YOUR_SERVER_ID
MPANEL_PAIRING_TOKEN=YOUR_ONE_TIME_TOKEN
MPANEL_INTERVAL=30
\`\`\`

Do not commit this file or share the pairing token.

### 6. Start the Agent

\`\`\`bash
sudo systemctl start mpanel-agent
sudo systemctl status mpanel-agent
\`\`\`

For live logs:

\`\`\`bash
sudo journalctl -u mpanel-agent -f
\`\`\`

A successful first connection should report a pairing success and then periodic heartbeats.

After pairing, the permanent credential is stored at:

\`\`\`text
/opt/mpanel-agent/agent-token
\`\`\`

The pairing token is one-time and can be removed from \`mpanel-agent.env\` after successful pairing.

## Agent capabilities

The current Agent supports controlled jobs for:

- Server heartbeat/telemetry
- Website provisioning through Nginx
- GitHub deployment
- DNS record operations through BIND9
- MySQL/MariaDB database operations
- Let's Encrypt SSL
- File listing and file operations
- File upload/download

## Feature prerequisites

Install only what you need for the feature being tested:

\`\`\`bash
sudo apt install -y php-fpm
sudo apt install -y mariadb-server
sudo apt install -y bind9 bind9utils
sudo apt install -y certbot
\`\`\`

The Agent expects PHP-FPM, MariaDB/MySQL, BIND9 or Certbot to be present when their corresponding jobs are used.

## Security

- Never commit pairing or Agent credentials.
- Use HTTPS when the control plane is exposed beyond localhost.
- The Agent accepts only explicitly supported job types.
- Website paths are restricted to \`/var/www/{domain}\`.
- The systemd service uses \`ProtectSystem\`, \`ProtectHome\`, \`NoNewPrivileges\` and explicit writable paths.
