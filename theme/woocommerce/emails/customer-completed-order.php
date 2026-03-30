<?php
/**
 * Customer completed order email - Custom SlyBot
 */

defined( 'ABSPATH' ) || exit;

/*
 * Header do email
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<h2>Sua licença SlyBot foi ativada</h2>

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
Seu pagamento foi confirmado e sua licença do <strong>SlyBot</strong> foi ativada com sucesso.
</p>

<p>
Agora você já pode baixar o robô, acessar o curso e iniciar a instalação.
</p>

<hr>

<h3>Curso SlyBot</h3>

<p>
Preparamos um curso completo para ensinar a instalação e utilização do robô.
</p>

<p>
Acesse o curso através do link abaixo:
</p>

<p>
<a href="https://slybot.com.br/curso-slybot/">
Acessar curso do SlyBot
</a>
</p>

<p>
Obs: É necessário estar logado na sua conta para acessar o curso.
</p>

<hr>

<h3>Download do robô</h3>

<p>
Acesse sua área de usuário para baixar o robô e gerenciar sua licença.
</p>

<p>
<a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>">
Acessar minha conta
</a>
</p>

<hr>

<h3>Próximos passos</h3>

<ol>
<li>Assistir ao curso de instalação</li>
<li>Baixar o robô SlyBot</li>
<li>Instalar no MetaTrader 5</li>
<li>Inserir sua licença no robô</li>
<li>Começar a operar</li>
</ol>

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
Caso precise de ajuda com a instalação ou ativação do robô, nossa equipe de suporte estará disponível para ajudar.
</p>

<p>
<a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>">
Acessar minha conta
</a>
</p>

<?php

/**
 * Conteúdo adicional configurado no WooCommerce
 */
if ( $additional_content ) {

	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );

}

/*
 * Footer
 */
do_action( 'woocommerce_email_footer', $email );