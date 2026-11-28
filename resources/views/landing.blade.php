@php
    $primaryHref = auth()->check() ? route('interviews.create') : route('register');
    $primaryLabel = 'Começar uma entrevista';
    $title = 'FalaDev: simulador de entrevista técnica em inglês para devs';
    $description = 'Treine entrevistas de algoritmos, system design e comportamentais falando em inglês com uma IA e receba notas de conteúdo, comunicação e inglês.';
    $appUrl = rtrim(config('app.url'), '/');
    // canonical only once the real domain is configured (not localhost)
    $hasDomain = ! str_contains($appUrl, 'localhost') && ! str_contains($appUrl, '127.0.0.1');
    $faq = [
        ['q' => 'Quanto tempo dura uma entrevista?', 'a' => '15 ou 30 minutos, à sua escolha. Cada resposta tem até 3 minutos.'],
        ['q' => 'O que acontece com o meu áudio?', 'a' => 'Ele vira texto para você revisar. Guardamos a transcrição e o relatório; o áudio não é guardado.'],
        ['q' => 'O FalaDev avalia minha pronúncia?', 'a' => 'Não. A nota de inglês olha gramática, vocabulário e clareza do texto. Pronúncia e sotaque ficam de fora.'],
        ['q' => 'Preciso de microfone?', 'a' => 'Sim. Antes de começar, um teste confere se sua voz está sendo captada; ele fica no seu navegador.', 'phone' => false],
        ['q' => 'A nota diz se vou ser contratado?', 'a' => 'Não. O relatório é prática com feedback, não uma previsão de contratação.'],
    ];
    // Real questions from the interviewer's script (App\Interview\FakeInterviewer)
    $questions = [
        ['Algoritmos', 'How would you check whether a string of brackets is balanced?'],
        ['System design', 'How would you design a rate limiter for a public API?'],
        ['Comportamental', 'Tell me about a time you disagreed with a teammate.'],
        ['Algoritmos', 'How would you detect whether a linked list has a cycle?'],
        ['System design', 'Which database would you choose here, and why?'],
        ['Comportamental', 'What was the result, and how did you measure it?'],
        ['Algoritmos', 'What is the time and space complexity of that approach?'],
        ['System design', 'Let’s design a URL shortener. What would you clarify first?'],
        ['Comportamental', 'Looking back, what would you do differently?'],
        ['Algoritmos', 'Which edge cases would you test?'],
    ];
    $rowA = array_slice($questions, 0, 5);
    $rowB = array_slice($questions, 5);
