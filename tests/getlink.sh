#!/bin/sh

HOST=172.17.0.2
USER_UUID=162a3771-4bff-49ac-88c9-eec91ab99a99

curl -X GET "http://$HOST/link" -H  "accept: application/json" -H  "X-USER-UUID: $USER_UUID"