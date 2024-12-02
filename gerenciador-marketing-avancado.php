<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*
Plugin Name: BrandAI - Gerenciador de Marketing Avançado
Plugin URI: https://publicidadeja.com.br
Description: Um plugin avançado para gerenciar campanhas de marketing e materiais.
Version: 1.2.5
Author: Publicidade Já
Author URI: https://publicidadeja.com.br/
Text Domain: gma-plugin
*/

// Ativar relatório de erros (apenas para desenvolvimento)
if (defined('WP_DEBUG') && WP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('GMA_VERSION', '1.2.1');
define('GMA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GMA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GMA_LICENSE_API_URL', 'https://licenca.publicidadeja.com.br/api');
define('GMA_LICENSE_VERIFY_ENDPOINT', GMA_LICENSE_API_URL . '/verificar.php');
define('GMA_PLUGIN_SLUG', 'brandaipro');
define('GMA_LICENSE_CHECK_INTERVAL', DAY_IN_SECONDS);
define('GMA_LICENSE_CACHE_KEY', 'gma_license_status');

// Incluir arquivos necessários
require_once GMA_PLUGIN_DIR . 'includes/database.php';
require_once GMA_PLUGIN_DIR . 'includes/admin-menu.php';
require_once GMA_PLUGIN_DIR . 'includes/openai-config.php';
require_once GMA_PLUGIN_DIR . 'includes/campanhas.php';
require_once GMA_PLUGIN_DIR . 'includes/materiais.php';
require_once GMA_PLUGIN_DIR . 'includes/estatisticas.php';
require_once GMA_PLUGIN_DIR . 'includes/admin-editar-material.php';
require_once GMA_PLUGIN_DIR . 'includes/taxonomies.php';




// Ativar plugin
register_activation_hook(__FILE__, 'gma_ativar_plugin');

function gma_ativar_plugin() {
    gma_criar_tabelas();
    gma_criar_tabela_estatisticas();
    create_logs_table();

    global $wpdb;
    $tabela_campanhas = $wpdb->prefix . 'gma_campanhas';
    $coluna_existe = $wpdb->get_results("SHOW COLUMNS FROM $tabela_campanhas LIKE 'tipo_campanha'");
    if (empty($coluna_existe)) {
        $wpdb->query("ALTER TABLE $tabela_campanhas ADD COLUMN tipo_campanha VARCHAR(255) NOT NULL DEFAULT 'marketing'");
    }

    flush_rewrite_rules();
}

// Desativar plugin
register_deactivation_hook(__FILE__, 'gma_desativar_plugin');

function gma_desativar_plugin() {
    flush_rewrite_rules();
}

// Em gerenciador-marketing-avancado.php
function gma_enqueue_admin_assets($hook) {
    $gma_pages = array(
        'toplevel_page_gma-plugin',
        'marketing_page_gma-editar-campanha',
        'marketing_page_gma-editar-material',
        'marketing_page_gma-novo-material',
        'marketing_page_gma-listar-materiais',
        'marketing_page_gma-estatisticas'
    );

    if (in_array($hook, $gma_pages)) {
        // CSS Admin
        wp_enqueue_style(
            'gma-admin-style',
            plugins_url('/gerenciador-marketing-avancado/assets/css/admin-style.css'),
            array(),
            GMA_VERSION
        );

      
      
        // JS Admin
        wp_enqueue_script(
            'gma-admin-script',
            plugins_url('/gerenciador-marketing-avancado/assets/js/admin-script.js'),
            array('jquery'),
            GMA_VERSION,
            true
        );

        // Adicione este bloco para localizar o script
        wp_localize_script('gma-admin-script', 'gmaData', array(
            'pluginUrl' => GMA_PLUGIN_URL,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gma_ajax_nonce'),
            'wpMediaTitle' => __('Escolha ou faça upload de uma imagem', 'gma-plugin'),
            'wpMediaButton' => __('Usar esta imagem', 'gma-plugin'),
            'soundUrl' => GMA_PLUGIN_URL . 'assets/sounds/notification.mp3'
        ));
    }
}
add_action('admin_enqueue_scripts', 'gma_enqueue_admin_assets');


// Adicionar variáveis de consulta personalizadas
function gma_query_vars($query_vars) {
    $query_vars[] = 'campanha_id';
    $query_vars[] = 'campanha_aprovacao';
    return $query_vars;
}
add_filter('query_vars', 'gma_query_vars');

// Regras de reescrita
function gma_rewrite_rules() {
    add_rewrite_rule('^campanha/([0-9]+)/?$', 'index.php?campanha_id=$matches[1]', 'top');
    add_rewrite_rule('^campanha-aprovacao/([0-9]+)/?$', 'index.php?campanha_id=$matches[1]&campanha_aprovacao=1', 'top');
}
add_action('init', 'gma_rewrite_rules');

// Enfileirar estilos e scripts para o frontend
function gma_enqueue_frontend_assets() {
   
  
    wp_enqueue_style('gma-frontend-style', GMA_PLUGIN_URL . 'assets/css/frontend-style.css', array(), GMA_VERSION);
    wp_enqueue_style('gma-public-style', GMA_PLUGIN_URL . 'assets/css/public.css', array(), GMA_VERSION);
    
    wp_enqueue_script('gma-frontend-script', GMA_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery'), GMA_VERSION, true);
    wp_enqueue_script('gma-public', GMA_PLUGIN_URL . 'assets/js/public.js', array('jquery'), GMA_VERSION, true);
    
    if (get_query_var('campanha_id')) {
        wp_enqueue_style('single-campanha-style', GMA_PLUGIN_URL . 'assets/css/single-campanha-style.css', array(), GMA_VERSION);
        wp_enqueue_script('gma-single-campanha', GMA_PLUGIN_URL . 'assets/js/gma-single-campanha.js', array('jquery'), GMA_VERSION, true);
    }

    wp_enqueue_script('gma-ajax', GMA_PLUGIN_URL . 'assets/js/gma-ajax.js', array('jquery'), GMA_VERSION, true);
    wp_localize_script('gma-ajax', 'gmaAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gma_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'gma_enqueue_frontend_assets');

// Usar template personalizado para exibição de campanha
function gma_custom_template($template) {
    $campanha_id = get_query_var('campanha_id');
    if ($campanha_id !== '' && $campanha_id !== false) {
        $campanha = gma_obter_campanha($campanha_id);

        if ($campanha && isset($campanha->tipo_campanha)) {
            $template_name = $campanha->tipo_campanha === 'aprovacao' ? 'single-campanha-aprovacao.php' : 'single-campanha.php';
            $new_template = GMA_PLUGIN_DIR . 'templates/' . $template_name;
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
    }
    return $template;
}
add_filter('template_include', 'gma_custom_template', 99);

// Funções AJAX
function gma_atualizar_clique() {
    check_ajax_referer('gma_ajax_nonce', 'nonce');
    $campanha_id = isset($_POST['campanha_id']) ? intval($_POST['campanha_id']) : 0;
    $material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;

    if ($campanha_id > 0 && $material_id > 0) {
        gma_atualizar_estatistica($campanha_id, 'cliques');
        wp_send_json_success(array('message' => 'Clique registrado com sucesso!'));
    } else {
        wp_send_json_error(array('message' => 'ID da campanha ou do material inválido.'));
    }
}
add_action('wp_ajax_gma_atualizar_clique', 'gma_atualizar_clique');
add_action('wp_ajax_nopriv_gma_atualizar_clique', 'gma_atualizar_clique');

// Função para salvar feedback e edição de materiais
function gma_salvar_feedback_e_edicao() {
    check_ajax_referer('gma_ajax_nonce', 'nonce');

    $material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;
    $feedback_arte = isset($_POST['feedback_arte']) ? sanitize_textarea_field($_POST['feedback_arte']) : '';
    $copy_editada = isset($_POST['copy_editada']) ? sanitize_textarea_field($_POST['copy_editada']) : '';

    if ($material_id > 0 && ($feedback_arte !== '' || $copy_editada !== '')) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'gma_materiais';

        $dados_atualizacao = array(
            'feedback' => $feedback_arte,
            'copy' => $copy_editada,
            'status_aprovacao' => 'pendente'
        );

        $resultado = $wpdb->update($tabela, $dados_atualizacao, array('id' => $material_id), array('%s', '%s', '%s'), array('%d'));

        if ($resultado !== false) {
            wp_send_json_success(array('message' => 'Feedback e edição salvos com sucesso!'));
        } else {
            wp_send_json_error(array('message' => 'Erro ao salvar feedback e edição.'));
        }
    } else {
        wp_send_json_error(array('message' => 'Dados inválidos.'));
    }
}
add_action('wp_ajax_gma_salvar_feedback_e_edicao', 'gma_salvar_feedback_e_edicao');
add_action('wp_ajax_nopriv_gma_salvar_feedback_e_edicao', 'gma_salvar_feedback_e_edicao');

// Ações AJAX adicionais
add_action('wp_ajax_gma_atualizar_status_material', 'gma_atualizar_status_material');
add_action('wp_ajax_nopriv_gma_atualizar_status_material', 'gma_atualizar_status_material');
add_action('wp_ajax_gma_salvar_feedback', 'gma_salvar_feedback');
add_action('wp_ajax_nopriv_gma_salvar_feedback', 'gma_salvar_feedback');

// Inicializar o plugin
function gma_init_plugin() {
    load_plugin_textdomain('gma-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'gma_init_plugin');

// Handler para a ação de exclusão de material
function gma_excluir_material_handler() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para realizar esta ação.'));
    }

    $material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$material_id) {
        wp_die(__('ID de material inválido.'));
    }

    check_admin_referer('gma_excluir_material_' . $material_id, 'gma_nonce');

    if (gma_excluir_material($material_id)) {
        wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=gma-listar-materiais')));
    } else {
        wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=gma-listar-materiais')));
    }
    exit;
}
add_action('admin_post_gma_excluir_material', 'gma_excluir_material_handler');

// Registrar mudança de status de uma campanha
add_action('transition_post_status', 'monitor_campaign_status', 10, 3);

function monitor_campaign_status($new_status, $old_status, $post) {
    if ($post->post_type == 'campaign') {
        if ($new_status === 'approved' && $old_status !== 'approved') {
            log_event('Campanha aprovada: ' . $post->ID);
        } elseif ($new_status === 'rejected' && $old_status !== 'rejected') {
            log_event('Campanha reprovada: ' . $post->ID);
        } elseif ($new_status !== $old_status) {
            log_event('Campanha editada: ' . $post->ID);
        }
    }
}

// Lógica para salvar eventos de log
if (!function_exists('log_event')) {
    function log_event($message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'campaign_logs';

        $wpdb->insert(
            $table_name,
            array(
                'event' => $message
            )
        );

        if ($wpdb->last_error) {
            error_log('DB Log Error: ' . $wpdb->last_error);
        } else {
            error_log('Log inserido com sucesso: ' . $message);
        }
    }
}


// Função para processar as sugestões da IA
add_action('wp_ajax_gma_get_copy_suggestions', 'gma_get_copy_suggestions_callback');
function gma_get_copy_suggestions_callback() {
    check_ajax_referer('gma_ajax_nonce', 'nonce');
    
    $copy = sanitize_textarea_field($_POST['copy']);
    
    // Aqui você deve implementar a chamada para sua API de IA
    // Este é apenas um exemplo de resposta
    $response = array(
        'success' => true,
        'data' => array(
            'suggestions' => 'Aqui virão as sugestões da IA para o texto: ' . $copy
        )
    );
    
    wp_send_json($response);
}

// Adiciona um widget personalizado ao dashboard do WordPress
add_action('wp_dashboard_setup', 'add_custom_dashboard_widget');

function add_custom_dashboard_widget() {
    wp_add_dashboard_widget(
        'custom_notifications',
        'Notificações de Campanha',
        'display_campaign_notifications'
    );
}

if (!function_exists('display_campaign_notifications')) {
    function display_campaign_notifications() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'campaign_logs';
        
        $logs = $wpdb->get_results("SELECT time, event FROM $table_name ORDER BY time DESC LIMIT 20");

        if (!empty($logs)) {
    echo '<ul>';
    foreach ($logs as $log) {
        echo '<li>' . esc_html($log->event) . ' em ' . esc_html($log->time) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>Sem notificações no momento</p>';
}
    }
}

// Função para criar tabela ao ativar o plugin
function create_logs_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'campaign_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        event text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Configuração do cron para limpar logs antigos diariamente
add_action('init', 'setup_cleanup_schedule');

function setup_cleanup_schedule() {
    if (!wp_next_scheduled('cleanup_old_logs')) {
        wp_schedule_event(time(), 'daily', 'cleanup_old_logs');
    }
}

add_action('cleanup_old_logs', 'remove_old_logs');

function remove_old_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'campaign_logs';

    $wpdb->query(
        "DELETE FROM $table_name WHERE time < NOW() - INTERVAL 30 DAY"
    );
}

// Shortcode para exibir campanhas
function gma_campanha_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'gma_campanha');

    $campanha_id = intval($atts['id']);
    if ($campanha_id > 0) {
        ob_start();
        include(GMA_PLUGIN_DIR . 'templates/shortcode-campanha.php');
        return ob_get_clean();
    }
    return '';
}
add_shortcode('gma_campanha', 'gma_campanha_shortcode');

