# Engenharia-de-Software-II
Labs
# =============================================================
# Sistema de Fila Refeitório — Configuração do Ambiente
# Copie este ficheiro para .env e preencha os valores
# =============================================================

# ----------------------------
# Aplicação
# ----------------------------
APP_NAME="Sistema de Fila Refeitório"
APP_ENV=development          # development | production
APP_DEBUG=true
APP_URL=http://localhost:8001

# ----------------------------
# Base de Dados (MySQL/MariaDB)
# ----------------------------
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fila_refeitorio
DB_USERNAME=root
DB_PASSWORD=Khelv-2004

# ----------------------------
# JWT
# ----------------------------
JWT_SECRET=MUDE_ESTE_SEGREDO_PARA_PRODUCAO_32chars_minimo
JWT_EXPIRY=86400               # segundos (86400 = 24 horas)
JWT_ALGORITHM=HS256

# ----------------------------
# CORS
# ----------------------------
CORS_ALLOWED_ORIGINS=http://localhost:4202
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization

# ----------------------------
# Polling / Tempo Real
# ----------------------------
QUEUE_POLL_INTERVAL_CLIENT=5   # segundos
QUEUE_POLL_INTERVAL_ADMIN=3    # segundos

# ----------------------------
# Tempo estimado por ticket
# ----------------------------
ESTIMATED_TIME_PER_TICKET=3    # minutos
