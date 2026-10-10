# mPanel Web Terminal — implementation notes

## Current status

The Laravel terminal workspace is a UI and authorization foundation only. It deliberately does not execute commands, create agent jobs for shell input, or expose a command endpoint.

## Existing architecture observed

- The Laravel app authenticates users and owns the website/server records.
- Website ownership is represented by `websites.server_id` and `servers.user_id`.
- The Python agent polls the Laravel API for privileged server-management jobs.
- The installed agent service currently runs with elevated privileges and uses systemd hardening options. It must never execute user-supplied shell commands as the agent service account.

## Required before enabling terminal sessions

1. Create a dedicated terminal gateway/service with authenticated, short-lived session tickets.
2. Authorize the website against the signed-in Laravel user before issuing a ticket.
3. Run each shell as a dedicated unprivileged website account or inside a properly configured sandbox/container; do not rely on a working-directory restriction alone.
4. Enforce filesystem boundaries, process/CPU/memory limits, session timeouts, concurrency limits, and audit logging.
5. Use an authenticated WebSocket with origin checks; do not expose the gateway directly to the public internet without a trusted proxy and TLS.
6. Keep the privileged agent job queue separate from the interactive terminal execution path.
7. Test cross-account filesystem access, privilege escalation attempts, session termination, and resource exhaustion before enabling the feature.

## Current routes

- `GET /terminal` lists websites owned through the user's servers.
- `GET /websites/{website}/terminal` displays the terminal workspace after checking ownership.

The start-session control is intentionally disabled until the gateway is implemented.
