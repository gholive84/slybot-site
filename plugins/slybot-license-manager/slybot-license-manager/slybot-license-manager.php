<?php
/*
Plugin Name: SlyBot License Manager
Description: Gerenciamento de licenças e validação MT5 para SlyBot.
Version: 1.0
Author: SlyBot
*/

define('SLYBOT_API_SECRET', 'SlyBot$SecureKey#2026!Xv8@Lm4^Qp7Zt2');

if (!defined('ABSPATH')) exit;

/* =====================================
   CRIAR TABELAS AO ATIVAR PLUGIN
===================================== */

register_activation_hook(__FILE__, 'slybot_create_tables');

function slybot_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $licenses_table = $wpdb->prefix . 'slybot_licenses';
    $accounts_table = $wpdb->prefix . 'slybot_accounts';
    $logs_table = $wpdb->prefix . 'slybot_logs';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql1 = "CREATE TABLE $licenses_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        order_id BIGINT UNSIGNED NOT NULL,
        license_key VARCHAR(64) NOT NULL,
        plan_type VARCHAR(20) NOT NULL,
        expiration_date DATETIME NULL,
        max_accounts INT NOT NULL,
        allow_prop TINYINT(1) DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY license_key (license_key)
    ) $charset_collate;";

    $sql2 = "CREATE TABLE $accounts_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        license_id BIGINT UNSIGNED NOT NULL,
        mt5_login VARCHAR(50) NOT NULL,
        mt5_server VARCHAR(100) NOT NULL,
        broker_name VARCHAR(100) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    
    $sql3 = "CREATE TABLE $logs_table (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id BIGINT UNSIGNED NOT NULL,
    mt5_login VARCHAR(50),
    broker VARCHAR(100),
    ip_address VARCHAR(45),
    result VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) $charset_collate;";

    

    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
}

/* =====================================
   HELPER: VERIFICAR LICENÇA ATIVA
   Retorna o objeto da licença ou false.
   Compartilhado com slybot-course.php —
   use function_exists para evitar conflito.
===================================== */

if (!function_exists('slybot_get_active_license')) {
    function slybot_get_active_license($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) return false;

        $licenses_table = $wpdb->prefix . 'slybot_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $licenses_table
             WHERE user_id = %d
               AND status = 'active'
               AND (expiration_date IS NULL OR expiration_date > NOW())
             ORDER BY id DESC
             LIMIT 1",
            $user_id
        ));

        return $license ?: false;
    }
}


/* =====================================
   CRIAR LICENÇA APÓS PAGAMENTO
===================================== */

add_action('woocommerce_order_status_completed', 'slybot_create_license_after_payment');

function slybot_create_license_after_payment($order_id) {
    global $wpdb;

    $order   = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    $annual_product_id = 274;
    $pro_product_id    = 275;

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        if ($product_id != $annual_product_id && $product_id != $pro_product_id) continue;

        $licenses_table = $wpdb->prefix . 'slybot_licenses';

        // Segurança: bloqueia criação de segundo plano Vitalício
        // mesmo que o bloqueio no checkout tenha falhado
        if ($product_id == $pro_product_id) {
            $already = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $licenses_table
                 WHERE user_id = %d AND plan_type = 'Vitalício'",
                $user_id
            ));

            if ($already > 0) {
                // Cancela o pedido automaticamente e notifica o admin
                $order->update_status('cancelled',
                    'Bloqueado automaticamente: usuário já possui licença Vitalícia.'
                );
                return;
            }
        }

        $license_key = wp_generate_password(32, false);

        if ($product_id == $annual_product_id) {
            $plan_type    = 'Anual';
            $max_accounts = 2;
            $allow_prop   = 0;
            $expiration   = date('Y-m-d H:i:s', strtotime('+1 year'));
        } else {
            $plan_type    = 'Vitalício';
            $max_accounts = 5;
            $allow_prop   = 1;
            $expiration   = null;
        }

        $wpdb->insert($licenses_table, [
            'user_id'         => $user_id,
            'order_id'        => $order_id,
            'license_key'     => $license_key,
            'plan_type'       => $plan_type,
            'expiration_date' => $expiration,
            'max_accounts'    => $max_accounts,
            'allow_prop'      => $allow_prop,
            'status'          => 'active'
        ]);
    }
}



/* =====================================
   API DE VALIDAÇÃO
===================================== */

