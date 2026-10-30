#!/usr/bin/env bash
#
# Starts the services the unit tests talk to, on a CI runner with docker and
# sudo. Called from the PHP Unit job in .github/workflows/main.yml:
#
# - a dummy clamd (tests/util/avirserver.php) on 127.0.0.1:5555
# - an ICAP ClamAV server reachable as icap:1344 (tests/unit/Scanner/ICAPScannerTest.php)
# - tests/util/avir.sh as /usr/bin/clamscan for the executable scanner
#
# It changes /usr/bin/clamscan and /etc/hosts, so it refuses to run outside
# GitHub Actions.
#
set -euo pipefail

if [[ "${GITHUB_ACTIONS:-}" != "true" ]]; then
	echo "Refusing to run outside GitHub Actions: this replaces /usr/bin/clamscan and edits /etc/hosts" >&2
	exit 1
fi

cd "$(dirname "$0")/../.."

ICAP_IMAGE=deepdiver/icap-clamav-service@sha256:efb1cc43b4fc239df3f79ff3be6987d6c0facb64a7d25d2ade9441c8db4d884b

# no --rm, so that docker logs still work if the container exits
docker rm -f icap >/dev/null 2>&1 || true
docker run -d --name icap -p 127.0.0.1:1344:1344 "$ICAP_IMAGE"
grep -q '[[:space:]]icap$' /etc/hosts || echo '127.0.0.1 icap' | sudo tee -a /etc/hosts >/dev/null
sudo ln -sf "$PWD/tests/util/avir.sh" /usr/bin/clamscan
nohup php tests/util/avirserver.php > /tmp/avirserver.log 2>&1 &
avirserver_pid=$!

avirserver_ready=false
for _ in $(seq 1 60); do
	if ! kill -0 "$avirserver_pid" 2>/dev/null; then
		break
	fi
	if (exec 3<>/dev/tcp/127.0.0.1/5555) 2>/dev/null; then
		avirserver_ready=true
		break
	fi
	sleep 1
done
# check again: something else may hold the port while the dummy clamd has died
sleep 1
if [[ $avirserver_ready != true ]] || ! kill -0 "$avirserver_pid" 2>/dev/null; then
	echo "Dummy clamd is not running or not listening on 127.0.0.1:5555:" >&2
	cat /tmp/avirserver.log >&2
	exit 1
fi

# c-icap accepts connections before clamd has loaded its signatures, so wait
# until an EICAR scan is actually reported as infected.
# shellcheck disable=SC2016 # the EICAR string is literal
eicar='X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'
# runs in a subshell, so a write to a reset connection (SIGPIPE) only fails the probe
icap_detects_eicar() (
	reshdr=$'HTTP/1.1 200 OK\r\n\r\n'
	exec 3<>/dev/tcp/127.0.0.1/1344 || exit 1
	printf 'RESPMOD icap://icap:1344/avscan ICAP/1.0\r\nHost: icap\r\nAllow: 204\r\nEncapsulated: res-hdr=0, res-body=%d\r\n\r\n%s%x\r\n%s\r\n0\r\n\r\n' \
		"${#reshdr}" "$reshdr" "${#eicar}" "$eicar" >&3
	# the server keeps the connection open, so read until the timeout
	response=$(timeout 5 cat <&3 | tr -d '\0' || true)
	[[ $response == *X-Infection-Found:* ]]
)
for _ in $(seq 1 60); do
	if [[ $(docker inspect -f '{{.State.Running}}' icap 2>/dev/null) != true ]]; then
		break
	fi
	if icap_detects_eicar 2>/dev/null; then
		echo "ICAP server is ready"
		exit 0
	fi
	sleep 5
done
echo "ICAP server is not running or did not detect EICAR in time" >&2
docker ps -a --filter name=icap >&2
docker logs icap >&2
exit 1
