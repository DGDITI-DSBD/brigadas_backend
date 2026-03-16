#!/usr/bin/env bash
# ================================================================
# test_reportes.sh — QA automatizado para endpoints de reportes
# Uso: ./test_reportes.sh <nombre_usuario> <password>
# Ejemplo: ./test_reportes.sh admin admin123
# ================================================================

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'
RED='\033[0;31m'; BOLD='\033[1m'; NC='\033[0m'
BASE_URL="http://127.0.0.1:8000/api/v1"

USUARIO="$1"
PASSWORD="$2"

if [ -z "$USUARIO" ] || [ -z "$PASSWORD" ]; then
    echo -e "${RED}❌ Uso: $0 <nombre_usuario> <password>${NC}"
    exit 1
fi

# ── Extrae solo el objeto JSON de la respuesta (ignora warnings PHP) ─
extract_json() { echo "$1" | grep -o '{.*}' | tail -1; }

# ── Pretty-print JSON ─────────────────────────────────────────────────
pretty() {
    local j; j=$(extract_json "$1")
    if command -v jq &>/dev/null; then
        echo "$j" | jq . 2>/dev/null || echo "$j"
    else
        echo "$j" | python3 -m json.tool 2>/dev/null || echo "$j"
    fi
}

# ── Evaluar si ok=true ────────────────────────────────────────────────
check_ok() {
    extract_json "$1" | python3 -c \
      "import sys,json; d=json.load(sys.stdin); print('✔ ok=true' if d.get('ok') else '✘ ok=false')" 2>/dev/null \
      || echo "? sin JSON"
}

# ────────────────────────────────────────────────────────────────
# PASO 1 — Verificar / levantar el servidor Laravel
# ────────────────────────────────────────────────────────────────
echo -e "\n${BOLD}${CYAN}══════════════════════════════════════════${NC}"
echo -e "${BOLD}${CYAN}  Laravel QA — Test de Reportes           ${NC}"
echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"
echo -e "\n${YELLOW}[1/5] Verificando servidor en puerto 8000...${NC}"

if lsof -i :8000 &>/dev/null; then
    echo -e "${GREEN}    ✔ Servidor ya está corriendo en :8000${NC}"
else
    echo -e "${YELLOW}    ⏳ Levantando php artisan serve...${NC}"
    (cd "$(dirname "$0")" && php artisan serve --quiet &)
    sleep 4
    if lsof -i :8000 &>/dev/null; then
        echo -e "${GREEN}    ✔ Servidor iniciado${NC}"
    else
        echo -e "${RED}    ❌ No se pudo iniciar el servidor${NC}"; exit 1
    fi
fi

# ────────────────────────────────────────────────────────────────
# PASO 2 — Login y extracción del token
# ────────────────────────────────────────────────────────────────
echo -e "\n${YELLOW}[2/5] Realizando login como '${USUARIO}'...${NC}"

LOGIN_RAW=$(curl -s -X POST "${BASE_URL}/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"nombre_usuario\": \"${USUARIO}\", \"password\": \"${PASSWORD}\"}")

echo -e "\n${CYAN}  ── Respuesta de login:${NC}"; pretty "$LOGIN_RAW"

LOGIN_JSON=$(extract_json "$LOGIN_RAW")

if command -v jq &>/dev/null; then
    TOKEN=$(echo "$LOGIN_JSON" | jq -r '.backdata.token // empty' 2>/dev/null)
else
    TOKEN=$(echo "$LOGIN_JSON" | python3 -c \
      "import sys,json; d=json.load(sys.stdin); print(d.get('backdata',{}).get('token','') or '')" 2>/dev/null)
fi

if [ -z "$TOKEN" ]; then
    echo -e "\n${RED}❌ No se pudo extraer el token.${NC}"; exit 1
fi
echo -e "\n${GREEN}    ✔ Token: ${TOKEN:0:50}...${NC}"

# ────────────────────────────────────────────────────────────────
# PASO 3 — Sin parámetros
# ────────────────────────────────────────────────────────────────
echo -e "\n${YELLOW}[3/5] GET /reportes/especialista (sin parámetros)${NC}"
RES1=$(curl -s "${BASE_URL}/reportes/especialista" -H "Authorization: Bearer ${TOKEN}")
echo -e "${CYAN}  ── Respuesta:${NC}"; pretty "$RES1"
OK1=$(check_ok "$RES1"); echo -e "  ${GREEN}${OK1}${NC}"

# ────────────────────────────────────────────────────────────────
# PASO 4 — Con fechas
# ────────────────────────────────────────────────────────────────
echo -e "\n${YELLOW}[4/5] GET /reportes/especialista?desde=2026-01-01&hasta=2026-03-04${NC}"
RES2=$(curl -s "${BASE_URL}/reportes/especialista?desde=2026-01-01&hasta=2026-03-04" \
    -H "Authorization: Bearer ${TOKEN}")
echo -e "${CYAN}  ── Respuesta:${NC}"; pretty "$RES2"
OK2=$(check_ok "$RES2"); echo -e "  ${GREEN}${OK2}${NC}"

# ────────────────────────────────────────────────────────────────
# PASO 5 — Con directorio_id
# ────────────────────────────────────────────────────────────────
echo -e "\n${YELLOW}[5/5] GET /reportes/especialista?directorio_id=1 (Admin)${NC}"
RES3=$(curl -s "${BASE_URL}/reportes/especialista?directorio_id=1" \
    -H "Authorization: Bearer ${TOKEN}")
echo -e "${CYAN}  ── Respuesta:${NC}"; pretty "$RES3"
OK3=$(check_ok "$RES3"); echo -e "  ${GREEN}${OK3}${NC}"

# ────────────────────────────────────────────────────────────────
# RESUMEN
# ────────────────────────────────────────────────────────────────
echo -e "\n${BOLD}${CYAN}══════════════════════════════════════════${NC}"
echo -e "${BOLD}  RESUMEN DE PRUEBAS${NC}"
echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"
echo -e "  Test 1 (sin parámetros):       ${OK1}"
echo -e "  Test 2 (filtro de fechas):      ${OK2}"
echo -e "  Test 3 (directorio_id admin):   ${OK3}"
echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}\n"
