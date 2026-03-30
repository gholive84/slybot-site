<?php
/*
Plugin Name: Slybot Strategies
Description: Painel de estratégias com backtest, simulador por lote/capital e filtro por ativo.
Version: 2.0
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
   REGISTRAR TAXONOMIA: ATIVO
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

    // Calculados
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

    /* --- FILTRO ATIVO --- */
    $selected_asset = isset($_GET['ativo']) ? sanitize_text_field($_GET['ativo']) : '';
    $sort_by        = isset($_GET['sort'])  ? sanitize_text_field($_GET['sort'])  : 'win_rate';
    $base_url       = wc_get_account_endpoint_url('estrategias-slybot');

    $assets = get_terms(['taxonomy' => 'slybot_asset', 'orderby' => 'name', 'hide_empty' => true]);

    /* --- QUERY --- */
    $query_args = [
        'post_type'      => 'slybot_strategy',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => $sort_by,
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ];

    if ($selected_asset) {
        $query_args['tax_query'] = [[
            'taxonomy' => 'slybot_asset',
            'field'    => 'slug',
            'terms'    => $selected_asset,
        ]];
    }

    $strategies = get_posts($query_args);

    /* --- FILTROS POR ATIVO --- */
    echo "<div class='slybot-strat-filters'>";
    $active_all = !$selected_asset ? 'active' : '';
    echo "<a href='" . esc_url(add_query_arg('sort', $sort_by, $base_url)) . "' class='slybot-filter-btn {$active_all}'>Todos</a>";

    if (!empty($assets) && !is_wp_error($assets)) {
        foreach ($assets as $asset) {
            $active = ($selected_asset === $asset->slug) ? 'active' : '';
            $url    = add_query_arg(['ativo' => $asset->slug, 'sort' => $sort_by], $base_url);
            echo "<a href='" . esc_url($url) . "' class='slybot-filter-btn {$active}'>" . esc_html($asset->name) . "</a>";
        }
    }
    echo "</div>";

    /* --- SIMULADOR --- */
    echo "
    <div class='slybot-simulator'>
        <div class='slybot-sim-field'>
            <label>Capital (R$)</label>
            <input type='number' id='sim-capital' value='10000' min='1' step='100'>
        </div>
        <div class='slybot-sim-hint'>
            Altere o capital para simular os resultados proporcionalmente.<br>
            O lote pode ser ajustado individualmente em cada estratégia.
        </div>
    </div>";

    /* --- ORDENAÇÃO --- */
    $sort_options = [
        'win_rate'             => '% Acerto',
        'profit_per_trade_base'=> 'Lucro/Trade',
        'total_profit_base'    => 'Lucro Total',
        'max_drawdown_pct'     => 'Drawdown',
    ];

    echo "<div class='slybot-sort-bar'>";
    echo "<span class='slybot-sort-label'>Ordenar por:</span>";
    foreach ($sort_options as $key => $label) {
        $active = ($sort_by === $key) ? 'active' : '';
        $url    = add_query_arg(['sort' => $key, 'ativo' => $selected_asset], $base_url);
        echo "<a href='" . esc_url($url) . "' class='slybot-sort-btn {$active}'>{$label}</a>";
    }
    echo "</div>";

    /* --- TABELA --- */
    if (empty($strategies)) {
        echo "<div class='slybot-strat-empty'>Nenhuma estratégia disponível no momento.</div>";
        return;
    }

    echo "<div class='slybot-strat-table-wrap'>";
    echo "<table class='slybot-strat-table' id='strat-table'>";
    echo "<thead><tr>
            <th>Ativo</th>
            <th>Estratégia</th>
            <th>Tipo</th>
            <th>Período</th>
            <th>Trades</th>
            <th>Acerto</th>
            <th>Fator Lucro</th>
            <th>Meses +</th>
            <th>Lote</th>
            <th>Drawdown</th>
            <th>Lucro/Trade</th>
            <th>Lucro Total</th>
            <th>Curva</th>
            <th>Arquivo</th>
          </tr></thead><tbody>";

    foreach ($strategies as $strategy) {

        $m   = get_post_meta($strategy->ID);
        $get = fn($k) => $m[$k][0] ?? '';

        $asset_terms  = wp_get_post_terms($strategy->ID, 'slybot_asset');
        $asset_name   = !empty($asset_terms) && !is_wp_error($asset_terms) ? esc_html($asset_terms[0]->name) : '—';
        $asset_slug   = !empty($asset_terms) && !is_wp_error($asset_terms) ? $asset_terms[0]->slug : '';

        $type         = $get('strategy_type') ?: '—';
        $date_start   = $get('date_start') ? date('m/Y', strtotime($get('date_start'))) : '—';
        $date_end     = $get('date_end')   ? date('m/Y', strtotime($get('date_end')))   : '—';
        $period       = "{$date_start} – {$date_end}";
        $total        = $get('total_trades') ?: '—';
        $win_rate     = $get('win_rate') !== '' ? floatval($get('win_rate')) : 0;
        $wins         = intval($get('win_count'));
        $losses       = intval($get('loss_count'));
        $pf           = $get('profit_factor') ?: '—';
        $pos_months   = $get('positive_months') ?: '—';
        $notes        = $get('admin_notes');

        // Lote base — futuros ou ações
        $base_lot_futures     = floatval($get('base_lot_futures'));
        $base_lot_stocks      = floatval($get('base_lot_stocks'));
        $is_stocks            = $base_lot_stocks > 0;
        $base_lot             = $is_stocks ? $base_lot_stocks : ($base_lot_futures ?: 1);
        $lot_step             = $is_stocks ? 100 : 1;
        $lot_label            = $is_stocks ? 'mín: ' . intval($base_lot) : 'mín: ' . intval($base_lot);
        $max_dd_pct           = floatval($get('max_drawdown_pct'));
        $profit_per_trade_base= floatval($get('profit_per_trade_base'));
        $total_profit_base    = floatval($get('total_profit_base'));

        // Curva
        $equity_html = '—';
        if ($get('equity_curve_id')) {
            $img  = wp_get_attachment_image_url($get('equity_curve_id'), 'thumbnail');
            $full = wp_get_attachment_image_url($get('equity_curve_id'), 'full');
            if ($img) $equity_html = "<a href='" . esc_url($full) . "' target='_blank'><img src='" . esc_url($img) . "' class='slybot-curve-thumb'></a>";
        }

        // Arquivo
        $file_html = '—';
        if ($get('strategy_file_id')) {
            $file_url  = wp_get_attachment_url($get('strategy_file_id'));
            $file_html = "<a href='" . esc_url($file_url) . "' class='slybot-download-btn' download>
                <svg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24'
                     fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                    <path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/>
                    <polyline points='7 10 12 15 17 10'/><line x1='12' y1='15' x2='12' y2='3'/>
                </svg> Baixar</a>";
        }

        echo "<tr
            data-base-lot='{$base_lot}'
            data-dd-pct='{$max_dd_pct}'
            data-ppt='{$profit_per_trade_base}'
            data-total-profit='{$total_profit_base}'>";

        echo "<td><span class='slybot-asset-tag'>{$asset_name}</span></td>";
        echo "<td>
                <div class='slybot-strat-name'>" . esc_html($strategy->post_title) . "</div>"
             . ($notes ? "<div class='slybot-strat-notes'>" . esc_html($notes) . "</div>" : "")
             . "</td>";
        echo "<td>{$type}</td>";
        echo "<td style='white-space:nowrap;font-size:12px'>{$period}</td>";
        echo "<td style='text-align:center'>{$total}</td>";
        echo "<td>
                <div class='slybot-winrate'>{$win_rate}%</div>
                <div class='slybot-wl'>{$wins}G / {$losses}L</div>
              </td>";
        echo "<td style='text-align:center;font-weight:600'>{$pf}</td>";
        echo "<td style='text-align:center'>{$pos_months}</td>";

        // Input de lote por linha — step e min corretos por tipo
        echo "<td style='text-align:center'>
                <input type='number'
                       class='slybot-lot-input'
                       value='{$base_lot}'
                       min='{$base_lot}'
                       step='{$lot_step}'
                       style='width:72px;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-weight:600;text-align:center'>
                <div style='font-size:10px;color:#9ca3af;margin-top:3px'>{$lot_label}</div>
              </td>";

        // Células calculadas pelo JS
        echo "<td class='slybot-calc' data-field='dd' style='text-align:center'>—</td>";
        echo "<td class='slybot-calc' data-field='ppt' style='text-align:center'>—</td>";
        echo "<td class='slybot-calc' data-field='total' style='text-align:center'>—</td>";

        echo "<td style='text-align:center'>{$equity_html}</td>";
        echo "<td style='text-align:center'>{$file_html}</td>";
        echo "</tr>";
    }

    echo "</tbody></table></div>";

    /* --- JAVASCRIPT SIMULADOR --- */
    echo "
    <script>
    (function() {
        function fmt(v) {
            return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calcRow(row) {
            var capital  = parseFloat(document.getElementById('sim-capital').value) || 10000;
            var baseLot  = parseFloat(row.dataset.baseLot)     || 1;
            var lotInput = row.querySelector('.slybot-lot-input');
            var lot      = lotInput ? (parseFloat(lotInput.value) || baseLot) : baseLot;

            var ddPct    = parseFloat(row.dataset.ddPct)       || 0;
            var pptBase  = parseFloat(row.dataset.ppt)         || 0;
            var totBase  = parseFloat(row.dataset.totalProfit) || 0;

            var lotFactor   = lot / baseLot;
            var ddVal       = (ddPct / 100) * capital;
            var ppt         = pptBase * lotFactor;
            var totalProfit = totBase * lotFactor;
            var totalPct    = capital > 0 ? (totalProfit / capital) * 100 : 0;

            row.querySelectorAll('.slybot-calc').forEach(function(cell) {
                var f = cell.dataset.field;
                if (f === 'dd')    cell.innerHTML = '<span style=\"color:#e74c3c;font-weight:600\">' + fmt(ddVal) + '</span><div style=\"font-size:11px;color:#9ca3af\">' + ddPct.toFixed(1) + '%</div>';
                if (f === 'ppt')   cell.innerHTML = fmt(ppt);
                if (f === 'total') cell.innerHTML = '<span style=\"font-weight:700;color:#16a34a\">' + fmt(totalProfit) + '</span><div style=\"font-size:11px;color:#9ca3af\">' + totalPct.toFixed(1) + '%</div>';
            });
        }

        function calcAll() {
            document.querySelectorAll('#strat-table tbody tr').forEach(calcRow);
        }

        // Capital global recalcula todas as linhas
        document.getElementById('sim-capital').addEventListener('input', calcAll);

        // Lote individual recalcula só a linha
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('slybot-lot-input')) {
                calcRow(e.target.closest('tr'));
            }
        });

        calcAll();
    })();
    </script>";
}