// Função para registrar os scripts e estilos globalmente
function gma_register_global_assets() {
    wp_register_style('gma-global-style', GMA_PLUGIN_URL . 'assets/css/global-style.css', array(), GMA_VERSION);
    wp_register_script('gma-global-script', GMA_PLUGIN_URL . 'assets/js/global-script.js', array('jquery'), GMA_VERSION, true);
}
add_action('init', 'gma_register_global_assets');

// Função para enfileirar os assets globais quando necessário
function gma_enqueue_global_assets() {
    wp_enqueue_style('gma-global-style');
    wp_enqueue_script('gma-global-script');
}

// Adicionar suporte para thumbnails se ainda não estiver ativado
function gma_add_thumbnail_support() {
    if (!current_theme_supports('post-thumbnails')) {
        add_theme_support('post-thumbnails');
    }
}
add_action('after_setup_theme', 'gma_add_thumbnail_support');

// Adicionar meta box para informações adicionais da campanha
function gma_add_campaign_meta_box() {
    add_meta_box(
        'gma_campaign_info',
        __('Informações da Campanha', 'gma-plugin'),
        'gma_campaign_meta_box_callback',
        'gma_campaign',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'gma_add_campaign_meta_box');

function gma_campaign_meta_box_callback($post) {
    wp_nonce_field('gma_save_campaign_meta', 'gma_campaign_meta_nonce');
    $campaign_type = get_post_meta($post->ID, '_gma_campaign_type', true);
    ?>
    <p>
        <label for="gma_campaign_type"><?php _e('Tipo de Campanha:', 'gma-plugin'); ?></label>
        <select name="gma_campaign_type" id="gma_campaign_type">
            <option value="marketing" <?php selected($campaign_type, 'marketing'); ?>><?php _e('Marketing', 'gma-plugin'); ?></option>
            <option value="aprovacao" <?php selected($campaign_type, 'aprovacao'); ?>><?php _e('Aprovação', 'gma-plugin'); ?></option>
        </select>
    </p>
    <?php
}

function gma_save_campaign_meta($post_id) {
    if (!isset($_POST['gma_campaign_meta_nonce']) || !wp_verify_nonce($_POST['gma_campaign_meta_nonce'], 'gma_save_campaign_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['gma_campaign_type'])) {
        update_post_meta($post_id, '_gma_campaign_type', sanitize_text_field($_POST['gma_campaign_type']));
    }
}
add_action('save_post_gma_campaign', 'gma_save_campaign_meta');

// Adicionar página de configurações do plugin
function gma_add_settings_page() {
    add_submenu_page(
        'gma-plugin',
        __('Configurações do GMA', 'gma-plugin'),
        __('Configurações', 'gma-plugin'),
        'manage_options',
        'gma-settings',
        'gma_settings_page_callback'
    );
}
add_action('admin_menu', 'gma_add_settings_page');

function gma_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('gma_settings');
            do_settings_sections('gma-settings');
            submit_button('Salvar Configurações');
            ?>
        </form>
    </div>
    <?php
}

// Registrar configurações
function gma_register_settings() {
    register_setting('gma_settings', 'gma_default_campaign_type');

    add_settings_section(
        'gma_general_settings',
        __('Configurações Gerais', 'gma-plugin'),
        'gma_general_settings_callback',
        'gma-settings'
    );

    add_settings_field(
        'gma_default_campaign_type',
        __('Tipo de Campanha Padrão', 'gma-plugin'),
        'gma_default_campaign_type_callback',
        'gma-settings',
        'gma_general_settings'
    );
}
add_action('admin_init', 'gma_register_settings');

function gma_general_settings_callback() {
    echo '<p>' . __('Configurações gerais para o Gerenciador de Marketing Avançado.', 'gma-plugin') . '</p>';
}

function gma_default_campaign_type_callback() {
    $option = get_option('gma_default_campaign_type');
    ?>
    <select name="gma_default_campaign_type">
        <option value="marketing" <?php selected($option, 'marketing'); ?>><?php _e('Marketing', 'gma-plugin'); ?></option>
        <option value="aprovacao" <?php selected($option, 'aprovacao'); ?>><?php _e('Aprovação', 'gma-plugin'); ?></option>
    </select>
    <?php
}

// functions.php
function gma_enqueue_calendar_scripts() {
    wp_enqueue_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
    wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js');
    wp_enqueue_script('gma-calendar', plugins_url('assets/js/calendar.js', GMA_PLUGIN_FILE));
    
    wp_localize_script('gma-calendar', 'gmaCalendar', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gma_calendar_nonce'),
        'eventos' => gma_obter_eventos_calendario()
    ));
}

