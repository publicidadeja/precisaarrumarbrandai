<?php
// includes/admin-menu.php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'gma_criar_menu_admin');

function gma_criar_menu_admin() {
    // Menu principal
    add_menu_page(
        'Gerenciador de Marketing Avançado',
        'BrandAI',
        'manage_options',
        'gma-plugin',
        'gma_pagina_principal',
        'dashicons-image-filter',
        30
    );

    // Submenus
    $submenus = array(
        array('Campanhas', 'gma-campanhas', 'gma_pagina_campanhas'),
        array('Editar Campanha', 'gma-editar-campanha', 'gma_pagina_editar_campanha'),
        array('Materiais', 'gma-materiais', 'gma_pagina_listar_materiais'),
        array('Novo Material', 'gma-novo-material', 'gma_pagina_novo_material'),
        array('Editar Material', 'gma-editar-material', 'gma_pagina_editar_material'),
        array('Categorias', 'gma-criar-categoria', 'gma_pagina_criar_categoria'),
        array('Calendário', 'gma-calendario', 'gma_pagina_calendario'),
        array('Relatório', 'gma-relatorio-campanhas', 'gma_pagina_relatorio_campanhas'),
        array('Ativação', 'gma-ativacao', 'gma_pagina_ativacao')
    );

    foreach ($submenus as $submenu) {
        add_submenu_page(
            'gma-plugin',
            $submenu[0],
            $submenu[0],
            'manage_options',
            $submenu[1],
            $submenu[2]
        );
    }
}

// Funções para exibir as páginas
function gma_pagina_principal() {
  // Verificar se existe uma notificação
    $notificacao = get_transient('gma_notificacao_admin');

    if ($notificacao) {
        // Exibir a notificação
        echo '<div class="notice notice-' . $notificacao['tipo'] . ' is-dismissible"><p>' . $notificacao['mensagem'] . '</p></div>';
        
        // Apagar a notificação para que ela não seja exibida novamente
        delete_transient('gma_notificacao_admin'); 
    }
    include GMA_PLUGIN_DIR . 'templates/pagina-principal.php';
}

function gma_pagina_campanhas() {
    global $wpdb;
    if (isset($_POST['criar_campanha'])) {
        $nome = sanitize_text_field($_POST['nome_campanha']);
        $cliente = sanitize_text_field($_POST['cliente_campanha']);
        $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        $tipo_campanha = isset($_POST['tipo_campanha']) ? sanitize_text_field($_POST['tipo_campanha']) : 'marketing';
        $campanha_id = gma_criar_campanha($nome, $cliente, $categoria_id, $tipo_campanha);
        wp_redirect(admin_url('admin.php?page=gma-campanhas'));
        exit;
    }

    $campanhas = isset($_GET['categoria']) ? 
        $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gma_campanhas WHERE categoria_id = %d ORDER BY data_criacao DESC", intval($_GET['categoria']))) :
        gma_listar_campanhas();

    include GMA_PLUGIN_DIR . 'templates/pagina-campanhas.php';
}

function gma_pagina_nova_campanha() {
    // Implementar lógica para criar nova campanha
    include GMA_PLUGIN_DIR . 'templates/pagina-nova-campanha.php';
}

