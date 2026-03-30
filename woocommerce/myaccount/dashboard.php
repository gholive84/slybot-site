<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$current_user = wp_get_current_user();
?>
<div class="slybot-dashboard">

<h2>Bem-vindo, <?php echo esc_html( $current_user->display_name ); ?> 👋</h2>

<p class="slybot-intro">
Seu acesso ao <strong>Slybot</strong> já está ativo.  
Para começar corretamente, recomendamos seguir os passos abaixo.
</p>

<div class="slybot-steps">

<div class="slybot-step">
<div class="slybot-step-number">1</div>
<div>
<h3>Assista ao Curso Slybot</h3>
<p>
Na aba <strong>Curso Slybot</strong> você encontrará todas as aulas explicando
como instalar, configurar e utilizar o robô corretamente.
</p>
</div>
</div>

<div class="slybot-step">
<div class="slybot-step-number">2</div>
<div>
<h3>Baixe o robô</h3>
<p>
Após assistir às aulas iniciais, vá até a aba <strong>Meus Robôs</strong>
para baixar a versão mais recente do robô.
</p>
</div>
</div>

<div class="slybot-step">
<div class="slybot-step-number">3</div>
<div>
<h3>Ative sua licença</h3>
<p>
Durante a instalação explicada no curso, você utilizará sua chave de licença
para ativar o robô automaticamente.
</p>
</div>
</div>

</div>

<div class="slybot-help">
<h3>Importante</h3>
<p>
Recomendamos assistir ao curso antes de instalar o robô para garantir
que tudo funcione corretamente em sua conta.
</p>
</div>

</div>