add_action('rest_api_init', function () {
    register_rest_route('slybot/v1', '/validate', [
        'methods' => 'POST',
        'callback' => 'slybot_validate_license',
        'permission_callback' => '__return_true'
    ]);
});

function slybot_validate_license($request) {
    global $wpdb;

    $login       = sanitize_text_field($request['mt5_login']);
    $server      = sanitize_text_field($request['server']);
    $license_key = sanitize_text_field($request['license_key']);
    $broker      = sanitize_text_field($request['broker']);
    $secret      = sanitize_text_field($request['secret']);

    $licenses_table = $wpdb->prefix . 'slybot_licenses';
    $accounts_table = $wpdb->prefix . 'slybot_accounts';
    $logs_table     = $wpdb->prefix . 'slybot_logs';

    $ip = $_SERVER['REMOTE_ADDR'];

    /* =====================================
       RATE LIMIT (20 requisições por minuto)
    ===================================== */

    $rate_key = 'slybot_rate_' . md5($ip);
    $requests = get_transient($rate_key);

    if ($requests && $requests > 20) {

        $wpdb->insert($logs_table, [
            'license_id' => 0,
            'mt5_login'  => $login,
            'broker'     => $broker,
            'ip_address' => $ip,
            'result'     => 'rate_limited'
        ]);

        return ['valid' => false, 'reason' => 'rate_limited'];
    }

    set_transient($rate_key, $requests ? $requests + 1 : 1, 60);

    /* =====================================
       SECRET
    ===================================== */

    if ($secret !== SLYBOT_API_SECRET) {

        $wpdb->insert($logs_table, [
            'license_id' => 0,
            'mt5_login'  => $login,
            'broker'     => $broker,
            'ip_address' => $ip,
            'result'     => 'unauthorized'
        ]);

        return ['valid' => false, 'reason' => 'unauthorized'];
    }

    /* =====================================
       BUSCAR LICENÇA
    ===================================== */

    $license = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $licenses_table 
         WHERE license_key = %s AND status = 'active'",
        $license_key
    ));

    if (!$license) {

        $wpdb->insert($logs_table, [
            'license_id' => 0,
            'mt5_login'  => $login,
            'broker'     => $broker,
            'ip_address' => $ip,
            'result'     => 'invalid_license'
        ]);

        return ['valid' => false, 'reason' => 'invalid_license'];
    }
    
    
    /* =====================================
   RATE LIMIT POR LICENÇA (60/min)
        ===================================== */
        
        $license_rate_key = 'slybot_license_rate_' . $license->id;
        $license_requests = get_transient($license_rate_key);
        
        if ($license_requests && $license_requests >= 60) {
        
            $wpdb->insert($logs_table, [
                'license_id' => $license->id,
                'mt5_login'  => $login,
                'broker'     => $broker,
                'ip_address' => $ip,
                'result'     => 'license_rate_limited'
            ]);
        
            return ['valid' => false, 'reason' => 'license_rate_limited'];
        }
        
        set_transient(
            $license_rate_key,
            $license_requests ? $license_requests + 1 : 1,
            60
        );
    
    
    

    /* =====================================
       EXPIRAÇÃO
    ===================================== */

    if ($license->expiration_date && strtotime($license->expiration_date) < time()) {

        $wpdb->insert($logs_table, [
            'license_id' => $license->id,
            'mt5_login'  => $login,
            'broker'     => $broker,
            'ip_address' => $ip,
            'result'     => 'expired'
        ]);

        return ['valid' => false, 'reason' => 'expired'];
    }

    /* =====================================
       PROP FIRM
    ===================================== */

    if (!$license->allow_prop) {
        if (
            stripos($broker, 'FTMO') !== false ||
            stripos($broker, 'The5ers') !== false
        ) {

            $wpdb->insert($logs_table, [
                'license_id' => $license->id,
                'mt5_login'  => $login,
                'broker'     => $broker,
                'ip_address' => $ip,
                'result'     => 'prop_not_allowed'
            ]);

            return ['valid' => false, 'reason' => 'prop_not_allowed'];
        }
    }

    /* =====================================
       DUPLICAÇÃO
    ===================================== */

    $already = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $accounts_table 
         WHERE license_id = %d AND mt5_login = %s",
        $license->id,
        $login
    ));

    if ($already > 0) {

        $wpdb->insert($logs_table, [
            'license_id' => $license->id,
            'mt5_login'  => $login,
            'broker'     => $broker,
            'ip_address' => $ip,
            'result'     => 'valid_existing'
        ]);

        return [
            'valid'       => true,
            'plan'        => $license->plan_type,
            'expiration'  => $license->expiration_date,
            'max_accounts'=> $license->max_accounts
        ];
    }

    /* =====================================
       LIMITE DE CONTAS
    ===================================== */

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $accounts_table WHERE license_id = %d",
        $license->id
    ));

    if ($count >= $license->max_accounts) {

        $wpdb->insert($logs_table, [
            'license_id' => $license->id,
            'mt5_login'  => $login,
            'broker'     => $broker,
            'ip_address' => $ip,
            'result'     => 'account_limit'
        ]);

        return ['valid' => false, 'reason' => 'account_limit'];
    }

    /* =====================================
       INSERIR CONTA
    ===================================== */

    $wpdb->insert($accounts_table, [
        'license_id' => $license->id,
        'mt5_login'  => $login,
        'mt5_server' => $server,
        'broker_name'=> $broker
    ]);

    $wpdb->insert($logs_table, [
        'license_id' => $license->id,
        'mt5_login'  => $login,
        'broker'     => $broker,
        'ip_address' => $ip,
        'result'     => 'valid_new'
    ]);

    return [
        'valid'       => true,
        'plan'        => $license->plan_type,
        'expiration'  => $license->expiration_date,
        'max_accounts'=> $license->max_accounts
    ];
}

