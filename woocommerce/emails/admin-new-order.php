<?php
/**
 * Admin new order email - Custom SlyBot
 */

defined( 'ABSPATH' ) || exit;

/*
 * Header do email
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<h2>Novo cliente SlyBot</h2>

<p>
O cliente realizou a compra do robô <strong>SlyBot</strong>.<br><br>

Após a <strong>confirmação do pagamento</strong>, o sistema enviará automaticamente ao cliente:

<ul>
<li>Dados de acesso ao robô</li>
<li>Licença de ativação</li>
<li>Link para download</li>
<li>Guia de instalação</li>
</ul>
</p>

<hr>

<h3>Informações do cliente</h3>

<p>
<strong>Cliente:</strong> <?php echo esc_html( $order->get_formatted_billing_full_name() ); ?><br>

<strong>Email:</strong> <?php echo esc_html( $order->get_billing_email() ); ?><br>

<strong>Pedido:</strong> #<?php echo esc_html( $order->get_id() ); ?>
</p>

<hr>

<h3>Produto / Plano adquirido</h3>

<?php
foreach ( $order->get_items() as $item ) {

    $product_name = $item->get_name();
    $qty = $item->get_quantity();

    echo '<p><strong>' . esc_html( $product_name ) . '</strong> (x' . esc_html( $qty ) . ')</p>';
}
?>

<hr>

<h3>Dados do robô</h3>

<?php

$mt5_login  = $order->get_meta('mt5_login');
$mt5_server = $order->get_meta('mt5_server');
$license_key = $order->get_meta('license_key');

echo '<p>';

if($mt5_login){
    echo '<strong>Conta MT5:</strong> ' . esc_html($mt5_login) . '<br>';
}

if($mt5_server){
    echo '<strong>Servidor:</strong> ' . esc_html($mt5_server) . '<br>';
}

if($license_key){
    echo '<strong>Licença:</strong> ' . esc_html($license_key) . '<br>';
}

if(!$mt5_login && !$mt5_server){
    echo 'Cliente ainda não informou dados da conta MT5.';
}

echo '</p>';

?>

<hr>

<h3>Detalhes do pedido</h3>

<?php

/*
 * Tabela de pedido
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * Meta do pedido
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

?>

<?php

/*
 * Footer
 */
do_action( 'woocommerce_email_footer', $email );