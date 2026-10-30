#!/usr/bin/env bash
#
# Starts the services the unit tests talk to, on a CI runner with docker and
# sudo. Called from the PHP Unit job in .github/workflows/main.yml:
#
# - a dummy clamd (tests/util/avirserver.php) on 127.0.0.1:5555
# - an ICAP ClamAV server reachable as icap:1344 (tests/unit/Scanner/ICAPScannerTest.php)
# - tests/util/avir.sh as /usr/bin/clamscan for the executable scanner
#
set -euo pipefail

cd "$(dirname "$0")/../.."

ICAP_IMAGE=deepdiver/icap-clamav-service@sha256:efb1cc43b4fc239df3f79ff3be6987d6c0facb64a7d25d2ade9441c8db4d884b

docker run -d --rm --name icap -p 127.0.0.1:1344:1344 "$ICAP_IMAGE"
grep -q '[[:space:]]icap$' /etc/hosts || echo '127.0.0.1 icap' | sudo tee -a /etc/hosts >/dev/null
sudo ln -sf "$PWD/tests/util/avir.sh" /usr/bin/clamscan
nohup php tests/util/avirserver.php > /tmp/avirserver.log 2>&1 &

timeout 60 bash -c 'until (exec 3<>/dev/tcp/127.0.0.1/5555) 2>/dev/null; do sleep 1; done'

# c-icap accepts connections before clamd has loaded its signatures, so wait
# until an EICAR scan is actually reported as infected.
# shellcheck disable=SC2016 # the EICAR string is literal
eicar='X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'
icap_detects_eicar() {
	local reshdr=$'HTTP/1.1 200 OK\r\n\r\n' response
	exec 3<>/dev/tcp/127.0.0.1/1344 || return 1
	printf 'RESPMOD icap://icap:1344/avscan ICAP/1.0\r\nHost: icap\r\nAllow: 204\r\nEncapsulated: res-hdr=0, res-body=%d\r\n\r\n%s%x\r\n%s\r\n0\r\n\r\n' \
		"${#reshdr}" "$reshdr" "${#eicar}" "$eicar" >&3
	# the server keeps the connection open, so read until the timeout
	response=$(timeout 5 cat <&3 | tr -d '\0' || true)
	exec 3>&-
	[[ $response == *X-Infection-Found:* ]]
}
for _ in $(seq 1 60); do
	if icap_detects_eicar 2>/dev/null; then
		echo "ICAP server is ready"
		exit 0
	fi
	sleep 5
done
echo "ICAP server did not detect EICAR in time" >&2
docker logs icap >&2
exit 1
