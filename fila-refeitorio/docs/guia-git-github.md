# 🐙 Guia: Git, Commit e Push para o GitHub

> Guia passo a passo para versionar o projeto **Sistema de Fila do Refeitório** e submetê-lo ao GitHub.

---

## 1. Pré-requisitos

### Instalar o Git

```bash
# Ubuntu / Debian
sudo apt update && sudo apt install git -y

# macOS (com Homebrew)
brew install git

# Windows
# Descarregue em: https://git-scm.com/download/win
```

Verificar instalação:
```bash
git --version
# git version 2.x.x
```

### Configurar identidade (apenas uma vez)

```bash
git config --global user.name  "O Seu Nome"
git config --global user.email "seuemail@exemplo.com"
git config --global core.editor "nano"    # editor preferido
```

---

## 2. Criar o Repositório no GitHub

1. Aceda a [github.com](https://github.com) e faça login
2. Clique em **"New repository"** (botão verde no canto superior direito)
3. Preencha:
   - **Repository name:** `fila-refeitorio`
   - **Description:** `Sistema de Gestão de Fila do Refeitório`
   - **Visibility:** Public ou Private (à sua escolha)
   - ⚠️ **NÃO** marque *"Add a README file"* (já temos um)
4. Clique **"Create repository"**
5. Copie o URL do repositório, ex:
   ```
   https://github.com/SEU-UTILIZADOR/fila-refeitorio.git
   ```

---

## 3. Criar o `.gitignore`

Na raiz do projeto, crie o ficheiro `.gitignore` para excluir ficheiros desnecessários:

```bash
cat > .gitignore << 'EOF'
# Ambiente
backend/src/config/.env

# Dependências Node
frontend/node_modules/
frontend/dist/

# Cache e logs
*.log
*.cache
.DS_Store
Thumbs.db

# IDE
.idea/
.vscode/
*.swp
EOF
```

---

## 4. Inicializar o Repositório Local

```bash
# Na raiz do projeto (pasta fila-refeitorio/)
cd fila-refeitorio

# Inicializar o Git
git init

# Ligar ao repositório remoto do GitHub
git remote add origin https://github.com/SEU-UTILIZADOR/fila-refeitorio.git

# Verificar ligação
git remote -v
# origin  https://github.com/SEU-UTILIZADOR/fila-refeitorio.git (fetch)
# origin  https://github.com/SEU-UTILIZADOR/fila-refeitorio.git (push)
```

---

## 5. Primeiro Commit e Push

```bash
# Ver estado dos ficheiros
git status

# Adicionar todos os ficheiros ao staging
git add .

# Verificar o que vai ser commitado
git status

# Criar o primeiro commit
git commit -m "feat: versão inicial do sistema de fila do refeitório

- Backend PHP com autenticação JWT
- Frontend Angular 17
- Painel do cliente e do administrador
- Polling em tempo real
- Schema MySQL completo"

# Definir branch principal como 'main'
git branch -M main

# Fazer push para o GitHub
git push -u origin main
```

Se for pedida autenticação, use o seu **Personal Access Token** (ver secção 7).

---

## 6. Fluxo de Trabalho Diário

Depois do setup inicial, o fluxo normal é:

```bash
# 1. Verificar estado
git status

# 2. Ver diferenças
git diff

# 3. Adicionar ficheiros modificados
git add .                         # todos
git add backend/src/controllers/  # pasta específica
git add frontend/src/app/         # outra pasta

# 4. Fazer commit com mensagem descritiva
git commit -m "fix: corrigir cálculo de posição na fila"

# 5. Enviar para o GitHub
git push
```

---

## 7. Autenticação no GitHub (Personal Access Token)

O GitHub já não aceita senha direta. Use um **token**:

1. Aceda a **GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)**
2. Clique **"Generate new token (classic)"**
3. Defina:
   - **Note:** `fila-refeitorio`
   - **Expiration:** 90 days (ou sem expiração)
   - **Scopes:** marque `repo` (acesso total a repositórios)
