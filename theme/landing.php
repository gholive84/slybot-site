<?php
/**
 * Template Name: Landing Page
 */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        /* ── Reset & Base ─────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:       #FF6A00;
            --orange-dark:  #E65C00;
            --bg-dark:      #080C16;
            --bg-light:     #F6F7F9;
            --bg-white:     #FFFFFF;
            --text-primary: #111827;
            --text-muted:   #6B7280;
            --border:       #E5E7EB;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            background: var(--bg-white);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ── Navbar ───────────────────────────────────────────────────── */
        .lp-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-dark);
            padding: 18px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .lp-nav__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lp-nav__logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            letter-spacing: -0.02em;
        }

        .lp-nav__logo span { color: var(--orange); }

        .lp-nav__links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }

        .lp-nav__links a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .lp-nav__links a:hover { color: #fff; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--orange);
            color: #fff;
            padding: 13px 24px;
            font-size: 0.9rem;
        }

        .btn-primary:hover { background: var(--orange-dark); transform: translateY(-1px); }

        .btn-outline {
            background: transparent;
            color: #fff;
            padding: 12px 24px;
            font-size: 0.9rem;
            border: 1.5px solid rgba(255,255,255,0.3);
        }

        .btn-outline:hover { border-color: #fff; color: #fff; }

        .btn-lg { padding: 16px 32px; font-size: 1rem; }

        /* ── Hero ─────────────────────────────────────────────────────── */
        .lp-hero {
            background: var(--bg-dark);
            padding: 100px 0 80px;
            text-align: center;
        }

        .lp-hero__label {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--orange);
            background: rgba(255,106,0,0.12);
            border: 1px solid rgba(255,106,0,0.25);
            border-radius: 100px;
            padding: 5px 14px;
            margin-bottom: 24px;
        }

        .lp-hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -0.02em;
            max-width: 760px;
            margin: 0 auto 20px;
        }

        .lp-hero h1 span { color: var(--orange); }

        .lp-hero__sub {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.6);
            max-width: 560px;
            margin: 0 auto 40px;
            line-height: 1.7;
        }

        .lp-hero__ctas {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 48px;
        }

        .lp-hero__badges {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .lp-hero__badge {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(255,255,255,0.65);
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 100px;
            padding: 7px 16px;
        }

        .lp-hero__badge svg { width: 14px; height: 14px; fill: var(--orange); flex-shrink: 0; }

        /* ── Section Base ─────────────────────────────────────────────── */
        .lp-section { padding: 88px 0; }
        .lp-section--dark { background: var(--bg-dark); }
        .lp-section--light { background: var(--bg-light); }
        .lp-section--white { background: var(--bg-white); }

        .section-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--orange);
            margin-bottom: 12px;
        }

        .section-title {
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 700;
            line-height: 1.25;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .section-title--white { color: #fff; }

        .section-sub {
            font-size: 1rem;
            color: var(--text-muted);
            max-width: 540px;
            line-height: 1.7;
        }

        .section-sub--white { color: rgba(255,255,255,0.6); }

        .section-header { margin-bottom: 52px; }
        .section-header--center { text-align: center; }
        .section-header--center .section-sub { margin: 0 auto; }

        /* ── Cards ────────────────────────────────────────────────────── */
        .card-grid { display: grid; gap: 20px; }
        .card-grid--2 { grid-template-columns: repeat(2, 1fr); }
        .card-grid--3 { grid-template-columns: repeat(3, 1fr); }

        .card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            transition: all 0.2s ease;
        }

        .card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.08); }

        .card--dark {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.08);
        }

        .card--dark:hover { background: rgba(255,255,255,0.07); }

        .card__icon {
            width: 44px;
            height: 44px;
            background: rgba(255,106,0,0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .card__icon svg { width: 20px; height: 20px; fill: var(--orange); }

        .card__title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .card--dark .card__title { color: #fff; }

        .card__text {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.65;
        }

        .card--dark .card__text { color: rgba(255,255,255,0.55); }

        /* ── Metodologia ──────────────────────────────────────────────── */
        .step-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--orange);
            opacity: 0.25;
            line-height: 1;
            margin-bottom: 12px;
        }

        /* ── Stats ────────────────────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            text-align: center;
            margin-bottom: 52px;
        }

        .stat__number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--orange);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat__label {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.55);
        }

        /* Resultado card */
        .result-card {
            background: rgba(255,106,0,0.07);
            border: 1px solid rgba(255,106,0,0.2);
            border-radius: 14px;
            padding: 32px;
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: center;
        }

        .result-card__item { flex: 1; min-width: 140px; }

        .result-card__label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.45);
            margin-bottom: 6px;
        }

        .result-card__value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }

        .result-card__value--orange { color: var(--orange); }

        .disclaimer {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.35);
            margin-top: 20px;
            line-height: 1.5;
        }

        /* ── Público ──────────────────────────────────────────────────── */
        .publico-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .publico-card {
            border-radius: 14px;
            padding: 32px;
        }

        .publico-card--sim {
            background: rgba(34,197,94,0.06);
            border: 1px solid rgba(34,197,94,0.2);
        }

        .publico-card--nao {
            background: rgba(239,68,68,0.06);
            border: 1px solid rgba(239,68,68,0.15);
        }

        .publico-card__title {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .publico-card ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }

        .publico-card li {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .publico-card li::before {
            flex-shrink: 0;
            margin-top: 2px;
            font-size: 0.85rem;
        }

        .publico-card--sim li::before { content: '✓'; color: #22C55E; font-weight: 700; }
        .publico-card--nao li::before { content: '✕'; color: #EF4444; font-weight: 700; }

        /* ── Planos ───────────────────────────────────────────────────── */
        .planos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            max-width: 860px;
            margin: 0 auto;
        }

        .plano-card {
            background: var(--bg-white);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 36px;
            position: relative;
        }

        .plano-card--popular {
            border-color: var(--orange);
            box-shadow: 0 0 0 1px var(--orange), 0 16px 48px rgba(255,106,0,0.12);
        }

        .plano-card__badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--orange);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 5px 16px;
            border-radius: 100px;
            white-space: nowrap;
        }

        .plano-card__name {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .plano-card__price {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 4px;
        }

        .plano-card__price sup {
            font-size: 1.2rem;
            font-weight: 600;
            vertical-align: super;
        }

        .plano-card__from {
            font-size: 0.82rem;
            color: var(--text-muted);
            text-decoration: line-through;
            margin-bottom: 8px;
        }

        .plano-card__period {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        .plano-card__divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }

        .plano-card__features { list-style: none; display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px; }

        .plano-card__features li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.88rem;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .plano-card__features li .check { color: #22C55E; font-weight: 700; flex-shrink: 0; }
        .plano-card__features li .no    { color: #D1D5DB; font-weight: 700; flex-shrink: 0; }
        .plano-card__features li.disabled { color: #9CA3AF; }

        .btn-full { width: 100%; }

        /* ── Lista de Espera ──────────────────────────────────────────── */
        .waitlist-box {
            background: rgba(255,106,0,0.07);
            border: 1px solid rgba(255,106,0,0.2);
            border-radius: 16px;
            padding: 48px;
            text-align: center;
            max-width: 580px;
            margin: 0 auto;
        }

        .waitlist-form {
            display: flex;
            gap: 10px;
            margin-top: 28px;
        }

        .waitlist-form input {
            flex: 1;
            padding: 14px 18px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .waitlist-form input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255,106,0,0.12);
        }

        .waitlist-note {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 14px;
        }

        /* ── FAQ ──────────────────────────────────────────────────────── */
        .faq-list { max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

        .faq-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .faq-question {
            width: 100%;
            background: none;
            border: none;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            transition: background 0.15s;
        }

        .faq-question:hover { background: var(--bg-light); }
        .faq-question.active { background: var(--bg-light); }

        .faq-icon {
            width: 22px;
            height: 22px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
            color: var(--orange);
            font-weight: 700;
            transition: transform 0.2s;
        }

        .faq-question.active .faq-icon { transform: rotate(45deg); }

        .faq-answer {
            display: none;
            padding: 0 24px 20px;
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.7;
        }

        .faq-answer.open { display: block; }

        /* ── Footer ───────────────────────────────────────────────────── */
        .lp-footer {
            background: var(--bg-dark);
            padding: 32px 0;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .lp-footer p {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.35);
        }

        .lp-footer a {
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            transition: color 0.2s;
        }

        .lp-footer a:hover { color: #fff; }

        /* ── Responsive ───────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .card-grid--3 { grid-template-columns: 1fr 1fr; }
            .stats-grid   { grid-template-columns: 1fr; gap: 24px; }
            .planos-grid  { grid-template-columns: 1fr; max-width: 460px; }
        }

        @media (max-width: 640px) {
            .lp-nav__links { display: none; }
            .card-grid--2, .card-grid--3 { grid-template-columns: 1fr; }
            .publico-grid  { grid-template-columns: 1fr; }
            .waitlist-form { flex-direction: column; }
            .lp-hero { padding: 72px 0 60px; }
            .lp-section { padding: 64px 0; }
            .waitlist-box { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────────────────── -->
<nav class="lp-nav">
    <div class="container">
        <div class="lp-nav__inner">
            <a href="#" class="lp-nav__logo"><img src="https://slybot.com.br/wp-content/uploads/2025/12/logo-1.png" alt="SlyBot" style="height:36px; width:auto;"></a>
            <ul class="lp-nav__links">
                <li><a href="#como-funciona">Como Funciona</a></li>
                <li><a href="#planos">Planos</a></li>
            </ul>
            <a href="#planos" class="btn btn-primary">Começar agora</a>
        </div>
    </div>
</nav>

<!-- ── Hero ────────────────────────────────────────────────────────────── -->
<section class="lp-hero" id="topo">
    <div class="container">
        <span class="lp-hero__label">Robô de Day Trade Automatizado</span>
        <h1>Opere com <span>disciplina</span> e execução consistente</h1>
        <p class="lp-hero__sub">SlyBot é um robô automatizado para day trade no MT5, baseado em análise estatística e modelos estruturados. Sem promessas — apenas metodologia.</p>
        <div class="lp-hero__ctas">
            <a href="#planos" class="btn btn-primary btn-lg">Ver planos</a>
            <a href="#como-funciona" class="btn btn-outline btn-lg">Como funciona</a>
        </div>
        <div class="lp-hero__badges">
            <span class="lp-hero__badge">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                MT5 Plataforma
            </span>
            <span class="lp-hero__badge">
                <svg viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                24/5 Monitoramento
            </span>
            <span class="lp-hero__badge">
                <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                100% Automatizado
            </span>
        </div>
    </div>
</section>

<!-- ── Metodologia ──────────────────────────────────────────────────────── -->
<section class="lp-section lp-section--light" id="como-funciona">
    <div class="container">
        <div class="section-header section-header--center">
            <span class="section-label">Metodologia</span>
            <h2 class="section-title">Como o SlyBot funciona</h2>
            <p class="section-sub">Um sistema baseado em lógica, dados e disciplina — não em achismos</p>
        </div>
        <div class="card-grid card-grid--2">
            <div class="card">
                <div class="step-number">01</div>
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div class="card__title">Análise Estatística</div>
                <p class="card__text">Entradas baseadas em padrões estatisticamente validados com dados históricos reais. Nenhuma operação sem embasamento.</p>
            </div>
            <div class="card">
                <div class="step-number">02</div>
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zm8 1v8M8 11l4-4 4 4"/></svg>
                </div>
                <div class="card__title">Estrutura de Dados</div>
                <p class="card__text">Leitura em tempo real das estruturas de mercado: suporte, resistência, topos e fundos — sem indicadores subjetivos.</p>
            </div>
            <div class="card">
                <div class="step-number">03</div>
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="card__title">Controle de Risco</div>
                <p class="card__text">Stop loss automático, gestão de contratos e regras rígidas de drawdown máximo. O robô protege seu capital primeiro.</p>
            </div>
            <div class="card">
                <div class="step-number">04</div>
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="card__title">Execução Automática</div>
                <p class="card__text">Ordens enviadas em milissegundos. Sem hesitação, sem emoção, sem clique manual. O robô executa exatamente o que foi programado.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Vantagens ────────────────────────────────────────────────────────── -->
<section class="lp-section lp-section--white">
    <div class="container">
        <div class="section-header section-header--center">
            <span class="section-label">Vantagens</span>
            <h2 class="section-title">Por que automatizar?</h2>
            <p class="section-sub">A automação resolve os maiores problemas do trader: emoção, inconsistência e falta de disciplina</p>
        </div>
        <div class="card-grid card-grid--3">
            <div class="card">
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <div class="card__title">Sem viés emocional</div>
                <p class="card__text">O robô não sente medo, ganância ou euforia. Opera com a mesma lógica todas as vezes.</p>
            </div>
            <div class="card">
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="card__title">Execução precisa</div>
                <p class="card__text">Ordens no preço exato, no momento certo, com o tamanho correto. Sem erros humanos.</p>
            </div>
            <div class="card">
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="card__title">Backtestado</div>
                <p class="card__text">Estratégias validadas com anos de dados históricos antes de operar com dinheiro real.</p>
            </div>
            <div class="card">
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div class="card__title">Sem overtrading</div>
                <p class="card__text">Opera somente quando as condições do setup estão presentes. Nada de entradas por impulso.</p>
            </div>
            <div class="card">
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="card__title">Configurável</div>
                <p class="card__text">Ajuste os parâmetros de risco, horários e ativos de acordo com o seu perfil.</p>
            </div>
            <div class="card">
                <div class="card__icon">
                    <svg viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div class="card__title">Consistência</div>
                <p class="card__text">O mesmo resultado esperado todos os dias. A consistência é o que separa traders amadores dos profissionais.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Resultados ───────────────────────────────────────────────────────── -->
<section class="lp-section lp-section--dark">
    <div class="container">
        <div class="section-header section-header--center">
            <span class="section-label">Resultados</span>
            <h2 class="section-title section-title--white">Backtests & Performance</h2>
            <p class="section-sub section-sub--white">Dados reais, metodologia transparente</p>
        </div>
        <div class="stats-grid">
            <div>
                <div class="stat__number">10.000+</div>
                <div class="stat__label">Operações analisadas</div>
            </div>
            <div>
                <div class="stat__number">3 anos</div>
                <div class="stat__label">Período de backtest</div>
            </div>
            <div>
                <div class="stat__number">B3 & Forex</div>
                <div class="stat__label">Ativos testados</div>
            </div>
        </div>
        <div class="result-card">
            <div class="result-card__item">
                <div class="result-card__label">Estratégia</div>
                <div class="result-card__value">WIN – 2025 / Estratégia A</div>
            </div>
            <div class="result-card__item">
                <div class="result-card__label">Lucro (1 contrato)</div>
                <div class="result-card__value result-card__value--orange">R$ 7.790,00</div>
            </div>
            <div class="result-card__item">
                <div class="result-card__label">Meses positivos</div>
                <div class="result-card__value">12 de 12</div>
            </div>
        </div>
        <p class="disclaimer">* Resultados obtidos em backtests com dados históricos e não representam garantia de resultados futuros. Rentabilidade passada não é garantia de rentabilidade futura.</p>
    </div>
</section>

<!-- ── Público ──────────────────────────────────────────────────────────── -->
<section class="lp-section lp-section--dark">
    <div class="container">
        <div class="section-header section-header--center">
            <span class="section-label">Público</span>
            <h2 class="section-title section-title--white">Para quem é o SlyBot?</h2>
        </div>
        <div class="publico-grid">
            <div class="publico-card publico-card--sim">
                <div class="publico-card__title">✓ É para você se</div>
                <ul>
                    <li>Opera ou quer operar mini índice (WIN) ou mini dólar (WDO)</li>
                    <li>Busca disciplina e consistência na operação</li>
                    <li>Quer automatizar sua estratégia no MT5</li>
                    <li>Entende que trading envolve risco e aceita perdas</li>
                    <li>Quer operar sem interferência emocional</li>
                </ul>
            </div>
            <div class="publico-card publico-card--nao">
                <div class="publico-card__title">✕ Não é para você se</div>
                <ul>
                    <li>Procura lucro garantido ou renda passiva sem risco</li>
                    <li>Nunca operou no mercado financeiro</li>
                    <li>Não vai dedicar tempo para configurar e acompanhar</li>
                    <li>Não aceita a possibilidade de períodos negativos</li>
                    <li>Quer ficar rico rapidamente sem aprender o mercado</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ── Planos ───────────────────────────────────────────────────────────── -->
<section class="lp-section lp-section--light" id="planos">
    <div class="container">
        <div class="section-header section-header--center">
            <span class="section-label">Planos</span>
            <h2 class="section-title">Escolha seu plano</h2>
            <p class="section-sub">Invista na sua operação com a ferramenta certa</p>
        </div>
        <div class="planos-grid">
            <!-- Plano Anual -->
            <div class="plano-card">
                <div class="plano-card__name">Plano Anual</div>
                <div class="plano-card__from">De R$ 1.995</div>
                <div class="plano-card__price"><sup>R$</sup>899</div>
                <div class="plano-card__period">Acesso por 12 meses</div>
                <hr class="plano-card__divider">
                <ul class="plano-card__features">
                    <li><span class="check">✓</span> 1 licença (1 conta MT5)</li>
                    <li><span class="check">✓</span> Robôs ilimitados inclusos</li>
                    <li><span class="check">✓</span> Atualizações durante o período</li>
                    <li><span class="check">✓</span> Suporte por e-mail</li>
                    <li class="disabled"><span class="no">✕</span> Futuros robôs inclusos</li>
                    <li class="disabled"><span class="no">✕</span> Comunidade exclusiva</li>
                    <li class="disabled"><span class="no">✕</span> Mesa proprietária permitida</li>
                </ul>
                <a href="https://slybot.com.br/checkout/?add-to-cart=274" class="btn btn-primary btn-full">Comprar agora</a>
            </div>
            <!-- Plano Profissional -->
            <div class="plano-card plano-card--popular">
                <div class="plano-card__badge">Mais popular</div>
                <div class="plano-card__name">Plano Profissional</div>
                <div class="plano-card__price"><sup>R$</sup>1.599</div>
                <div class="plano-card__period">Acesso permanente*</div>
                <hr class="plano-card__divider">
                <ul class="plano-card__features">
                    <li><span class="check">✓</span> 5 licenças (5 contas MT5)</li>
                    <li><span class="check">✓</span> Todos os futuros robôs inclusos</li>
                    <li><span class="check">✓</span> Atualizações permanentes</li>
                    <li><span class="check">✓</span> Suporte prioritário</li>
                    <li><span class="check">✓</span> Comunidade exclusiva</li>
                    <li><span class="check">✓</span> Mesa proprietária permitida</li>
                </ul>
                <a href="https://slybot.com.br/checkout/?add-to-cart=275" class="btn btn-primary btn-full">Comprar agora</a>
            </div>
        </div>
        <p style="text-align:center; font-size:0.78rem; color:var(--text-muted); margin-top:24px;">*Acesso permanente enquanto o projeto SlyBot existir e estiver ativo.</p>
    </div>
</section>

<!-- ── Lista de Espera ──────────────────────────────────────────────────── -->
<section class="lp-section lp-section--white">
    <div class="container">
        <div class="waitlist-box">
            <span class="section-label" style="display:block; margin-bottom:12px;">Lista de espera</span>
            <h2 class="section-title">Vagas limitadas</h2>
            <p class="section-sub" style="margin:0 auto;">Entre na lista de espera e seja avisado quando abrir novas vagas com condição especial.</p>
            <form class="waitlist-form" onsubmit="return false;">
                <input type="email" placeholder="seu@email.com" required>
                <button type="submit" class="btn btn-primary">Entrar na lista</button>
            </form>
            <p class="waitlist-note">Sem spam. Apenas um e-mail quando abrir vagas.</p>
        </div>
    </div>
</section>

<!-- ── FAQ ──────────────────────────────────────────────────────────────── -->
<section class="lp-section lp-section--light">
    <div class="container">
        <div class="section-header section-header--center">
            <span class="section-label">FAQ</span>
            <h2 class="section-title">Perguntas frequentes</h2>
        </div>
        <div class="faq-list">
            <div class="faq-item">
                <button class="faq-question">
                    O SlyBot garante lucro?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">Não. O SlyBot é uma ferramenta de automação. Os resultados variam de acordo com as condições do mercado, configuração e perfil de risco do trader. Resultados passados não garantem resultados futuros.</div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Preciso ter experiência em trading?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">Sim. Recomendamos conhecimento básico a intermediário do mercado financeiro. O SlyBot automatiza a execução, mas você precisa entender o que está operando e os riscos envolvidos.</div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Em quais ativos o robô opera?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">O SlyBot foi desenvolvido para operar na B3 (mini índice WIN e mini dólar WDO), Forex e Mercado dos EUA via MetaTrader 5.</div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Preciso deixar o PC ligado?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">Sim, ou você pode usar uma VPS (servidor virtual) para manter o MT5 rodando 24h sem depender do seu computador pessoal.</div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Posso usar em mesa proprietária?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">Apenas no Plano Profissional. O Plano Anual não inclui licença para uso em mesa proprietária.</div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Como recebo o robô após a compra?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">Após a confirmação do pagamento, você recebe o arquivo do robô e as instruções de instalação por e-mail em até 24 horas úteis.</div>
            </div>
        </div>
    </div>
</section>

<!-- ── Footer ───────────────────────────────────────────────────────────── -->
<footer class="lp-footer">
    <div class="container">
        <p>© <?php echo date('Y'); ?> SlyBot — Todos os direitos reservados &nbsp;·&nbsp; <a href="/politica-de-privacidade">Política de Privacidade</a> &nbsp;·&nbsp; <a href="/termos-de-uso">Termos de Uso</a></p>
    </div>
</footer>

<script>
// FAQ accordion
document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
        const answer = btn.nextElementSibling;
        const isOpen = answer.classList.contains('open');
        // fecha todos
        document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
        document.querySelectorAll('.faq-question').forEach(b => b.classList.remove('active'));
        // abre o clicado
        if (!isOpen) {
            answer.classList.add('open');
            btn.classList.add('active');
        }
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
