<?php
/**
 * Customer new account email - Custom SlyBot
 */

defined( 'ABSPATH' ) || exit;

/*
 * Header do email
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<h2>Bem-vindo ao SlyBot</h2>

<p>
Olá <strong><?php echo esc_html( $user_login ); ?></strong>,
</p>

<p>
Sua conta foi criada com sucesso na plataforma <strong>SlyBot</strong>.
</p>

<p>
Através da sua conta você poderá:
</p>

<ul>
<li>Baixar o robô SlyBot</li>
<li>Gerenciar suas licenças</li>
<li>Ver seus pedidos</li>
<li>Atualizar seus dados</li>
</ul>

<hr>

<h3>Dados da sua conta</h3>

<p>
<strong>Usuário:</strong> <?php echo esc_html( $user_login ); ?>
</p>

<?php if ( $password_generated && $set_password_url ) : ?>

<p>
Para definir sua senha de acesso, utilize o link abaixo:
</p>

<p>
<a href="<?php echo esc_attr( $set_password_url ); ?>">
Definir senha da conta
</a>
</p>

<?php endif; ?>

<hr>

<h3>Acesso à sua área do usuário</h3>

<p>
Você pode acessar sua conta a qualquer momento através do link abaixo:
</p>

<p>
<a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>">
Acessar minha conta
</a>
</p>

<hr>

<h3>Ativação do robô</h3>

<p>
Após a confirmação do pagamento do seu pedido, você receberá automaticamente um email contendo:
</p>

<ul>
<li>Licença de ativação do robô</li>
<li>Link para download do SlyBot</li>
<li>Guia de instalação</li>
<li>Instruções de ativação no MetaTrader 5</li>
</ul>

<p>
Se tiver qualquer dúvida, nossa equipe de suporte estará disponível para ajudar.
</p>

<?php

/**
 * Conteúdo adicional configurado no WooCommerce
 */
if ( $additional_content ) {

	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );

}

/*
 * Footer do email
 */
do_action( 'woocommerce_email_footer', $email );