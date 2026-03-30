<?php
/**
 * Customer failed order email - Custom SlyBot
 */

defined( 'ABSPATH' ) || exit;

/*
 * Header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<h2>Problema no pagamento do seu pedido</h2>

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
Não foi possível concluir o pagamento do seu pedido do <strong>SlyBot</strong>.
</p>

<p>
Isso pode ocorrer por alguns motivos, como:
</p>

<ul>
<li>Problema no método de pagamento</li>
<li>Transação recusada</li>
<li>Falha na comunicação com o gateway</li>
</ul>

<p>
Você pode tentar finalizar a compra novamente utilizando o link abaixo.
</p>

<p>
<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">
Finalizar pagamento
</a>
</p>

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

<hr>

<h3>Suporte</h3>

<p>
Se você continuar enfrentando problemas no pagamento, entre em contato com nosso suporte.
</p>

<p>
Você também pode acessar sua conta para revisar seu pedido.
</p>

<p>
<a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>">
Acessar minha conta
</a>
</p>

<?php

/*
 * Conteúdo adicional do WooCommerce
 */
if ( $additional_content ) {

	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );

}

/*
 * Footer
 */
do_action( 'woocommerce_email_footer', $email );