/* =====================================
   ENDPOINT MINHAS LICENÇAS
===================================== */

add_action('init', function() {
    add_rewrite_endpoint('minhas-licencas', EP_ROOT | EP_PAGES);
});

add_filter('woocommerce_account_menu_items', function($items) {
    $items['minhas-licencas'] = 'Minhas Licenças';
    return $items;
});



add_action('woocommerce_account_minhas-licencas_endpoint', 'slybot_licenses_content');

function slybot_licenses_content() {
    global $wpdb;

    $user_id = get_current_user_id();
    $licenses_table = $wpdb->prefix . 'slybot_licenses';
    $accounts_table = $wpdb->prefix . 'slybot_accounts';



/* =====================================
   DOWNLOADS DOS ROBÔS (ATUAIS DO PRODUTO)
===================================== */

$active_license = slybot_get_active_license($user_id);

if (!$active_license) {

    /* ----- SEM LICENÇA: tela de bloqueio ----- */
    $shop_url = home_url('/#planos');

    echo "
    <div class='slybot-locked-wrap'>

        <div class='slybot-locked-icon'>
            <svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 24 24'
                 fill='none' stroke='currentColor' stroke-width='1.5'
                 stroke-linecap='round' stroke-linejoin='round'>
                <rect x='3' y='11' width='18' height='11' rx='2' ry='2'/>
                <path d='M7 11V7a5 5 0 0 1 10 0v4'/>
            </svg>
        </div>

        <h2>Downloads dos Robôs</h2>

        <p>Os arquivos dos robôs estão disponíveis para todos os titulares de uma licença ativa.</p>
        <p>Adquira sua licença e libere o download imediatamente após a confirmação do pagamento.</p>

        <a href='" . esc_url($shop_url) . "' class='slybot-btn-primary'>
            Ver planos disponíveis
        </a>

    </div>
    ";

} else {

    /* ----- COM LICENÇA: exibe downloads normalmente ----- */
    echo "<div class='slybot-card'>";
    echo "<div class='slybot-header'>";
    echo "<div class='slybot-title'>Downloads dos Robôs</div>";
    echo "</div>";

    $customer_orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed'],
        'limit'       => -1,
    ]);

    $shown_products  = [];
    $download_rows   = '';

    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {

            $product = $item->get_product();
            if (!$product) continue;

            $product_id = $product->get_id();
            if (in_array($product_id, $shown_products)) continue;
            $shown_products[] = $product_id;

            if ($product->is_downloadable()) {
                foreach ($product->get_downloads() as $download) {
                    $download_rows .= "
                    <tr>
                        <td>" . esc_html($download->get_name()) . "</td>
                        <td>
                            <a class='button button-primary' href='" . esc_url($download->get_file()) . "'>
                                Baixar
                            </a>
                        </td>
                    </tr>";
                }
            }
        }
    }

    if ($download_rows) {
        echo "<table class='slybot-table'>";
        echo "<thead><tr><th>Robô</th><th></th></tr></thead>";
        echo "<tbody>{$download_rows}</tbody>";
        echo "</table>";
    } else {
        echo "<p style='color:#6b7280;font-size:14px;'>Nenhum arquivo disponível para download no momento.</p>";
    }

    echo "</div>";

}


    /* =====================================
       REMOVER CONTA
    ===================================== */
    if (isset($_POST['remove_account_id'])) {
        $remove_id = intval($_POST['remove_account_id']);
        $wpdb->delete($accounts_table, ['id' => $remove_id]);

        echo "<div style='background:#eaf7ea;padding:14px;margin-bottom:25px;border-left:4px solid #2ecc71;border-radius:8px;'>
                Conta removida com sucesso.
              </div>";
    }

    $licenses = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $licenses_table WHERE user_id = %d",
        $user_id
    ));

    if (!$licenses) {
        echo "<p>Você ainda não possui licenças ativas.</p>";
        return;
    }

    foreach ($licenses as $license) {

        $accounts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $accounts_table WHERE license_id = %d",
            $license->id
        ));

        $used = count($accounts);
        $max  = $license->max_accounts;
        $percent = $max > 0 ? ($used / $max) * 100 : 0;

        /* =====================================
           DEFINIR BADGE DO PLANO
        ===================================== */
        $badge_plan = $license->plan_type === 'professional'
            ? 'slybot-badge slybot-badge-professional'
            : 'slybot-badge slybot-badge-annual';

        /* =====================================
           DEFINIR STATUS VISUAL
        ===================================== */
        $status_class = 'slybot-status-active';
        $status_label = 'Ativa';

        if ($license->status === 'blocked') {
            $status_class = 'slybot-status-blocked';
            $status_label = 'Bloqueada';
        } elseif ($license->expiration_date && strtotime($license->expiration_date) < time()) {
            $status_class = 'slybot-status-expired';
            $status_label = 'Expirada';
        }

        echo "<div class='slybot-card'>";

        /* =====================================
           HEADER
        ===================================== */
        echo "<div class='slybot-header'>";
        echo "<div class='slybot-title'>Sua Licença</div>";
        echo "<div style='display:flex;gap:10px;align-items:center;'>";
        echo "<span class='{$badge_plan}'>" . ucfirst($license->plan_type) . "</span>";
        echo "<span class='slybot-status {$status_class}'>{$status_label}</span>";
        echo "</div>";
        echo "</div>";

        /* =====================================
           CHAVE
        ===================================== */
        echo "<div class='slybot-key-wrapper'>";
        echo "<input type='text' value='{$license->license_key}' readonly>";
        echo "<button class='button button-primary'
                onclick=\"navigator.clipboard.writeText('{$license->license_key}')\">
                Copiar
              </button>";
        echo "</div>";

        /* =====================================
           VALIDADE
        ===================================== */
        if ($license->expiration_date) {
            echo "<p><strong>Expira em:</strong> {$license->expiration_date}</p>";
        } else {
            echo "<p><strong>Validade:</strong> Vitalício</p>";
        }

        /* =====================================
           USO DE CONTAS
        ===================================== */
        echo "<p><strong>Uso de contas:</strong> {$used} / {$max}</p>";
        echo "<div class='slybot-progress'>
                <div class='slybot-progress-bar' style='width:{$percent}%'></div>
              </div>";

        /* =====================================
           TABELA DE CONTAS
        ===================================== */
        echo "<table class='slybot-table'>";
        echo "<thead>
                <tr>
                    <th>Login</th>
                    <th>Servidor</th>
                    <th></th>
                </tr>
              </thead><tbody>";

        if ($accounts) {
            foreach ($accounts as $account) {
                echo "<tr>
                        <td>{$account->mt5_login}</td>
                        <td>{$account->mt5_server}</td>
                        <td>
                            <form method='post'>
                                <input type='hidden' name='remove_account_id' value='{$account->id}'>
                                <button class='slybot-remove-btn'>Remover</button>
                            </form>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Nenhuma conta vinculada ainda.</td></tr>";
        }

        echo "</tbody></table>";

        /* =====================================
           INSTRUÇÕES
        ===================================== */
        echo "<div class='slybot-info-box'>
                <strong>Como ativar ou trocar de conta:</strong><br><br>
                1. Instale o robô no MT5.<br>
                2. Insira sua chave de licença.<br>
                3. A validação acontece automaticamente.<br><br>
                Para trocar de conta:<br>
                • Remova a conta atual.<br>
                • Abra o robô na nova conta.<br>
                • A nova conta será vinculada automaticamente.
              </div>";

        echo "</div>";
    }
}