function gma_gerar_link_google_calendar($campanha) {
    $params = array(
        'action' => 'TEMPLATE',
        'text' => urlencode($campanha->nome),
        'details' => urlencode('Cliente: ' . $campanha->cliente),
        'dates' => date('Ymd', strtotime($campanha->data_criacao))
    );
    
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

function gma_agendar_lembrete($campanha_id, $data_lembrete) {
    wp_schedule_single_event(
        strtotime($data_lembrete),
        'gma_enviar_lembrete_campanha',
        array($campanha_id)
    );
}

function gma_enviar_lembrete_campanha($campanha_id) {
    $campanha = gma_obter_campanha($campanha_id);
    $admin_email = get_option('admin_email');
    
    wp_mail(
        $admin_email,
        'Lembrete: Campanha ' . $campanha->nome,
        'Não esqueça da campanha: ' . $campanha->nome
    );
}

add_action('gma_enviar_lembrete_campanha', 'gma_enviar_lembrete_campanha');

// Fim do arquivo

add_shortcode('gma_video', 'gma_video_shortcode');

function gma_video_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'src' => '',
        'width' => '100%',
        'height' => 'auto',
        'controls' => 'true'
    ), $atts);

    return wp_video_shortcode(array(
        'src' => $atts['src'],
        'width' => $atts['width'],
        'height' => $atts['height'],
        'controls' => $atts['controls']
    ));
}

