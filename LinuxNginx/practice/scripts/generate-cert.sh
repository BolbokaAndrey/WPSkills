#!/usr/bin/env sh
set -eu

mkdir -p certs
openssl req -x509 -newkey rsa:2048 -sha256 -nodes -days 30 \
  -keyout certs/key.pem \
  -out certs/cert.pem \
  -subj '/CN=bitrix.172.16.11.34.nip.io' \
  -addext 'subjectAltName=DNS:bitrix.172.16.11.34.nip.io'
