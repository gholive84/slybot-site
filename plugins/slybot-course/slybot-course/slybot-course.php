<?php
/*
Plugin Name: Slybot Course
Description: Curso Slybot integrado à área Minha Conta — acesso vinculado à licença ativa.
Version: 1.3
Author: Slybot
*/

if (!defined('ABSPATH')) exit;


/* =====================================
   HELPER: VERIFICAR LICENÇA ATIVA
===================================== */

function slybot_get_active_license($user_id = null) {
    global $wpdb;

    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;

    $licenses_table = $wpdb->prefix . 'slybot_licenses';

    $license = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $licenses_table
         WHERE user_id = %d
           AND status = 'active'
           AND (expiration_date IS NULL OR expiration_date > NOW())
         ORDER BY id DESC
         LIMIT 1",
        $user_id
    ));

    return $license ?: false;
}


/* =====================================
   REGISTRAR CUSTOM POST TYPE
===================================== */

add_action('init', function() {

    register_post_type('slybot_course', [
        'label'     => 'Curso Slybot',
        'public'    => false,
        'show_ui'   => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports'  => ['title', 'editor', 'thumbnail', 'page-attributes'],
    ]);

});


/* =====================================
   REGISTRAR TAXONOMIA DE SEÇÕES
===================================== */

add_action('init', function() {

    register_taxonomy('slybot_section', 'slybot_course', [
        'label'  => 'Seções',
        'labels' => [
            'name'          => 'Seções',
            'singular_name' => 'Seção',
            'add_new_item'  => 'Adicionar Seção',
            'edit_item'     => 'Editar Seção',
            'new_item'      => 'Nova Seção',
            'view_item'     => 'Ver Seção',
            'search_items'  => 'Buscar Seções',
        ],
        'hierarchical'      => true,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => false,
    ]);

});


/* =====================================
   CRIAR ENDPOINT MINHA CONTA
===================================== */

add_action('init', function() {
    add_rewrite_endpoint('curso-slybot', EP_ROOT | EP_PAGES);
});


/* =====================================
   ADICIONAR MENU NA CONTA
===================================== */

add_filter('woocommerce_account_menu_items', function($items) {

    $new_items = [];

    foreach ($items as $key => $label) {
        $new_items[$key] = $label;
        if ($key === 'minhas-licencas') {
            $new_items['curso-slybot'] = 'Curso Slybot';
        }
    }

    return $new_items;

}, 40);


/* =====================================
   CONTEÚDO DO CURSO
===================================== */

add_action('woocommerce_account_curso-slybot_endpoint', function() {

    /* --- GATE --- */
    $license = slybot_get_active_license();

    if (!$license) {
        slybot_course_render_locked();
        return;
    }

    /* --- AULA ATUAL --- */
    $current_lesson = isset($_GET['lesson']) ? intval($_GET['lesson']) : 0;

    $all_lessons = get_posts([
        'post_type'      => 'slybot_course',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ]);

    if (empty($all_lessons)) {
        echo "<p>Nenhuma aula disponível no momento.</p>";
        return;
    }

    if (!$current_lesson) {
        $current_lesson = $all_lessons[0]->ID;
    }

    $current_post = get_post($current_lesson);

    if ($current_post && $current_post->post_type !== 'slybot_course') {
        $current_post = $all_lessons[0];
    }

    /* --- SEÇÕES --- */
    $sections = get_terms([
        'taxonomy'   => 'slybot_section',
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => false,
    ]);

    // Mapeia aulas por seção
    $lessons_by_section = [];
    $lessons_no_section = [];

    foreach ($all_lessons as $lesson) {
        $lesson_terms = wp_get_post_terms($lesson->ID, 'slybot_section');

        if (empty($lesson_terms) || is_wp_error($lesson_terms)) {
            $lessons_no_section[] = $lesson;
        } else {
            $sid = $lesson_terms[0]->term_id;
            $lessons_by_section[$sid][] = $lesson;
        }
    }

    /* --- LAYOUT --- */
    $has_sections = !empty($sections) && !is_wp_error($sections);

    echo "<div class='slybot-course-layout'>";

    /* ----- SIDEBAR ----- */
    echo "<div class='slybot-course-sidebar'>";

    $counter = 1;

    if ($has_sections) {
        foreach ($sections as $section) {

            if (empty($lessons_by_section[$section->term_id])) continue;

            echo "<div class='slybot-section'>";
            echo "<div class='slybot-section-title'>" . esc_html($section->name) . "</div>";

            foreach ($lessons_by_section[$section->term_id] as $lesson) {
                slybot_render_lesson_item($lesson, $current_lesson, $counter);
                $counter++;
            }

            echo "</div>";
        }
    }

    // Aulas sem seção
    if (!empty($lessons_no_section)) {

        if ($has_sections) {
            echo "<div class='slybot-section'>";
            echo "<div class='slybot-section-title'>Geral</div>";
        }

        foreach ($lessons_no_section as $lesson) {
            slybot_render_lesson_item($lesson, $current_lesson, $counter);
            $counter++;
        }

        if ($has_sections) echo "</div>";
    }

    echo "</div>"; // .slybot-course-sidebar

    /* ----- CONTEÚDO ----- */
    echo "<div class='slybot-course-content'>";

    if ($current_post) {
        echo "<h2>" . esc_html($current_post->post_title) . "</h2>";
        echo "<div class='slybot-video'>";
        echo apply_filters('the_content', $current_post->post_content);
        echo "</div>";
    } else {
        echo "<p>Selecione uma aula na barra lateral.</p>";
    }

    echo "</div>"; // .slybot-course-content
    echo "</div>"; // .slybot-course-layout

});