// Função para gerar código de licença aleatório
function gma_gerar_codigo_licenca($length = 16) {
    // Caracteres permitidos no código
    $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = '';
    
    // Gera o código aleatório
    for ($i = 0; $i < $length; $i++) {
        $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    
    // Adiciona hífens para melhor legibilidade (formato: XXXX-XXXX-XXXX-XXXX)
    return substr($codigo, 0, 4) . '-' . substr($codigo, 4, 4) . '-' . 
           substr($codigo, 8, 4) . '-' . substr($codigo, 12, 4);
}

// Função chamada quando um pedido é completado no WooCommerce
function gma_criar_licenca_apos_compra($order_id) {
    // Obtém o pedido
    $order = wc_get_order($order_id);
    
    // Verifica se o pedido é válido
    if (!$order) return;
    
    // Verifica o status do pedido
    if ($order->get_status() != 'completed') return;
    
    // Gera o código de licença
    $codigo_licenca = gma_gerar_codigo_licenca();
    
    // Data de expiração (1 ano a partir de hoje)
    $data_expiracao = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    // Salva a licença no banco de dados
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'gma_licencas',
        array(
            'codigo' => $codigo_licenca,
            'order_id' => $order_id,
            'user_id' => $order->get_user_id(),
            'data_criacao' => current_time('mysql'),
            'data_expiracao' => $data_expiracao,
            'status' => 'ativo'
        ),
        array('%s', '%d', '%d', '%s', '%s', '%s')
    );
    
    // Envia email para o cliente com o código de licença
    $to = $order->get_billing_email();
    $subject = 'Seu código de licença do BrandAI';
    $message = "Obrigado por sua compra!\n\n";
    $message .= "Seu código de licença é: " . $codigo_licenca . "\n";
    $message .= "Válido até: " . date('d/m/Y', strtotime($data_expiracao));
    
    wp_mail($to, $subject, $message);
}
add_action('woocommerce_order_status_completed', 'gma_criar_licenca_apos_compra');



