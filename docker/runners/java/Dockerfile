# Runs Java challenges (see app/Challenges/Running).
FROM eclipse-temurin:21-jdk-alpine
COPY --chmod=755 <<'SH' /runner/run
#!/bin/sh
# The program arrives on stdin as a tar archive.
set -e
mkdir -p /tmp/code /tmp/out && cd /tmp/code && tar -xf -
javac -J-XX:+UseSerialGC -J-XX:TieredStopAtLevel=1 -J-Xshare:auto -nowarn -encoding UTF-8 -d /tmp/out Main.java Solution.java
exec java -XX:+UseSerialGC -XX:TieredStopAtLevel=1 -Xshare:auto -Xss16m -Xmx192m -cp /tmp/out Main
SH
ENV HOME=/tmp
USER 65534:65534
