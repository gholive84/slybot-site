<?php
/*
Plugin Name: Slybot Strategies
Description: Painel de estratégias com backtest, filtro por ativo e robô.
Version: 3.0
Author: Slybot
*/

if (!defined('ABSPATH')) exit;


/* =====================================
   REGISTRAR CPT
===================================== */

add_action('init', function() {
    register_post_type('slybot_strategy', [
        'label'     => 'Estratégias',
        'labels'    => [
            'name'          => 'Estratégias',
            'singular_name' => 'Estratégia',
            'add_new'       => 'Nova Estratégia',
            'add_new_item'  => 'Adicionar Estratégia',
            'edit_item'     => 'Editar Estratégia',
        ],
        'public'       => false,
        'show_ui'      => true,
        'menu_icon'    => 'dashicons-chart-line',
        'supports'     => ['title'],
        'show_in_menu' => true,
    ]);
});


/* =====================================
   TAXONOMIA: ATIVO
===================================== */

add_action('init', function() {
    register_taxonomy('slybot_asset', 'slybot_strategy', [
        'label'             => 'Ativos',
        'hierarchical'      => false,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => false,
    ]);
});


/* =====================================
   TAXONOMIA: ROBÔ
===================================== */

add_action('init', function() {
    register_taxonomy('slybot_robot', 'slybot_strategy', [
        'label'             => 'Robôs',
        'labels'            => [
            'name'          => 'Robôs',
            'singular_name' => 'Robô',
            'add_new_item'  => 'Adicionar Robô',
            'edit_item'     => 'Editar Robô',
        ],
        'hierarchical'      => false,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => false,
    ]);
});


/* =====================================
   ENDPOINT MINHA CONTA
===================================== */

add_action('init', function() {
    add_rewrite_endpoint('estrategias-slybot', EP_ROOT | EP_PAGES);
});

register_activation_hook(__FILE__, function() {
    add_rewrite_endpoint('estrategias-slybot', EP_ROOT | EP_PAGES);
    flush_rewrite_rules();
});


/* =====================================
   MENU MINHA CONTA
===================================== */

add_filter('woocommerce_account_menu_items', 'slybot_strategies_menu_item', 50);

function slybot_strategies_menu_item($items) {
    $new_items = [];
    foreach ($items as $key => $label) {
        $new_items[$key] = $label;
        if ($key === 'curso-slybot') {
            $new_items['estrategias-slybot'] = 'Estratégias';
        }
    }
    if (!isset($new_items['estrategias-slybot'])) {
        $fallback = [];
        foreach ($new_items as $key => $label) {
            if ($key === 'edit-account') $fallback['estrategias-slybot'] = 'Estratégias';
            $fallback[$key] = $label;
        }
        return $fallback;
    }
    return $new_items;
}


/* =====================================
   META BOX — CAMPOS ADMIN
===================================== */

add_action('add_meta_boxes', function() {
    add_meta_box(
        'slybot_strategy_fields',
        'Dados da Estratégia',
        'slybot_strategy_meta_box',
        'slybot_strategy',
        'normal',
        'high'
    );
});

