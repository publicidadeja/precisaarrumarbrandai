<?php 
// Adicione no início de cada arquivo de template (páginas admin)

if (!gma_verificar_licenca_ativa()) {
    echo '<div class="notice notice-error"><p>Licença inválida ou expirada. Por favor, <a href="' . 
         admin_url('admin.php?page=gma-licenca') . 
         '">ative sua licença</a> para continuar usando o plugin.</p></div>';
    return;
}
if (!defined('ABSPATH')) exit;

// Carrega o Media Uploader
wp_enqueue_media();

// Localiza os scripts para AJAX
wp_localize_script('jquery', 'gma_ajax', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('gma_copy_suggestions')
));
?>

<div class="gma-create-wrap">
    <div class="gma-create-container">
        <h1 class="gma-create-title">Criar Novo Material</h1>

        <div class="gma-create-card">
            <form method="post" class="gma-create-form" id="gma-material-form">
                <?php wp_nonce_field('gma_novo_material', 'gma_novo_material_nonce'); ?>
                
                <div class="gma-form-grid">
                    <div class="gma-form-group">
                        <label for="campanha_id">
                            <i class="dashicons dashicons-megaphone"></i> Campanha
                        </label>
                        <select name="campanha_id" id="campanha_id" required>
                            <option value="">Selecione uma campanha</option>
                            <?php 
                            $campanhas = gma_listar_campanhas();
                            foreach ($campanhas as $campanha): 
                                $tipo = esc_attr($campanha->tipo_campanha);
                            ?>
                                <option value="<?php echo esc_attr($campanha->id); ?>" 
                                        data-tipo="<?php echo $tipo; ?>">
                                    <?php echo esc_html($campanha->nome); ?> 
                                    (<?php echo ucfirst($tipo); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="gma-form-group">
                        <label for="gma-media-upload">
                            <i class="dashicons dashicons-format-image"></i> Mídia
                        </label>
                        <div class="gma-upload-container">
                            <input type="text" name="imagem_url" id="gma-imagem-url" 
                                   class="gma-input" required readonly>
                            <input type="hidden" name="arquivo_id" id="gma-arquivo-id">
                            <button type="button" id="gma-upload-btn" class="gma-button secondary">
                                <i class="dashicons dashicons-upload"></i> Selecionar
                            </button>
                        </div>
                        
                        <div class="gma-preview-section" id="gma-preview-section" style="display: none;">
                            <h3 class="gma-preview-title">
                                <i class="dashicons dashicons-visibility"></i> Preview do Material
                            </h3>
                            <div class="gma-preview-container">
                                <div id="gma-media-preview" class="gma-media-preview-large"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="gma-form-group">
                        <label for="tipo_midia">
                            <i class="dashicons dashicons-media-default"></i> Tipo de Mídia
                        </label>
                        <select name="tipo_midia" id="tipo_midia" required>
                            <option value="imagem">Imagem</option>
                            <option value="video">Vídeo</option>
                        </select>
                    </div>

                    <div class="gma-form-group full-width">
                        <label for="copy">
                            <i class="dashicons dashicons-editor-paste-text"></i> Copy
                        </label>
                        <textarea name="copy" id="copy" rows="5" required></textarea>
                        <div class="gma-character-count">
                            <span id="char-count">0</span> caracteres
                        </div>
                        <button type="button" id="get-suggestions" class="gma-button secondary">
                            <i class="dashicons dashicons-admin-customizer"></i> 
                            <span class="button-text">Obter Sugestões AI</span>
                        </button>
                        <div id="suggestions-container" style="display: none;">
                            <h3>Sugestões da IA</h3>
                            <div id="suggestions-content"></div>
                        </div>
                    </div>

                    <div class="gma-form-group full-width" id="canva-group" style="display: none;">
                        <label for="link_canva">
                            <i class="dashicons dashicons-art"></i> Link do Canva
                        </label>
                        <input type="url" name="link_canva" id="link_canva" 
                               class="gma-input" placeholder="https://www.canva.com/...">
                    </div>
                </div>

                <div class="gma-form-actions">
                    <button type="submit" name="criar_material" class="gma-button primary">
                        <i class="dashicons dashicons-saved"></i> Criar Material
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=gma-materiais'); ?>" 
                       class="gma-button secondary">
                        <i class="dashicons dashicons-arrow-left-alt"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4a90e2;
    --secondary-color: #2ecc71;
    --danger-color: #e74c3c;
    --text-color: #2c3e50;
    --background-color: #f5f6fa;
    --card-background: #ffffff;
    --border-radius: 10px;
    --transition: all 0.3s ease;
}

.gma-create-wrap {
    padding: 20px;
    background: var(--background-color);
    min-height: 100vh;
}

.gma-create-container {
    max-width: 800px;
    margin: 0 auto;
}