function gma_pagina_editar_campanha() {
    if (isset($_GET['campanha_id'])) {
        $campanha_id = intval($_GET['campanha_id']);
        $campanha = gma_obter_campanha($campanha_id);

        if ($campanha) {
            include GMA_PLUGIN_DIR . 'templates/pagina-editar-campanha.php';
        } else {
            echo '<div class="notice notice-error"><p>Campanha não encontrada.</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>ID da campanha não fornecido.</p></div>';
    }
}

function gma_pagina_listar_materiais() {
  // Verificar se existe uma notificação
    $notificacao = get_transient('gma_notificacao_admin');

    if ($notificacao) {
        // Exibir a notificação
        echo '<div class="notice notice-' . $notificacao['tipo'] . ' is-dismissible"><p>' . $notificacao['mensagem'] . '</p></div>';
        
        // Apagar a notificação para que ela não seja exibida novamente
        delete_transient('gma_notificacao_admin'); 
    }
    global $wpdb;
    $tabela_materiais = $wpdb->prefix . 'gma_materiais';
    $tabela_campanhas = $wpdb->prefix . 'gma_campanhas';

    $periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'mes';

    $materiais = $wpdb->get_results("
    SELECT m.*, c.nome AS nome_campanha, c.tipo_campanha
    FROM $tabela_materiais m
    LEFT JOIN $tabela_campanhas c ON m.campanha_id = c.id
    ORDER BY m.data_criacao DESC
    ");

    include GMA_PLUGIN_DIR . 'templates/pagina-listar-materiais.php';
}

function gma_pagina_novo_material() {
    if (!session_id()) {
        session_start();
    }

    require_once GMA_PLUGIN_DIR . 'includes/materiais.php';

    $messages = array();

    if (isset($_POST['criar_material']) && wp_verify_nonce($_POST['gma_novo_material_nonce'], 'gma_novo_material')) {
        if (!isset($_SESSION['material_criado'])) {
            $campanha_id = intval($_POST['campanha_id']);
            $imagem_url = esc_url_raw($_POST['imagem_url']);
            $copy = wp_kses_post($_POST['copy']);
            $link_canva = esc_url_raw($_POST['link_canva']);
            $arquivo_id = isset($_POST['arquivo_id']) ? intval($_POST['arquivo_id']) : null;

            $resultado = gma_criar_material($campanha_id, $imagem_url, $copy, $link_canva, $arquivo_id);

            if ($resultado) {
                $_SESSION['material_criado'] = true;
                $messages[] = array('type' => 'success', 'message' => 'Material criado com sucesso!');
            } else {
                $messages[] = array('type' => 'error', 'message' => 'Erro ao criar o material. Por favor, tente novamente. Verifique o log de erros.');
            }
        } else {
            $messages[] = array('type' => 'warning', 'message' => 'Material já criado. Atualize a página.');
        }
    }

    unset($_SESSION['material_criado']);

    foreach ($messages as $message) {
        echo '<div class="notice notice-' . $message['type'] . ' is-dismissible"><p>' . $message['message'] . '</p></div>';
    }

    $campanhas = gma_listar_campanhas();
    include GMA_PLUGIN_DIR . 'templates/pagina-novo-material.php';
}

function gma_pagina_editar_material() {
    if (!isset($_GET['id']) || !isset($_GET['tipo'])) {
        wp_die('Parâmetros inválidos.');
    }

    $material_id = intval($_GET['id']);
    $tipo_campanha = sanitize_text_field($_GET['tipo']);
    $material = gma_obter_material($material_id);

    if (!$material) {
        wp_die('Material não encontrado.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($tipo_campanha === 'aprovacao' && isset($_POST['atualizar_material_aprovacao'])) {
            check_admin_referer('editar_material_aprovacao', 'gma_nonce');
            
            $status = sanitize_text_field($_POST['status_aprovacao']);
            $feedback = sanitize_textarea_field($_POST['feedback']);
            $copy = isset($_POST['copy']) ? sanitize_textarea_field($_POST['copy']) : '';
            $imagem_url = isset($_POST['imagem_url']) ? esc_url_raw($_POST['imagem_url']) : $material->imagem_url;
            
            $resultado = gma_atualizar_material_aprovacao($material_id, $status, $feedback, $copy, $imagem_url);

            if ($resultado) {
                set_transient('gma_notificacao_admin', array(
                    'tipo' => 'success',
                    'mensagem' => 'Material de aprovação atualizado com sucesso.'
                ), 45);
            } else {
                set_transient('gma_notificacao_admin', array(
                    'tipo' => 'error',
                    'mensagem' => 'Erro ao atualizar o material de aprovação.'
                ), 45);
            }
            
            wp_redirect(admin_url('admin.php?page=gma-materiais'));
            exit;
        }
    }

    include GMA_PLUGIN_DIR . 'templates/editar-material-aprovacao.php';
}

function gma_pagina_listar_categorias() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.'));
    }

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['category_id'])) {
            check_admin_referer('delete_category', 'gma_nonce');
            $category_id = intval($_POST['category_id']);
            if (gma_excluir_categoria($category_id)) {
                wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=gma-categorias')));
                exit;
            } else {
                add_settings_error('gma_messages', 'category_delete_error', 'Erro ao excluir a categoria.', 'error');
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['category_id']) && isset($_POST['new_name'])) {
            check_admin_referer('edit_category', 'gma_nonce');
            $category_id = intval($_POST['category_id']);
            $new_name = sanitize_text_field($_POST['new_name']);
            if (gma_atualizar_categoria($category_id, $new_name)) {
                wp_redirect(add_query_arg('message', 'updated', admin_url('admin.php?page=gma-categorias')));
                exit;
            } else {
                add_settings_error('gma_messages', 'category_update_error', 'Erro ao atualizar a categoria.', 'error');
            }
        }
    }

    include GMA_PLUGIN_DIR . 'templates/pagina-listar-categoria.php';
}

