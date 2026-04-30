<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );


/* =====================================================
   SITEMAP XML — gerado via PHP, sem plugin
===================================================== */

add_action( 'init', function() {
    add_rewrite_rule( '^sitemap\.xml$', 'index.php?slybot_sitemap=1', 'top' );
} );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'slybot_sitemap';
    return $vars;
} );

add_action( 'template_redirect', function() {
    if ( ! get_query_var( 'slybot_sitemap' ) ) return;

    $home     = esc_url( home_url( '/' ) );
    $modified = date( 'Y-m-d' );

    header( 'Content-Type: application/xml; charset=UTF-8' );
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    echo '<url>';
    echo   '<loc>' . $home . '</loc>';
    echo   '<lastmod>' . $modified . '</lastmod>';
    echo   '<changefreq>weekly</changefreq>';
    echo   '<priority>1.0</priority>';
    echo '</url>';
    echo '</urlset>';
    exit;
} );


/* =====================================================
   ESTILOS DO CHILD THEME
===================================================== */

function hello_elementor_child_scripts_styles() {
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [ 'hello-elementor-theme-style' ],
        HELLO_ELEMENTOR_CHILD_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );


/* =====================================================
   FONT AWESOME
===================================================== */

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'
    );
});


/* =====================================================
   CHECKOUT: PRODUTO VAI DIRETO SEM CARRINHO
   (já existia — mantido)
===================================================== */

add_filter( 'woocommerce_add_to_cart_redirect', function() {
    return wc_get_checkout_url();
});

add_filter( 'woocommerce_add_to_cart_validation', function( $passed, $product_id ) {
    WC()->cart->empty_cart();
    return $passed;
}, 10, 2 );


/* =====================================================
   MENU MINHA CONTA — ORDEM E LABELS
   (já existia — mantido)
===================================================== */

add_filter( 'woocommerce_account_menu_items', 'slybot_reorder_account_menu' );

function slybot_reorder_account_menu( $items ) {
    $new = [];
    foreach ( $items as $key => $label ) {
        if ( $key === 'dashboard' )     $new[$key] = 'Painel';
        if ( $key === 'orders' )        $new[$key] = $label;
        if ( $key === 'downloads' )     $new[$key] = $label;
        if ( $key === 'edit-address' ) {
            $new[$key] = $label;
            $new['minhas-licencas'] = 'Meus Robôs';
        }
        if ( $key === 'edit-account' )  $new[$key] = 'Detalhes da conta';
        if ( $key === 'customer-logout' ) $new[$key] = 'Sair';
    }
    return $new;
}

add_filter( 'woocommerce_account_menu_items', 'slybot_remove_downloads_menu', 999 );

function slybot_remove_downloads_menu( $items ) {
    unset( $items['downloads'] );
    return $items;
}


/* =====================================================
   MINHA CONTA — TÍTULO DINÂMICO
   Retorna o título correspondente ao endpoint ativo.
===================================================== */

function slybot_get_account_title() {

    $titles = [
        'dashboard'       => 'Minha conta',
        'orders'          => 'Pedidos',
        'view-order'      => 'Detalhes do pedido',
        'edit-address'    => 'Endereços',
        'edit-account'    => 'Detalhes da conta',
        'minhas-licencas'    => 'Meus Robôs',
        'curso-slybot'       => 'Curso Slybot',
        'estrategias-slybot' => 'Estratégias',
        'lost-password'      => 'Recuperar senha',
    ];

    global $wp;

    foreach ( $titles as $endpoint => $title ) {
        if ( isset( $wp->query_vars[ $endpoint ] ) ) {
            return $title;
        }
    }

    // Fallback: dashboard
    return 'Minha conta';
}


/* =====================================================
   MINHA CONTA — FULL WIDTH
   Remove sidebar e força layout em tela cheia
   no Hello Elementor.
===================================================== */



add_filter('body_class', function($classes) {
    if (is_account_page()) {
        $classes[] = 'elementor-page';
        $classes[] = 'elementor-page-full-width';
        $classes[] = 'page-template-elementor_header_footer';
    }
    return $classes;
});

