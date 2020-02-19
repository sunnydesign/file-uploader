#!/bin/sh

HOST=172.17.0.2
HASH=cdfc99d87c168ddd6fa072af806ca1fce3f13bd93f8a38dd4fecf4ee9126af2a
USER_UUID=162a3771-4bff-49ac-88c9-eec91ab99a99

curl -X GET "http://$HOST/$HASH" -H  "accept: application/json" -H  "X-USER-UUID: $USER_UUID" -o testfile.ext