# FalaDev

Simulador de entrevistas técnicas por voz, em inglês, para devs brasileiros. Visão, escopo e regras do produto ficam em `cursor/skills/FalaDev.md`, mantido apenas localmente (fora do git).

## Stack

Laravel 13 (PHP 8.3+) · Inertia 3 + Vue 3 + TypeScript · Tailwind 4 · PostgreSQL 17 · Laravel Fortify (autenticação) · Pest (testes).

## Rodando localmente

Pré-requisitos: PHP 8.3+ com as extensões `pdo_pgsql` e `pgsql` habilitadas no `php.ini`, Composer, Node 20+ e Docker.

No Windows, o PHP precisa de um pacote de certificados para chamar APIs por HTTPS (ElevenLabs): configure `curl.cainfo` e `openssl.cafile` no `php.ini` apontando para um `cacert.pem`. Sem isso, as chamadas falham com "cURL error 60".

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
- `app/Http/Controllers/InterviewSessionController`: configuração da entrevista (`/interviews/create`), página da sessão (`/interviews/{id}`), início e encerramento. A página da sessão mostra os testes de áudio, a entrevista em andamento (`interviews/Room`) ou a transcrição depois de concluída. Cada usuário tem no máximo um rascunho aberto: salvar de novo atualiza o mesmo rascunho. Sessões de outros usuários respondem 404.
- **Uma entrevista por vez:** com uma em andamento (`User::interviewInProgress()`), a tela inicial mostra "Continuar entrevista", `/interviews/create` e o salvamento da configuração levam de volta a ela, e iniciar outra é recusado.
- `app/Http/Controllers/InterviewTurnController`: ações na pergunta atual (transcrever a gravação, enviar a resposta revisada, repetir a pergunta).
- `app/Interview/InterviewFlow`: regras da entrevista por turnos. Cada escrita trava a linha da sessão; repetir uma ação já feita (clique duplo, reenvio após queda de conexão) não tem efeito. Ao enviar uma resposta, o entrevistador decide a próxima pergunta ou encerra. Se o tempo acabou, a resposta em andamento ainda conta e depois a entrevista termina.
- `app/Interview/Contracts/Interviewer`: decide a próxima pergunta. `FakeInterviewer` segue um roteiro fixo por categoria: 3 perguntas principais (15 min) ou 4 (30 min), cada uma seguida de uma pergunta de aprofundamento. Ele não lê as respostas nem adapta ao nível; isso fica para o LLM.
- `app/Services/InterviewUsage`: limite mensal. Só contam entrevistas **iniciadas**; rascunhos não consomem o limite. Cada início grava um registro em `usage_records`, que não é apagado junto com a sessão: apagar uma entrevista não devolve a cota.
- **Histórico e exclusão:** `/interviews` lista todas as sessões do usuário (15 por página). Na página da sessão, "Apagar entrevista" remove a sessão, as perguntas, as respostas e o relatório, depois de confirmação.
- **Relatório (`app/Evaluation`):** ao terminar uma entrevista com pelo menos uma resposta, a sessão vai para `evaluating` e o job `EvaluateInterview` roda na fila. Sucesso leva a `report_ready`; qualquer falha leva a `failed_retryable`, com a transcrição preservada e o botão "Tentar novamente". A página consulta o servidor a cada 3 s enquanto o relatório é preparado. `Rubric` define as dimensões (a técnica muda com a categoria) e a escala de 1 a 5. `EvaluationResult` rejeita notas fora da escala. O `FakeEvaluator` dá nota fixa 3, cita trechos das respostas e deixa claro, no relatório e na tela, que é simulado.
- **Estados da sessão:** `InterviewStatus::canTransitionTo()` define as transições permitidas e `InterviewSession::transitionTo()` é o único jeito de mudar o status. Por enquanto: `draft → ready` (testes de áudio concluídos), `ready → draft` (configuração alterada), `ready → asking` (entrevista iniciada), `asking → completed` (última pergunta respondida, tempo esgotado ou encerramento pelo usuário), `completed → evaluating` (há respostas), `evaluating → report_ready | failed_retryable` e `failed_retryable → evaluating` (nova tentativa). Gravar, transcrever e revisar acontecem no navegador e não viram estado no servidor, porque nada é salvo até o envio.

## Voz (TTS/STT)

Os provedores ficam atrás de interfaces em `app/Speech`, escolhidas em `config/faladev.php`.

