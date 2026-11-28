# Runs PHP challenges (see app/Challenges/Running).
FROM php:8.4-cli-alpine
COPY --chmod=755 <<'SH' /runner/run
#!/bin/sh
# The program arrives on stdin as a tar archive.
set -e
mkdir -p /tmp/code && cd /tmp/code && tar -xf -
exec php -n -d display_errors=stderr -d error_reporting=-1 -d memory_limit=192M -d zend.assertions=-1 main.php
SH
ENV HOME=/tmp
USER 65534:65534
