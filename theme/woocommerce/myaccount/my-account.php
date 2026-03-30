<div class="slybot-account">

    <aside class="slybot-sidebar">

        <nav class="slybot-menu">
            <?php wc_get_template( 'myaccount/navigation.php' ); ?>
        </nav>

    </aside>

    <main class="slybot-content">

        <h1 class="slybot-page-title"><?php echo esc_html( slybot_get_account_title() ); ?></h1>

        <?php do_action( 'woocommerce_account_content' ); ?>

    </main>

</div>