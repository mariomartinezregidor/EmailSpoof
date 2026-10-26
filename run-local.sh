#!/usr/bin/env bash
# Arranca el servidor de desarrollo local en http://127.0.0.1:8080
set -e
cd "$(dirname "$0")"
mkdir -p data
echo "Servidor en http://127.0.0.1:8080  (Ctrl+C para salir)"
exec php -S 127.0.0.1:8080
