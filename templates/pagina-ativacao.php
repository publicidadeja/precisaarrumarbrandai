
<?php
if (!defined('ABSPATH')) exit;

// Verifica se o usuário tem permissão
if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.'));
}

$licenca_atual = get_option('gma_license_key');
$licenca_ativa = gma_verificar_licenca_ativa();
$mensagem_status = '';
$tipo_mensagem = '';

// Handler de ativação
if (isset($_POST['ativar_licenca'])) {
    if (!wp_verify_nonce($_POST['gma_licenca_nonce'], 'gma_ativar_licenca')) {
        wp_die('Ação não autorizada');
    }

    $nova_licenca = sanitize_text_field($_POST['codigo_licenca']);
    
    // Dados para enviar à API
    $api_data = [
        'headers' => [
            'X-API-KEY' => '@Speaker120123',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'body' => [
            'codigo_licenca' => $nova_licenca,
            'site_url' => $_SERVER['HTTP_HOST'],
            'action' => 'verificar',
            'produto' => 'brandaipro'
        ],
        'timeout' => 15
    ];

    // Log para debug
    error_log('Enviando requisição para API de licença:');
    error_log(print_r($api_data, true));

    // Requisição para o sistema de licenças
    $response = wp_remote_post('https://licenca.publicidadeja.com.br/api/', $api_data);

    if (!is_wp_error($response)) {
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log para debug
        error_log('Resposta da API de licença:');
        error_log('Código HTTP: ' . $response_code);
        error_log('Corpo da resposta: ' . $body);

        $body_data = json_decode($body);
        
        if ($response_code === 200 && isset($body_data->success) && $body_data->success && isset($body_data->valid) && $body_data->valid) {
            update_option('gma_license_key', $nova_licenca);
            if (isset($body_data->expiration)) {
                update_option('gma_license_expiration', $body_data->expiration);
            }
            $mensagem_status = 'Licença ativada com sucesso!';
            $tipo_mensagem = 'success';
            $licenca_atual = $nova_licenca;
            $licenca_ativa = true;
        } else {
            $mensagem_status = isset($body_data->error) ? $body_data->error : 'Erro na validação da licença: ' . $body;
            $tipo_mensagem = 'error';
            error_log('Erro na validação da licença: ' . $body);
        }
    } else {
        $mensagem_status = 'Erro ao conectar com o servidor de licenças: ' . $response->get_error_message();
        $tipo_mensagem = 'error';
        error_log('Erro na requisição: ' . $response->get_error_message());
    }
}

// Handler de desativação
if (isset($_POST['action']) && $_POST['action'] === 'gma_desativar_licenca') {
    if (!wp_verify_nonce($_POST['gma_licenca_nonce'], 'gma_desativar_licenca')) {
        wp_die('Ação não autorizada');
    }

    delete_option('gma_license_key');
    delete_option('gma_license_expiration');
    $mensagem_status = 'Licença desativada com sucesso!';
    $tipo_mensagem = 'success';
    $licenca_atual = '';
    $licenca_ativa = false;
}
?>

<div class="wrap gma-activation-wrap">
    <div class="gma-activation-container">
        <div class="gma-activation-header">
            <img src="https://plugins.publicidadeja.com.br/wp-content/uploads/2024/11/BrandAI-Logo-300x300.png" alt="BrandAI Logo" class="gma-logo">
            <h1>Ativação do BrandAI Pro</h1>
        </div>

        <?php if (!empty($mensagem_status)): ?>
            <div class="gma-notice <?php echo $tipo_mensagem === 'success' ? 'gma-notice-success' : 'gma-notice-error'; ?>">
                <p><?php echo esc_html($mensagem_status); ?></p>
            </div>
        <?php endif; ?>

        <div class="gma-status-card">
            <?php if ($licenca_atual && $licenca_ativa): ?>
                <div class="gma-status-active">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div class="gma-status-info">
                        <h3>Licença Ativa</h3>
                        <p>Seu BrandAI Pro está ativado e funcionando!</p>
                        <?php 
                        $expiration = get_option('gma_license_expiration');
                        if ($expiration): 
                        ?>
                            <p class="gma-expiration">Válido até: <?php echo date('d/m/Y', strtotime($expiration)); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="gma-status-inactive">
                    <span class="dashicons dashicons-warning"></span>
                    <div class="gma-status-info">
                        <h3>Licença Inativa</h3>
                        <p>Ative sua licença para usar todos os recursos do BrandAI Pro.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="gma-activation-card">
            <form method="post" class="gma-activation-form">
                <?php wp_nonce_field('gma_ativar_licenca', 'gma_licenca_nonce'); ?>
                
                <div class="gma-form-group">
                    <label for="codigo_licenca">
                        <span class="dashicons dashicons-key"></span>
                        Código de Licença
                    </label>
                    <input type="text" 
                           name="codigo_licenca" 
                           id="codigo_licenca" 
                           value="<?php echo esc_attr($licenca_atual); ?>"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           required>
                    <p class="description">Digite o código de licença recebido após a compra.</p>
                </div>

                <?php if (!$licenca_ativa): ?>
                    <button type="submit" name="ativar_licenca" class="gma-button primary">
                        <span class="dashicons dashicons-yes"></span>
                        Ativar Licença
                    </button>
                <?php else: ?>
                    <div class="gma-button-group">
                        <button type="submit" name="ativar_licenca" class="gma-button secondary">
                            <span class="dashicons dashicons-update"></span>
                            Atualizar Licença
                        </button>
                        
                        <form method="post" class="gma-deactivate-form">
                            <?php wp_nonce_field('gma_desativar_licenca', 'gma_licenca_nonce'); ?>
                            <input type="hidden" name="action" value="gma_desativar_licenca">
                            <button type="submit" class="gma-button danger" 
                                    onclick="return confirm('Tem certeza que deseja desativar a licença?');">
                                <span class="dashicons dashicons-no-alt"></span>
                                Desativar Licença
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="gma-help-card">
            <h3>Precisa de ajuda?</h3>
            <p>Se você está tendo problemas com sua licença ou precisa de suporte, entre em contato:</p>
            <a href="https://publicidadeja.com.br/suporte" target="_blank" class="gma-button outline">
                <span class="dashicons dashicons-admin-users"></span>
                Contatar Suporte
            </a>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4A90E2;
    --secondary-color: #2ECC71;
    --danger-color: #E74C3C;
    --warning-color: #F1C40F;
    --text-color: #2C3E50;
    --bg-color: #F5F6FA;
    --card-bg: #FFFFFF;
    --border-radius: 10px;
    --transition: all 0.3s ease;
}

