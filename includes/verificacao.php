<?php
function gma_verificar_licenca_ativa() {
    $licenca = get_option('gma_license_key');
    $site_url = get_site_url();
    
    if (empty($licenca)) {
        return false;
    }

    // Verificar cache primeiro
    $cache_key = 'gma_license_check_' . md5($licenca . $site_url);
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result === 'valid';
    }

    // Se não há cache, fazer requisição à API
    $api_url = 'https://licenca.publicidadeja.com.br/api/verificar.php';
    $response = wp_remote_post($api_url, array(
        'body' => array(
            'codigo_licenca' => $licenca,
            'site_url' => $site_url,
            'produto' => 'brandaipro'
        ),
        'headers' => array(
            'X-API-KEY' => 'sua_chave_api_aqui'
        )
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    $is_valid = isset($data->valid) && $data->valid === true;
    
    // Guardar em cache por 24 horas
    set_transient($cache_key, $is_valid ? 'valid' : 'invalid', DAY_IN_SECONDS);

    return $is_valid;
}

function gma_ativar_licenca($codigo_licenca) {
    $site_url = get_site_url();
    $api_url = 'https://licenca.publicidadeja.com.br/api/ativar.php';
    
    $response = wp_remote_post($api_url, array(
        'body' => array(
            'codigo_licenca' => $codigo_licenca,
            'site_url' => $site_url,
            'produto' => 'brandaipro'
        ),
        'headers' => array(
            'X-API-KEY' => 'sua_chave_api_aqui'
        )
    ));

    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => 'Erro ao conectar com o servidor de licenças'
        );
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (isset($data->success) && $data->success) {
        update_option('gma_license_key', $codigo_licenca);
        delete_transient('gma_license_check_' . md5($codigo_licenca . $site_url));
        
        return array(
            'success' => true,
            'message' => 'Licença ativada com sucesso!'
        );
    }

    return array(
        'success' => false,
        'message' => $data->message ?? 'Erro ao ativar licença'
    );
}

function gma_desativar_licenca() {
    $licenca = get_option('gma_license_key');
    $site_url = get_site_url();
    
    if (empty($licenca)) {
        return array(
            'success' => false,
            'message' => 'Nenhuma licença ativa'
        );
    }

    $api_url = 'https://licenca.publicidadeja.com.br/api/desativar.php';
    
    $response = wp_remote_post($api_url, array(
        'body' => array(
            'codigo_licenca' => $licenca,
            'site_url' => $site_url,
            'produto' => 'brandaipro'
        ),
        'headers' => array(
            'X-API-KEY' => 'sua_chave_api_aqui'
        )
    ));

    delete_option('gma_license_key');
    delete_transient('gma_license_check_' . md5($licenca . $site_url));

    return array(
        'success' => true,
        'message' => 'Licença desativada com sucesso!'
    );
}

// Hooks para ações de ativação/desativação
add_action('admin_post_gma_ativar_licenca', function() {
    if (!isset($_POST['gma_licenca_nonce']) || 
        !wp_verify_nonce($_POST['gma_licenca_nonce'], 'gma_ativar_licenca')) {
        wp_die('Ação não autorizada');
    }

    $codigo_licenca = sanitize_text_field($_POST['codigo_licenca']);
    $resultado = gma_ativar_licenca($codigo_licenca);
    
    wp_redirect(add_query_arg(
        array(
            'page' => 'gma-licenca',
            'message' => $resultado['message'],
            'type' => $resultado['success'] ? 'success' : 'error'
        ),
        admin_url('admin.php')
    ));
    exit;
});

add_action('admin_post_gma_desativar_licenca', function() {
    if (!isset($_POST['gma_licenca_nonce']) || 
        !wp_verify_nonce($_POST['gma_licenca_nonce'], 'gma_desativar_licenca')) {
        wp_die('Ação não autorizada');
    }

    $resultado = gma_desativar_licenca();
    
    wp_redirect(add_query_arg(
        array(
            'page' => 'gma-licenca',
            'message' => $resultado['message'],
            'type' => $resultado['success'] ? 'success' : 'error'
        ),
        admin_url('admin.php')
    ));
    exit;
});