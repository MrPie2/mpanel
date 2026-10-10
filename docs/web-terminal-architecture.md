# mPanel Web Terminal — implementation notes

## Current status

The Laravel terminal workspace is a UI and authorization foundation only. It deliberately does not execute commands, create agent jobs for shell input, or expose a command endpoint. Keep the session-start control disabled until the isolation prerequisites below are implemented and verified on a real Linux host.

## Existing architecture observed in the repository

- Laravel authenticates users and owns the website/server records.
- Website ownership is represented by `websites.server_id` and `servers.user_id`.
- Website document roots are currently provisioned as `/var/www/<domain>`.
- The Python agent polls Laravel for server-management jobs and the systemd unit is installed without a dedicated `User=` setting, so it runs with elevated privileges.
- The current website provisioner creates the document root and Nginx vhost but does not create a dedicated Linux user, apply a per-site ownership policy, or create a per-site PHP-FPM pool.
- The current Nginx template points websites at a shared PHP-FPM socket. A terminal restricted only by its working directory would therefore not be a security boundary.
- The agent can read and modify website files as a privileged service. That existing management path must remain separate from interactive shell execution.

## Blocking prerequisite: establish website isolation

Before enabling interactive terminals, introduce and test a site execution identity model. For each website, provision a stable, non-root Linux identity (or a separate container identity) and record its mapping to the mPanel website ID. The identity must not be selected from a client-supplied username, path, or command.

The provisioning migration must be designed for existing sites as well as new sites:

1. Define a deterministic, collision-resistant Linux username from the internal website ID, and verify it against the OS before use.
2. Assign a documented ownership and permission model to the website's writable files. Do not recursively change ownership blindly on a live server; inspect existing files and provide a reversible migration with a dry-run/report phase.
3. Ensure the site's PHP runtime has matching isolation (for example, a dedicated PHP-FPM pool running as the site identity) before claiming shell isolation. A site shell must not be able to read another site's secrets or write to another site's document root.
4. Keep mPanel-managed operations such as Nginx changes, TLS issuance, DNS, and account provisioning in the privileged agent. Those operations must validate server-side identifiers and paths and must never accept arbitrary shell strings.
5. Add a feature/readiness state so a site cannot start a terminal session until its identity, filesystem permissions, and runtime isolation have all passed server-side checks.

Do not enable terminal sessions on the current shared-PHP-FPM configuration.

## Read-only agent preflight added

The Python agent now recognizes the internal `terminal_isolation_audit` job type. Its payload is expected to contain the server-side `website_id` and validated `domain`. The agent derives the proposed Linux username from the numeric website ID; it does not accept a username or filesystem path from the caller.

The audit checks, without changing the host:

- that `/var/www/<domain>` exists as the expected canonical directory and is not itself a symlink;
- whether the deterministic non-root site account exists;
- whether the document-root owner and write permissions match the initial policy;
- whether a per-site PHP-FPM pool declares the expected user/group;
- whether the Nginx vhost references that pool's socket.

It always reports terminal readiness as blocked because the interactive gateway and resource-enforced PTY sandbox are not implemented. It does not create users, change permissions, rewrite Nginx/PHP-FPM configuration, or mark a website ready. A successful job completion means only that the read-only audit ran—not that terminal execution is safe.

The authenticated terminal workspace now exposes a “Run isolation audit” action. Laravel checks that the website belongs to the signed-in user, creates the job using the website's stored server ID/domain, and displays the latest job report. The completion handler validates the report shape, requires all six expected check names exactly once, requires `read_only: true`, `status: blocked`, `ready: false`, and a failing gateway/sandbox check, and verifies that the reported Linux username exactly matches the deterministic username for that website ID. It only records a valid report as `blocked` and clears `terminal_ready_at`; it cannot promote a website to ready. The report is available after the agent processes the queued job, so the page may need refreshing. Runtime tests and a live Linux-host audit have not yet been run.

## Target terminal architecture

1. The authenticated Laravel controller authorizes the requested website through its server owner before issuing a short-lived, single-use session ticket.
2. The browser opens a WSS connection to a dedicated terminal gateway using that ticket. Validate ticket signature, expiry, one-time use, website ID, user ID, server ID, and allowed origin. Never place a long-lived API or agent token in browser JavaScript.
3. The gateway accepts only a website ID and a valid ticket—not a shell command, username, executable, or filesystem path from the browser.
4. The gateway resolves the website ID to the server-side configured Linux identity and canonical document root. It launches a PTY as that unprivileged identity with a minimal environment and a controlled initial directory.
5. Use OS-enforced isolation in addition to UID separation where available (for example, a container or systemd scope with filesystem, process, memory, CPU, and syscall restrictions). A working-directory restriction alone is insufficient.
6. Enforce per-user and per-site session concurrency, idle and absolute timeouts, bounded input/output, process and resource limits, and audit events for session open/close and policy violations. Do not log passwords, tokens, or terminal input by default.
7. Terminate the PTY process group when the WebSocket closes, the ticket expires, or a session limit is reached. Run the gateway as a dedicated unprivileged service and expose it only through a trusted TLS-terminating proxy.

## Required security tests before release

- User A cannot open User B's website terminal by changing a URL, website ID, or WebSocket payload.
- A site identity cannot read or modify another site's files, credentials, environment files, or application secrets.
- The session cannot gain root privileges, access the agent token, control the host service manager, or reach the privileged agent's job queue.
- Expired and replayed tickets are rejected; invalid origins and unauthenticated WebSocket connections are rejected.
- Closing the browser reliably terminates the PTY process group; idle, maximum duration, concurrency, and resource limits are enforced.
- Malformed frames and excessive output do not crash the gateway or exhaust memory/CPU.
- Existing file-manager, Git deploy, SSL, DNS, and provisioning workflows continue to work after the isolation migration.

## Current routes

- `GET /terminal` lists websites owned through the user's servers.
- `GET /websites/{website}/terminal` displays the terminal workspace after checking ownership.
- `POST /websites/{website}/terminal/audit` queues a read-only isolation audit after checking website ownership.

The start-session control is intentionally disabled until the gateway and the site isolation model are implemented, deployed, and tested. This PR does not claim that interactive shell execution is available.

## Readiness tracking added in the current branch

The `websites` table now has three fields to support a staged rollout:

- `terminal_linux_user`: the server-side managed Linux identity assigned to this website; it must never come from browser input.
- `terminal_isolation_status`: defaults to `pending`; this branch does not yet have an agent operation that can mark it ready.
- `terminal_ready_at`: timestamp to record when all required isolation checks have passed.

The UI displays the current isolation status. A default value of `pending` is intentionally fail-closed. Do not set a website to ready manually just to enable the terminal: the future agent implementation must verify the OS account, canonical document root, ownership and permissions, per-site PHP-FPM pool, and sandbox policy before reporting readiness.