.gma-activation-wrap {
    background: var(--bg-color);
    padding: 30px;
    margin: 20px;
    border-radius: var(--border-radius);
}

.gma-activation-container {
    max-width: 800px;
    margin: 0 auto;
}

.gma-activation-header {
    text-align: center;
    margin-bottom: 40px;
}

.gma-logo {
    max-width: 200px;
    margin-bottom: 20px;
}

.gma-activation-header h1 {
    font-size: 2.5em;
    color: var(--text-color);
    margin: 0;
}

.gma-notice {
    padding: 15px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    animation: slideIn 0.5s ease;
}

.gma-notice-success {
    background: #D4EDDA;
    color: #155724;
    border: 1px solid #C3E6CB;
}

.gma-notice-error {
    background: #F8D7DA;
    color: #721C24;
    border: 1px solid #F5C6CB;
}

.gma-status-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.gma-status-active,
.gma-status-inactive {
    display: flex;
    align-items: center;
    gap: 20px;
}

.gma-status-active .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: var(--secondary-color);
}

.gma-status-inactive .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: var(--warning-color);
}

.gma-status-info h3 {
    margin: 0;
    font-size: 1.5em;
    color: var(--text-color);
}

.gma-status-info p {
    margin: 5px 0;
    color: #666;
}

.gma-expiration {
    font-weight: bold;
    color: var(--primary-color);
}

.gma-activation-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.gma-form-group {
    margin-bottom: 20px;
}

.gma-form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--text-color);
}

.gma-form-group input {
    width: 100%;
    padding: 12px;
    border: 2px solid #E1E1E1;
    border-radius: var(--border-radius);
    font-size: 1.2em;
    transition: var(--transition);
    text-align: center;
    letter-spacing: 2px;
}

.gma-form-group input:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(74,144,226,0.2);
}

.description {
    color: #666;
    font-size: 0.9em;
    margin-top: 5px;
}

.gma-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
}

.gma-button.primary {
    background: var(--primary-color);
    color: white;
    width: 100%;
}

.gma-button.secondary {
    background: var(--secondary-color);
    color: white;
}

.gma-button.danger {
    background: var(--danger-color);
    color: white;
}

