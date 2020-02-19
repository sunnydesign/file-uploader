#!/bin/sh

HOST=172.17.0.2
HASH=0ab77263c1a8ea2a62d90868754471ec5b7e65741754f40463ba8970613de201
FILE=./milky-way.jpg

curl -F data="@$FILE" "http://$HOST/$HASH" -H "accept: application/json"