<!doctype html>
<html lang="pt-BR" data-type="software" data-radius="soft" data-buttons="tonal">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Privacidade no FalaDev: o que acontece com sua voz e suas respostas</title>
<meta name="description" content="Como o FalaDev trata o áudio, as transcrições e os relatórios das entrevistas, e como apagar seus dados.">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<meta name="theme-color" content="#111111">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@400..700&family=Funnel+Sans:wght@400..600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/system.css">
<style>
:root {
  --p-bg: #111111; --p-surface: #1a1a1a; --p-ink: #f4f4f0; --p-muted: #848482;
  --p-accent: #c8f136; --p-accent-ink: #111111; --p-action: #c8f136; --p-action-ink: #111111;
  --font-display: "Funnel Display", system-ui, sans-serif;
  --font-text: "Funnel Sans", system-ui, sans-serif;
  --display-weight: 400; --display-tracking: -0.03em;
}
.legal { padding-block: calc(var(--nav) + var(--s-8)) var(--s-9); }
.legal h1 { font-size: var(--t-h2); }
.legal h2 { font-size: var(--t-h3); margin-top: var(--s-7); }
.legal p, .legal li { max-width: 62ch; font-size: var(--t-body); line-height: 1.7; }
.legal ul { padding-left: 1.2em; }
.nav__logo svg { width: 28px; height: 28px; }
.nav__logo { gap: 10px; }
</style>
</head>
<body>
<header class="nav">
  <div class="wrap nav__bar">
    <a class="skip-link" href="#top">Pular para o conteúdo</a>
    <a class="nav__logo" href="{{ route('home') }}" aria-label="FalaDev, início">
      <svg viewBox="0 0 40 40" aria-hidden="true"><path fill="#c8f136" d="M9 5H31A6 6 0 0 1 37 11V23A6 6 0 0 1 31 29H19L10 36L11 29H9A6 6 0 0 1 3 23V11A6 6 0 0 1 9 5ZM16.5 11.5L9.5 17L16.5 22.5L18.3 20.4L13.4 17L18.3 13.6ZM23.5 11.5L21.7 13.6L26.6 17L21.7 20.4L23.5 22.5L30.5 17Z"/></svg>FalaDev
    </a>
  </div>
</header>
<main id="top" class="wrap legal">
  <h1>Privacidade no FalaDev</h1>
  <p>O que acontece com a sua voz, as suas respostas e os seus relatórios.</p>

  <h2>Microfone</h2>
  <ul>
    <li>O navegador pede acesso ao microfone só quando você clica em testar ou em gravar.</li>
    <li>Enquanto grava, a tela mostra um indicador e os botões de parar e cancelar.</li>
    <li>O teste de microfone fica no seu navegador: nada dele é enviado.</li>
  </ul>

  <h2>Áudio e transcrição</h2>
  <ul>
    <li>Cada resposta gravada é enviada a um serviço de transcrição para virar texto.</li>
    <li>O áudio não é guardado. Ficam a transcrição que você revisou e enviou e o relatório.</li>
    <li>O texto das respostas é enviado ao modelo de IA que conduz e avalia a entrevista.</li>
  </ul>

  <h2>Dados técnicos</h2>
  <p>Registramos dados técnicos de uso, como o resultado do teste de microfone, falhas de transcrição e o tempo das etapas, para melhorar o produto. Eles não incluem sua voz nem o texto das suas respostas.</p>

  <h2>Apagar seus dados</h2>
  <ul>
    <li>No histórico, abra uma entrevista e use “Apagar entrevista”: perguntas, respostas e relatório são apagados.</li>
    <li>Em Configurações, “Excluir conta” apaga a conta e todas as entrevistas.</li>
  </ul>

  <h2>Treinamento de modelos</h2>
  <p>Suas transcrições não são usadas para treinar modelos sem um consentimento seu, pedido à parte.</p>

  <p style="margin-top: var(--s-8)"><a class="link" href="{{ route('home') }}">Voltar para o início</a></p>
</main>
</body>
</html>
