#!/usr/bin/env bash
# Copia el codigo de produccion al repositorio publico limpio, comprueba que no
# haya datos privados y hace commit + push.
# Uso: ./publicar.sh "Mensaje del commit"
set -euo pipefail

ORIGEN="$(cd "$(dirname "$0")" && pwd)"
DESTINO="/tmp/opencode/EmailSpoof"
MENSAJE="${1:-Actualizacion}"

# Lista de datos privados que no pueden llegar al repositorio publico
PRIVADOS="marietelabs|localmail|dergli|mario\.martinez|regidor"

if [ ! -d "$DESTINO/.git" ]; then
    echo "No existe el repositorio limpio en $DESTINO" >&2
    exit 1
fi

rsync -av --delete \
    --exclude config.php \
    --exclude data/ \
    --exclude .git/ \
    --exclude README.md \
    --exclude publicar.sh \
    --exclude ComoPublicar.txt \
    "$ORIGEN/" "$DESTINO/"

if grep -rn -i -E "$PRIVADOS" --exclude-dir=.git "$DESTINO"; then
    echo "Se han encontrado datos privados. Revisa y corrige antes de publicar." >&2
    exit 1
fi

cd "$DESTINO"
git add -A
if git diff --cached --quiet; then
    echo "No hay cambios que publicar."
    exit 0
fi
git commit -m "$MENSAJE"
git push
echo "Publicado correctamente."
