# Runs C# challenges (see app/Challenges/Running). Calls the Roslyn compiler
# directly: much faster than "dotnet build" for a two-file program.
FROM mcr.microsoft.com/dotnet/sdk:9.0-alpine
ENV DOTNET_CLI_TELEMETRY_OPTOUT=1 DOTNET_NOLOGO=1 DOTNET_SKIP_FIRST_TIME_EXPERIENCE=1 DOTNET_CLI_HOME=/tmp HOME=/tmp DOTNET_TieredPGO=0 DOTNET_EnableWriteXorExecute=0
RUN set -e; mkdir -p /runner; \
    csc=$(ls -d /usr/share/dotnet/sdk/*/Roslyn/bincore/csc.dll | head -n1); \
    refs=$(ls -d /usr/share/dotnet/packs/Microsoft.NETCore.App.Ref/*/ref/net*/ | head -n1); \
    runtime=$(ls /usr/share/dotnet/shared/Microsoft.NETCore.App/ | head -n1); \
    echo "$csc" > /runner/csc-path; \
    { echo "-nologo"; echo "-target:exe"; echo "-langversion:latest"; echo "-nullable:disable"; echo "-warn:0"; echo "-unsafe-"; \
      for dll in "$refs"*.dll; do echo "-r:$dll"; done; } > /runner/csc.rsp; \
    printf '{"runtimeOptions":{"tfm":"net9.0","framework":{"name":"Microsoft.NETCore.App","version":"%s"}}}' "$runtime" > /runner/app.runtimeconfig.json
COPY --chmod=755 <<'SH' /runner/run
#!/bin/sh
# The program arrives on stdin as a tar archive.
set -e
mkdir -p /tmp/code /tmp/out && cd /tmp/code && tar -xf -
dotnet "$(cat /runner/csc-path)" @/runner/csc.rsp -out:/tmp/out/app.dll Main.cs Solution.cs > /tmp/out/build.log || { grep -v '^$' /tmp/out/build.log >&2; exit 1; }
cp /runner/app.runtimeconfig.json /tmp/out/app.runtimeconfig.json
exec dotnet /tmp/out/app.dll
SH
USER 65534:65534
