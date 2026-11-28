# Runs Python challenges (see app/Challenges/Running).
FROM python:3.13-alpine
COPY --chmod=755 <<'SH' /runner/run
#!/bin/sh
# The program arrives on stdin as a tar archive.
set -e
mkdir -p /tmp/code && cd /tmp/code && tar -xf -
exec python3 -I -B -u main.py
SH
ENV HOME=/tmp PYTHONDONTWRITEBYTECODE=1
USER 65534:65534
