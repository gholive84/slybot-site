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
               'billing_city', 'billing_state', 'billing_postcode', 'billing_country'];
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

    /* Grid 2 colunas com template areas */
    form.woocommerce-checkout {
        display: grid !important;
        grid-template-columns: 1fr 380px !important;
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
    #payment .payment_methods {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 16px !important;
    }
    #payment .payment_methods li {
        background: #fff !important;
        border: 1.5px solid #e5e7eb !important;
        border-radius: 10px !important;
        padding: 14px 16px !important;
        margin-bottom: 8px !important;
        cursor: pointer !important;
        transition: border-color .2s !important;
    }
    #payment .payment_methods li.payment_method_asaas,
    #payment .payment_methods li:has(input[type=radio]:checked) {
        border-color: #ff6a00 !important;
        background: #fff8f0 !important;
    }
    #payment .payment_methods .payment_box {
        background: transparent !important;
        border: none !important;
        padding: 10px 0 0 26px !important;
        font-size: 13px !important;
        color: #6b7280 !important;
    }
    #payment .payment_methods .payment_box::before { display: none !important; }
    #payment .payment_methods label {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #111827 !important;
        cursor: pointer !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    #payment .payment_methods input[type=radio] {
        accent-color: #ff6a00;
        width: 16px;
        height: 16px;
        flex-shrink: 0;
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

    /* ── Notificações de erro/aviso ── */
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

    /* ── "Já tem conta?" ── */
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