add_action('wp_head', function() {
    if (!is_account_page()) return;
    ?>
    <style>
    /* Remove título e zera espaço da barra branca */
    .woocommerce-account .page-header {
        display: none !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 0 !important;
    }

    /* Zera padding/margin do site-main que cria o espaço branco */
    .woocommerce-account #content.site-main,
    .woocommerce-account .site-main {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }

    /* Full width */
    #content.site-main,
    .woocommerce-account .page-content,
    .woocommerce-account .page-content > .woocommerce {
        max-width: 100% !important;
        width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    /* Padding confortável no conteúdo interno */
    .woocommerce-account .page-content {
        padding: 0 60px 40px !important;
    }

    @media (max-width: 768px) {
        .woocommerce-account .page-content {
            padding: 20px !important;
        }
    }
    </style>
    <?php
}, 99);


/* =====================================================
   1. BLOQUEAR REGISTRO DIRETO
      /minha-conta/register → redireciona para login.
      Conta só pode ser criada via checkout.
===================================================== */

add_action( 'template_redirect', 'slybot_block_direct_register' );

function slybot_block_direct_register() {

    if ( ! is_account_page() ) return;

    global $wp;

    if ( ! isset( $wp->query_vars['register'] ) ) return;

    if ( is_user_logged_in() ) {
        wp_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
        exit;
    }

    wp_redirect( wc_get_page_permalink( 'myaccount' ) );
    exit;
}


/* =====================================================
   2. PÁGINA /MINHA-CONTA — SÓ LOGIN, SEM REGISTRO
      Esconde o formulário de registro e centraliza o login.
===================================================== */

add_action( 'wp_head', 'slybot_hide_register_form' );

function slybot_hide_register_form() {

    if ( ! is_account_page() ) return;
    if ( is_user_logged_in() ) return;

    ?>
    <style>
    .woocommerce-form-register,
    .woocommerce .col-2.u-column2,
    .woocommerce-page .col-2.u-column2 {
        display: none !important;
    }
    .woocommerce .u-columns,
    .woocommerce-page .u-columns {
        display: flex !important;
        justify-content: center !important;
    }
    .woocommerce .u-columns .u-column1,
    .woocommerce-page .u-columns .u-column1 {
        float: none !important;
        width: 100% !important;
        max-width: 420px !important;
    }
    </style>
    <?php
}

// Aviso abaixo do formulário de login informando como criar conta
add_action( 'woocommerce_login_form_end', 'slybot_login_notice' );

function slybot_login_notice() {

    if ( is_user_logged_in() ) return;

    $shop_url = home_url( '/#planos' );

    echo "
    <div class='slybot-login-notice'>
        <svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24'
             fill='none' stroke='currentColor' stroke-width='2'
             stroke-linecap='round' stroke-linejoin='round'>
            <circle cx='12' cy='12' r='10'/>
            <line x1='12' y1='8' x2='12' y2='12'/>
            <line x1='12' y1='16' x2='12.01' y2='16'/>
        </svg>
        <span>
            Ainda não tem uma conta? O cadastro é criado automaticamente ao adquirir um plano.
            <a href='" . esc_url( $shop_url ) . "'>Ver planos →</a>
        </span>
    </div>
    ";
}


/* =====================================================
   3. DESATIVAR PÁGINA DO CARRINHO
      /carrinho → redireciona para a loja.
===================================================== */

add_action( 'template_redirect', 'slybot_disable_cart_page' );

function slybot_disable_cart_page() {

    if ( ! is_cart() ) return;

    wp_redirect( home_url( '/#planos' ), 301 );
    exit;
}


/* =====================================================
   4. PÁGINA PÓS-PEDIDO PERSONALIZADA
      Substitui o conteúdo padrão do order-received
      por um resumo limpo com instruções de próximos passos.
===================================================== */

// Remove o conteúdo padrão do WooCommerce
remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

add_action( 'woocommerce_thankyou', 'slybot_custom_thankyou', 10 );

function slybot_custom_thankyou( $order_id ) {

    if ( ! $order_id ) return;

    $order      = wc_get_order( $order_id );
    $myaccount  = wc_get_page_permalink( 'myaccount' );
    $shop_url   = home_url( '/#planos' );

    // Nome do produto comprado
    $product_names = [];
    foreach ( $order->get_items() as $item ) {
        $product_names[] = $item->get_name();
    }
    $product_label = implode( ', ', $product_names );

    // Status do pedido
    $status        = $order->get_status();
    $is_paid       = in_array( $status, [ 'completed', 'processing' ] );

    $status_color  = $is_paid ? '#16a34a' : '#d97706';
    $status_icon   = $is_paid ? '✓' : '⏳';
    $status_label  = $is_paid ? 'Pagamento confirmado' : 'Aguardando confirmação do pagamento';
    $status_msg    = $is_paid
        ? 'Sua licença será ativada em instantes. Você receberá um e-mail de confirmação.'
        : 'Assim que o pagamento for confirmado, sua licença será ativada automaticamente e você receberá um e-mail.';

    echo "
    <div class='slybot-thankyou'>

        <div class='slybot-thankyou-icon'>
            <svg xmlns='http://www.w3.org/2000/svg' width='52' height='52' viewBox='0 0 24 24'
                 fill='none' stroke='#ff6a00' stroke-width='1.5'
                 stroke-linecap='round' stroke-linejoin='round'>
                <path d='M22 11.08V12a10 10 0 1 1-5.93-9.14'/>
                <polyline points='22 4 12 14.01 9 11.01'/>
            </svg>
        </div>

        <h2>Pedido recebido!</h2>
        <p class='slybot-thankyou-sub'>Obrigado pela sua compra. Veja abaixo o resumo.</p>

        <div class='slybot-thankyou-card'>

            <div class='slybot-thankyou-row'>
                <span class='slybot-thankyou-label'>Pedido</span>
                <span class='slybot-thankyou-value'>#" . esc_html( $order->get_order_number() ) . "</span>
            </div>

            <div class='slybot-thankyou-row'>
                <span class='slybot-thankyou-label'>Plano</span>
                <span class='slybot-thankyou-value'>" . esc_html( $product_label ) . "</span>
            </div>

            <div class='slybot-thankyou-row'>
                <span class='slybot-thankyou-label'>Total</span>
                <span class='slybot-thankyou-value'>" . wp_kses_post( $order->get_formatted_order_total() ) . "</span>
            </div>

            <div class='slybot-thankyou-row'>
                <span class='slybot-thankyou-label'>Status</span>
                <span class='slybot-thankyou-value slybot-status-pill' style='color:{$status_color};background:none;padding:0'>
                    {$status_icon} {$status_label}
                </span>
            </div>

        </div>

        <div class='slybot-thankyou-notice'>
            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'
                 fill='none' stroke='currentColor' stroke-width='2'
                 stroke-linecap='round' stroke-linejoin='round' style='flex-shrink:0;margin-top:2px'>
                <circle cx='12' cy='12' r='10'/>
                <line x1='12' y1='8' x2='12' y2='12'/>
                <line x1='12' y1='16' x2='12.01' y2='16'/>
            </svg>
            <span>{$status_msg}</span>
        </div>

        <a href='" . esc_url( $myaccount ) . "' class='slybot-btn-primary' style='margin-top:30px;display:inline-block'>
            Acessar Minha Conta →
        </a>

    </div>
    ";
}


/* =====================================================
   LISTA DE ESPERA — AJAX + EMAIL
===================================================== */

add_action( 'wp_ajax_slybot_waitlist',        'slybot_waitlist_handler' );
add_action( 'wp_ajax_nopriv_slybot_waitlist', 'slybot_waitlist_handler' );

function slybot_waitlist_handler() {

    check_ajax_referer( 'slybot_waitlist_nonce', 'nonce' );

    $nome     = sanitize_text_field( $_POST['nome']     ?? '' );
    $email    = sanitize_email(      $_POST['email']    ?? '' );
    $telefone = sanitize_text_field( $_POST['telefone'] ?? '' );

    if ( empty( $nome ) || empty( $email ) || empty( $telefone ) ) {
        wp_send_json_error( [ 'message' => 'Preencha todos os campos.' ] );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => 'E-mail inválido.' ] );
    }

    $body = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>SlyBot - Lista de Espera</title></head>