function slybot_strategy_meta_box($post) {

    wp_nonce_field('slybot_strategy_save', 'slybot_strategy_nonce');

    $f = [];
    $meta_keys = [
        'strategy_type', 'date_start', 'date_end', 'total_trades', 'win_count',
        'profit_factor', 'positive_months', 'base_lot_futures', 'base_lot_stocks',
        'max_drawdown_pct', 'profit_per_trade_base', 'total_profit_base',
        'equity_curve_id', 'strategy_file_id', 'admin_notes'
    ];
    foreach ($meta_keys as $k) {
        $f[$k] = get_post_meta($post->ID, $k, true);
    }

    $total    = intval($f['total_trades']);
    $wins     = intval($f['win_count']);
    $losses   = $total > 0 ? $total - $wins : 0;
    $win_rate = $total > 0 ? round(($wins / $total) * 100, 1) : 0;

    $equity_url = $f['equity_curve_id'] ? wp_get_attachment_image_url($f['equity_curve_id'], 'medium') : '';
    $file_url   = $f['strategy_file_id'] ? wp_get_attachment_url($f['strategy_file_id']) : '';
    $file_name  = $file_url ? basename($file_url) : '';

    ?>
    <style>
    .sm-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; margin-top:12px; }
    .sm-full { grid-column:1/-1; }
    .sm-field { display:flex; flex-direction:column; gap:5px; }
    .sm-field label { font-weight:600; font-size:12px; color:#374151; text-transform:uppercase; letter-spacing:.4px; }
    .sm-field input, .sm-field textarea, .sm-field select {
        padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; width:100%;
    }
    .sm-field textarea { resize:vertical; min-height:80px; }
    .sm-hint { font-size:11px; color:#9ca3af; margin-top:2px; }
    .sm-computed { background:#f9fafb; border-radius:8px; padding:12px 16px; font-size:13px; color:#374151; }
    .sm-computed span { font-weight:700; color:#ff6a00; }
    .sm-section { grid-column:1/-1; font-size:11px; font-weight:700; text-transform:uppercase;
                  letter-spacing:.6px; color:#9ca3af; padding-top:8px; border-top:1px solid #f3f4f6; margin-top:4px; }
    .sm-preview img { max-width:180px; border-radius:8px; margin-top:6px; display:block; }
    </style>

    <div class="sm-grid">

        <!-- IDENTIFICAÇÃO -->
        <div class="sm-section">Identificação</div>

        <div class="sm-field">
            <label>Tipo de Estratégia</label>
            <input type="text" name="strategy_type" value="<?php echo esc_attr($f['strategy_type']); ?>"
                   placeholder="Ex: Momentum, Mean Reversion...">
        </div>

        <div class="sm-field">
            <label>Início do Backtest</label>
            <input type="date" name="date_start" value="<?php echo esc_attr($f['date_start']); ?>">
        </div>

        <div class="sm-field">
            <label>Fim do Backtest</label>
            <input type="date" name="date_end" value="<?php echo esc_attr($f['date_end']); ?>">
        </div>

        <!-- TRADES -->
        <div class="sm-section">Trades</div>

        <div class="sm-field">
            <label>Total de Trades</label>
            <input type="number" name="total_trades" min="0" value="<?php echo esc_attr($f['total_trades']); ?>">
        </div>

        <div class="sm-field">
            <label>Quantidade de Gains</label>
            <input type="number" name="win_count" min="0" value="<?php echo esc_attr($f['win_count']); ?>">
        </div>

        <div class="sm-field">
            <label>Meses Positivos</label>
            <input type="text" name="positive_months" value="<?php echo esc_attr($f['positive_months']); ?>"
                   placeholder="Ex: 10 de 12">
        </div>

        <div class="sm-field sm-full">
            <div class="sm-computed">
                Taxa de acerto: <span><?php echo $win_rate; ?>%</span> &nbsp;|&nbsp;
                Gains: <span><?php echo $wins; ?></span> &nbsp;|&nbsp;
                Loss: <span><?php echo $losses; ?></span>
                <p class="sm-hint">Calculado ao salvar.</p>
            </div>
        </div>

        <!-- FINANCEIRO BASE -->
        <div class="sm-section">Financeiro — Base de cálculo (R$10.000)</div>

        <div class="sm-field">
            <label>Lote Base — Futuros</label>
            <input type="number" name="base_lot_futures" step="1" min="0"
                   value="<?php echo esc_attr($f['base_lot_futures']); ?>"
                   placeholder="Ex: 1">
            <span class="sm-hint">Preencha se for WIN, WDO, etc. Múltiplos de 1.</span>
        </div>

        <div class="sm-field">
            <label>Lote Base — Ações</label>
            <input type="number" name="base_lot_stocks" step="100" min="0"
                   value="<?php echo esc_attr($f['base_lot_stocks']); ?>"
                   placeholder="Ex: 100">
            <span class="sm-hint">Preencha se for ações. Múltiplos de 100.</span>
        </div>

        <div class="sm-field">
            <label>Máx. Drawdown (%)</label>
            <input type="number" name="max_drawdown_pct" step="0.01" min="0"
                   value="<?php echo esc_attr($f['max_drawdown_pct']); ?>"
                   placeholder="Ex: 15.5">
        </div>

        <div class="sm-field">
            <label>Fator de Lucro</label>
            <input type="number" name="profit_factor" step="0.01" min="0"
                   value="<?php echo esc_attr($f['profit_factor']); ?>"
                   placeholder="Ex: 1.85">
        </div>

        <div class="sm-field">
            <label>Lucro por Trade R$ (lote base)</label>
            <input type="number" name="profit_per_trade_base" step="0.01"
                   value="<?php echo esc_attr($f['profit_per_trade_base']); ?>"
                   placeholder="Ex: 45.00">
        </div>

        <div class="sm-field">
            <label>Lucro Total R$ (lote base)</label>
            <input type="number" name="total_profit_base" step="0.01"
                   value="<?php echo esc_attr($f['total_profit_base']); ?>"
                   placeholder="Ex: 6000.00">
        </div>

        <!-- MÍDIA -->
        <div class="sm-section">Mídia e Arquivo</div>

        <div class="sm-field sm-full">
            <label>Curva de Capital (imagem)</label>
            <div class="sm-preview">
                <?php if ($equity_url): ?>
                    <img src="<?php echo esc_url($equity_url); ?>" id="equity-preview">
                <?php else: ?>
                    <img id="equity-preview" style="display:none">
                <?php endif; ?>
            </div>
            <input type="hidden" name="equity_curve_id" id="equity_curve_id"
                   value="<?php echo esc_attr($f['equity_curve_id']); ?>">
            <button type="button" class="button" id="equity-upload-btn" style="margin-top:6px;width:fit-content">
                <?php echo $equity_url ? 'Trocar imagem' : 'Selecionar imagem'; ?>
            </button>
        </div>

        <div class="sm-field sm-full">
            <label>Arquivo da Estratégia</label>
            <span id="strategy-file-name" style="font-size:13px;color:#6b7280;">
                <?php echo $file_name ? esc_html($file_name) : 'Nenhum arquivo selecionado'; ?>
            </span>
            <input type="hidden" name="strategy_file_id" id="strategy_file_id"
                   value="<?php echo esc_attr($f['strategy_file_id']); ?>">
            <button type="button" class="button" id="strategy-file-btn" style="margin-top:6px;width:fit-content">
                <?php echo $file_name ? 'Trocar arquivo' : 'Selecionar arquivo'; ?>
            </button>
        </div>

        <!-- COMENTÁRIOS -->
        <div class="sm-section">Comentários</div>

        <div class="sm-field sm-full">
            <label>Comentários (visíveis para o aluno)</label>
            <textarea name="admin_notes"
                      placeholder="Sua análise, dicas de uso, observações..."
            ><?php echo esc_textarea($f['admin_notes']); ?></textarea>
        </div>

    </div>

    <script>
    jQuery(document).ready(function($) {
        var equityFrame, fileFrame;

        $('#equity-upload-btn').on('click', function(e) {
            e.preventDefault();
            if (!equityFrame) {
                equityFrame = wp.media({ title: 'Curva de Capital', multiple: false });
                equityFrame.on('select', function() {
                    var a = equityFrame.state().get('selection').first().toJSON();
                    $('#equity_curve_id').val(a.id);
                    $('#equity-preview').attr('src', a.url).show();
                    $('#equity-upload-btn').text('Trocar imagem');
                });
            }
            equityFrame.open();
        });

        $('#strategy-file-btn').on('click', function(e) {
            e.preventDefault();
            if (!fileFrame) {
                fileFrame = wp.media({ title: 'Arquivo da Estratégia', multiple: false });
                fileFrame.on('select', function() {
                    var a = fileFrame.state().get('selection').first().toJSON();
                    $('#strategy_file_id').val(a.id);
                    $('#strategy-file-name').text(a.filename);
                    $('#strategy-file-btn').text('Trocar arquivo');
                });
            }
            fileFrame.open();
        });
    });
    </script>
    <?php
}


/* =====================================
   SALVAR META FIELDS
===================================== */

add_action('save_post_slybot_strategy', function($post_id) {

    if (!isset($_POST['slybot_strategy_nonce'])) return;
    if (!wp_verify_nonce($_POST['slybot_strategy_nonce'], 'slybot_strategy_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $text_fields = ['strategy_type', 'date_start', 'date_end', 'positive_months', 'admin_notes'];
    foreach ($text_fields as $k) {
        if (isset($_POST[$k])) {
            update_post_meta($post_id, $k,
                $k === 'admin_notes' ? sanitize_textarea_field($_POST[$k]) : sanitize_text_field($_POST[$k])
            );
        }
    }

    $numeric_fields = [
        'total_trades', 'win_count', 'profit_factor',
        'base_lot_futures', 'base_lot_stocks',
        'max_drawdown_pct', 'profit_per_trade_base',
        'total_profit_base', 'equity_curve_id', 'strategy_file_id'
    ];
    foreach ($numeric_fields as $k) {
        if (isset($_POST[$k])) {
            update_post_meta($post_id, $k, floatval($_POST[$k]));
        }
    }

    $total = intval($_POST['total_trades'] ?? 0);
    $wins  = intval($_POST['win_count'] ?? 0);
    update_post_meta($post_id, 'win_rate',   $total > 0 ? round(($wins / $total) * 100, 1) : 0);
    update_post_meta($post_id, 'loss_count', $total - $wins);
});


/* =====================================
   ENQUEUE MEDIA NO ADMIN
===================================== */

add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'])) return;
    global $post;
    if (!$post || $post->post_type !== 'slybot_strategy') return;
    wp_enqueue_media();
});


/* =====================================
   CONTEÚDO DO PAINEL
===================================== */

add_action('woocommerce_account_estrategias-slybot_endpoint', 'slybot_strategies_content');

function slybot_strategies_content() {

    /* --- GATE --- */
    if (!function_exists('slybot_get_active_license') || !slybot_get_active_license()) {
        $shop_url = home_url('/#planos');
        echo "
        <div class='slybot-locked-wrap'>
            <div class='slybot-locked-icon'>
                <svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 24 24'
                     fill='none' stroke='currentColor' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'>
                    <rect x='3' y='11' width='18' height='11' rx='2' ry='2'/>
                    <path d='M7 11V7a5 5 0 0 1 10 0v4'/>
                </svg>
            </div>
            <h2>Estratégias Slybot</h2>
            <p>As sugestões de estratégia estão disponíveis para titulares de licença ativa.</p>
            <a href='" . esc_url($shop_url) . "' class='slybot-btn-primary'>Ver planos disponíveis</a>
        </div>";
        return;
    }

    /* --- PARÂMETROS DE FILTRO --- */
    $selected_asset = isset($_GET['ativo']) ? sanitize_text_field($_GET['ativo']) : '';
    $selected_robot = isset($_GET['robo'])  ? sanitize_text_field($_GET['robo'])  : '';
    $sort_by        = isset($_GET['sort'])  ? sanitize_text_field($_GET['sort'])  : 'win_rate';
    $base_url       = wc_get_account_endpoint_url('estrategias-slybot');

    $assets = get_terms(['taxonomy' => 'slybot_asset', 'orderby' => 'name', 'hide_empty' => true]);
    $robots = get_terms(['taxonomy' => 'slybot_robot', 'orderby' => 'name', 'hide_empty' => true]);

    /* --- AVISO DE CAPITAL MÍNIMO --- */
    echo "
    <div class='slybot-capital-notice'>
        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'
             fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
            <circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/>
        </svg>
        Estratégias calibradas para <strong>capital mínimo de R$&nbsp;10.000</strong>. Os resultados exibidos são referentes ao lote padrão para esse capital.
    </div>";

    /* --- FILTROS --- */
    echo "<div class='slybot-filters-wrap'>";

    // Filtro por Ativo
    echo "<div class='slybot-filter-group'>";
    echo "<span class='slybot-filter-label'>Ativo</span>";
    echo "<div class='slybot-filter-pills'>";
    $active_all = (!$selected_asset) ? 'active' : '';
    $url_all = add_query_arg(['sort' => $sort_by, 'robo' => $selected_robot], $base_url);
    echo "<a href='" . esc_url($url_all) . "' class='slybot-filter-btn {$active_all}'>Todos</a>";
    if (!empty($assets) && !is_wp_error($assets)) {
        foreach ($assets as $asset) {
            $active = ($selected_asset === $asset->slug) ? 'active' : '';
            $url    = add_query_arg(['ativo' => $asset->slug, 'sort' => $sort_by, 'robo' => $selected_robot], $base_url);
            echo "<a href='" . esc_url($url) . "' class='slybot-filter-btn {$active}'>" . esc_html($asset->name) . "</a>";
        }
    }
    echo "</div></div>";

    // Filtro por Robô
    if (!empty($robots) && !is_wp_error($robots)) {
        echo "<div class='slybot-filter-group'>";
        echo "<span class='slybot-filter-label'>Robô</span>";
        echo "<div class='slybot-filter-pills'>";
        $active_all_r = (!$selected_robot) ? 'active' : '';
        $url_all_r = add_query_arg(['sort' => $sort_by, 'ativo' => $selected_asset], $base_url);
        echo "<a href='" . esc_url($url_all_r) . "' class='slybot-filter-btn {$active_all_r}'>Todos</a>";
        foreach ($robots as $robot) {
            $active = ($selected_robot === $robot->slug) ? 'active' : '';
            $url    = add_query_arg(['robo' => $robot->slug, 'sort' => $sort_by, 'ativo' => $selected_asset], $base_url);
            echo "<a href='" . esc_url($url) . "' class='slybot-filter-btn {$active}'>" . esc_html($robot->name) . "</a>";
        }
        echo "</div></div>";
    }

    echo "</div>"; // .slybot-filters-wrap

    /* --- ORDENAÇÃO --- */
    $sort_options = [
        'win_rate'              => '% Acerto',
        'profit_per_trade_base' => 'Lucro/Trade',
        'total_profit_base'     => 'Lucro Total',
        'max_drawdown_pct'      => 'Drawdown',
    ];

    echo "<div class='slybot-sort-bar'>";
    echo "<span class='slybot-sort-label'>Ordenar por:</span>";
    foreach ($sort_options as $key => $label) {
        $active = ($sort_by === $key) ? 'active' : '';
        $url    = add_query_arg(['sort' => $key, 'ativo' => $selected_asset, 'robo' => $selected_robot], $base_url);
        echo "<a href='" . esc_url($url) . "' class='slybot-sort-btn {$active}'>{$label}</a>";
    }
    echo "</div>";

    /* --- QUERY --- */
    $query_args = [
        'post_type'      => 'slybot_strategy',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => $sort_by,
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ];

    $tax_queries = [];
    if ($selected_asset) {
        $tax_queries[] = ['taxonomy' => 'slybot_asset', 'field' => 'slug', 'terms' => $selected_asset];
    }
    if ($selected_robot) {
        $tax_queries[] = ['taxonomy' => 'slybot_robot', 'field' => 'slug', 'terms' => $selected_robot];
    }
    if (!empty($tax_queries)) {
        $query_args['tax_query'] = array_merge(['relation' => 'AND'], $tax_queries);
    }

    $strategies = get_posts($query_args);

    /* --- CARDS --- */
    if (empty($strategies)) {
        echo "<div class='slybot-strat-empty'>Nenhuma estratégia encontrada para os filtros selecionados.</div>";
        return;
    }

    echo "<div class='slybot-strat-grid'>";

    foreach ($strategies as $strategy) {

        $m   = get_post_meta($strategy->ID);
        $get = fn($k) => $m[$k][0] ?? '';

        // Taxonomias
        $asset_terms = wp_get_post_terms($strategy->ID, 'slybot_asset');
        $asset_name  = !empty($asset_terms) && !is_wp_error($asset_terms) ? esc_html($asset_terms[0]->name) : '';

        $robot_terms = wp_get_post_terms($strategy->ID, 'slybot_robot');
        $robot_name  = !empty($robot_terms) && !is_wp_error($robot_terms) ? esc_html($robot_terms[0]->name) : '';

        // Dados
        $type       = $get('strategy_type') ?: '';
        $date_start = $get('date_start') ? date('M/Y', strtotime($get('date_start'))) : '';
        $date_end   = $get('date_end')   ? date('M/Y', strtotime($get('date_end')))   : '';
        $period     = ($date_start && $date_end) ? "{$date_start} – {$date_end}" : '';
        $total      = $get('total_trades') ? intval($get('total_trades')) : 0;
        $win_rate   = $get('win_rate') !== '' ? floatval($get('win_rate')) : 0;
        $wins       = intval($get('win_count'));
        $losses     = intval($get('loss_count'));
        $pf         = $get('profit_factor') ? floatval($get('profit_factor')) : 0;
        $pos_months = $get('positive_months') ?: '—';
        $dd_pct     = $get('max_drawdown_pct') ? floatval($get('max_drawdown_pct')) : 0;
        $ppt        = $get('profit_per_trade_base') ? floatval($get('profit_per_trade_base')) : 0;
        $tot_profit = $get('total_profit_base') ? floatval($get('total_profit_base')) : 0;
        $notes      = $get('admin_notes');

        // Lote
        $base_lot_futures = floatval($get('base_lot_futures'));
        $base_lot_stocks  = floatval($get('base_lot_stocks'));
        $is_stocks        = $base_lot_stocks > 0;
        $base_lot         = $is_stocks ? intval($base_lot_stocks) : intval($base_lot_futures ?: 1);
        $lot_label        = $is_stocks ? $base_lot . ' ações' : $base_lot . ' contrato' . ($base_lot > 1 ? 's' : '');

        // Curva
        $equity_html = '';
        if ($get('equity_curve_id')) {
            $img  = wp_get_attachment_image_url($get('equity_curve_id'), 'medium');
            $full = wp_get_attachment_image_url($get('equity_curve_id'), 'full');
            if ($img) $equity_html = "<a href='" . esc_url($full) . "' target='_blank' class='slybot-card-curve-link'><img src='" . esc_url($img) . "' class='slybot-curve-thumb' alt='Curva de Capital'></a>";
        }

        // Arquivo
        $file_html = '';
        if ($get('strategy_file_id')) {
            $file_url  = wp_get_attachment_url($get('strategy_file_id'));
            $file_html = "<a href='" . esc_url($file_url) . "' class='slybot-download-btn' download>
                <svg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24'
                     fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>
                    <path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/>
                    <polyline points='7 10 12 15 17 10'/><line x1='12' y1='15' x2='12' y2='3'/>
                </svg> Baixar</a>";
        }

        // Formatação de valores
        $fmt_ppt    = $ppt    ? 'R$ ' . number_format($ppt, 2, ',', '.') : '—';
        $fmt_profit = $tot_profit ? 'R$ ' . number_format($tot_profit, 2, ',', '.') : '—';
        $fmt_dd     = $dd_pct ? number_format($dd_pct, 1, ',', '.') . '%' : '—';
        $fmt_pf     = $pf     ? number_format($pf, 2, ',', '.') : '—';
        $fmt_trades = $total  ? $total : '—';

        echo "<div class='slybot-card'>";

        // Header do card — só tags (type vai para o corpo)
        echo "<div class='slybot-card-header'>";
        if ($asset_name) echo "<span class='slybot-tag slybot-tag-asset'>{$asset_name}</span>";
        if ($robot_name) echo "<span class='slybot-tag slybot-tag-robot'>{$robot_name}</span>";
        echo "</div>";

        // Corpo
        echo "<div class='slybot-card-body'>";

        // Título + tipo
        echo "<div class='slybot-card-title-row'>";
        echo "<h3 class='slybot-card-title'>" . esc_html($strategy->post_title) . "</h3>";
        if ($type) echo "<span class='slybot-card-type'>{$type}</span>";
        echo "</div>";

        // Win rate — faixa hero full-width
        echo "<div class='slybot-metric-hero'>
                <div class='slybot-metric-hero-rate'>{$win_rate}%</div>
                <div class='slybot-metric-hero-info'>
                    <div class='slybot-metric-hero-label'>Taxa de Acerto</div>
                    <div class='slybot-metric-hero-wl'>{$wins}G &nbsp;/&nbsp; {$losses}L</div>
                </div>
              </div>";

        // Métricas secundárias — grid 3 colunas full-width
        echo "<div class='slybot-metrics-grid'>";
        echo "<div class='slybot-metric'><div class='slybot-metric-val'>{$fmt_pf}</div><div class='slybot-metric-lbl'>Fator Lucro</div></div>";
        echo "<div class='slybot-metric'><div class='slybot-metric-val'>{$pos_months}</div><div class='slybot-metric-lbl'>Meses +</div></div>";
        echo "<div class='slybot-metric slybot-metric-danger'><div class='slybot-metric-val'>{$fmt_dd}</div><div class='slybot-metric-lbl'>Drawdown</div></div>";
        echo "<div class='slybot-metric'><div class='slybot-metric-val'>{$fmt_ppt}</div><div class='slybot-metric-lbl'>Lucro/Trade</div></div>";
        echo "<div class='slybot-metric slybot-metric-profit'><div class='slybot-metric-val'>{$fmt_profit}</div><div class='slybot-metric-lbl'>Lucro Total</div></div>";
        echo "<div class='slybot-metric'><div class='slybot-metric-val'>{$fmt_trades}</div><div class='slybot-metric-lbl'>Trades</div></div>";
        echo "</div>"; // .slybot-metrics-grid

        // Período e lote
        echo "<div class='slybot-card-info'>";
        if ($period)    echo "<span class='slybot-card-info-item'>📅 {$period}</span>";
        echo "<span class='slybot-card-info-item'>📦 Lote: {$lot_label}</span>";
        echo "</div>";

        // Comentários
        if ($notes) {
            echo "<div class='slybot-card-notes'>" . esc_html($notes) . "</div>";
        }

        echo "</div>"; // .slybot-card-body

        // Footer — curva + download
        if ($equity_html || $file_html) {
            echo "<div class='slybot-card-footer'>";
            echo "<div class='slybot-card-footer-left'>{$equity_html}</div>";
            echo "<div class='slybot-card-footer-right'>{$file_html}</div>";
            echo "</div>";
        }

        echo "</div>"; // .slybot-card
    }

    echo "</div>"; // .slybot-strat-grid
}


/* =====================================
   ESTILOS
===================================== */

add_action('wp_head', 'slybot_strategies_styles');

function slybot_strategies_styles() {
    if (!is_account_page()) return;
    ?>
    <style>
    /* Capital mínimo */
    .slybot-capital-notice {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff8f0;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #92400e;
        line-height: 1.5;
    }
    .slybot-capital-notice svg { flex-shrink: 0; color: #ff6a00; }
    .slybot-capital-notice strong { color: #c2410c; }

    /* Filtros */
    .slybot-filters-wrap { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
    .slybot-filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .slybot-filter-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .5px; color: #9ca3af; white-space: nowrap; min-width: 36px;
    }
    .slybot-filter-pills { display: flex; flex-wrap: wrap; gap: 6px; }
    .slybot-filter-btn {
        padding: 6px 14px; border-radius: 30px; border: 1px solid #e5e7eb;
        background: #fff; color: #374151; font-size: 12px; font-weight: 500;
        text-decoration: none; transition: all .2s;
    }
    .slybot-filter-btn:hover, .slybot-filter-btn.active {
        background: #ff6a00; border-color: #ff6a00; color: #fff !important;
    }

    /* Ordenação */
    .slybot-sort-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
    .slybot-sort-label { font-size: 11px; color: #9ca3af; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
    .slybot-sort-btn {
        padding: 5px 14px; border-radius: 6px; border: 1px solid #e5e7eb;
        background: #f9fafb; color: #374151; font-size: 12px; font-weight: 500;
        text-decoration: none; transition: all .2s;
    }
    .slybot-sort-btn:hover, .slybot-sort-btn.active {
        background: #111827; border-color: #111827; color: #fff !important;
    }

    /* Grid de cards */
    .slybot-strat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    /* Card */
    .slybot-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: box-shadow .2s, transform .2s;
    }
    .slybot-card:hover {
        box-shadow: 0 8px 32px rgba(0,0,0,0.10);
        transform: translateY(-2px);
    }

    /* Header do card — só tags */
    .slybot-card-header {
        background: #0f172a;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .slybot-tag {
        display: inline-block; padding: 4px 11px; border-radius: 20px;
        font-size: 11px; font-weight: 700; letter-spacing: .3px; white-space: nowrap;
    }
    .slybot-tag-asset { background: rgba(255,106,0,.18); color: #ff6a00; border: 1px solid rgba(255,106,0,.35); }
    .slybot-tag-robot { background: rgba(99,102,241,.18); color: #a5b4fc; border: 1px solid rgba(99,102,241,.35); }

    /* Corpo */
    .slybot-card-body { padding: 16px 16px 12px; flex: 1; display: flex; flex-direction: column; gap: 12px; }

    /* Título + tipo */
    .slybot-card-title-row { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
    .slybot-card-title { font-size: 15px; font-weight: 700; color: #111827; margin: 0; line-height: 1.3; }
    .slybot-card-type { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; white-space: nowrap; }

    /* Win rate — hero row full-width */
    .slybot-metric-hero {
        background: #0f172a;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .slybot-metric-hero-rate { font-size: 40px; font-weight: 800; color: #4ade80; line-height: 1; flex-shrink: 0; }
    .slybot-metric-hero-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; }
    .slybot-metric-hero-wl { font-size: 14px; font-weight: 600; color: #e2e8f0; margin-top: 6px; }

    /* Grid de métricas secundárias — 2 colunas */
    .slybot-metrics-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .slybot-metric {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 10px;
        padding: 10px 12px;
        text-align: center;
    }
    .slybot-metric-val { font-size: 15px; font-weight: 700; color: #111827; line-height: 1.2; white-space: nowrap; }
    .slybot-metric-lbl { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; color: #9ca3af; margin-top: 4px; }
    .slybot-metric-danger .slybot-metric-val { color: #ef4444; }
    .slybot-metric-profit .slybot-metric-val { color: #16a34a; }

    /* Info (período + lote) */
    .slybot-card-info { display: flex; gap: 12px; flex-wrap: wrap; }
    .slybot-card-info-item { font-size: 11px; color: #6b7280; }

    /* Notas */
    .slybot-card-notes {
        font-size: 12px; color: #6b7280; line-height: 1.5;
        border-left: 3px solid #ff6a00; padding-left: 10px;
        background: #fff8f0; border-radius: 0 6px 6px 0; padding: 8px 10px 8px 12px;
    }

    /* Footer */
    .slybot-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-top: 1px solid #f3f4f6;
        gap: 10px;
    }
    .slybot-card-footer-left {}
    .slybot-curve-thumb {
        width: 72px; height: 44px; object-fit: cover; border-radius: 6px;
        border: 1px solid #e5e7eb; cursor: pointer;
        transition: transform .2s, box-shadow .2s;
        display: block;
    }
    .slybot-curve-thumb:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .slybot-download-btn {
        display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
        background: #f3f4f6; color: #374151 !important; border-radius: 8px;
        font-size: 12px; font-weight: 600; text-decoration: none; transition: all .2s; white-space: nowrap;
    }
    .slybot-download-btn:hover { background: #ff6a00; color: #fff !important; }

    /* Empty */
    .slybot-strat-empty {
        padding: 48px; text-align: center; color: #9ca3af; font-size: 15px;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
    }

    /* Bloqueio */
    .slybot-locked-wrap {
        text-align: left; padding: 40px; background: #fff; border: 1px solid #e5e7eb;
        border-radius: 16px; max-width: 860px; margin: 0 0 30px 0;
    }
    .slybot-locked-icon { color: #ff6a00; margin-bottom: 20px; }
    .slybot-locked-wrap h2 { font-size: 22px; margin-bottom: 12px; }
    .slybot-locked-wrap p { color: #6b7280; font-size: 15px; line-height: 1.6; margin-bottom: 10px; }
    .slybot-btn-primary {
        display: inline-block; margin-top: 20px; padding: 14px 32px; background: #ff6a00;
        color: #fff !important; border-radius: 10px; font-weight: 700; font-size: 15px;
        text-decoration: none; transition: background .2s;
    }
    .slybot-btn-primary:hover { background: #e55e00; }

    @media (max-width: 640px) {
        .slybot-strat-grid { grid-template-columns: 1fr; }
        .slybot-card-metrics { flex-direction: column; }
        .slybot-metric-main { min-width: unset; width: 100%; }
        .slybot-filter-group { flex-direction: column; align-items: flex-start; }
    }
    </style>
    <?php
}
