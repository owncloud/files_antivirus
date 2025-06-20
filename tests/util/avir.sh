#!/bin/bash

content=$(tee)

case $content in
	PING)
		echo "PONG"
		exit 0
		;;
	VERSION)
		echo "ClamAV FakeTest"
		exit 0
		;;
	*kitten)
		echo "Oh my god! : Kitten FOUND"
		exit 1
		;;
esac

echo "$1 : OK"