<body style="margin:0;padding:0;background:#020817;font-family:Arial,Helvetica,sans-serif;color:#ffffff;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#020817;padding:40px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#020817;border:1px solid #1e293b;border-radius:8px;padding:40px;">
<tr><td>
<p style="margin:0 0 25px 0;"><img src="https://slybot.com.br/wp-content/uploads/2025/12/logo-1.png" width="160" alt="SlyBot"></p>
<h1 style="font-size:28px;margin:0 0 20px 0;color:#ffffff;">Você entrou na lista de espera 🚀</h1>
<p style="color:#cbd5f5;font-size:16px;">Olá <strong>' . esc_html( $nome ) . '</strong>,</p>
<p style="color:#cbd5f5;font-size:16px;line-height:1.6;">Seu cadastro na <strong>lista de espera do SlyBot</strong> foi recebido com sucesso.</p>
<p style="color:#cbd5f5;font-size:16px;line-height:1.6;">Assim que o robô estiver disponível para aquisição, você será um dos primeiros a ser avisado.</p>
<hr style="border:none;border-top:1px solid #1e293b;margin:30px 0;">
<h3 style="margin-bottom:15px;">Seus dados cadastrados</h3>
<p style="color:#cbd5f5;margin:6px 0;"><strong>Nome:</strong> ' . esc_html( $nome ) . '</p>
<p style="color:#cbd5f5;margin:6px 0;"><strong>Email:</strong> ' . esc_html( $email ) . '</p>
<p style="color:#cbd5f5;margin:6px 0;"><strong>Telefone:</strong> ' . esc_html( $telefone ) . '</p>
<hr style="border:none;border-top:1px solid #1e293b;margin:30px 0;">
<h3 style="margin-bottom:15px;">O que é o SlyBot?</h3>
<p style="color:#cbd5f5;line-height:1.6;">O <strong>SlyBot</strong> é um robô de trading automatizado desenvolvido para operar no <strong>MetaTrader 5</strong>, utilizando estratégias avançadas para identificar oportunidades no mercado.</p>
<p style="color:#cbd5f5;line-height:1.6;">Quando o lançamento oficial acontecer você receberá:</p>
<ul style="color:#cbd5f5;line-height:1.8;padding-left:20px;">
<li>Link para aquisição do robô</li>
<li>Download do SlyBot</li>
<li>Licença de ativação</li>
<li>Acesso ao curso de instalação</li>
</ul>
<hr style="border:none;border-top:1px solid #1e293b;margin:30px 0;">
<p style="color:#94a3b8;font-size:14px;">Você recebeu este email porque se cadastrou na lista de espera do SlyBot.</p>
<p style="color:#94a3b8;font-size:14px;">© SlyBot - Todos os direitos reservados</p>
</td></tr></table>
</td></tr></table>
</body></html>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: SlyBot <contato@slybot.com.br>',
    ];

    $sent = wp_mail( $email, 'Você entrou na lista de espera do SlyBot 🚀', $body, $headers );

    // Salvar em arquivo TXT
    $upload_dir = wp_upload_dir();
    $file_path  = $upload_dir['basedir'] . '/lista-espera.txt';
    $line       = date('d/m/Y H:i:s') . ' | ' . $nome . ' | ' . $email . ' | ' . $telefone . PHP_EOL;
    file_put_contents( $file_path, $line, FILE_APPEND | LOCK_EX );

    // Notificação interna para a equipe
    $admin_body = '
<html><body style="font-family:Arial,sans-serif;background:#020817;color:#fff;padding:30px;">
<h2 style="color:#ff6a00;">Novo cadastro na lista de espera</h2>
<p><strong>Nome:</strong> ' . esc_html( $nome ) . '</p>
<p><strong>E-mail:</strong> ' . esc_html( $email ) . '</p>
<p><strong>Telefone:</strong> ' . esc_html( $telefone ) . '</p>
</body></html>';

    wp_mail( [ 'contato@slybot.com.br', 'gholive@gmail.com' ], 'Lista de Espera: ' . $nome, $admin_body, $headers );

    if ( $sent ) {
        wp_send_json_success( [ 'message' => 'Cadastro realizado! Verifique seu e-mail.' ] );
    } else {
        wp_send_json_error( [ 'message' => 'Erro ao enviar e-mail. Tente novamente.' ] );
    }
}