@endphp
<!doctype html>
<html lang="pt-BR" data-type="software" data-radius="soft" data-buttons="tonal" data-motion>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="index, follow">
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="FalaDev">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="Entrevistas de 15 ou 30 minutos em inglês, por voz, com relatório de conteúdo técnico, comunicação e inglês.">
<meta property="og:image" content="{{ url('share.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon.ico" sizes="any">
<meta name="theme-color" content="#111111">
@if ($hasDomain)
<link rel="canonical" href="{{ $appUrl }}/">
@else
<!-- <link rel="canonical" href="https://[domínio do FalaDev]/"> : sai do comentário quando APP_URL tiver o domínio real -->
@endif
<meta name="site-kind" content="software">
<meta name="page-type" content="software">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@400..700&family=Funnel+Sans:wght@400..600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/system.css">
<link rel="stylesheet" href="assets/ui/cards.css">
<link rel="stylesheet" href="assets/motion/motion.css">
<link rel="stylesheet" href="assets/backgrounds/backgrounds.css">
<link rel="stylesheet" href="assets/layout/layout.css">
<link rel="stylesheet" href="assets/icons/icon.css">
<style>
:root {
  --p-bg: #111111; --p-surface: #1a1a1a; --p-ink: #f4f4f0; --p-muted: #848482;
  --p-accent: #c8f136; --p-accent-ink: #111111; --p-action: #c8f136; --p-action-ink: #111111;
  --p-inv-bg: #f4f4f0; --p-inv-surface: #e2e2de; --p-inv-ink: #111111; --p-inv-muted: #5d5d5c;
  --p-inv-accent: #111111; --p-inv-accent-ink: #f4f4f0; --p-inv-action: #111111; --p-inv-action-ink: #f4f4f0;
  --font-display: "Funnel Display", system-ui, sans-serif;
  --font-text: "Funnel Sans", system-ui, sans-serif;
  --font-mono: "DM Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
  --display-weight: 400;
  --display-tracking: -0.03em;
  --display-tracking-xl: -0.04em;
  --ly-scale: 0.98;
}
</style>
<!-- the client's own lit ground: the brand's off-white and graphite, in place of the kit's sand -->
<style>:root { --ground-bg: #f4f4f0; --ground-surface: #e2e2de; --ground-ink: #111111; --ground-muted: #5d5d5c; --ground-accent: #111111; }</style>
<link rel="stylesheet" href="assets/ui/base.css">
<script>document.documentElement.classList.add('m-enter');setTimeout(()=>document.documentElement.classList.add('m-enter-done'),700)</script>
<script src="assets/backgrounds/backgrounds.js"></script>
<script>document.documentElement.classList.add('mo-js');setTimeout(()=>document.documentElement.classList.add('mo-safe'),2500)</script>
<link rel="modulepreload" href="assets/moments/moments.js">
<link rel="modulepreload" href="assets/moments/lib.js">
<link rel="modulepreload" href="assets/moments/letters-assemble/moment.js">
<script>document.documentElement.classList.add('sc-js');setTimeout(()=>document.documentElement.classList.add('sc-safe'),2500)</script>
<link rel="stylesheet" href="assets/scenes/scenes.css">
<link rel="stylesheet" href="assets/scenes/parts-lock-into-the-whole/scene.css">
<style>
  .nav__logo svg { width: 28px; height: 28px; flex: none; }
  .nav__logo { gap: 10px; }

  /* ---------- FalaDev screens: graphite windows, lime accent */
  .fd { font-family: var(--font-text); font-size: 13px; line-height: 1.45; color: #f4f4f0; background: #161616;
        border: 1px solid rgb(244 244 240 / .12); border-radius: 16px; box-shadow: 0 30px 60px -30px rgb(0 0 0 / .8); }
  .fd small, .fd .fd-meta { font-size: 12px; color: #a3a3a0; }
  .fd-pad { padding: 18px; display: grid; gap: 12px; }
  .fd-q { font-size: 15px; line-height: 1.45; font-weight: 500; color: #f4f4f0; }
  .fd-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px; border-radius: 999px; border: 1px solid rgb(244 244 240 / .18); font-size: 12px; color: #d6d6d2; }
  .fd-chip--on { background: #c8f136; border-color: #c8f136; color: #111111; }
  .fd-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .fd-score { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 6px 12px; align-items: center; }
  .fd-meter { grid-column: 1 / -1; height: 6px; border-radius: 3px; background: rgb(244 244 240 / .12); overflow: hidden; }
  .fd-meter > b { display: block; height: 100%; border-radius: 3px; background: #f4f4f0; width: calc(var(--v) * 100%); }
  .fd-meter--accent > b { background: #c8f136; }
  .fd-quote { border-left: 2px solid #c8f136; padding-left: 10px; font-style: italic; color: #d6d6d2; margin: 0; }
  .fd-btn { display: inline-flex; align-items: center; justify-content: center; height: 30px; padding: 0 12px; border-radius: 8px; font-size: 12px; font-weight: 500; border: 1px solid rgb(244 244 240 / .2); color: #f4f4f0; }
  .fd-btn--go { background: #c8f136; border-color: transparent; color: #111111; }
  .fd-box { border: 1px solid rgb(244 244 240 / .14); border-radius: 10px; padding: 10px 12px; color: #e6e6e2; }
  .fd-rec { display: inline-flex; align-items: center; gap: 6px; color: #f4f4f0; font-weight: 500; }
  .fd-rec::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #c8f136; animation: fd-pulse 1.6s ease-in-out infinite; }

  /* ---------- hero: the interview screen with floating chips and a lime glow */
  .fd-stage { position: relative; height: 100%; min-height: 520px; display: grid; place-items: center; border-radius: var(--r-lg); overflow: hidden;
              background: radial-gradient(60% 55% at 55% 45%, rgb(200 241 54 / .22), transparent 70%), #141414; border: 1px solid rgb(244 244 240 / .08); }
  .fd-stage::before { content: ""; position: absolute; inset: -20%; background: radial-gradient(30% 30% at 30% 70%, rgb(200 241 54 / .16), transparent 70%); animation: fd-drift 14s ease-in-out infinite alternate; }
  .fd-hero-card { position: relative; z-index: 1; width: min(420px, calc(100% - 48px)); }
  .fd-person { display: flex; align-items: center; gap: 10px; }
  .fd-avatar { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: #c8f136; flex: none; }
  .fd-avatar svg { width: 18px; height: 18px; }
  .fd-avatar--you { background: #2a2a2a; color: #f4f4f0; font-size: 12px; font-weight: 500; }
  .fd-speaking { display: flex; align-items: center; gap: 3px; height: 18px; margin-left: auto; }
  .fd-speaking i { width: 3px; height: 100%; border-radius: 2px; background: #c8f136; transform-origin: 50% 50%; animation: fd-wave 1.1s ease-in-out infinite; }
  .fd-speaking i:nth-child(2) { animation-delay: -.15s } .fd-speaking i:nth-child(3) { animation-delay: -.3s }
  .fd-speaking i:nth-child(4) { animation-delay: -.45s } .fd-speaking i:nth-child(5) { animation-delay: -.6s }
  .fd-type { min-height: 60px; }
  .fd-type::after { content: ""; display: inline-block; width: 2px; height: 1em; margin-left: 2px; vertical-align: -2px; background: #c8f136; animation: fd-caret 1s steps(1) infinite; }
  .fd-float { position: absolute; z-index: 2; display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 999px;
              background: #f4f4f0; color: #111111; font-size: 13px; font-weight: 500; box-shadow: 0 16px 32px -18px rgb(0 0 0 / .7);
              animation: fd-float 6s ease-in-out infinite; }
  .fd-float::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #111111; }
  .fd-float--a { top: 9%; left: 6%; }
  .fd-float--b { top: 30%; right: 5%; animation-delay: -1.5s; }
  .fd-float--c { bottom: 14%; left: 4%; animation-delay: -3s; }
  .fd-float--d { bottom: 6%; right: 8%; animation-delay: -4.5s; background: #c8f136; }

  /* ---------- problem: three pains and the question marquee */
  .fd-pains { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--gutter); }
  .fd-pain { display: grid; gap: var(--s-4); align-content: start; padding: var(--s-6); border-radius: var(--r-lg); background: var(--surface); border: 1px solid var(--line);
             transition: transform .25s var(--ease), border-color .25s var(--ease); }
  .fd-pain:hover { transform: translateY(-4px); border-color: rgb(200 241 54 / .6); }
  .fd-pain svg { width: 28px; height: 28px; color: #c8f136; }
  .fd-pain p { margin: 0; font-size: var(--t-lead); line-height: 1.45; }
  .fd-marquee { margin-top: var(--s-8); display: grid; gap: var(--s-4); overflow: hidden;
                -webkit-mask: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); mask: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
  .fd-track { display: flex; gap: var(--s-4); width: max-content; animation: fd-scroll 48s linear infinite; }
  .fd-track--rev { animation-direction: reverse; animation-duration: 56s; }
  .fd-marquee:hover .fd-track { animation-play-state: paused; }
  .fd-qchip { display: inline-flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); white-space: nowrap; font-size: 15px; }
  .fd-qchip b { font-weight: 500; font-size: 12px; padding: 3px 9px; border-radius: 999px; background: #c8f136; color: #111111; }

  /* ---------- the scene's screen: the interview room cut into its panels */
  .fd-room .pl__panel { background: #1a1a1a; color: #f4f4f0; }
  .fd-room .pl__bar { display: flex; align-items: center; gap: 14px; padding: 0 14px; }
  .fd-room .pl__bar .dots { display: inline-flex; gap: 5px; }
  .fd-room .pl__bar .dots i { width: 9px; height: 9px; border-radius: 50%; background: rgb(244 244 240 / .2); }
  .fd-room .pl__bar .tab { color: #a3a3a0; }
  .fd-room .pl__bar .tab.on { color: #f4f4f0; border-bottom: 2px solid #c8f136; padding-block: 11px 9px; }
  .fd-room .pl__side { display: grid; align-content: start; gap: 8px; padding: 14px; }
  .fd-room .pl__side small { color: #a3a3a0; margin-top: 6px; }
  .fd-room .pl__side span { display: block; padding: 6px 8px; border-radius: 6px; color: #d6d6d2; }
  .fd-room .pl__side span.on { background: rgb(244 244 240 / .08); color: #f4f4f0; }
  .fd-room .pl__main { display: grid; align-content: start; gap: 14px; padding: 18px 20px; }
  .fd-room .pl__title { margin: 0; font-family: var(--font-display); font-size: 22px; letter-spacing: -0.02em; }
  .fd-room .pl__insp { display: grid; align-content: start; gap: 12px; padding: 14px; }
  .fd-room .pl__insp h4 { margin: 0; font-size: 13px; font-weight: 500; }
  .fd-room [data-ink="x"] { display: block; height: 4px; border-radius: 2px; background: #c8f136; width: calc(var(--v) * 100%); }
  .fd-room .fd-progress { height: 4px; border-radius: 2px; background: rgb(244 244 240 / .12); }

  /* ---------- steps */
  #como-funciona .panel { --p-h: 300px; }
  @media (max-width: 639px) { #como-funciona .panel { --p-h: 220px; } }

  /* ---------- report: bars fill when the section comes into view */
  .fd-frame { height: 560px; display: grid; place-items: center; padding: 40px; background: #e2e2de; }
  .fd-report { width: 100%; max-width: 480px; }
  .fd-fill .fd-meter > b { transition: width 1.2s cubic-bezier(.2, .8, .2, 1); }
  .fd-fill:not(.is-in) .fd-meter > b { width: 0; }
  .fd-fill .fd-meter:nth-of-type(2) > b { transition-delay: .15s }
  @media (max-width: 639px) { .fd-frame { height: auto; padding: 16px; } }

  #frase .mo-la__sub { color: var(--ink); }
  /* ---------- statement facts */
  .fd-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--s-5) var(--gutter); margin: var(--s-8) 0 0; }
  .fd-facts > div { display: grid; gap: var(--s-1); border-top: 1px solid var(--line); padding-top: var(--s-4); }
  .fd-facts dt { color: var(--muted); font-size: var(--t-small); }
  .fd-facts dd { margin: 0; font-family: var(--font-display); font-size: var(--t-stat); letter-spacing: -0.02em; }

  @media (max-width: 639px) {
    .fd-pains { grid-template-columns: 1fr; gap: var(--s-4); }
    .fd-pain { padding: var(--s-5); grid-template-columns: 28px minmax(0, 1fr); align-items: start; }
    .fd-no-phone { display: none !important; }
  }
  @media (max-width: 1023px) {
    .fd-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .fd-float--b, .fd-float--c { display: none; }
  }
  @media (min-width: 640px) and (max-width: 1279px) { #como-funciona { --h: 2; } }
  @media (max-width: 639px) {
    .fd-stage { min-height: 0; }
    .fd-float { font-size: 12px; padding: 7px 11px; }
    .fd-float--a { top: 12px; left: 12px; } .fd-float--d { bottom: 12px; right: 12px; }
    .fd-marquee { margin-top: var(--s-6); }
    .fd-facts { margin-top: var(--s-7); }
  }

  /* FAQ: grows with its answers on short screens and keeps a dark margin before the light footer */
  main > #perguntas { height: auto; min-height: 100svh; padding-bottom: calc(var(--s-10, 128px) + var(--bar)); }
  main > #perguntas > .wrap { height: auto; }

  @media (max-width: 379px) { footer.closing > .wrap { grid-template-columns: minmax(0, 1fr); } .btn--xl { white-space: normal; } }

  @keyframes fd-float { 0%, 100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
  @keyframes fd-drift { from { transform: translate(-4%, 3%) } to { transform: translate(6%, -5%) } }
  @keyframes fd-wave { 0%, 100% { transform: scaleY(.25) } 50% { transform: scaleY(1) } }
  @keyframes fd-pulse { 0%, 100% { opacity: 1 } 50% { opacity: .35 } }
  @keyframes fd-caret { 50% { opacity: 0 } }
  @keyframes fd-scroll { to { transform: translateX(-50%) } }
  @media (prefers-reduced-motion: reduce) {
    .fd-float, .fd-stage::before, .fd-speaking i, .fd-rec::before, .fd-type::after { animation: none !important; }
    .fd-track { animation: none; flex-wrap: wrap; width: auto; }
    .fd-track [aria-hidden="true"] { display: none; }
    .fd-fill:not(.is-in) .fd-meter > b { width: calc(var(--v) * 100%); }
  }
</style>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'WebSite', 'name' => 'FalaDev', 'url' => $appUrl.'/', 'inLanguage' => 'pt-BR'],
        [
            '@type' => 'SoftwareApplication',
            'name' => 'FalaDev',
            'applicationCategory' => 'EducationalApplication',
            'operatingSystem' => 'Web',
            'inLanguage' => 'pt-BR',
            'url' => $appUrl.'/',
            'description' => $description,
            'featureList' => [
                'Entrevistas de algoritmos, system design e comportamentais',
                'Níveis Júnior, Pleno e Sênior',
                'Entrevistas de 15 ou 30 minutos',
                'Respostas por voz com transcrição revisável',
                'Relatório com notas de 1 a 5 e trechos da resposta',
            ],
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ], $faq),
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
</head>
<body>
<!-- type: software. hero split (the interview screen with floating chips), nav-mega, close-type, footer-statement,
     buttons tonal, the client's logo and palette (graphite, lime, off-white), type funnel.
     effect: parts-lock-into-the-whole scene (#tela); moment: letters-assemble (#frase) -->

<header class="nav">
  <div class="wrap nav__bar">
    <a class="skip-link" href="#top">Pular para o conteúdo</a>
    <a class="nav__logo" href="#top" aria-label="FalaDev, início">
      <svg viewBox="0 0 40 40" aria-hidden="true"><path fill="#c8f136" d="M9 5H31A6 6 0 0 1 37 11V23A6 6 0 0 1 31 29H19L10 36L11 29H9A6 6 0 0 1 3 23V11A6 6 0 0 1 9 5ZM16.5 11.5L9.5 17L16.5 22.5L18.3 20.4L13.4 17L18.3 13.6ZM23.5 11.5L21.7 13.6L26.6 17L21.7 20.4L23.5 22.5L30.5 17Z"/></svg>FalaDev
    </a>
    <nav class="nav__links" aria-label="Principal">
      <div class="nav__item">
        <a class="nav__link" href="#tela">Produto <svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        <div class="nav__menu">
          <div class="wrap grid">
            <div class="menu-col span-3">
              <p class="t-label">Na entrevista</p>
              <a href="#tela">Sala de entrevista<span>Pergunta, voz e transcrição</span></a>
              <a href="#como-funciona">Como funciona<span>Escolha, responda, leia</span></a>
              <a href="#problema">Perguntas de exemplo<span>Algoritmos, design, comportamento</span></a>
            </div>
            <div class="menu-col span-3">
              <p class="t-label">Depois dela</p>
              <a href="#relatorio">Relatório<span>Quatro notas com trechos seus</span></a>
              <a href="#perguntas">Dúvidas<span>Tempo, áudio e pronúncia</span></a>
            </div>
            <a class="menu-card span-3 start-10" href="#relatorio">
              <div class="panel">
                <div class="stage stage--fade-br"><div class="at-corner" style="--ax:24px;--ay:24px">
                  <div class="fd fd-pad" style="width:260px" role="img" aria-label="Notas do relatório do FalaDev">
                    <div class="fd-score"><span>Conteúdo técnico</span><span>4 de 5</span><span class="fd-meter fd-meter--accent"><b style="--v:.8"></b></span></div>
                    <div class="fd-score"><span>Inglês</span><span>3 de 5</span><span class="fd-meter"><b style="--v:.6"></b></span></div>
                  </div>
                </div></div>
              </div>
              <span class="t-card">Relatório por dimensão</span>
              <span class="t-small muted">Técnico, comunicação e inglês</span>
            </a>
          </div>
        </div>
      </div>
      <div class="nav__item"><a class="nav__link" href="#como-funciona">Como funciona</a></div>
      <div class="nav__item"><a class="nav__link" href="#relatorio">Relatório</a></div>
      <div class="nav__item"><a class="nav__link" href="#perguntas">Dúvidas</a></div>
    </nav>
    <div class="nav__actions">
      @auth
        <a class="nav__link" href="{{ route('dashboard') }}">Painel</a>
      @else
        <a class="nav__link" href="{{ route('login') }}">Entrar</a>
      @endauth
      <a class="btn btn--primary" href="{{ $primaryHref }}">{{ $primaryLabel }}</a>
    </div>
    <button class="nav__toggle" type="button" aria-expanded="false" aria-label="Menu">
      <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path d="M2 5h14M2 13h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
  </div>
</header>

<main id="top">
  <section class="hero hero--split" data-enter aria-labelledby="hero-h">
    <div class="wrap"><div class="hero-text grid hero__grid">
      <div class="hero__text span-5">
        <p class="hero__kicker"><span class="dot" aria-hidden="true"></span>Entrevistas de 15 ou 30 minutos</p>
        <h1 id="hero-h">Simulador de entrevista em inglês para devs</h1>
        <p class="hero__lead">A IA pergunta em voz alta, você responde falando e recebe notas de conteúdo técnico, comunicação e inglês.</p>
        <div class="btn-row">
          <a class="btn btn--primary" href="{{ $primaryHref }}">{{ $primaryLabel }}<svg class="m-arrow" viewBox="0 0 12 12" aria-hidden="true"><path class="m-arrow__stem" d="M1 6h8"/><path class="m-arrow__head" d="M4.5 2.5 8 6l-3.5 3.5"/></svg></a>
          <a class="btn btn--secondary" href="#como-funciona">Ver como funciona</a>
        </div>
      </div>
      <div class="hero__media span-6 start-7" style="background:none">
        <div class="fd-stage">
          <div class="fd fd-pad fd-hero-card" role="img" aria-label="Tela da entrevista: o entrevistador pergunta em inglês, sua resposta é transcrita e a nota aparece">
            <div class="fd-row"><small>Algoritmos · Pleno</small><span class="fd-rec">11:42</span></div>
            <div class="fd-person">
              <span class="fd-avatar" aria-hidden="true"><svg viewBox="0 0 40 40"><path fill="#111111" d="M9 5H31A6 6 0 0 1 37 11V23A6 6 0 0 1 31 29H19L10 36L11 29H9A6 6 0 0 1 3 23V11A6 6 0 0 1 9 5ZM16.5 11.5L9.5 17L16.5 22.5L18.3 20.4L13.4 17L18.3 13.6ZM23.5 11.5L21.7 13.6L26.6 17L21.7 20.4L23.5 22.5L30.5 17Z"/></svg></span>
              <span>Entrevistador</span>
              <span class="fd-speaking" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <p class="fd-q" style="margin:0" lang="en">What is the time and space complexity of that approach?</p>
            <div class="fd-person"><span class="fd-avatar fd-avatar--you" aria-hidden="true">Você</span><small>Transcrição para revisar</small></div>
            <div class="fd-box fd-type" lang="en" data-type-text="It runs in linear time, because I go through the array once, and it uses linear extra space for the hash map.">It runs in linear time, because I go through the array once, and it uses linear extra space for the hash map.</div>
            <div class="fd-row"><span class="fd-btn">Gravar de novo</span><span class="fd-btn fd-btn--go">Enviar resposta</span></div>
          </div>
          <span class="fd-float fd-float--a">Voz em inglês</span>
          <span class="fd-float fd-float--b">Transcrição revisável</span>
          <span class="fd-float fd-float--c">Áudio não é guardado</span>
          <span class="fd-float fd-float--d">Notas de 1 a 5</span>
        </div>
      </div>
    </div></div>
  </section>

  <section class="section" id="problema" aria-labelledby="problem-h">
    <div class="wrap">
      <div class="section-head">
        <h2 id="problem-h">Saber a resposta não basta. <span class="muted">Na entrevista em inglês, você precisa explicar.</span></h2>
      </div>
      <div class="fd-pains">
        <div class="fd-pain">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v3"/><path d="M3 3l18 18"/></svg>
          <p>Você resolve o problema, mas trava ao explicar o raciocínio em inglês.</p>
        </div>
        <div class="fd-pain">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/></svg>
          <p>Treina algoritmos em silêncio, sem ninguém perguntando em voz alta.</p>
        </div>
        <div class="fd-pain">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01"/></svg>
          <p>Sai da entrevista sem saber se o problema foi o conteúdo ou o inglês.</p>
        </div>
      </div>
      <div class="fd-marquee" aria-label="Perguntas que o entrevistador faz">
        @foreach ([$rowA, $rowB] as $i => $row)
          <div @class(['fd-track', 'fd-track--rev' => $i === 1])>
            @foreach ([false, true] as $copy)
              @foreach ($row as [$tag, $text])
                <span class="fd-qchip" @if ($copy) aria-hidden="true" @endif><b>{{ $tag }}</b><span lang="en">{{ $text }}</span></span>
              @endforeach
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <section class="sc" id="tela" data-big-scene="parts-lock-into-the-whole" data-big-scene-opts='{"hold": 1}' style="--h: 1" aria-labelledby="pl-h">
    <div class="sc__stage">
      <p class="pl__word" aria-hidden="true">15</p>
      <div class="pl__ui fd-room" data-ui role="img" aria-label="A sala de entrevista do FalaDev: perguntas, pergunta atual, transcrição da resposta e notas">
        <div class="pl__panel pl__bar"><span class="dots"><i></i><i></i><i></i></span><b>FalaDev</b> <span class="tab on">Entrevista</span><span class="tab">Histórico</span><span class="tab">Relatório</span></div>
        <div class="pl__panel pl__side"><small>Perguntas</small><span>1. Two numbers that add up</span><span class="on">2. Complexity</span><span>3. Balanced brackets</span><span>4. Edge cases</span><small>Tempo</small><span>11:42 restantes</span></div>
        <div class="pl__panel pl__main">
          <p class="pl__title">Entrevista de <span class="pl__title-word">15</span> minutos</p>
          <small class="fd-meta">Pergunta 2 · aprofundamento</small>
          <p class="fd-q" style="margin:0" lang="en">What is the time and space complexity of that approach?</p>
          <div class="fd-row"><span class="fd-rec" data-layer="1">Resposta gravada · 0:48</span><span class="fd-btn">Repetir pergunta</span></div>
          <div class="fd-box" data-layer="1" lang="en">It runs in linear time, because I go through the array once. It also uses linear extra space for the hash map.</div>
          <div class="fd-progress"><b data-layer="1" data-ink="x" style="--v:.27"></b></div>
          <div class="fd-row" data-layer="2"><span class="fd-btn">Gravar de novo</span><span class="fd-btn fd-btn--go">Enviar resposta</span></div>
        </div>
        <div class="pl__panel pl__insp">
          <h4>Relatório</h4>
          <div class="fd-score" data-layer="2"><span>Conteúdo técnico</span><span>4</span><span class="fd-meter"><b style="--v:.8"></b></span></div>
          <div class="fd-score" data-layer="2"><span>Comunicação</span><span>3</span><span class="fd-meter"><b style="--v:.6"></b></span></div>
          <div class="fd-box" data-layer="3"><small>Inglês</small><br><span lang="en">“go through the array” → “iterate over the array”</span></div>
        </div>
      </div>
      <div class="pl__spec">
        <h2 id="pl-h">A sala de entrevista do FalaDev</h2>
        <dl>
          <div><dt>Categorias</dt><dd>Algoritmos, system design, comportamental</dd></div>
          <div><dt>Níveis</dt><dd>Júnior, Pleno, Sênior</dd></div>
          <div><dt>Duração</dt><dd>15 ou 30 minutos</dd></div>
          <div><dt>Cada resposta</dt><dd>Até 3 minutos, revisada</dd></div>
        </dl>
      </div>
    </div>
  </section>

  <section class="section" id="como-funciona" style="--hm: 2" aria-labelledby="steps-h">
    <div class="wrap">
      <div class="section-head">
        <h2 id="steps-h">Uma entrevista em 3 passos</h2>
      </div>
      <div class="grid">
        <article class="card card--row card--open span-4 m-nolift">
          <span class="step-num">1</span>
          <h3>Escolha a entrevista</h3>
          <p>Categoria, nível e duração, depois um teste rápido de microfone.</p>
          <div class="panel frame--lit">
            <div class="stage stage--fade-b"><div class="at-rise fit" style="--fit-max:300px;--ax:max(24px, 50% - 150px);--ay:32px">
              <div class="fd fd-pad" role="img" aria-label="Configuração: Algoritmos, Pleno, 15 minutos">
                <small>Categoria</small>
                <div class="fd-row" style="justify-content:flex-start;flex-wrap:wrap;gap:6px"><span class="fd-chip fd-chip--on">Algoritmos</span><span class="fd-chip">System design</span><span class="fd-chip">Comportamental</span></div>
                <small>Nível</small>
                <div class="fd-row" style="justify-content:flex-start;gap:6px"><span class="fd-chip">Júnior</span><span class="fd-chip">Pleno</span><span class="fd-chip">Sênior</span></div>
                <small>Duração</small>
                <div class="fd-row" style="justify-content:flex-start;gap:6px"><span class="fd-chip">15 minutos</span><span class="fd-chip">30 minutos</span></div>
              </div>
            </div></div>
          </div>
        </article>
        <article class="card card--row card--open span-4 m-nolift">
          <span class="step-num">2</span>
          <h3>Grave a resposta em voz alta</h3>
          <p>Grave até 3 minutos e corrija a transcrição antes de enviar.</p>
          <div class="panel frame--lit">
            <div class="stage stage--fade-b"><div class="at-rise fit" style="--fit-max:300px;--ax:max(24px, 50% - 150px);--ay:32px">
              <div class="fd fd-pad" role="img" aria-label="Gravando uma resposta de 48 segundos">
                <div class="fd-row"><span class="fd-rec">Gravando</span><small>0:48 de 3:00</small></div>
                <div class="fd-speaking" style="margin:0;height:28px" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                <div class="fd-box" lang="en">I would use a hash map to store each number and its index.</div>
                <div class="fd-row"><span class="fd-btn">Gravar de novo</span><span class="fd-btn fd-btn--go">Enviar resposta</span></div>
              </div>
            </div></div>
          </div>
        </article>
        <article class="card card--row card--open span-4 m-nolift">
          <span class="step-num">3</span>
          <h3>Leia o relatório da entrevista</h3>
          <p>Notas de 1 a 5, cada uma com o trecho da sua resposta que a explica.</p>
          <div class="panel frame--lit">
            <div class="stage stage--fade-b"><div class="at-rise fit" style="--fit-max:300px;--ax:max(24px, 50% - 150px);--ay:32px">
              <div class="fd fd-pad" role="img" aria-label="Relatório com notas por dimensão">
                <div class="fd-row"><span>Resultado geral</span><span style="font-size:20px">3 de 5</span></div>
                <div class="fd-score"><span>Conteúdo técnico</span><span>4</span><span class="fd-meter fd-meter--accent"><b style="--v:.8"></b></span></div>
                <div class="fd-score"><span>Raciocínio</span><span>3</span><span class="fd-meter"><b style="--v:.6"></b></span></div>
                <div class="fd-score"><span>Comunicação</span><span>3</span><span class="fd-meter"><b style="--v:.6"></b></span></div>
              </div>
            </div></div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="section ground-inverse" id="relatorio" aria-labelledby="report-h">
    <div class="wrap">
      <div class="grid grid--top">
        <div class="span-5 stack-6">
          <h2 id="report-h">Relatório com quatro notas. <span class="muted">Cada uma com um trecho seu.</span></h2>
          <p class="t-lead fd-no-phone">Conteúdo técnico, estrutura do raciocínio, comunicação e inglês, de 1 a 5, com a correção das frases que mais pesaram.</p>
        </div>
        <div class="span-6 start-7 frame fd-frame">
          <div class="fd fd-pad fd-report fd-fill" role="img" aria-label="Relatório: nota geral 3 de 5, um ponto forte citado da resposta e uma correção de inglês">
            <div class="fd-row"><span>Resultado geral</span><span style="font-size:28px;font-family:var(--font-display)">3 de 5</span></div>
            <div class="fd-score"><span>Conteúdo técnico</span><span>4 de 5</span><span class="fd-meter fd-meter--accent"><b style="--v:.8"></b></span></div>
            <div class="fd-score"><span>Inglês</span><span>3 de 5</span><span class="fd-meter"><b style="--v:.6"></b></span></div>
            <small>Ponto forte · pergunta 1</small>
            <p class="fd-quote" lang="en">“I would use a hash map to store each number and its index, then check the complement in one pass.”</p>
            <small>Inglês · pergunta 2</small>
            <div class="fd-box"><span lang="en">“go through the array” → “iterate over the array”</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="frase" data-moment="letters-assemble" data-moment-opts='{"spread": 1}' aria-labelledby="frase-h">
    <div class="wrap">
      <h2 class="mo-la__type" id="frase-h">Pense alto, em inglês.</h2>
      <p class="mo-la__sub t-lead measure">Você responde falando, como na entrevista de verdade, e revisa o texto antes de enviar.</p>
      <dl class="fd-facts">
        <div><dt>Categorias</dt><dd>3</dd></div>
        <div><dt>Níveis</dt><dd>3</dd></div>
        <div><dt>Notas no relatório</dt><dd>4</dd></div>
        <div><dt>Minutos por resposta</dt><dd>até 3</dd></div>
      </dl>
    </div>
  </section>

  <section class="section" id="perguntas" aria-labelledby="faq-h">
    <div class="wrap">
      <div class="grid grid--top">
        <div class="span-5 stack-4">
          <h2 id="faq-h">Dúvidas sobre a entrevista</h2>
          <a class="link" href="{{ route('privacy') }}">Ler a política de privacidade</a>
        </div>
        <div class="faq span-6 start-7">
          @foreach ($faq as $item)
            <div @class(['faq__item', 'fd-no-phone' => ($item['phone'] ?? true) === false])><h3>{{ $item['q'] }}</h3><p>{{ $item['a'] }}</p></div>
          @endforeach
        </div>
      </div>
    </div>
  </section>
</main>

<footer class="footer closing footer--statement" id="contact" aria-labelledby="close-h">
  <div class="wrap">
    <div class="closing__cta close--type">
      <div class="section-head">
        <h2 id="close-h">Treine sua próxima entrevista em inglês</h2>
        <p class="t-small muted">Entrevistas de 15 ou 30 minutos, com relatório ao final.</p>
      </div>
      <a class="btn btn--primary btn--xl btn--wide" href="{{ $primaryHref }}">{{ $primaryLabel }}<svg class="m-arrow" viewBox="0 0 12 12" aria-hidden="true"><path class="m-arrow__stem" d="M1 6h8"/><path class="m-arrow__head" d="M4.5 2.5 8 6l-3.5 3.5"/></svg></a>
    </div>
    <div class="closing__links">
      <div class="grid">
        <div class="span-4 stack-4">
          <a class="nav__logo" href="#top" aria-label="FalaDev, início">
            <svg viewBox="0 0 40 40" aria-hidden="true"><path fill="currentColor" d="M9 5H31A6 6 0 0 1 37 11V23A6 6 0 0 1 31 29H19L10 36L11 29H9A6 6 0 0 1 3 23V11A6 6 0 0 1 9 5ZM16.5 11.5L9.5 17L16.5 22.5L18.3 20.4L13.4 17L18.3 13.6ZM23.5 11.5L21.7 13.6L26.6 17L21.7 20.4L23.5 22.5L30.5 17Z"/></svg>FalaDev
          </a>
          <p class="footer__lede">Simulador de entrevistas técnicas em inglês, por voz, para devs brasileiros.</p>
        </div>
        <div class="span-2 footer__col"><h3>Produto</h3><ul><li><a href="#tela">Sala de entrevista</a></li><li><a href="#como-funciona">Como funciona</a></li><li><a href="#problema">Perguntas de exemplo</a></li></ul></div>
        <div class="span-2 footer__col"><h3>Relatório</h3><ul><li><a href="#relatorio">Notas</a></li><li><a href="#perguntas">Dúvidas</a></li></ul></div>
        <div class="span-2 footer__col"><h3>Conta</h3><ul>
          @auth
            <li><a href="{{ route('dashboard') }}">Painel</a></li>
          @else
            <li><a href="{{ route('login') }}">Entrar</a></li>
            <li><a href="{{ route('register') }}">Criar conta</a></li>
          @endauth
        </ul></div>
        <div class="span-2 footer__col"><h3>Dados</h3><ul><li><a href="{{ route('privacy') }}">Privacidade</a></li></ul></div>
      </div>
      <div class="footer__legal">
        <span>&copy; {{ date('Y') }} FalaDev &nbsp; <a href="{{ route('privacy') }}">Privacidade</a></span>
        <div class="socials"></div>
      </div>
    </div>
  </div>
</footer>

<nav class="actionbar" aria-label="Ação rápida">
  <a class="btn btn--primary" href="{{ $primaryHref }}">{{ $primaryLabel }}</a>
</nav>

<script>
  // Hero: the answer is typed again every few seconds; report bars fill when they come into view.
  (function () {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const box = document.querySelector('[data-type-text]');
    if (box && !reduced) {
      const text = box.dataset.typeText;
      let i = 0;
      const tick = () => {
        i = i >= text.length + 40 ? 0 : i + 1;
        box.textContent = text.slice(0, Math.min(i, text.length));
        setTimeout(tick, i === 0 ? 600 : 38);
      };
      tick();
    }
    const fills = document.querySelectorAll('.fd-fill');
    if (!('IntersectionObserver' in window) || reduced) {
      fills.forEach(el => el.classList.add('is-in'));
      return;
    }
    const io = new IntersectionObserver(entries => entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
    }), { threshold: 0.4 });
    fills.forEach(el => io.observe(el));
  })();
</script>
<script type="module" src="assets/system.js"></script>
<script type="module" src="assets/motion/motion.js"></script>
<script type="module" src="assets/layout/layout.js"></script>
<script type="module" src="assets/scenes/scenes.js"></script>
</body>
</html>