/* =====================================
   HELPER: RENDERIZAR ITEM DE AULA
===================================== */

function slybot_render_lesson_item($lesson, $current_lesson, $counter) {

    $lesson_url = wc_get_account_endpoint_url('curso-slybot') . '?lesson=' . $lesson->ID;
    $active     = ($current_lesson == $lesson->ID) ? 'active' : '';

    echo "<a class='slybot-lesson-item {$active}' href='" . esc_url($lesson_url) . "'>";
    echo "<span class='slybot-lesson-number'>{$counter}</span>";
    echo esc_html($lesson->post_title);
    echo "</a>";
}


/* =====================================
   TELA DE ACESSO BLOQUEADO
===================================== */

function slybot_course_render_locked() {

    $shop_url = home_url('/#planos');

    echo "
    <div class='slybot-locked-wrap'>

        <div class='slybot-locked-icon'>
            <svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 24 24'
                 fill='none' stroke='currentColor' stroke-width='1.5'
                 stroke-linecap='round' stroke-linejoin='round'>
                <rect x='3' y='11' width='18' height='11' rx='2' ry='2'/>
                <path d='M7 11V7a5 5 0 0 1 10 0v4'/>
            </svg>
        </div>

        <h2>Acesso ao curso</h2>

        <p>O Curso Slybot está disponível para todos os titulares de uma licença ativa.</p>
        <p>Adquira sua licença e desbloqueie todas as aulas imediatamente após a confirmação do pagamento.</p>

        <a href='" . esc_url($shop_url) . "' class='slybot-btn-primary'>
            Ver planos disponíveis
        </a>

    </div>
    ";
}


/* =====================================
   REDIRECIONAR AULAS PARA MINHA CONTA
===================================== */

add_action('template_redirect', function() {

    if (!is_singular('slybot_course')) return;

    if (!is_user_logged_in()) {
        wp_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    if (!slybot_get_active_license()) {
        wp_redirect(wc_get_account_endpoint_url('curso-slybot'));
        exit;
    }

    $lesson_id = get_the_ID();
    $url       = wc_get_account_endpoint_url('curso-slybot') . '?lesson=' . $lesson_id;
    wp_redirect($url);
    exit;

});


/* =====================================
   ESTILOS
===================================== */

add_action('wp_head', function() {

    if (!is_account_page()) return;

    ?>
    <style>

    .slybot-course-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 30px;
        align-items: start;
    }

    .slybot-course-sidebar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        position: sticky;
        top: 20px;
    }

    /* Seção */
    .slybot-section { margin-bottom: 4px; }

    .slybot-section-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #9ca3af;
        padding: 12px 14px 4px;
    }

    .slybot-section + .slybot-section {
        border-top: 1px solid #f3f4f6;
        padding-top: 4px;
        margin-top: 4px;
    }

    /* Item de aula */
    .slybot-lesson-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 8px;
        text-decoration: none;
        color: #374151;
        font-size: 14px;
        transition: all .2s;
    }

    .slybot-lesson-item:hover { background: #f3f4f6; }

    .slybot-lesson-item.active {
        background: #fff3eb;
        color: #ff6a00;
        font-weight: 600;
    }

    .slybot-lesson-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #f3f4f6;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        flex-shrink: 0;
    }

    .slybot-lesson-item.active .slybot-lesson-number {
        background: #ff6a00;
        color: #fff;
    }

    /* Conteúdo */
    .slybot-course-content h2 { margin-top: 0; margin-bottom: 20px; }

    .slybot-video iframe {
        width: 100%;
        max-width: 800px;
        height: 450px;
        border-radius: 12px;
        display: block;
    }

    /* Bloqueio */
    .slybot-locked-wrap {
        text-align: left;
        padding: 40px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        max-width: 860px;
        margin: 0 0 30px 0;
    }

    .slybot-locked-icon { color: #ff6a00; margin-bottom: 20px; }
    .slybot-locked-wrap h2 { font-size: 22px; margin-bottom: 12px; }

    .slybot-locked-wrap p {
        color: #6b7280;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .slybot-btn-primary {
        display: inline-block;
        margin-top: 20px;
        padding: 14px 32px;
        background: #ff6a00;
        color: #fff !important;
        border-radius: 10px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        transition: background .2s;
    }

    .slybot-btn-primary:hover { background: #e55e00; }

    /* Responsivo */
    @media (max-width: 768px) {
        .slybot-course-layout { grid-template-columns: 1fr; }
        .slybot-course-sidebar { position: static; }
        .slybot-video iframe { height: 240px; }
    }

    </style>
    <?php

});