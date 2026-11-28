# Runs Go challenges (see app/Challenges/Running). The build cache is warmed
# here, so a run compiles only the user's code.
FROM golang:1.25-alpine
ENV GOCACHE=/opt/gocache GOPATH=/tmp/go GOTOOLCHAIN=local GOFLAGS=-buildvcs=false CGO_ENABLED=0 HOME=/tmp
RUN mkdir -p /tmp/warm && cd /tmp/warm \
 && printf 'package main\nimport (\n_ "bufio"\n_ "errors"\n"fmt"\n_ "maps"\n_ "math"\n_ "os"\n_ "slices"\n_ "sort"\n_ "strconv"\n_ "strings"\n_ "unicode"\n_ "container/heap"\n_ "container/list"\n)\nfunc main() { fmt.Println() }\n' > main.go \
 && go build -o /tmp/warm/app main.go && rm -rf /tmp/warm /tmp/go \
 && chmod -R a+rwX /opt/gocache
COPY --chmod=755 <<'SH' /runner/run
#!/bin/sh
# The program arrives on stdin as a tar archive.
set -e
mkdir -p /tmp/code && cd /tmp/code && tar -xf -
cp -r /opt/gocache /tmp/gocache
export GOCACHE=/tmp/gocache
go build -o /tmp/app main.go solution.go
exec /tmp/app
SH
USER 65534:65534
