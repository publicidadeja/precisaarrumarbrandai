<?php
class GMA_License_API {
    private $api_url = 'https://licenca.publicidadeja.com.br/api';
    private $api_key;

    public function __construct() {
        $this->api_key = get_option('gma_api_key');
    }

    public function activate_license($license_key) {
        return $this->make_request('activate', array(
            'license_key' => $license_key,
            'site_url' => get_site_url()
        ));
    }

    public function deactivate_license($license_key) {
        return $this->make_request('deactivate', array(
            'license_key' => $license_key,
            'site_url' => get_site_url()
        ));
    }

    private function make_request($endpoint, $body) {
        $response = wp_remote_post($this->api_url . '/' . $endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response));
    }
}