**Voz do entrevistador (TTS): frases pré-gravadas.** As páginas nunca chamam a ElevenLabs. Toda frase fixa (frase de teste, abertura, encerramento e perguntas do roteiro) é gravada uma vez em MP3 e servida como arquivo estático de `public/audio/interviewer/`, versionado no git:

```bash
php artisan faladev:record-phrases --dry-run   # quantas frases faltam e quantos caracteres vão gastar
php artisan faladev:record-phrases             # grava só as que faltam
```

- O nome do arquivo é um hash do texto e da voz (voz, modelo e formato). Editar uma frase ou trocar a voz aponta para um arquivo novo; até gravar de novo, a página mostra só o texto. Arquivos antigos podem ser apagados à mão.
- O comando usa o `TextToSpeech` configurado em `FALADEV_TTS_DRIVER`:
    - `elevenlabs`: voz real. Precisa de `ELEVENLABS_API_KEY` no `.env` (nunca no código nem no `.env.example`). Voz, modelo e formato ficam em `config/services.php` (padrão: voz "Matilda", modelo `eleven_v4_turbo`, MP3 64 kbps).
    - `fake`: sinal sonoro em WAV. O comando recusa esse driver, porque não gera MP3.
- Perguntas sem gravação (por exemplo, as que o LLM vai gerar) aparecem só como texto, com aviso na tela. TTS sob demanda fica para quando o entrevistador real existir.

**Transcrição (STT):** interface `SpeechToText` (`FALADEV_STT_DRIVER`). Por enquanto só existe o `fake`, que ignora o áudio e devolve um texto simulado para a pessoa substituir na revisão; a tela avisa. O áudio chega como upload temporário (até 5 MB, WebM/Ogg/MP4) e não é guardado. Nos metadados do turno ficam só contagem e latência da transcrição.

**Entrevistador:** interface `Interviewer` (`FALADEV_INTERVIEWER_DRIVER`), hoje só `fake` (roteiro fixo, ver acima).

**Avaliador:** interface `Evaluator` (`FALADEV_EVALUATOR_DRIVER`), hoje só `fake` (relatório simulado). O relatório é gerado pela fila, então o worker precisa estar rodando; `composer run dev` já inicia um.

Os testes nunca chamam provedores reais: `Http::preventStrayRequests()` em `tests/TestCase.php` e drivers `fake` no `phpunit.xml`. Os testes das frases usam um diretório temporário (`fakePrerecordedPhrases()` em `tests/Pest.php`).

O teste de microfone roda só no navegador (`resources/js/composables/useAudioRecorder.ts`): pede permissão no clique, mostra o volume ao vivo, grava localmente e libera o microfone ao terminar. Nada é enviado ao servidor.

## Padrões de interface

Telas novas usam os componentes do FalaDev em vez de montar a estrutura à mão:

- `PageHeader`: título (`h1`), descrição e ações da página.
- `OptionCardGroup`: escolha única em cartões, com `input radio` nativo (funciona com o `<Form>` do Inertia e com teclado).
- `interview/MicrophoneCheck` e `interview/SampleAudioCheck`: testes de áudio antes da entrevista.
- `interview/AnswerRecorder`: uma resposta (gravar → transcrever → revisar/corrigir → enviar). Se a transcrição falhar, a gravação continua em memória para tentar de novo. Avisa antes de sair da página com resposta não enviada.
- `useAudioRecorder`: gravação com permissão, nível de entrada, parar/cancelar e reprodução local. Nas respostas grava a 32 kbps para o upload ficar pequeno.
- `useAudioPlayer`: toca as frases do entrevistador, uma ou várias em sequência. Se o navegador bloquear o autoplay, volta para "idle" e a pessoa clica para ouvir.

As cores vêm sempre dos tokens de `resources/css/app.css` (`bg-primary`, `text-muted-foreground`, `bg-voice`...), nunca de valores fixos.

## Idioma

A interface é em português (`APP_LOCALE=pt_BR`); as entrevistas são em inglês. Os textos das telas ficam direto nos componentes Vue. As mensagens do servidor (validação, login, senha e e-mails) ficam em `lang/pt_BR/*.php` e `lang/pt_BR.json`.

## Configuração do produto

`config/faladev.php` concentra as regras configuráveis. Por enquanto, só o limite mensal de entrevistas iniciadas por conta (`FALADEV_MONTHLY_SESSION_LIMIT`, padrão 10, valor provisório até medir o custo por sessão).
