# 🍽️ Sistema de Fila do Refeitório

> Sistema web completo para gestão de filas de atendimento em refeitório, com painel do cliente, painel de administrador e atualizações em tempo real via polling.

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Estrutura do Projeto](#estrutura-do-projeto)
4. [Pré-requisitos](#pré-requisitos)
5. [Instalação Rápida](#instalação-rápida)
6. [Instalação Manual](#instalação-manual)
7. [Configuração `.env`](#configuração-env)
8. [Endpoints da API](#endpoints-da-api)
9. [Utilização](#utilização)
10. [Credenciais Padrão](#credenciais-padrão)
11. [Git & GitHub](#git--github)

---

## Visão Geral

O sistema permite que clientes retirem senhas digitais para serviços do refeitório (Café, Almoço, Lanche, Jantar) e acompanhem a sua posição na fila em tempo real. Os administradores gerem as filas a partir de um painel dedicado.

### Funcionalidades

| Área | Funcionalidade |
|------|---------------|
| Auth | Registo e login com JWT |
| Cliente | Retirar senha, ver posição, cancelar |
| Admin | Chamar próximo, concluir atendimento, estatísticas |
| Geral | Polling automático (3–5 s), interface responsiva |

---

## Stack Tecnológico

**Backend** — PHP 7.4+ · MySQL/MariaDB · JWT HS256 (implementação nativa)

**Frontend** — Angular 17 · TypeScript · RxJS · SCSS

---

## Estrutura do Projeto

```
fila-refeitorio/
├── backend/
│   ├── index.php                  ← Ponto de entrada + router
│   ├── database/
│   │   └── schema.sql             ← Schema completo + dados iniciais
│   └── src/
│       ├── config/
│       │   ├── .env.example
│       │   ├── Config.php         ← Carregador de variáveis de ambiente
│       │   └── Database.php       ← Singleton PDO
│       ├── controllers/
│       │   ├── AuthController.php
│       │   ├── ServicesController.php
│       │   ├── TicketsController.php
│       │   └── AdminController.php
│       ├── middleware/
│       │   └── AuthMiddleware.php
│       └── utils/
│           ├── JWT.php
│           └── Response.php
├── frontend/
│   ├── angular.json
│   ├── package.json
│   ├── tsconfig.json
│   └── src/
│       ├── main.ts
│       ├── index.html
│       ├── styles.scss
│       ├── environments/
│       │   └── environment.ts
│       └── app/
│           ├── app.component.ts
│           ├── app.config.ts
│           ├── app.routes.ts
│           ├── models/index.ts
│           ├── guards/auth.guard.ts
│           ├── services/
│           │   ├── auth.service.ts
│           │   ├── queue.service.ts
│           │   └── auth.interceptor.ts
│           └── components/
│               ├── auth/login/ & register/
│               ├── client/dashboard/
│               └── admin/admin-panel/
├── setup.sh                       ← Script de setup automático
├── README.md
└── docs/
    ├── manual-utilizador.pdf
    └── guia-git-github.md
```

---

## Pré-requisitos

- **PHP** 7.4 ou superior
- **MySQL** 5.7+ ou **MariaDB** 10.3+
- **Node.js** 18+ e **npm** 9+
- Servidor web (ou `php -S` para desenvolvimento)

---

## Instalação Rápida

```bash
# 1. Clonar o repositório
git clone https://github.com/SEU-UTILIZADOR/fila-refeitorio.git
cd fila-refeitorio

# 2. Executar o script de setup
chmod +x setup.sh && ./setup.sh

# 3. Importar a base de dados
mysql -u root -p < backend/database/schema.sql

# 4. Editar as credenciais
nano backend/src/config/.env

# 5. Iniciar (dois terminais)
cd backend  && php -S localhost:8001 index.php
cd frontend && npm start
```

Aceda em **http://localhost:4202**

---

## Instalação Manual

### Backend

```bash
cd backend/src/config
cp .env.example .env
# edite .env com as suas credenciais

# Importar schema
mysql -u root -p < ../../database/schema.sql

# Iniciar servidor de desenvolvimento
cd ../..
php -S localhost:8001 index.php
```

### Frontend

```bash
cd frontend
npm install
npm start          # http://localhost:4202
```

---

## Configuração `.env`

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fila_refeitorio
DB_USERNAME=root
DB_PASSWORD=SUA_SENHA

JWT_SECRET=SEGREDO_ALEATORIO_MINIMO_32_CHARS
JWT_EXPIRY=86400

CORS_ALLOWED_ORIGINS=http://localhost:4202
```

---

## Endpoints da API

Base URL: `http://localhost:8001/api`

### Públicos
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/services` | Listar serviços |
| GET | `/queue/{id}` | Info da fila pública |

### Autenticação
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/auth/register` | Registar utilizador |
| POST | `/auth/login` | Login |
| GET  | `/auth/me` | Perfil (requer token) |

### Cliente `[Bearer Token]`
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/tickets` | Criar senha |
| GET  | `/tickets/my` | Minha senha ativa |
| DELETE | `/tickets/{id}` | Cancelar senha |

### Administrador `[Bearer Token + role:admin]`
| Método | Rota | Descrição |
|--------|------|-----------|
| GET  | `/admin/services` | Todos os serviços |
| GET  | `/admin/queue/{id}` | Fila por serviço |
| POST | `/admin/call/{id}` | Chamar próximo |
| POST | `/admin/complete/{id}` | Concluir atendimento |
| GET  | `/admin/stats/{id}` | Estatísticas do dia |

---

## Utilização

### Fluxo do Cliente
1. Aceder a `http://localhost:4202`
2. Registar ou fazer login
3. Selecionar um serviço disponível
4. Aguardar a chamada (atualização automática a cada 5 s)
5. Quando chamado, dirigir-se ao balcão

### Fluxo do Administrador
1. Login com conta `admin`
2. Selecionar o serviço na aba correspondente
3. Clicar **"Chamar Próximo"** para chamar a próxima senha
4. Clicar **"Concluir"** após atendimento
5. Consultar **Estatísticas** do dia no separador dedicado

---

## Credenciais Padrão

| Papel | E-mail | Senha |
|-------|--------|-------|
| Admin | `admin@refeitorio.ao` | `Admin@123` |

> ⚠️ **Altere a senha do admin imediatamente em produção.**

---

## Git & GitHub

Consulte `docs/guia-git-github.md` para instruções detalhadas sobre como fazer commit e push para o GitHub.

---

**Versão:** 1.0.0 · **Data:** Maio 2026