.gma-button.outline {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.gma-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.gma-button-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.gma-help-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 30px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.gma-help-card h3 {
    color: var(--text-color);
    margin-top: 0;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .gma-activation-wrap {
        padding: 15px;
        margin: 10px;
    }

    .gma-button-group {
        flex-direction: column;
    }

    .gma-button {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Formata o input da licença enquanto o usuário digita
    const licenseInput = document.getElementById('codigo_licenca');
    if (licenseInput) {
        licenseInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
            let formatted = '';
            
            for (let i = 0; i < value.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += value[i];
            }
            
            e.target.value = formatted;
        });
    }

    // Animação dos notices
    const notices = document.querySelectorAll('.gma-notice');
    notices.forEach(notice => {
        notice.style.opacity = '0';
        setTimeout(() => {
            notice.style.opacity = '1';
            notice.style.transform = 'translateY(0)';
        }, 100);
    });

    // Validação do formulário antes do envio
    const activationForm = document.querySelector('.gma-activation-form');
    if (activationForm) {
        activationForm.addEventListener('submit', function(e) {
            const licenseKey = licenseInput.value.replace(/-/g, '');
            if (licenseKey.length !== 16) {
                e.preventDefault();
                alert('Por favor, insira um código de licença válido (16 caracteres).');
                return false;
            }
        });
    }

    // Efeito de hover nos botões
    const buttons = document.querySelectorAll('.gma-button');
    buttons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-2px)';
        });
        button.addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Função para copiar código de licença
    function setupCopyLicense() {
        const licenseDisplay = document.querySelector('.gma-status-info p:nth-child(2)');
        if (licenseDisplay && licenseDisplay.textContent.includes('Código:')) {
            licenseDisplay.style.cursor = 'pointer';
            licenseDisplay.title = 'Clique para copiar';
            
            licenseDisplay.addEventListener('click', function() {
                const licenseText = this.textContent.replace('Código: ', '').trim();
                navigator.clipboard.writeText(licenseText).then(() => {
                    // Feedback visual
                    const originalText = this.textContent;
                    this.textContent = '✓ Código copiado!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Erro ao copiar:', err);
                });
            });
        }
    }
    setupCopyLicense();

    // Função para mostrar/esconder senha
    function setupPasswordToggle() {
        const licenseInput = document.getElementById('codigo_licenca');
        if (licenseInput) {
            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = 'gma-toggle-visibility';
            toggleButton.innerHTML = '<span class="dashicons dashicons-visibility"></span>';
            
            licenseInput.parentNode.style.position = 'relative';
            licenseInput.parentNode.appendChild(toggleButton);

            toggleButton.addEventListener('click', function() {
                const type = licenseInput.getAttribute('type') === 'password' ? 'text' : 'password';
                licenseInput.setAttribute('type', type);
                this.innerHTML = `<span class="dashicons dashicons-${type === 'password' ? 'visibility' : 'hidden'}"></span>`;
            });
        }
    }
    setupPasswordToggle();

    // Adiciona contagem regressiva para licenças próximas do vencimento
    function setupExpirationCountdown() {
        const expirationElement = document.querySelector('.gma-expiration');
        if (expirationElement) {
            const expirationDate = new Date(expirationElement.textContent.replace('Válido até: ', '').split('/').reverse().join('-'));
            const now = new Date();
            const daysUntilExpiration = Math.ceil((expirationDate - now) / (1000 * 60 * 60 * 24));

            if (daysUntilExpiration <= 30) {
                const countdownElement = document.createElement('div');
                countdownElement.className = 'gma-countdown';
                countdownElement.textContent = `⚠️ Sua licença expira em ${daysUntilExpiration} dias`;
                expirationElement.insertAdjacentElement('afterend', countdownElement);
            }
        }
    }
    setupExpirationCountdown();

    // Adiciona feedback visual ao processar o formulário
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="dashicons dashicons-update gma-spin"></span> Processando...';
                
                // Restaura o botão se o envio demorar muito
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }, 10000);
            }
        });
    });

    // Adiciona tooltip de ajuda
    const helpIcon = document.createElement('span');
    helpIcon.className = 'dashicons dashicons-editor-help gma-help-icon';
    helpIcon.title = 'O código de licença deve ter 16 caracteres no formato XXXX-XXXX-XXXX-XXXX';
    document.querySelector('label[for="codigo_licenca"]').appendChild(helpIcon);

    // Adiciona efeito de shake em caso de erro
    const errorNotices = document.querySelectorAll('.gma-notice-error');
    errorNotices.forEach(notice => {
        notice.classList.add('gma-shake');
        setTimeout(() => {
            notice.classList.remove('gma-shake');
        }, 500);
    });
});

// Adiciona os estilos CSS necessários para as novas funcionalidades
const styleSheet = document.createElement('style');
styleSheet.textContent = `
    .gma-countdown {
        color: #e74c3c;
        margin-top: 5px;
        font-weight: bold;
        animation: blink 2s infinite;
    }

    .gma-help-icon {
        margin-left: 5px;
        color: #666;
        cursor: help;
    }

    .gma-toggle-visibility {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
    }

    .gma-spin {
        animation: spin 1s linear infinite;
    }

    .gma-shake {
        animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    @keyframes shake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-4px); }
        40%, 60% { transform: translateX(4px); }
    }
`;
document.head.appendChild(styleSheet);