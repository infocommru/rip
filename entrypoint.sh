#!/bin/sh
set -e

envsubst '${BASE_URL}' < web/.htaccess > web/.htaccess.tmp \
  && mv web/.htaccess.tmp web/.htaccess

exec apache2-foreground