# GenZ NewZ SSH Portal Setup

This enables a read-only terminal portal so users can SSH in and browse categories/articles.

## What was added
- Artisan command: `portal:ssh`
- Wrapper script: `scripts/ssh-portal.sh`

## Manual test
From the project root:

```bash
php artisan portal:ssh
```

## Recommended SSH setup (restricted portal user)

1. Create a dedicated Linux user (no shell access beyond the portal):

```bash
sudo useradd -m -d /home/newsportal -s /bin/bash newsportal
sudo mkdir -p /home/newsportal/.ssh
sudo chmod 700 /home/newsportal/.ssh
sudo chown -R newsportal:newsportal /home/newsportal/.ssh
```

2. Add public keys to `/home/newsportal/.ssh/authorized_keys`.

3. Force the SSH session to run the portal command only:

```text
command="/home/genznewz/htdocs/genznewz.com/scripts/ssh-portal.sh",no-agent-forwarding,no-port-forwarding,no-pty,no-user-rc,no-X11-forwarding ssh-ed25519 AAAA... user@host
```

4. Optional: if you want interactive TTY menus, remove `no-pty` from the key options.

5. Restart SSH service if needed:

```bash
sudo systemctl restart sshd
```

## User access
Users connect with:

```bash
ssh newsportal@genznewz.com
```

They will land directly in the GenZ NewZ terminal portal and can:
- View latest headlines
- View featured stories
- Browse by category
- Search articles
- Read full article text

## Safety notes
- This does **not** change `tv.genznewz.com`.
- Portal is read-only (no publishing/editing actions).
- Keep this portal under a dedicated SSH user instead of reusing admin users.
