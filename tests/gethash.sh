#!/bin/sh

HOST=172.17.0.2
HASH=cdfc99d87c168ddd6fa072af806ca1fce3f13bd93f8a38dd4fecf4ee9126af2a

curl -X GET "http://$HOST/$HASH" -H  "accept: application/json" -o testfile.ext