/* =====================================
   MENU ADMIN SLYBOT
===================================== */

add_action('admin_menu', 'slybot_register_admin_menu');

function slybot_register_admin_menu() {
    add_menu_page(
        'SlyBot Licenças',
        'SlyBot',
        'manage_options',
        'slybot-licenses',
        'slybot_admin_page',
        'dashicons-shield',
        26
    );
}


function slybot_admin_page() {
    global $wpdb;

    $licenses_table = $wpdb->prefix . 'slybot_licenses';
    $accounts_table = $wpdb->prefix . 'slybot_accounts';

    echo "<div class='wrap'><h1>SlyBot Licenças</h1>";

    /* =====================================
       BUSCA
    ===================================== */

    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    echo "<form method='get' style='margin-bottom:20px;'>";
    echo "<input type='hidden' name='page' value='slybot-licenses'>";
    echo "<input type='text' name='s' placeholder='Buscar por nome, email ou chave...' value='{$search}' style='width:300px;padding:5px;'>";
    echo "<button class='button'>Buscar</button>";
    echo "</form>";
    
    
    
    
    /* =====================================
   EXCLUIR LICENÇA COMPLETA
    ===================================== */
    if (isset($_POST['delete_license'])) {

    if (
        !isset($_POST['slybot_delete_license_nonce']) ||
        !wp_verify_nonce($_POST['slybot_delete_license_nonce'], 'slybot_delete_license_action')
    ) {
        wp_die('Falha de segurança.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Permissão negada.');
    }

    $license_id = intval($_POST['license_id']);

    // Apagar contas
    $wpdb->delete($accounts_table, ['license_id' => $license_id]);

    // Apagar logs
    $logs_table = $wpdb->prefix . 'slybot_logs';
    $wpdb->delete($logs_table, ['license_id' => $license_id]);

    // Apagar licença
    $wpdb->delete($licenses_table, ['id' => $license_id]);

    echo "<div class='updated'><p>Licença excluída permanentemente.</p></div>";
}
    
    
    

    /* =====================================
       AÇÕES ADMIN
    ===================================== */

    if (isset($_POST['admin_remove_account'])) {
        $remove_id = intval($_POST['admin_remove_account']);
        $wpdb->delete($accounts_table, ['id' => $remove_id]);
        echo "<div class='updated'><p>Conta removida.</p></div>";
    }

    if (isset($_POST['admin_reset_accounts'])) {
        $license_id = intval($_POST['license_id']);
        $wpdb->delete($accounts_table, ['license_id' => $license_id]);
        echo "<div class='updated'><p>Todas as contas foram resetadas.</p></div>";
    }

    if (isset($_POST['update_license'])) {
        $license_id = intval($_POST['license_id']);
        $status = sanitize_text_field($_POST['status']);
        $max_accounts = intval($_POST['max_accounts']);
        $allow_prop = intval($_POST['allow_prop']);
        $expiration = sanitize_text_field($_POST['expiration_date']);

        $wpdb->update($licenses_table, [
            'status' => $status,
            'max_accounts' => $max_accounts,
            'allow_prop' => $allow_prop,
            'expiration_date' => $expiration ?: null
        ], ['id' => $license_id]);

        echo "<div class='updated'><p>Licença atualizada.</p></div>";
    }

    /* =====================================
       QUERY LICENÇAS
    ===================================== */

    if ($search) {

        $licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*
             FROM {$licenses_table} l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE u.display_name LIKE %s
                OR u.user_email LIKE %s
                OR l.license_key LIKE %s
             ORDER BY l.created_at DESC",
            "%$search%", "%$search%", "%$search%"
        ));

    } else {

        $licenses = $wpdb->get_results(
            "SELECT * FROM {$licenses_table} ORDER BY created_at DESC"
        );
    }

    if (!$licenses) {
        echo "<p>Nenhuma licença encontrada.</p>";
        echo "</div>";
        return;
    }

    /* =====================================
       LISTAGEM
    ===================================== */

    foreach ($licenses as $license) {

        $user = get_userdata($license->user_id);
        $user_name = $user ? esc_html($user->display_name) : 'Usuário não encontrado';
        $user_email = $user ? esc_html($user->user_email) : '-';
        $user_edit_link = $user ? admin_url('user-edit.php?user_id=' . $license->user_id) : '#';

        echo "<div style='background:#fff;margin-bottom:15px;border:1px solid #ddd;border-radius:8px;'>";

        // HEADER (Clicável)
        echo "<div style='padding:15px;cursor:pointer;background:#f7f7f7;'
                onclick=\"this.nextElementSibling.style.display = (this.nextElementSibling.style.display === 'none') ? 'block' : 'none';\">
                <strong>{$user_name}</strong><br>
                <span style='color:#666;font-size:13px;'>{$user_email}</span>
              </div>";

        // CONTEÚDO EXPANDIDO
        echo "<div style='display:none;padding:20px;'>";

        echo "<p><strong>Licença ID:</strong> {$license->id}</p>";
        echo "<p><strong>Plano:</strong> " . ucfirst($license->plan_type) . "</p>";
        echo "<p><strong>Status:</strong> {$license->status}</p>";
        echo "<p><strong>Chave:</strong> {$license->license_key}</p>";

        if ($license->expiration_date) {
            echo "<p><strong>Expira em:</strong> {$license->expiration_date}</p>";
        } else {
            echo "<p><strong>Validade:</strong> Vitalício</p>";
        }

        echo "<hr>";

        // FORM EDITAR
        echo "<form method='post'>";
        echo "<input type='hidden' name='license_id' value='{$license->id}'>";

        echo "<p>Status:
            <select name='status'>
                <option value='active' ".selected($license->status,'active',false).">Ativa</option>
                <option value='blocked' ".selected($license->status,'blocked',false).">Bloqueada</option>
            </select>
        </p>";

        echo "<p>Máx Contas:
            <input type='number' name='max_accounts' value='{$license->max_accounts}' min='1'>
        </p>";

        echo "<p>Permitir Prop Firm:
            <select name='allow_prop'>
                <option value='1' ".selected($license->allow_prop,1,false).">Sim</option>
                <option value='0' ".selected($license->allow_prop,0,false).">Não</option>
            </select>
        </p>";

        echo "<p>Expiração:
            <input type='datetime-local' name='expiration_date' value='".($license->expiration_date ? date('Y-m-d\TH:i', strtotime($license->expiration_date)) : '')."'>
        </p>";

        echo "<p><button type='submit' name='update_license' class='button button-primary'>Salvar</button></p>";
        echo "</form>";

        echo "<hr>";
        
       

        // CONTAS
        $accounts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $accounts_table WHERE license_id = %d",
            $license->id
        ));

        echo "<h4>Contas Vinculadas</h4>";

        if ($accounts) {
            echo "<ul>";
            foreach ($accounts as $account) {
                echo "<li>
                        {$account->mt5_login} - {$account->mt5_server}
                        <form method='post' style='display:inline;margin-left:10px;'>
                            <input type='hidden' name='admin_remove_account' value='{$account->id}'>
                            <button class='button'>Remover</button>
                        </form>
                      </li>";
            }
            echo "</ul>";

            echo "<form method='post'>
                    <input type='hidden' name='license_id' value='{$license->id}'>
                    <button name='admin_reset_accounts' class='button'>Resetar Todas Contas</button>
                  </form>";
        } else {
            echo "<p>Nenhuma conta vinculada.</p>";
        }

        echo "<p style='margin-top:15px;'>
                <a href='{$user_edit_link}' class='button'>Editar Usuário</a>
              </p>";
              
               echo "<form method='post' style='margin-top:15px;' 
        onsubmit=\"return confirm('Tem certeza que deseja excluir esta licença? Essa ação é permanente e removerá contas e logs.');\">
        <input type='hidden' name='license_id' value='{$license->id}'>
        " . wp_nonce_field('slybot_delete_license_action', 'slybot_delete_license_nonce', true, false) . "
        <button name='delete_license' 
                class='button' 
                style='background:#c0392b;color:#fff;border:none;'>
            Excluir Licença
        </button>
      </form>";

        echo "</div>"; // fim conteúdo expandido
        echo "</div>"; // fim card
    }

    echo "</div>";
}


