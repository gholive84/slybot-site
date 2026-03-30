
<nav class="slybot-account-nav">

<ul>
    <button class="slybot-sidebar-toggle">
<i class="fa-solid fa-bars"></i>
</button>

<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>

<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">

<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>">

<span class="slybot-icon">

<?php
switch ($endpoint) {

case 'dashboard':
echo '<i class="fa-solid fa-house slybot-icon"></i>';
break;

case 'orders':
echo '<i class="fa-solid fa-box slybot-icon"></i>';
break;

case 'edit-address':
echo '<i class="fa-solid fa-location-dot slybot-icon"></i>';
break;

case 'minhas-licencas':
echo '<i class="fa-solid fa-robot slybot-icon"></i>';
break;

case 'curso-slybot':
echo '<i class="fa-solid fa-graduation-cap slybot-icon"></i>';
break;

case 'edit-account':
echo '<i class="fa-solid fa-gear slybot-icon"></i>';
break;

case 'customer-logout':
echo '<i class="fa-solid fa-right-from-bracket slybot-icon"></i>';
break;

}
?>

</span>

<span class="slybot-label">
<?php echo esc_html( $label ); ?>
</span>

</a>

</li>

<?php endforeach; ?>

</ul>

</nav>