<?php
if (!defined('ABSPATH')) exit;

class BrandAI_License {
    private $api_url = 'https://licenca.publicidadeja.com.br/api/';
    
    public function __construct() {
        add_action('admin_init', [$this, 'schedule_license_check']);
        add_action('brandai_daily_license_check', [$this, 'verify_license']);
    }

    public function verify_license() {
        $license_key = get_option('brandai_license_key');
        
        if (!$license_key) {
            update_option('brandai_license_status', 'invalid');
            return false;
        }

        $response = wp_remote_post($this->api_url, [
            'body' => json_encode([
                'license_key' => $license_key,
                'domain' => get_site_url()
            ]),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            update_option('brandai_license_status', 'invalid');
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (!empty($body->valid)) {
            update_option('brandai_license_status', 'valid');
            update_option('brandai_license_expiry', $body->expiry_date);
            return true;
        }

        update_option('brandai_license_status', 'invalid');
        return false;
    }

    public function schedule_license_check() {
        if (!wp_next_scheduled('brandai_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'brandai_daily_license_check');
        }
    }

    public function is_license_valid() {
        $status = get_option('brandai_license_status');
        $expiry = get_option('brandai_license_expiry');

        if ($status !== 'valid') {
            return false;
        }

        if ($expiry && strtotime($expiry) < time()) {
            update_option('brandai_license_status', 'expired');
            return false;
        }

        return true;
    }
}

// Inicializar o gerenciador de licenças
function brandai_init_license() {
    global $brandai_license;
    $brandai_license = new BrandAI_License();
}
add_action('plugins_loaded', 'brandai_init_license');

// Função auxiliar para verificar se a licença está ativa
function brandai_is_license_active() {
    global $brandai_license;
    return $brandai_license->is_license_valid();
}