.gma-create-title {
    font-size: 2.5em;
    color: var(--text-color);
    text-align: center;
    margin-bottom: 30px;
    font-weight: 700;
}

.gma-create-card {
    background: var(--card-background);
    border-radius: var(--border-radius);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 30px;
    animation: slideIn 0.5s ease;
}

.gma-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.gma-form-group {
    margin-bottom: 20px;
}

.gma-form-group.full-width {
    grid-column: 1 / -1;
}

.gma-form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-color);
}

.gma-input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e1e1;
    border-radius: var(--border-radius);
    font-size: 1em;
    transition: var(--transition);
}

.gma-input:focus, select:focus, textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
}

.gma-upload-container {
    display: flex;
    gap: 10px;
}

.gma-preview-section {
    margin-top: 20px;
    background: var(--card-background);
    border-radius: var(--border-radius);
    padding: 20px;
}

.gma-preview-container {
    background: var(--background-color);
    border-radius: var(--border-radius);
    padding: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}

.gma-media-preview-large {
    max-width: 100%;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.gma-media-preview-large img,
.gma-media-preview-large video {
    max-width: 100%;
    height: auto;
    display: block;
}

.gma-character-count {
    text-align: right;
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.gma-button {
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
    text-decoration: none;
}

.gma-button.primary {
    background: var(--primary-color);
    color: white;
}

.gma-button.secondary {
    background: var(--secondary-color);
    color: white;
}

.gma-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.gma-button:disabled {
    background-color: #95a5a6;
    cursor: not-allowed;
    transform: none;
}

.gma-form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    justify-content: flex-end;
}

#suggestions-container {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
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
    .gma-form-grid {
        grid-template-columns: 1fr;
    }
    
    .gma-form-actions {
        flex-direction: column;
    }
    
    .gma-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Variável global para o media uploader
    var custom_uploader;

    // Controle de exibição dos campos baseado no tipo de campanha
    $('#campanha_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var tipoCampanha = selectedOption.data('tipo');
        
        if (tipoCampanha === 'marketing') {
            $('#canva-group').show();
        } else {
            $('#canva-group').hide();
            $('#link_canva').val('');
        }
    });

    // Upload de mídia
    $('#gma-upload-btn').click(function(e) {
        e.preventDefault();
        
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
        
        custom_uploader = wp.media({
            title: 'Selecionar Mídia',
            button: {
                text: 'Usar esta mídia'
            },
            multiple: false,
            library: {
                type: $('#tipo_midia').val() === 'imagem' ? 'image' : 'video'
            }
        });

        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#gma-imagem-url').val(attachment.url);
            $('#gma-arquivo-id').val(attachment.id);
            
            var previewHtml = '';
            if (attachment.type === 'image') {
                previewHtml = `<img src="${attachment.url}" alt="Preview da Imagem">`;
            } else if (attachment.type === 'video') {
                previewHtml = `<video src="${attachment.url}" controls></video>`;
            }
            
            $('#gma-media-preview').html(previewHtml);
            $('#gma-preview-section').fadeIn(300);
        });

        custom_uploader.open();
    });

    // Resetar o uploader quando mudar o tipo de mídia
    $('#tipo_midia').on('change', function() {
        custom_uploader = null;
        $('#gma-media-preview').empty();
        $('#gma-preview-section').hide();
        $('#gma-imagem-url').val('');
        $('#gma-arquivo-id').val('');
    });

    // Contador de caracteres
    $('#copy').on('input', function() {
        $('#char-count').text($(this).val().length);
    });

    // Sugestões AI
    $('#get-suggestions').on('click', function() {
    const button = $(this);
    const buttonText = button.find('.button-text');
    const copy = $('#copy').val();
    
    if (!copy) {
        alert('Por favor, insira algum texto primeiro.');
        return;
    }
    
    button.prop('disabled', true);
    buttonText.html('Obtendo sugestões... <i class="dashicons dashicons-update gma-loading"></i>');
    
    $.ajax({
        url: ajaxurl,  // Mudança aqui
        type: 'POST',
        data: {
            action: 'gma_get_copy_suggestions',
            nonce: '<?php echo wp_create_nonce("gma_copy_suggestions"); ?>', // Mudança aqui
            copy: copy
        },
        success: function(response) {
            if (response.success) {
                $('#suggestions-content').html(response.data.suggestions);
                $('#suggestions-container').slideDown();
            } else {
                alert('Erro: ' + (response.data?.message || 'Falha ao obter sugestões'));
            }
        },
        error: function() {
            alert('Erro ao conectar com o servidor. Tente novamente.');
        },
        complete: function() {
            button.prop('disabled', false);
            buttonText.html('<i class="dashicons dashicons-admin-customizer"></i> Obter Sugestões AI');
        }
    });
});

    // Validação do formulário
    $('#gma-material-form').on('submit', function(e) {
        var isValid = true;
        
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
        }
    });
});
</script>

