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