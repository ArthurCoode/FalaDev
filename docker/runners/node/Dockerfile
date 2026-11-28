# Runs JavaScript and TypeScript challenges (see app/Challenges/Running).
FROM node:24-alpine
COPY --chmod=755 <<'SH' /runner/run
#!/bin/sh
# The program arrives on stdin as a tar archive.
set -e
mkdir -p /tmp/code && cd /tmp/code && tar -xf -
exec node --disable-warning=ExperimentalWarning --stack-size=4000 main.js
SH
ENV HOME=/tmp NODE_ENV=production
USER 65534:65534