/* =====================================================
   ESTILOS GERAIS SLYBOT (login notice + thankyou)
===================================================== */

add_action( 'wp_head', 'slybot_access_styles' );

function slybot_access_styles() {

    if ( ! is_account_page() && ! is_wc_endpoint_url( 'order-received' ) ) return;

    ?>
    <style>

    /* --- aviso no login --- */
    .slybot-login-notice {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 20px;
        padding: 14px 16px;
        background: #fff8f3;
        border: 1px solid #ffe0cc;
        border-radius: 10px;
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }

    .slybot-login-notice a {
        color: #ff6a00;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .slybot-login-notice a:hover { text-decoration: underline; }

    /* --- página pós-pedido --- */
    .slybot-thankyou {
        text-align: center;
        max-width: 520px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .slybot-thankyou-icon { margin-bottom: 20px; }

    .slybot-thankyou h2 {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .slybot-thankyou-sub {
        color: #6b7280;
        font-size: 15px;
        margin-bottom: 30px;
    }

    .slybot-thankyou-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 20px;
        text-align: left;
    }

    .slybot-thankyou-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 22px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }

    .slybot-thankyou-row:last-child { border-bottom: none; }

    .slybot-thankyou-label {
        color: #9ca3af;
        font-weight: 500;
    }

    .slybot-thankyou-value {
        font-weight: 600;
        color: #111827;
    }

    .slybot-thankyou-notice {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #fff8f3;
        border: 1px solid #ffe0cc;
        border-radius: 10px;
        padding: 16px 18px;
        font-size: 14px;
        color: #6b7280;
        line-height: 1.6;
        text-align: left;
    }

    .slybot-btn-primary {
        padding: 14px 36px;
        background: #ff6a00;
        color: #fff !important;
        border-radius: 10px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none !important;
        transition: background .2s;
    }

    .slybot-btn-primary:hover { background: #e55e00; }

    </style>
    <?php
}


/* =====================================================
   CHECKOUT — REMOVER CAMPOS DE ENDEREÇO E NOTAS
===================================================== */

add_filter('woocommerce_checkout_fields', function($fields) {
    $remove = ['billing_company', 'billing_address_1', 'billing_address_2',
               'billing_city', 'billing_state', 'billing_postcode', 'billing_country',
               'billing_number', 'billing_neighborhood', 'billing_bairro'];
    foreach ($remove as $key) {
        unset($fields['billing'][$key]);
    }
    return $fields;
});

add_filter('woocommerce_enable_order_notes_field', '__return_false');


/* =====================================================
   CHECKOUT — ESTILO MODERNO
===================================================== */

add_action('wp_head', function() {
    if (!is_checkout() || is_wc_endpoint_url('order-received')) return;
    ?>
    <style>
    /* ── Layout geral ── */
    .woocommerce-checkout .woocommerce,
    .woocommerce-page .woocommerce {
        max-width: 1060px !important;
        margin: 0 auto !important;
        padding: 40px 24px 60px !important;
    }

    /* Grid 2 colunas: form | resumo */
    form.woocommerce-checkout {
        display: grid !important;
        grid-template-columns: 1fr 360px !important;
        grid-template-areas: "customer review" !important;
        gap: 28px !important;
        align-items: start !important;
    }
    #customer_details     { grid-area: customer; }
    #order_review_heading { display: none !important; }
    #order_review         { grid-area: review; }

    @media (max-width: 860px) {
        form.woocommerce-checkout {
            grid-template-columns: 1fr !important;
            grid-template-areas: "customer" "review" !important;
        }
    }

    /* ── Remove colunas internas do WC ── */
    .col2-set { width: 100% !important; }
    .col2-set .col-1,
    .col2-set .col-2 { float: none !important; width: 100% !important; clear: both; }

    /* ── Seções como cards ── */
    .woocommerce-billing-fields,
    .woocommerce-additional-fields,
    #ship-to-different-address {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 28px 28px 20px;
        margin-bottom: 20px !important;
    }

    /* ── Títulos de seção ── */
    .woocommerce-billing-fields > h3,
    .woocommerce-additional-fields > h3,
    #ship-to-different-address h3,
    #order_review_heading {
        font-size: 15px !important;
        font-weight: 700 !important;
        color: #111827 !important;
        margin: 0 0 20px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid #f3f4f6 !important;
        text-transform: none !important;
    }

    /* ── Labels dos campos ── */
    .woocommerce-checkout .form-row label,
    .woocommerce-checkout .form-row label.checkbox {
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: .4px !important;
        color: #374151 !important;
        margin-bottom: 6px !important;
        display: block !important;
    }
    .woocommerce-checkout .form-row label abbr {
        color: #ff6a00 !important;
        text-decoration: none !important;
    }

    /* ── Inputs ── */
    .woocommerce-checkout .form-row .input-text,
    .woocommerce-checkout .form-row select,
    .woocommerce-checkout .form-row textarea {
        width: 100% !important;
        padding: 11px 14px !important;
        border: 1.5px solid #e5e7eb !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        color: #111827 !important;
        background: #fff !important;
        box-shadow: none !important;
        transition: border-color .2s, box-shadow .2s !important;
        outline: none !important;
        height: auto !important;
    }
    .woocommerce-checkout .form-row .input-text:focus,
    .woocommerce-checkout .form-row select:focus,
    .woocommerce-checkout .form-row textarea:focus {
        border-color: #ff6a00 !important;
        box-shadow: 0 0 0 3px rgba(255,106,0,.10) !important;
    }
    .woocommerce-checkout .form-row .input-text::placeholder { color: #9ca3af !important; }

    /* ── Campo inline (half width) ── */
    .woocommerce-checkout .form-row-first,
    .woocommerce-checkout .form-row-last {
        width: calc(50% - 8px) !important;
        display: inline-block !important;
    }
    .woocommerce-checkout .form-row-first { margin-right: 16px !important; }

    /* ── Mensagem de erro dos campos ── */
    .woocommerce-checkout .woocommerce-invalid .input-text,
    .woocommerce-checkout .woocommerce-invalid select {
        border-color: #ef4444 !important;
    }

    /* ── Resumo do pedido ── */
    #order_review {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
    }
    .woocommerce-checkout-review-order {
        padding: 0 !important;
    }
    table.woocommerce-checkout-review-order-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 14px !important;
        margin: 0 !important;
    }
    table.woocommerce-checkout-review-order-table thead {
        background: #f9fafb;
        border-bottom: 1px solid #f3f4f6;
    }
    table.woocommerce-checkout-review-order-table th {
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: .4px !important;
        color: #9ca3af !important;
        padding: 14px 20px !important;
    }
    table.woocommerce-checkout-review-order-table td {
        padding: 14px 20px !important;
        border-bottom: 1px solid #f3f4f6 !important;
        color: #374151 !important;
        vertical-align: middle !important;
    }
    table.woocommerce-checkout-review-order-table .cart-subtotal td,
    table.woocommerce-checkout-review-order-table .cart-subtotal th { font-weight: 500 !important; }
    table.woocommerce-checkout-review-order-table .order-total td,
    table.woocommerce-checkout-review-order-table .order-total th {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #111827 !important;
        border-bottom: none !important;
    }
    table.woocommerce-checkout-review-order-table .order-total td bdi,
    table.woocommerce-checkout-review-order-table .order-total td .woocommerce-Price-amount {
        color: #ff6a00 !important;
    }

    /* ── Seção de pagamento ── */
    #payment {
        background: #f8fafc !important;
        border-top: 1px solid #f3f4f6 !important;
        padding: 20px !important;
        margin: 0 !important;
        border-radius: 0 0 16px 16px !important;
    }

    /* Tabs horizontais lado a lado */
    #payment .payment_methods {
        display: flex !important;
        gap: 10px !important;
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 12px !important;
        flex-wrap: nowrap !important;
    }
    #payment .payment_methods li {
        flex: 1 !important;
        height: 50px !important;
        border: 1.5px solid #e5e7eb !important;
        border-radius: 10px !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
        cursor: pointer !important;
        overflow: hidden !important;
        transition: background .15s, border-color .15s !important;
    }

    /* Radio invisível */
    #payment .payment_methods input[type=radio] {
        position: absolute !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    /* Label: botão centralizado */
    #payment .payment_methods > li > label {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        height: 50px !important;
        padding: 0 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: .4px !important;
        color: #374151 !important;
        cursor: pointer !important;
        margin: 0 !important;
        white-space: nowrap !important;
        transition: background .15s, color .15s !important;
    }

    /* Tab selecionado */
    #payment .payment_methods li:has(input[type=radio]:checked) {
        background: #111827 !important;
        border-color: #111827 !important;
    }
    #payment .payment_methods li:has(input[type=radio]:checked) > label {
        color: #fff !important;
    }
    #payment .payment_methods li:not(:has(input:checked)):hover {
        border-color: #d1d5db !important;
        background: #f9fafb !important;
    }

    /* Boxes ficam escondidas no li — JS as move para o container externo */
    #payment .payment_methods .payment_box { display: none !important; }

    /* Container externo do payment box */
    #slybot-pbox-container {
        background: #fff;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 4px 16px 16px;
        margin-bottom: 16px;
    }
    #slybot-pbox-container .payment_box {
        display: block !important;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #slybot-pbox-container .payment_box::before { display: none !important; }
    .slybot-pbox-item { display: block; width: 100%; }

    /* ── Card visual preview ── */
    #slybot-card-preview {
        display: block;
        width: 100%;
        max-width: 240px;
        margin: 4px auto 14px;
        aspect-ratio: 1.586;
        perspective: 900px;
    }
    .slybot-cc-inner {
        position: relative;
        width: 100%; height: 100%;
        transform-style: preserve-3d;
        transition: transform 0.55s ease;
    }
    .slybot-cc-inner.is-flipped { transform: rotateY(180deg); }
    .slybot-cc-front, .slybot-cc-back {
        position: absolute;
        inset: 0;
        border-radius: 14px;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(0,0,0,0.28);
    }
    /* Frente */
    .slybot-cc-front {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px 16px 14px;
    }
    .slybot-cc-front::before {
        content: '';
        position: absolute;
        top: -50px; right: -50px;
        width: 170px; height: 170px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
        pointer-events: none;
    }
    /* Verso */
    .slybot-cc-back {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        transform: rotateY(180deg);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .slybot-cc-stripe {
        background: #0d1117;
        height: 38px;
        margin-top: 22px;
    }
    .slybot-cc-sig-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
    }
    .slybot-cc-sig {
        flex: 1;
        height: 30px;
        background: repeating-linear-gradient(90deg, #fff 0 5px, #e5e5e5 5px 10px);
        border-radius: 3px;
    }
    .slybot-cc-cvv-box {
        background: #fff;
        border-radius: 4px;
        padding: 4px 10px;
        text-align: center;
        min-width: 44px;
    }
    .slybot-cc-cvv-lbl {
        font-size: 7px; font-weight: 700; letter-spacing: .6px;
        color: #6b7280; text-transform: uppercase;
    }
    .slybot-cc-cvv-val {
        font-size: 13px; font-weight: 700; color: #111827;
        letter-spacing: 2px; font-family: 'Courier New', monospace;
    }
    /* Elementos comuns */
    .slybot-cc-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative; z-index: 1;
    }
    .slybot-cc-number {
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 2px;
        color: #fff;
        text-align: center;
        font-family: 'Courier New', monospace;
        position: relative; z-index: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        text-shadow: 0 1px 4px rgba(0,0,0,.3);
    }
    .slybot-cc-footer {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        position: relative; z-index: 1;
    }
    .slybot-cc-lbl {
        font-size: 7px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .8px; color: rgba(255,255,255,.5); margin-bottom: 2px;
    }
    .slybot-cc-holder {
        font-size: 10px; font-weight: 600; color: #fff;
        max-width: 120px; overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; letter-spacing: .5px;
    }
    .slybot-cc-expiry {
        font-size: 11px; font-weight: 600; color: #fff;
        letter-spacing: 1px; font-family: 'Courier New', monospace; text-align: right;
    }
    .slybot-cc-brand {
        font-size: 12px; font-weight: 800; color: rgba(255,255,255,.9);
        letter-spacing: 1px; text-transform: uppercase; font-style: italic;
        text-shadow: 0 1px 4px rgba(0,0,0,.4); text-align: right;
    }


    /* ── Campos do gateway de pagamento ── */
    #payment .payment_box label,
    #payment .payment_box .form-row label {
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: .4px !important;
        color: #374151 !important;
        margin-bottom: 6px !important;
        display: block !important;
    }
    #payment .payment_box input,
    #payment .payment_box select,
    #payment .payment_box textarea {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 11px 14px !important;
        border: 1.5px solid #e5e7eb !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        color: #111827 !important;
        background: #fff !important;
        box-shadow: none !important;
        outline: none !important;
        height: auto !important;
        box-sizing: border-box !important;
        display: block !important;
        transition: border-color .2s, box-shadow .2s !important;
    }
    #payment .payment_box input:focus,
    #payment .payment_box select:focus {
        border-color: #ff6a00 !important;
        box-shadow: 0 0 0 3px rgba(255,106,0,.10) !important;
    }
    #payment .payment_box input::placeholder { color: #9ca3af !important; font-size: 13px !important; }
    #payment .payment_box .form-row,
    #payment .payment_box p { margin-bottom: 10px !important; }
    .woocommerce-checkout #payment div.form-row { padding: 0 !important; }
    #payment .payment_box .form-row-first,
    #payment .payment_box .form-row-last {
        width: calc(50% - 6px) !important;
        display: inline-block !important;
        vertical-align: top !important;
        box-sizing: border-box !important;
    }
    #payment .payment_box .form-row-first { margin-right: 12px !important; }
    #payment .payment_box > p:first-child,
    #payment .payment_box .asaas-description {
        font-size: 13px !important; color: #6b7280 !important; margin-bottom: 16px !important;
    }

    /* ── Botão finalizar pedido ── */
    #payment .place-order { padding: 0 !important; margin: 0 !important; }
    #place_order {
        display: block !important;
        width: 100% !important;
        padding: 16px 24px !important;
        background: #ff6a00 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 10px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        letter-spacing: .2px !important;
        cursor: pointer !important;
        transition: background .2s !important;
        text-align: center !important;
    }
    #place_order:hover { background: #e55e00 !important; }

    /* ── Notificações ── */
    .woocommerce-NoticeGroup .woocommerce-error,
    .woocommerce-NoticeGroup .woocommerce-message {
        border-left: 4px solid #ff6a00 !important;
        border-radius: 8px !important;
        padding: 14px 18px !important;
        background: #fff8f0 !important;
        font-size: 14px !important;
        margin-bottom: 20px !important;
    }
    .woocommerce-NoticeGroup .woocommerce-error li,
    .woocommerce-NoticeGroup .woocommerce-message li { margin: 0 !important; }
    .woocommerce-form-login-toggle .woocommerce-info {
        background: #f8fafc !important;
        border: 1px solid #e5e7eb !important;
        border-left: 4px solid #ff6a00 !important;
        border-radius: 8px !important;
        padding: 14px 18px !important;
        font-size: 14px !important;
        color: #374151 !important;
        margin-bottom: 20px !important;
    }
    .woocommerce-form-login-toggle .woocommerce-info::before { display: none !important; }
    .woocommerce-form-login-toggle a { color: #ff6a00 !important; font-weight: 600; }
    </style>
    <?php
}, 99);


