# FalaDev

Simulador de entrevistas técnicas por voz, em inglês, para devs brasileiros. Visão, escopo e regras do produto ficam em `cursor/skills/FalaDev.md`, mantido apenas localmente (fora do git).

## Stack

Laravel 13 (PHP 8.3+) · Inertia 3 + Vue 3 + TypeScript · Tailwind 4 · PostgreSQL 17 · Laravel Fortify (autenticação) · Pest (testes).

## Rodando localmente

Pré-requisitos: PHP 8.3+ com as extensões `pdo_pgsql` e `pgsql` habilitadas no `php.ini`, Composer, Node 20+ e Docker.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

docker compose up -d --wait   # PostgreSQL em 127.0.0.1:5433
php artisan migrate
php artisan db:seed           # usuário de dev: dev@faladev.test / password

composer run dev              # servidor, fila, logs e Vite juntos
```

Acesse http://127.0.0.1:8000.

O PostgreSQL do Docker usa a porta **5433** para não conflitar com uma instalação local na 5432. As credenciais (`faladev` / `secret`) servem apenas para desenvolvimento.

## Testes

```bash
php artisan test                                              # SQLite em memória (padrão do phpunit.xml)
DB_CONNECTION=pgsql DB_DATABASE=faladev_testing php artisan test  # contra o Postgres
```

Para a segunda opção, crie o banco uma vez:
`docker exec faladev-pgsql psql -U faladev -d faladev -c "CREATE DATABASE faladev_testing"`.

## Estrutura do domínio

- `app/Enums`: `InterviewCategory`, `InterviewLevel`, `InterviewDuration` (15/30) e `InterviewStatus` (estados da seção 8).
- `app/Models/InterviewSession`: pertence a um usuário e tem vários `turns` ordenados por posição. `status` não é atribuível em massa.
- `app/Models/InterviewTurn`: pergunta, transcrição confirmada, dica/repetição e metadados operacionais. A posição é única por sessão.
- `app/Http/Controllers/DashboardController`: tela inicial com as 5 entrevistas mais recentes e o uso do mês.

## Idioma

A interface é em português (`APP_LOCALE=pt_BR`); as entrevistas são em inglês. Os textos das telas ficam direto nos componentes Vue. As mensagens do servidor (validação, login, senha e e-mails) ficam em `lang/pt_BR/*.php` e `lang/pt_BR.json`.

## Configuração do produto

`config/faladev.php` concentra as regras configuráveis. Por enquanto, só o limite mensal de entrevistas por conta (`FALADEV_MONTHLY_SESSION_LIMIT`, padrão 10, valor provisório até medir o custo por sessão).