function gma_pagina_criar_categoria() {
    include GMA_PLUGIN_DIR . 'templates/pagina-criar-categoria.php';
}

function gma_pagina_relatorio_campanhas() {
    include GMA_PLUGIN_DIR . 'templates/pagina-relatorio-campanhas.php';
}

function gma_atualizar_categoria($id, $novo_nome) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gma_categorias';
    
    $result = $wpdb->update(
        $table_name,
        array('nome' => $novo_nome),
        array('id' => $id),
        array('%s'),
        array('%d')
    );

    if ($result === false) {
        error_log("Erro ao atualizar categoria: " . $wpdb->last_error);
        return false;
    }

    return true;
}

function gma_excluir_categoria($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gma_categorias';
    
    $result = $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );

    if ($result === false) {
        error_log("Erro ao excluir categoria: " . $wpdb->last_error);
        return false;
    }

    return true;
}

function gma_pagina_calendario() {
    ?>
    <div class="wrap">
        <h1>Calendário de Campanhas</h1>
        <div id="calendario">
            <?php
            // Obter campanhas do banco de dados
            $campanhas = gma_listar_campanhas();
            
            // Criar array de eventos para o calendário
            $eventos = array();
            foreach ($campanhas as $campanha) {
                $eventos[] = array(
                    'title' => esc_html($campanha->nome),
                    'start' => $campanha->data_criacao,
                    'url' => admin_url('admin.php?page=gma-editar-campanha&campanha_id=' . $campanha->id)
                );
            }
            ?>
            <div id='calendar'></div>
        </div>
    </div>

    <!-- Incluir FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@4.4.0/main.min.css' rel='stylesheet' />
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.4.0/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@4.4.0/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.4.0/main.min.js'></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ['dayGrid'],
            locale: 'pt-br',
            events: <?php echo json_encode($eventos); ?>,
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek'
            }
        });
        calendar.render();
    });
    </script>

    <style>
    #calendar {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    .fc-event {
        cursor: pointer;
        padding: 5px;
    }
    </style>
    <?php
}




// Função que renderiza a página de ativação
function gma_pagina_ativacao() {
    include GMA_PLUGIN_DIR . 'templates/pagina-ativacao.php';
}

function gma_pagina_licenca() {
    if (isset($_POST['gma_codigo_licenca'])) {
        $codigo = sanitize_text_field($_POST['gma_codigo_licenca']);
        update_option('gma_codigo_licenca', $codigo);
        update_option('gma_ultima_verificacao_licenca', 0); // Força nova verificação
        
        if (gma_verificar_status_licenca()) {
            echo '<div class="notice notice-success"><p>Licença ativada com sucesso!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Código de licença inválido.</p></div>';
        }
    }
    
    $codigo_atual = get_option('gma_codigo_licenca');
    ?>
    <div class="wrap">
        <h1>Configurações de Licença</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="gma_codigo_licenca">Código da Licença</label></th>
                    <td>
                        <input type="text" name="gma_codigo_licenca" id="gma_codigo_licenca" 
                               value="<?php echo esc_attr($codigo_atual); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            <?php submit_button('Salvar Licença'); ?>
        </form>
    </div>
    <?php
}