add_action('admin_menu', 'slybot_register_logs_submenu');

function slybot_register_logs_submenu() {
    add_submenu_page(
        'slybot-licenses',
        'Logs de Validação',
        'Logs',
        'manage_options',
        'slybot-logs',
        'slybot_logs_page'
    );
}



function slybot_logs_page() {
    global $wpdb;

    $logs_table = $wpdb->prefix . 'slybot_logs';
    $licenses_table = $wpdb->prefix . 'slybot_licenses';

    echo "<div class='wrap'><h1>Logs de Validação</h1>";

    // Filtros
    $search_license = isset($_GET['license_id']) ? intval($_GET['license_id']) : '';
    $search_result  = isset($_GET['result']) ? sanitize_text_field($_GET['result']) : '';

    echo "<form method='get' style='margin-bottom:20px;'>";
    echo "<input type='hidden' name='page' value='slybot-logs'>";
    echo "License ID: <input type='number' name='license_id' value='{$search_license}' style='width:80px;'> ";
    echo "Resultado: 
            <select name='result'>
                <option value=''>Todos</option>
                <option value='valid'>valid</option>
                <option value='valid_existing'>valid_existing</option>
                <option value='invalid_license'>invalid_license</option>
                <option value='expired'>expired</option>
                <option value='prop_not_allowed'>prop_not_allowed</option>
                <option value='account_limit'>account_limit</option>
                <option value='unauthorized'>unauthorized</option>
                <option value='rate_limited'>rate_limited</option>
            </select>
          ";
    echo "<button class='button'>Filtrar</button>";
    echo "</form>";

    // Query dinâmica
    $where = "WHERE 1=1";

    if ($search_license) {
        $where .= $wpdb->prepare(" AND license_id = %d", $search_license);
    }

    if ($search_result) {
        $where .= $wpdb->prepare(" AND result = %s", $search_result);
    }

    $logs = $wpdb->get_results("
        SELECT * FROM {$logs_table}
        {$where}
        ORDER BY created_at DESC
        LIMIT 200
    ");

    if (!$logs) {
        echo "<p>Nenhum log encontrado.</p>";
        echo "</div>";
        return;
    }

    echo "<table class='widefat striped'>";
    echo "<thead>
            <tr>
                <th>ID</th>
                <th>License</th>
                <th>Login</th>
                <th>Broker</th>
                <th>IP</th>
                <th>Resultado</th>
                <th>Data</th>
            </tr>
          </thead>";
    echo "<tbody>";

    foreach ($logs as $log) {
        echo "<tr>
                <td>{$log->id}</td>
                <td>{$log->license_id}</td>
                <td>{$log->mt5_login}</td>
                <td>{$log->broker}</td>
                <td>{$log->ip_address}</td>
                <td>{$log->result}</td>
                <td>{$log->created_at}</td>
              </tr>";
    }

    echo "</tbody></table>";
    echo "</div>";
}


/* =====================================
   AGENDAR LIMPEZA DE LOGS
===================================== */

register_activation_hook(__FILE__, 'slybot_schedule_log_cleanup');
register_deactivation_hook(__FILE__, 'slybot_clear_log_cleanup');

function slybot_schedule_log_cleanup() {
    if (!wp_next_scheduled('slybot_cleanup_logs_event')) {
        wp_schedule_event(time(), 'daily', 'slybot_cleanup_logs_event');
    }
}

function slybot_clear_log_cleanup() {
    wp_clear_scheduled_hook('slybot_cleanup_logs_event');
}


/* =====================================
   FUNÇÃO DE LIMPEZA
===================================== */

add_action('slybot_cleanup_logs_event', 'slybot_cleanup_old_logs');

function slybot_cleanup_old_logs() {
    global $wpdb;

    $logs_table = $wpdb->prefix . 'slybot_logs';

    // 🔧 Defina aqui quantos dias manter
    $days_to_keep = 30;

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $logs_table 
             WHERE created_at < NOW() - INTERVAL %d DAY",
            $days_to_keep
        )
    );
}