function gma_exibir_pagina_licenca() {
    // Verifica se o formulário foi enviado
    if (isset($_POST['gma_licenca_submit'])) {
        // Verifica o nonce para segurança
        if (check_admin_referer('gma_validar_licenca')) {
            $codigo_licenca = sanitize_text_field($_POST['gma_codigo_licenca']);
            // Valida a licença
            if (gma_validar_licenca($codigo_licenca)) {
                update_option('gma_codigo_licenca', $codigo_licenca);
                update_option('gma_licenca_status', 'valida');
                echo '<div class="notice notice-success"><p>Licença válida e ativada com sucesso!</p></div>';
            } else {
                update_option('gma_licenca_status', 'invalida');
                echo '<div class="notice notice-error"><p>Código de licença inválido ou expirado!</p></div>';
            }
        }
    }

    // Obtém o código de licença atual
    $codigo_atual = get_option('gma_codigo_licenca', '');
    $status_licenca = get_option('gma_licenca_status', '');
    ?>
    <div class="wrap">
        <h1>Licença BrandAI</h1>
        <?php
        // Exibe o status da licença
        if ($status_licenca === 'valida') {
            echo '<div class="notice notice-success inline"><p>Licença ativa</p></div>';
        } elseif ($status_licenca === 'invalida') {
            echo '<div class="notice notice-error inline"><p>Licença inválida</p></div>';
        }
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('gma_validar_licenca'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gma_codigo_licenca">Código de Licença</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="gma_codigo_licenca" 
                               name="gma_codigo_licenca" 
                               value="<?php echo esc_attr($codigo_atual); ?>" 
                               class="regular-text"
                               placeholder="XXXX-XXXX-XXXX-XXXX">
                        <p class="description">
                            Insira o código de licença recebido após a compra do plugin.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Salvar Licença', 'primary', 'gma_licenca_submit'); ?>
        </form>
    </div>
    <?php
}

// Função para validar a licença
function gma_validar_licenca($codigo) {
    global $wpdb;
    $tabela_licencas = $wpdb->prefix . 'gma_licencas';
    
    // Verifica se o código existe e está ativo
    $licenca = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabela_licencas 
        WHERE codigo = %s 
        AND status = 'ativo' 
        AND data_expiracao > NOW()",
        $codigo
    ));

    return !empty($licenca);
}

