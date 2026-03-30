<?php
/**
 * Customer refunded order email - Custom SlyBot
 */

defined( 'ABSPATH' ) || exit;

/*
 * Header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<h2>Reembolso do pedido</h2>

<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	printf( 'Olá %s,', esc_html( $order->get_billing_first_name() ) );
} else {
	echo 'Olá,';
}
?>
</p>

<p>
<?php
if ( $partial_refund ) {
	echo 'Seu pedido foi <strong>parcialmente reembolsado</strong>.';
} else {
	echo 'Seu pedido foi <strong>reembolsado</strong>.';
}
?>
</p>

<p>
O valor foi devolvido através do mesmo método de pagamento utilizado na compra.
</p>

<hr>

<h3>Detalhes do pedido</h3>

<?php

/*
 * Tabela do pedido
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * Meta do pedido
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

?>

<hr>

<h3>Conta do cliente</h3>

<p>
<strong>Email da conta:</strong> <?php echo esc_html( $order->get_billing_email() ); ?>
</p>

<p>
Você pode acessar sua conta para visualizar seus pedidos:
</p>

<p>
<a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>">
Acessar minha conta
</a>
</p>

<?php

/*
 * Conteúdo adicional configurado no WooCommerce
 */
if ( $additional_content ) {

	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );

}

/*
 * Footer
 */
do_action( 'woocommerce_email_footer', $email );