add_action('delete_user', 'slybot_cleanup_user_licenses');

function slybot_cleanup_user_licenses($user_id) {
    global $wpdb;

    $licenses_table = $wpdb->prefix . 'slybot_licenses';
    $accounts_table = $wpdb->prefix . 'slybot_accounts';
    $logs_table     = $wpdb->prefix . 'slybot_logs';

    $licenses = $wpdb->get_results($wpdb->prepare(
        "SELECT id FROM $licenses_table WHERE user_id = %d",
        $user_id
    ));

    if ($licenses) {
        foreach ($licenses as $license) {

            $wpdb->delete($accounts_table, ['license_id' => $license->id]);
            $wpdb->delete($logs_table, ['license_id' => $license->id]);
            $wpdb->delete($licenses_table, ['id' => $license->id]);
        }
    }
}



add_action('wp_head', function() {
    if (is_account_page()) {
        echo "
        <style>

        .slybot-card {
            background: linear-gradient(145deg,#ffffff,#f9fafc);
            border-radius:18px;
            padding:40px;
            margin-bottom:30px;
            max-width:860px;
            box-shadow:0 15px 35px rgba(0,0,0,0.06);
            border:1px solid rgba(0,0,0,0.05);
            transition:all .3s ease;
        }

        .slybot-card:hover {
            transform:translateY(-3px);
            box-shadow:0 20px 45px rgba(0,0,0,0.08);
        }

        .slybot-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:25px;
        }

        .slybot-title {
            font-size:22px;
            font-weight:700;
            letter-spacing:0.3px;
        }

        .slybot-badge {
            padding:8px 18px;
            border-radius:30px;
            font-size:11px;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:1px;
        }

        .slybot-badge-annual {
            background:rgba(231,76,60,0.1);
            color:#e74c3c;
        }

        .slybot-badge-professional {
            background:rgba(46,204,113,0.12);
            color:#27ae60;
        }

        .slybot-key-wrapper {
            display:flex;
            gap:12px;
            margin-bottom:25px;
        }

        .slybot-key-wrapper input {
            flex:1;
            padding:14px;
            border-radius:12px;
            border:1px solid #ddd;
            font-size:14px;
            background:#f8f9fb;
        }

        .slybot-progress {
            background:#eef1f5;
            border-radius:50px;
            height:14px;
            overflow:hidden;
            margin-top:10px;
        }

        .slybot-progress-bar {
            height:100%;
            border-radius:50px;
            background:linear-gradient(90deg,#2c3e50,#34495e);
            transition:width .4s ease;
        }

        .slybot-table {
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        .slybot-table th {
            text-align:left;
            padding:14px;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:0.5px;
            color:#888;
        }

        .slybot-table td {
            padding:16px 14px;
            border-top:1px solid #f1f1f1;
        }

        .slybot-remove-btn {
            background:#f8d7da;
            color:#c0392b;
            border:none;
            padding:8px 14px;
            border-radius:8px;
            cursor:pointer;
            transition:all .2s ease;
        }

        .slybot-remove-btn:hover {
            background:#e74c3c;
            color:#fff;
        }

        .slybot-info-box {
            background:#f5f7fa;
            padding:22px;
            border-radius:14px;
            border:1px solid #eee;
            font-size:14px;
            margin-top:35px;
        }
        
        
        .slybot-status {
            padding:6px 14px;
            border-radius:20px;
            font-size:11px;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:1px;
        }
        
        .slybot-status-active {
            background:rgba(46,204,113,0.12);
            color:#27ae60;
        }
        
        .slybot-status-blocked {
            background:rgba(231,76,60,0.12);
            color:#e74c3c;
        }
        
        .slybot-status-expired {
            background:rgba(243,156,18,0.12);
            color:#f39c12;
        }

        /* ---- tela de bloqueio (downloads e curso) ---- */
        .slybot-locked-wrap {
            text-align:left;
            padding:40px;
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:16px;
            max-width:860px;
            margin:0 0 30px 0;
        }

        .slybot-locked-icon {
            color:#ff6a00;
            margin-bottom:20px;
        }

        .slybot-locked-wrap h2 {
            font-size:22px;
            margin-bottom:12px;
        }

        .slybot-locked-wrap p {
            color:#6b7280;
            font-size:15px;
            line-height:1.6;
            margin-bottom:10px;
        }

        .slybot-btn-primary {
            display:inline-block;
            margin-top:20px;
            padding:14px 32px;
            background:#ff6a00;
            color:#fff !important;
            border-radius:10px;
            font-weight:700;
            font-size:15px;
            text-decoration:none;
            transition:background .2s;
        }

        .slybot-btn-primary:hover {
            background:#e55e00;
        }

        </style>
        ";
    }
});