function gma_verificar_licenca_ativa() {
    $licenca = get_option('gma_license_key');
    $dominio = $_SERVER['HTTP_HOST'];
    
    // Verifica se existe licença configurada
    if (empty($licenca)) {
        return false;
    }

    // Verifica cache
    $cache_key = 'gma_license_check_' . md5($licenca . $dominio);
    $cached_response = get_transient($cache_key);
    
    if ($cached_response !== false) {
        return $cached_response === 'valid';
    }

    // Faz a requisição para a API
    $response = wp_remote_post('https://licenca.publicidadeja.com.br/api/', [
        'timeout' => 15,
        'headers' => [
            'X-API-KEY' => '@Speaker120123',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'body' => [
            'codigo_licenca' => $licenca,
            'site_url' => $dominio,
            'action' => 'verificar',
            'produto' => 'brandaipro'
        ]
    ]);

    // Verifica se houve erro na requisição
    if (is_wp_error($response)) {
        error_log('BrandAI License Error: ' . $response->get_error_message());
        set_transient($cache_key, 'error', HOUR_IN_SECONDS);
        return false;
    }

    // Processa a resposta
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (!$data || !isset($data->success)) {
        error_log('BrandAI License Invalid Response: ' . $body);
        set_transient($cache_key, 'error', HOUR_IN_SECONDS);
        return false;
    }

    // Armazena em cache
    set_transient($cache_key, $data->valid ? 'valid' : 'invalid', DAY_IN_SECONDS);

    return $data->success && $data->valid;
}
// Em gerenciador-marketing-avancado.php
class GMA_License_API {
    private $api_url;
    private $api_key;

    public function __construct() {
        $this->api_url = 'https://licenca.publicidadeja.com.br/api/';
        $this->api_key = '@Speaker120123';
    }

    public function verify_license($license_key) {
        if (empty($license_key)) {
            return (object) ['valid' => false, 'message' => 'Licença não fornecida'];
        }

        $site_url = str_replace(['http://', 'https://', 'www.'], '', get_site_url());
        
        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'X-Api-Key' => $this->api_key,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'action' => 'verificar',
                'codigo_licenca' => $license_key,
                'site_url' => $site_url
            ],
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return (object) ['valid' => false, 'message' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (empty($data) || !isset($data->success)) {
            return (object) ['valid' => false, 'message' => 'Resposta inválida da API'];
        }

        return (object) [
            'valid' => isset($data->valid) ? $data->valid : false,
            'message' => isset($data->message) ? $data->message : '',
            'expiration' => isset($data->expiration) ? $data->expiration : null
        ];
    }
}

function gma_verificar_status_licenca() {
    $codigo_licenca = get_option('gma_codigo_licenca');
    $ultima_verificacao = get_option('gma_ultima_verificacao_licenca', 0);
    
    // Log para debug
    error_log('GMA: Verificando licença...');
    error_log('GMA: Código da licença: ' . $codigo_licenca);
    error_log('GMA: Última verificação: ' . date('Y-m-d H:i:s', $ultima_verificacao));

    // Verificar a cada 24 horas
    if (time() - $ultima_verificacao < DAY_IN_SECONDS) {
        $status = get_option('gma_licenca_status') === 'valid';
        error_log('GMA: Usando cache da licença. Status: ' . ($status ? 'válida' : 'inválida'));
        return $status;
    }

    try {
        $api = new GMA_License_API();
        $resultado = $api->verify_license($codigo_licenca);
        
        error_log('GMA: Resposta da API: ' . print_r($resultado, true));

        update_option('gma_ultima_verificacao_licenca', time());
        update_option('gma_licenca_status', $resultado->valid ? 'valid' : 'invalid');
        
        // Se a licença for inválida, adiciona um aviso no admin
        if (!$resultado->valid) {
            add_action('admin_notices', 'gma_admin_notice_license_invalid');
        }

        return $resultado->valid;

    } catch (Exception $e) {
        error_log('GMA: Erro ao verificar licença: ' . $e->getMessage());
        return false;
    }
}

function gma_admin_notice_license_invalid() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <strong>Gerenciador Marketing Avançado:</strong> 
            Sua licença está inválida ou expirada. Por favor, 
            <a href="<?php echo admin_url('admin.php?page=gma-settings'); ?>">
                verifique suas configurações de licença
            </a>.
        </p>
    </div>
    <?php
}

