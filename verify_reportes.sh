#!/bin/bash

# Configuration
BASE_URL="http://127.0.0.1:8000/api/v1"
ADMIN_USER="admin"
ADMIN_PASS="admin123"
SPEC_USER="esp.medicina"
SPEC_PASS="medicina2026"
SPEC_DIRECTORIO_ID=8

echo "--- 1. Login as Admin ---"
# Filter out PHP warnings and get only JSON
ADMIN_TOKEN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"nombre_usuario\": \"$ADMIN_USER\", \"password\": \"$ADMIN_PASS\"}" | sed -n '/{/,$p' | jq -r '.backdata.token')

if [ "$ADMIN_TOKEN" == "null" ] || [ -z "$ADMIN_TOKEN" ]; then
  echo "Error: Could not login as Admin"
  exit 1
fi
echo "Admin Token obtained."

echo -e "\n--- 2. Login as Specialist ($SPEC_USER) ---"
SPEC_TOKEN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"nombre_usuario\": \"$SPEC_USER\", \"password\": \"$SPEC_PASS\"}" | sed -n '/{/,$p' | jq -r '.backdata.token')

if [ "$SPEC_TOKEN" == "null" ] || [ -z "$SPEC_TOKEN" ]; then
  echo "Error: Could not login as Specialist"
  exit 1
fi
echo "Specialist Token obtained."

echo -e "\n--- 3. Test Specialist Report (Own) ---"
curl -s -X GET "$BASE_URL/reportes/especialista" \
  -H "Authorization: Bearer $SPEC_TOKEN" | jq '.' | head -n 30

echo -e "\n--- 4. Test Admin Report for Specialist ID $SPEC_DIRECTORIO_ID ---"
curl -s -X GET "$BASE_URL/reportes/especialista?directorio_id=$SPEC_DIRECTORIO_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq '.' | head -n 30

echo -e "\n--- 5. Test List All Specialists (Admin) ---"
curl -s -X GET "$BASE_URL/reportes/especialistas" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq '.' | head -n 20

echo -e "\n--- 6. Test CSV Export (Admin) ---"
echo "Sending request to export CSV..."
curl -s -I -X GET "$BASE_URL/reportes/exportar" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | grep -E "HTTP/|Content-Type|Content-Disposition"

echo -e "\n--- 7. Test CSV Export (Specialist - Should be 403) ---"
curl -s -i -X GET "$BASE_URL/reportes/exportar" \
  -H "Authorization: Bearer $SPEC_TOKEN" | grep "HTTP/"

echo -e "\n--- 8. Test Filtering by Municipio ---"
# Pick a municipio_id from the seeder (1-10)
MUNICIPIO_ID=1
curl -s -X GET "$BASE_URL/reportes/especialista?municipio_id=$MUNICIPIO_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq '.backdata.metricas_avanzadas.cobertura_municipal'