4. Clique **"Generate token"** e **copie o token imediatamente**

Ao fazer push, quando pedido:
- **Username:** o seu nome de utilizador do GitHub
- **Password:** cole o token (não a sua senha)

### Guardar credenciais (opcional)

```bash
# Guardar credenciais localmente (só em máquina pessoal)
git config --global credential.helper store
```

### Autenticação com SSH (alternativa mais segura)

```bash
# Gerar chave SSH
ssh-keygen -t ed25519 -C "seuemail@exemplo.com"

# Copiar a chave pública
cat ~/.ssh/id_ed25519.pub

# Adicionar no GitHub: Settings → SSH keys → New SSH key → Colar e guardar

# Testar ligação
ssh -T git@github.com

# Alterar URL do remote para SSH
git remote set-url origin git@github.com:SEU-UTILIZADOR/fila-refeitorio.git
```

---

## 8. Boas Práticas de Commit

### Convenção de mensagens (Conventional Commits)

```
tipo: descrição breve (máx. 72 chars)

[corpo opcional — explica o porquê]
[rodapé opcional — referências a issues]
```

| Tipo | Quando usar |
|------|-------------|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `docs` | Documentação |
| `style` | Formatação, SCSS |
| `refactor` | Refactorização sem nova funcionalidade |
| `test` | Testes |
| `chore` | Tarefas de manutenção (deps, build) |

**Exemplos:**
```bash
git commit -m "feat: adicionar notificação sonora ao ser chamado"
git commit -m "fix: corrigir token expirado não redireciona para login"
git commit -m "docs: atualizar README com instruções de produção"
git commit -m "style: melhorar responsividade do painel admin"
git commit -m "chore: atualizar angular para 17.3"
```

---

## 9. Trabalhar com Branches

```bash
# Criar branch para nova funcionalidade
git checkout -b feature/notificacoes-push

# Trabalhar... fazer commits...
git add .
git commit -m "feat: adicionar suporte a Web Push Notifications"

# Enviar branch para o GitHub
git push -u origin feature/notificacoes-push

# Voltar ao main e fazer merge
git checkout main
git merge feature/notificacoes-push

# Apagar branch local após merge
git branch -d feature/notificacoes-push
```

---

## 10. Comandos Úteis

```bash
# Ver histórico de commits
git log --oneline --graph

# Desfazer último commit (mantendo ficheiros)
git reset --soft HEAD~1

# Descartar alterações num ficheiro
git checkout -- backend/src/controllers/AuthController.php

# Ver todas as branches
git branch -a

# Atualizar com alterações do GitHub
git pull

# Ver diferenças entre commits
git diff HEAD~1 HEAD

# Guardar alterações temporariamente
git stash
git stash pop
```

---

## 11. Submeter para Repositório Específico

Se precisar submeter para um repositório já existente ou de outra pessoa/organização:

```bash
# Remover remote atual
git remote remove origin

# Adicionar novo remote
git remote add origin https://github.com/ORGANIZACAO/REPOSITORIO.git

# Forçar push (cuidado! apaga o histórico remoto)
git push -u origin main --force

# Ou push normal se o repositório estiver vazio
git push -u origin main
```

---

## 12. Exemplo Completo — Do Zero ao GitHub

```bash
# Na pasta do projeto
cd fila-refeitorio

# Criar .gitignore
echo "backend/src/config/.env
frontend/node_modules/
frontend/dist/
*.log
.DS_Store" > .gitignore

# Inicializar
git init
git remote add origin https://github.com/SEU-UTILIZADOR/fila-refeitorio.git

# Commit inicial
git add .
git commit -m "feat: versão inicial do sistema de fila do refeitório"
git branch -M main
git push -u origin main

# ✅ Projeto disponível em:
# https://github.com/SEU-UTILIZADOR/fila-refeitorio
```

---

*Guia preparado para o Sistema de Fila do Refeitório · Versão 1.0.0*
