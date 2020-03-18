#!/bin/bash
# rsync script
rsync -avzh --delete --include-from=".rsyncinclude" --include="/vendor" --exclude-from=".rsyncignore" --exclude=".env" --chmod=Du=rwx,Dgo=rx,Fu=rw,Fog=r ./ /deploy/www/laravel-echo/laravel-echo/