// Adiciona a verificação no carregamento do WordPress
add_action('wp_loaded', 'gma_verificar_status_licenca');

// Adiciona verificação ao salvar as configurações
add_action('update_option_gma_codigo_licenca', function($old_value, $new_value) {
    if ($old_value !== $new_value) {
        // Força uma nova verificação ao alterar o código da licença
        update_option('gma_ultima_verificacao_licenca', 0);
        gma_verificar_status_licenca();
    }
}, 10, 2);


function gma_exibir_aviso_licenca() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!gma_verificar_licenca_ativa()) {
        echo '<div class="notice notice-error">';
        echo '<p>Sua licença do BrandAI PRO está inativa. ';
        echo '<a href="' . admin_url('admin.php?page=gma-ativacao') . '">Clique aqui para ativar sua licença</a>.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'gma_exibir_aviso_licenca');

// Atualizar a função de verificação remota
function gma_verificar_licenca_remota($codigo_licenca) {
    $api_url = 'https://licenca.publicidadeja.com.br/api/verify';
    
    $response = wp_remote_post($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-API-Key' => 'sua_chave_api_secreta'
        ),
        'body' => json_encode(array(
            'license_key' => $codigo_licenca,
            'site_url' => get_site_url(),
            'product' => 'brandaipro',
            'version' => GMA_VERSION
        ))
    ));

    if (is_wp_error($response)) {
        error_log('Erro na verificação de licença: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response));
    return !empty($body->valid);
}

function gma_handle_license_error($error) {
    error_log('[BrandAI] ' . $error);
    set_transient('gma_license_error', $error, HOUR_IN_SECONDS);
}

function gma_ativar_licenca() {
    if (!isset($_POST['gma_licenca_nonce']) || 
        !wp_verify_nonce($_POST['gma_licenca_nonce'], 'gma_ativar_licenca')) {
        wp_die('Ação não autorizada');
    }

    $codigo_licenca = sanitize_text_field($_POST['codigo_licenca']);
    $dominio = $_SERVER['HTTP_HOST'];

    $response = wp_remote_post('https://licenca.publicidadeja.com.br/api/verificar.php', array(
        'timeout' => 15,
        'body' => array(
            'codigo_licenca' => $codigo_licenca,
            'site_url' => $dominio,
            'action' => 'verificar',
            'produto' => 'brandaipro'
        )
    ));

    if (is_wp_error($response)) {
        wp_redirect(admin_url('admin.php?page=gma-ativacao&message=Erro na conexão&type=error'));
        exit;
    }

    $body = json_decode(wp_remote_retrieve_body($response));
    
    if (isset($body->status) && $body->status === 'ativo') {
        update_option('gma_license_key', $codigo_licenca);
        wp_redirect(admin_url('admin.php?page=gma-ativacao&message=Licença ativada com sucesso&type=success'));
    } else {
        wp_redirect(admin_url('admin.php?page=gma-ativacao&message=Licença inválida ou já em uso&type=error'));
    }
    exit;
}
add_action('admin_post_gma_ativar_licenca', 'gma_ativar_licenca');

function gma_handle_ativacao_licenca() {
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão para acessar esta página.');
    }

    if (!isset($_POST['gma_licenca_nonce']) || !wp_verify_nonce($_POST['gma_licenca_nonce'], 'gma_ativar_licenca')) {
        wp_die('Verificação dfunction gma_verificar_licenca_ativa() {
e segurança falhou.');
    }

    $codigo_licenca = sanitize_text_field($_POST['codigo_licenca']);
    update_option('gma_license_key', $codigo_licenca);

    if (gma_verificar_licenca_ativa()) {
        wp_redirect(admin_url('admin.php?page=gma-ativacao&message=Licença ativada com sucesso&type=success'));
    } else {
        wp_redirect(admin_url('admin.php?page=gma-ativacao&message=Licença inválida ou já em uso&type=error'));
    }
    exit;
}
add_action('admin_post_gma_ativar_licenca', 'gma_handle_ativacao_licenca');

add_action('init', 'gma_load_plugin_textdomain');

function gma_load_plugin_textdomain() {
    load_plugin_textdomain('gerenciador-marketing-avancado', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('admin_init', 'gma_debug_license');

function gma_debug_license() {
    if (isset($_GET['debug_license']) && current_user_can('manage_options')) {
        $licenca = get_option('gma_license_key');
        error_log("Licença atual: " . $licenca);
        error_log("Status da licença: " . (gma_verificar_licenca_ativa() ? 'ativa' : 'inativa'));
    }
}

add_action('admin_init', function() {
    $response = wp_remote_post(GMA_LICENSE_API_URL . '/verificar.php', array(
        'body' => array(
            'codigo_licenca' => get_option('gma_codigo_licenca'),
            'site_url' => $_SERVER['HTTP_HOST']
        )
    ));
    
    if (is_wp_error($response)) {
        error_log('Erro de licença: ' . $response->get_error_message());
    }
});

function gma_verificar_licenca($codigo_licenca) {
    $cache_key = 'gma_license_status_' . md5($codigo_licenca);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $response = wp_remote_post(GMA_LICENSE_VERIFY_ENDPOINT, array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('gma_api_token') // Obtenha o token da opção
        ),
        'body' => json_encode(array(
            'codigo_licenca' => $codigo_licenca,
            'site_url' => get_site_url(),
            'action' => 'verificar',
            'produto' => GMA_PLUGIN_SLUG
        )),
        'timeout' => 15
    ));

    if (is_wp_error($response)) {
        error_log('Erro na verificação de licença: ' . $response->get_error_message());
        return false; // ou lance uma exceção
    }

    $body = json_decode(wp_remote_retrieve_body($response));
    if (isset($body->success) && $body->success && isset($body->valid) && $body->valid) {
        $status = true;
    } else {
        $status = false;
        error_log('Resposta da API de licença inválida: ' . json_encode($body));
    }

    set_transient($cache_key, $status, HOUR_IN_SECONDS);
    return $status;
}


