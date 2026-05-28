#!/bin/bash
# =============================================================
# setup.sh — Script de instalação do Sistema de Fila Refeitório
# =============================================================

set -e
BOLD='\033[1m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'

echo ""
echo -e "${BOLD}🍽️  Sistema de Fila Refeitório — Setup${NC}"
echo "============================================"
echo ""

# ── 1. Verificar dependências ─────────────────────────────────
echo -e "${BOLD}[1/5] Verificando dependências...${NC}"

command -v php  >/dev/null 2>&1 || { echo -e "${RED}✗ PHP não encontrado. Instale PHP 7.4+${NC}"; exit 1; }
command -v mysql>/dev/null 2>&1 || echo -e "${YELLOW}⚠  mysql não encontrado — instale MySQL/MariaDB${NC}"
command -v node >/dev/null 2>&1 || { echo -e "${RED}✗ Node.js não encontrado. Instale Node.js 18+${NC}"; exit 1; }
command -v npm  >/dev/null 2>&1 || { echo -e "${RED}✗ npm não encontrado.${NC}"; exit 1; }

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
NODE_VER=$(node --version)
echo -e "${GREEN}✓ PHP ${PHP_VER}${NC}"
echo -e "${GREEN}✓ Node.js ${NODE_VER}${NC}"

# ── 2. Configurar .env ────────────────────────────────────────
echo ""
echo -e "${BOLD}[2/5] Configurando variáveis de ambiente...${NC}"

ENV_FILE="backend/src/config/.env"
ENV_EXAMPLE="backend/src/config/.env.example"

if [ ! -f "$ENV_FILE" ]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
    echo -e "${GREEN}✓ .env criado a partir do exemplo.${NC}"
    echo -e "${YELLOW}  ► Edite ${ENV_FILE} com as suas credenciais de BD!${NC}"
else
    echo -e "${GREEN}✓ .env já existe — sem alterações.${NC}"
fi

# ── 3. Base de dados ──────────────────────────────────────────
echo ""
echo -e "${BOLD}[3/5] Base de dados...${NC}"
echo -e "${YELLOW}  Para importar o schema, execute:${NC}"
echo "  mysql -u root -p < backend/database/schema.sql"
echo ""
echo -e "  O schema cria a BD 'fila_refeitorio' e insere dados iniciais."
echo -e "  Admin padrão: admin@refeitorio.ao / Admin@123"

# ── 4. Frontend — instalar dependências ───────────────────────
echo ""
echo -e "${BOLD}[4/5] Instalando dependências do frontend...${NC}"
cd frontend
if [ ! -d "node_modules" ]; then
    echo "  Executando npm install (pode demorar alguns minutos)..."
    npm install --silent
    echo -e "${GREEN}✓ Dependências instaladas.${NC}"
else
    echo -e "${GREEN}✓ node_modules já existe.${NC}"
fi
cd ..

# ── 5. Resumo ─────────────────────────────────────────────────
echo ""
echo -e "${BOLD}[5/5] Setup concluído!${NC}"
echo "============================================"
echo ""
echo -e "${BOLD}🚀 Para iniciar o sistema:${NC}"
echo ""
echo -e "  Backend:   cd backend && php -S localhost:8001 index.php"
echo -e "  Frontend:  cd frontend && npm start"
echo ""
echo -e "  🌐 Frontend: ${GREEN}http://localhost:4202${NC}"
echo -e "  🔌 API:      ${GREEN}http://localhost:8001/api${NC}"
echo ""
echo -e "${BOLD}📚 Consulte o README.md para mais informações.${NC}"
echo ""