/* =====================================================
   CHECKOUT — CARD VISUAL LIVE PREVIEW (JS)
===================================================== */

add_action('wp_footer', function() {
    if (!is_checkout() || is_wc_endpoint_url('order-received')) return;
    ?>
    <script>
    (function() {
        'use strict';

        var CHIP_SVG = '<svg viewBox="0 0 50 38" width="34" height="26" xmlns="http://www.w3.org/2000/svg">'
            + '<rect width="50" height="38" rx="5" fill="#d4a843"/>'
            + '<rect x="2" y="12" width="46" height="14" fill="#b8912a"/>'
            + '<rect x="17" y="2" width="16" height="34" fill="#b8912a"/>'
            + '<rect x="2" y="2" width="46" height="34" rx="5" fill="none" stroke="#c49a30" stroke-width="1.5"/>'
            + '<rect x="17" y="12" width="16" height="14" fill="#d4a843"/>'
            + '</svg>';

        var CARD_HTML = '<div class="slybot-cc-inner" id="slcc-inner">'
            /* Frente */
            + '<div class="slybot-cc-front">'
            +   '<div class="slybot-cc-top">'
            +     '<div class="slybot-cc-chip">' + CHIP_SVG + '</div>'
            +     '<div class="slybot-cc-brand" id="slcc-brand"></div>'
            +   '</div>'
            +   '<div class="slybot-cc-number" id="slcc-num">•••• •••• •••• ••••</div>'
            +   '<div class="slybot-cc-footer">'
            +     '<div><div class="slybot-cc-lbl">Titular</div><div class="slybot-cc-holder" id="slcc-holder">NOME COMPLETO</div></div>'
            +     '<div><div class="slybot-cc-lbl">Validade</div><div class="slybot-cc-expiry" id="slcc-expiry">MM/AA</div></div>'
            +   '</div>'
            + '</div>'
            /* Verso */
            + '<div class="slybot-cc-back">'
            +   '<div class="slybot-cc-stripe"></div>'
            +   '<div class="slybot-cc-sig-row">'
            +     '<div class="slybot-cc-sig"></div>'
            +     '<div class="slybot-cc-cvv-box">'
            +       '<div class="slybot-cc-cvv-lbl">CVV</div>'
            +       '<div class="slybot-cc-cvv-val" id="slcc-cvv">•••</div>'
            +     '</div>'
            +   '</div>'
            + '</div>'
            + '</div>';

        /* ── Setup: move boxes para container externo (após Asaas popular) ── */
        function setupLayout() {
            var ul = document.querySelector('#payment .payment_methods');
            if (!ul) return;

            // Remove container anterior (ex: em updated_checkout)
            var old = document.getElementById('slybot-pbox-container');
            if (old) old.remove();
            document.querySelectorAll('#slybot-pbox-container').forEach(function(el){ el.remove(); });

            var container = document.createElement('div');
            container.id  = 'slybot-pbox-container';
            ul.parentNode.insertBefore(container, ul.nextSibling);

            function moveBoxes() {
                // Limpa container de runs anteriores
                while (container.firstChild) container.removeChild(container.firstChild);
                ul.querySelectorAll('li').forEach(function(li) {
                    var radio = li.querySelector('input[type=radio]');
                    var box   = li.querySelector('.payment_box');
                    if (!box || !radio) return;
                    var wrap = document.createElement('div');
                    wrap.className      = 'slybot-pbox-item';
                    wrap.dataset.method = radio.value;
                    wrap.style.display  = radio.checked ? 'block' : 'none';
                    wrap.appendChild(box);
                    container.appendChild(wrap);
                });
                // Remove preview antigo e reinicializa
                var oldPrev = document.getElementById('slybot-card-preview');
                if (oldPrev) oldPrev.remove();
                addCardPreview();
            }

            // Verifica se todos os payment_boxes têm conteúdo
            var boxes = ul.querySelectorAll('.payment_box');
            var ready = true;
            boxes.forEach(function(b) { if (b.children.length === 0) ready = false; });

            if (ready) {
                moveBoxes();
            } else {
                // Aguarda Asaas popular os campos
                var obs = new MutationObserver(function() {
                    var done = true;
                    ul.querySelectorAll('.payment_box').forEach(function(b) { if (b.children.length === 0) done = false; });
                    if (done) { obs.disconnect(); moveBoxes(); }
                });
                obs.observe(ul, { childList: true, subtree: true });
                setTimeout(function() { obs.disconnect(); moveBoxes(); }, 2000);
            }

            // Alterna visibilidade ao trocar tab
            ul.addEventListener('change', function(e) {
                if (!e.target || e.target.type !== 'radio') return;
                container.querySelectorAll('.slybot-pbox-item').forEach(function(item) {
                    item.style.display = item.dataset.method === e.target.value ? 'block' : 'none';
                });
            });
        }

        /* Encontra o payment_box do cartão (não-pix) — busca no container ou no li */
        function findCreditBox() {
            // Primeiro: busca no container externo (após JS mover)
            var container = document.getElementById('slybot-pbox-container');
            if (container) {
                var found = null;
                container.querySelectorAll('.slybot-pbox-item').forEach(function(wrap) {
                    if (wrap.dataset.method && wrap.dataset.method.toLowerCase().indexOf('pix') === -1) {
                        found = wrap.querySelector('.payment_box') || wrap;
                    }
                });
                if (found) return found;
            }
            // Fallback: busca no li original
            var fallback = null;
            document.querySelectorAll('#payment .payment_methods li').forEach(function(li) {
                var radio = li.querySelector('input[type=radio]');
                if (radio && radio.value.toLowerCase().indexOf('pix') === -1) {
                    fallback = li.querySelector('.payment_box');
                }
            });
            return fallback;
        }

        /* Insere o card preview no topo do payment_box */
        function insertCardPreview(box) {
            if (document.getElementById('slybot-card-preview')) return;
            var cardEl = document.createElement('div');
            cardEl.id  = 'slybot-card-preview';
            cardEl.innerHTML = CARD_HTML;
            box.insertBefore(cardEl, box.firstChild);
            bindFields();
        }

        /* Adiciona card preview (aguarda campos se necessário) */
        function addCardPreview() {
            var box = findCreditBox();
            if (!box) return;
            if (document.getElementById('slybot-card-preview')) return;

            if (box.querySelector('input, select')) {
                insertCardPreview(box);
                return;
            }
            var obs = new MutationObserver(function() {
                if (box.querySelector('input, select')) {
                    obs.disconnect();
                    insertCardPreview(box);
                }
            });
            obs.observe(box, { childList: true, subtree: true });
            setTimeout(function() {
                obs.disconnect();
                if (!document.getElementById('slybot-card-preview')) insertCardPreview(box);
            }, 1500);
        }

        /* Detecta bandeira pelo número */
        function detectBrand(v) {
            v = v.replace(/\D/g, '');
            if (/^4/.test(v))           return 'VISA';
            if (/^5[1-5]/.test(v))      return 'MASTERCARD';
            if (/^3[47]/.test(v))       return 'AMEX';
            if (/^(60|65|38|35)/.test(v)) return 'ELO';
            if (/^3(?:0[0-5]|[68])/.test(v)) return 'DINERS';
            if (/^6(?:011|5)/.test(v))  return 'DISCOVER';
            return '';
        }

        /* Live update */
        function maskNum(v) {
            v = v.replace(/\D/g, '').substring(0, 16);
            var o = '';
            for (var i = 0; i < 16; i++) {
                if (i > 0 && i % 4 === 0) o += ' ';
                o += v[i] !== undefined ? v[i] : '•';
            }
            return o;
        }
        function bindFields() {
            var box = findCreditBox();
            if (!box) return;

            var numEl    = document.getElementById('slcc-num');
            var holderEl = document.getElementById('slcc-holder');
            var expiryEl = document.getElementById('slcc-expiry');
            var cvvEl    = document.getElementById('slcc-cvv');
            var inner    = document.getElementById('slcc-inner');
            var monthSel = null, yearSel = null;

            function flip(on)  { if (inner) inner.classList[on ? 'add' : 'remove']('is-flipped'); }
            function upExp()   {
                if (!expiryEl) return;
                var m = monthSel && monthSel.value ? monthSel.value : 'MM';
                var y = yearSel  && yearSel.value  ? String(yearSel.value).slice(-2) : 'AA';
                expiryEl.textContent = m + '/' + y;
            }

            // Inputs: classificar por atributos
            box.querySelectorAll('input').forEach(function(inp) {
                if (inp.type === 'radio' || inp.type === 'checkbox' || inp.type === 'hidden') return;
                var key = (inp.id + ' ' + inp.name + ' ' + (inp.placeholder || '') + ' ' + (inp.getAttribute('data-label') || '')).toLowerCase();
                var isNum  = key.indexOf('number') > -1 || key.indexOf('numero') > -1 || key.indexOf('cart') > -1;
                var isName = key.indexOf('holder') > -1 || key.indexOf('nome') > -1 || key.indexOf('name') > -1 || key.indexOf('titular') > -1;
                var isCvv  = key.indexOf('cvv') > -1 || key.indexOf('ccv') > -1 || key.indexOf('cvc') > -1 || key.indexOf('seguranca') > -1 || key.indexOf('security') > -1 || key.indexOf('codigo') > -1;

                if (isNum) {
                    inp.addEventListener('input', function() {
                        if (numEl)    numEl.textContent    = maskNum(this.value);
                        var brandEl = document.getElementById('slcc-brand');
                        if (brandEl) brandEl.textContent  = detectBrand(this.value);
                    });
                }
                if (isName) {
                    inp.addEventListener('input', function() {
                        if (holderEl) holderEl.textContent = this.value.toUpperCase() || 'NOME COMPLETO';
                    });
                }
                if (isCvv) {
                    inp.addEventListener('focus',  function() { flip(true);  });
                    inp.addEventListener('blur',   function() { flip(false); });
                    inp.addEventListener('input',  function() {
                        if (cvvEl) cvvEl.textContent = this.value || '•••';
                    });
                }

                // Fallback: se nenhuma categoria, tenta pelo valor atual
                if (!isNum && !isName && !isCvv && inp.value.replace(/\D/g,'').length >= 13) {
                    // Provavelmente número do cartão
                    inp.addEventListener('input', function() {
                        if (numEl) numEl.textContent = maskNum(this.value);
                        var brandEl = document.getElementById('slcc-brand');
                        if (brandEl) brandEl.textContent = detectBrand(this.value);
                    });
                }
            });

            // Selects de mês/ano — busca por atributos ou por valores das options
            box.querySelectorAll('select').forEach(function(sel) {
                var key = (sel.id + ' ' + sel.name + ' ' + (sel.getAttribute('aria-label') || '')).toLowerCase();
                var isMes = key.indexOf('month') > -1 || key.indexOf('mes') > -1 || key.indexOf('mês') > -1;
                var isAno = key.indexOf('year')  > -1 || key.indexOf('ano') > -1;
                // Fallback: opções de 1–12 = mês; opções com 4 dígitos = ano
                if (!isMes && !isAno && sel.options.length > 0) {
                    var firstVal = parseInt(sel.options[sel.options.length > 1 ? 1 : 0].value);
                    if (!isNaN(firstVal) && firstVal <= 12) isMes = true;
                    else if (!isNaN(firstVal) && firstVal > 1000)  isAno = true;
                }
                if (isMes) { monthSel = sel; sel.addEventListener('change', upExp); }
                if (isAno) { yearSel  = sel; sel.addEventListener('change', upExp); }
            });

            // Exibe valores já preenchidos na página
            upExp();
        }

        /* Init */
        jQuery(document.body).on('updated_checkout', setupLayout);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupLayout);
        } else {
            setupLayout();
        }

    })();
    </script>
    <?php
}, 99);