/* =====================================
   ESTILOS
===================================== */

add_action('wp_head', 'slybot_strategies_styles');

function slybot_strategies_styles() {
    if (!is_account_page()) return;
    ?>
    <style>
    /* Simulador */
    .slybot-simulator {
        display: flex;
        align-items: flex-end;
        gap: 20px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .slybot-sim-field { display: flex; flex-direction: column; gap: 6px; }
    .slybot-sim-field label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; }
    .slybot-sim-field input {
        padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 15px; font-weight: 600; width: 160px;
    }
    .slybot-sim-hint { font-size: 12px; color: #9ca3af; align-self: flex-end; padding-bottom: 4px; }

    /* Filtros */
    .slybot-strat-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .slybot-filter-btn {
        padding: 7px 16px; border-radius: 30px; border: 1px solid #e5e7eb;
        background: #fff; color: #374151; font-size: 13px; font-weight: 500;
        text-decoration: none; transition: all .2s;
    }
    .slybot-filter-btn:hover, .slybot-filter-btn.active {
        background: #ff6a00; border-color: #ff6a00; color: #fff !important;
    }

    /* Ordenação */
    .slybot-sort-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .slybot-sort-label { font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
    .slybot-sort-btn {
        padding: 5px 14px; border-radius: 6px; border: 1px solid #e5e7eb;
        background: #f9fafb; color: #374151; font-size: 12px; font-weight: 500;
        text-decoration: none; transition: all .2s;
    }
    .slybot-sort-btn:hover, .slybot-sort-btn.active {
        background: #111827; border-color: #111827; color: #fff !important;
    }

    /* Tabela */
    .slybot-strat-table-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; }
    .slybot-strat-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .slybot-strat-table thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .slybot-strat-table th {
        padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; white-space: nowrap;
    }
    .slybot-strat-table td { padding: 14px; border-top: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
    .slybot-strat-table tbody tr:hover { background: #fafafa; }

    .slybot-strat-name { font-weight: 600; font-size: 14px; color: #111827; margin-bottom: 4px; }
    .slybot-strat-notes { font-size: 12px; color: #6b7280; line-height: 1.5; max-width: 240px; }
    .slybot-asset-tag {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        background: #fff3eb; color: #ff6a00; font-size: 11px; font-weight: 700;
    }
    .slybot-winrate { font-weight: 700; color: #16a34a; font-size: 14px; }
    .slybot-wl { font-size: 11px; color: #9ca3af; margin-top: 2px; }
    .slybot-curve-thumb {
        width: 60px; height: 38px; object-fit: cover; border-radius: 6px;
        border: 1px solid #e5e7eb; cursor: pointer; transition: transform .2s;
    }
    .slybot-curve-thumb:hover { transform: scale(1.1); }
    .slybot-download-btn {
        display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px;
        background: #f3f4f6; color: #374151 !important; border-radius: 8px;
        font-size: 12px; font-weight: 600; text-decoration: none; transition: all .2s; white-space: nowrap;
    }
    .slybot-download-btn:hover { background: #ff6a00; color: #fff !important; }
    .slybot-strat-empty {
        padding: 40px; text-align: center; color: #9ca3af; font-size: 15px;
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

    @media (max-width: 768px) {
        .slybot-simulator { flex-direction: column; }
        .slybot-sim-field input { width: 100%; }
    }
    </style>
    <?php
}