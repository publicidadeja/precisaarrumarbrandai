
<?php


if (!gma_verificar_licenca_ativa()) {
    echo '<div class="notice notice-error"><p>Licença inválida ou expirada. Por favor, <a href="' . 
         admin_url('admin.php?page=gma-licenca') . 
         '">ative sua licença</a> para continuar usando o plugin.</p></div>';
    return;
}

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles for both snippets
wp_enqueue_style('gma-admin-style', plugins_url('/assets/css/admin-style.css', dirname(__FILE__)));
wp_enqueue_script('gma-admin-script', plugins_url('/assets/js/admin-script.js', dirname(__FILE__)), array('jquery'), '1.0', true);
wp_enqueue_script('jquery');
wp_enqueue_script('swiper', 'https://unpkg.com/swiper/swiper-bundle.min.js', array(), null, true);
wp_enqueue_script('gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js', array(), null, true);
wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
wp_enqueue_script('gma-script', plugin_dir_url(__FILE__) . '../assets/js/gma-script.js', array('jquery', 'swiper', 'gsap', 'sweetalert2'), '1.0.0', true);
wp_enqueue_style('roboto-font', 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
wp_enqueue_style('swiper-css', 'https://unpkg.com/swiper/swiper-bundle.min.css');
wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

get_header();

$campanha_id = get_query_var('campanha_id');
$campanha = gma_obter_campanha($campanha_id);
$materiais = gma_listar_materiais($campanha_id);

if ($campanha) :
?>

<div class="gma-container">
    <h1 class="gma-title"><?php echo esc_html($campanha->nome); ?></h1>

    <?php if ($materiais) : ?>
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($materiais as $material) : ?>
                    <div class="swiper-slide">
                        <div class="gma-material" data-material-id="<?php echo esc_attr($material->id); ?>">
                            <div class="gma-material-image-container">
                          <?php
$file_extension = strtolower(pathinfo($material->imagem_url, PATHINFO_EXTENSION));
$video_extensions = ['mp4', 'webm', 'ogg', 'mov'];

if (in_array($file_extension, $video_extensions)) : ?>
    <div class="video-wrapper">
        <div class="video-indicator-pulse">
            <i class="fas fa-video"></i>
        </div>
        <video 
            class="gma-material-video" 
            controls="controls"
            preload="metadata"
            playsinline
            webkit-playsinline
        >
            <source src="<?php echo esc_url($material->imagem_url); ?>" type="video/<?php echo $file_extension; ?>">
            Seu navegador não suporta o elemento de vídeo.
        </video>
    </div>
<?php else : ?>
    <img class="gma-material-image lightbox-trigger" src="<?php echo esc_url($material->imagem_url); ?>" alt="Material">
<?php endif; ?>        
                              
                              
                            </div>
                          
                            <div class="gma-material-content">
                                <p class="gma-copy"><?php echo wp_kses_post($material->copy ?? ''); ?></p>
                                <div class="gma-acoes">
                                    <button class="gma-aprovar" data-action="aprovar" <?php echo $material->status_aprovacao === 'aprovado' ? 'disabled' : ''; ?>>
                                        <i class="fas fa-check"></i>
                                        <span>Aprovar</span>
                                    </button>
                                    <button class="gma-reprovar" data-action="reprovar" <?php echo $material->status_aprovacao === 'reprovado' ? 'disabled' : ''; ?>>
                                        <i class="fas fa-times"></i>
                                        <span>Reprovar</span>
                                    </button>
                                    <button class="gma-editar" data-action="editar">
                                        <i class="fas fa-edit"></i>
                                        <span>Editar</span>
                                    </button>
                                </div>
                                <div class="gma-edicao">
                                    <h3>Editar Material</h3>
                                    <textarea class="gma-alteracao-arte" rows="4" placeholder="Descreva as alterações necessárias"></textarea>
                                    <textarea class="gma-copy-edit" rows="4" placeholder="Editar copy"><?php echo esc_textarea($material->copy ?? ''); ?></textarea>
                                    <button class="gma-salvar-edicao" data-material-id="<?php echo esc_attr($material->id); ?>">Salvar</button>
                                    <button class="gma-cancelar-edicao">Cancelar</button>
                                </div>
                            </div>
                            <p class="gma-status status-<?php echo esc_attr($material->status_aprovacao ?? 'pendente'); ?>">
                                <?php echo esc_html(ucfirst($material->status_aprovacao ?? 'Pendente')); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    <?php endif; ?>
</div>

<div id="imageLightbox" class="lightbox">
    <span class="close-lightbox">×</span>
    <img class="lightbox-content" id="lightboxImage" src="" alt="Lightbox Image">
</div>



<style>
/* Variáveis CSS */
:root {
    --primary-color: #6e8efb;
    --secondary-color: #a777e3;
    --success-color: #46b450;
    --error-color: #dc3232;
    --border-radius: 8px;
    --box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Estilos gerais */
.wrap {
    max-width: 1200px;
    margin: 20px auto;
    font-family: 'Roboto', Arial, sans-serif;
}

/* Filtros */
.gma-filter {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}

.gma-filter-select {
    padding: 8px;
    border-radius: var(--border-radius);
    border: 1px solid #ddd;
    min-width: 200px;
}

.gma-filter-input {
    padding: 8px;
    border-radius: var(--border-radius);
    border: 1px solid #ddd;
    min-width: 200px;
}

/* Grid e Cards */
.gma-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.gma-card {
    background: #fff;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.column-header {
    padding: 15px;
    color: white;
    text-align: center;
    font-weight: bold;
    text-transform: uppercase;
}

.approved { background-color: #4CAF50; }
.rejected { background-color: #f44336; }
.pending { background-color: #ff9800; }

.materials-list {
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Material Card */
.material-card {
    background: #fff;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.material-image {
    width: 100%;
    max-width: 300px;
    height: auto;
}

.material-image img,
.material-image video {
    width: 100%;
    height: auto;
    display: block;
}

.material-info {
    padding: 15px;
}

.campaign-type {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 12px;
    color: white;
    margin: 5px 0;
}

.campaign-type.aprovacao {
    background: linear-gradient(135deg, #6e8efb, #4a6cf7);
}

.campaign-type.marketing {
    background: linear-gradient(135deg, #a777e3, #8854d0);
}

.material-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

.button {
    padding: 8px 15px;
    border-radius: var(--border-radius);
    border: none;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.delete-button {
    background-color: #f44336;
}
.campaign-name {
    background: #f5f5f5;
    padding: 10px;
    font-weight: bold;
    text-align: center;
    border-bottom: 1px solid #ddd;
    color: #333;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Responsividade */
@media screen and (max-width: 782px) {
    .gma-filter {
        flex-direction: column;
    }

    .gma-filter-select {
        width: 100%;
    }
}

.video-container {
    position: relative;
    cursor: pointer;
}

.video-thumbnail {
    position: relative;
}

.play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.7);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.video-container video {
    width: 100%;
    height: auto;
    max-width: 100%;
    display: block;
}

.material-media {
    width: 100%;
    border-radius: var(--border-radius);
}

/* Swiper Styles */
.gma-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 15px;
    box-sizing: border-box;
}

.gma-title {
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    text-align: center;
    margin: 20px 0;
    color: #333;
}

.swiper-container {
    width: 100%;
    padding: 20px 0;
    overflow: hidden;
    position: relative;
}

.swiper-slide {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.gma-material {
    width: 100%;
    max-width: 500px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin: 10px;
    position: relative; /* Para posicionar o status */
}

.gma-material-image-container {
    width: 100%;
    position: relative;
    padding-top: 56.25%; /* Aspect ratio 16:9 */
}

.gma-material-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px 12px 0 0;
}

.gma-material-content {
    padding: 20px;
}

.gma-copy {
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 15px;
}

.gma-status {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.8);
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: bold;
    color: #333;
}

.gma-status.status-aprovado {
    background: #2ecc71;
    color: #fff;
}

.gma-status.status-reprovado {
    background: #e74c3c;
    color: #fff;
}

.gma-status.status-pendente {
    background: #f39c12;
    color: #fff;
}

.gma-acoes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.gma-acoes button {
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.gma-aprovar { background-color: #2ecc71; color: white; }
.gma-reprovar { background-color: #e74c3c; color: white; }
.gma-editar { background-color: #3498db; color: white; }

/* Adicione/modifique estes estilos na seção .gma-edicao */
.gma-edicao {
    display: none;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.gma-edicao h3 {
    margin-bottom: 15px;
    font-size: 18px;
    color: #333;
}

.gma-edicao textarea {
    width: 100%;
    min-height: 120px; /* Altura mínima aumentada */
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.5;
    resize: vertical; /* Permite redimensionar verticalmente */
}

.gma-edicao button {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    margin-right: 10px;
    margin-bottom: 10px;
    min-width: 120px;
}

.gma-salvar-edicao {
    background-color: #2ecc71;
    color: white;
}

.gma-cancelar-edicao {
    background-color: #95a5a6;
    color: white;
}

/* Ajustes para mobile */
@media (max-width: 768px) {
    .gma-edicao {
        padding: 15px;
        margin: 10px -15px;
        border-radius: 0;
    }

    .gma-edicao textarea {
        min-height: 150px; /* Altura ainda maior no mobile */
        font-size: 16px; /* Fonte maior para melhor legibilidade */
        padding: 15px;
    }

    .gma-edicao button {
        width: 100%;
        margin-right: 0;
        margin-bottom: 10px;
        padding: 15px;
    }
}
/* Estilos específicos para mobile */
@media (max-width: 768px) {
    .swiper-container {
        padding: 10px 0;
    }

    .gma-material {
        margin: 5px;
    }

    .swiper-slide {
        width: 100% !important; /* Força largura total no mobile */
    }

    .gma-material-content {
        padding: 15px;
    }

    .gma-copy {
        font-size: 14px;
    }

    .gma-acoes {
        grid-template-columns: 1fr; /* Botões empilhados no mobile */
    }

    .gma-acoes button {
        width: 100%;
        margin-bottom: 5px;
    }

    .gma-status {
        top: 15px;
        right: 15px;
    }
}

/* Ajustes do Lightbox */
.lightbox {
    display: none;
    position: fixed;
    z-index: 1000;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    padding: 20px;
    box-sizing: border-box;
}

.lightbox-content {
    max-width: 100%;
    max-height: 90vh;
    width: auto;
    height: auto;
    position: relative;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    object-fit: contain;
}

.close-lightbox {
    position: fixed;
    top: 15px;
    right: 25px;
    color: #fff;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
}

/* Estilos específicos para vídeos */
.gma-material-image-container video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    cursor: pointer;
}

/* Estilo para o botão de play personalizado */
.video-play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 60px;
    height: 60px;
    background-color: rgba(0, 0, 0, 0.7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
}

.video-play-button:before {
    content: '';
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 10px 0 10px 20px;
    border-color: transparent transparent transparent #ffffff;
    margin-left: 5px;
}

/* Classe específica para imagens que podem abrir no lightbox */
img.lightbox-trigger {
    cursor: pointer;
}

/* Responsividade */
@media (max-width: 768px) {
    .lightbox {
        padding: 10px;
    }
    
    .lightbox-content {
        max-height: 80vh;
    }
    
    .close-lightbox {
        top: 10px;
        right: 15px;
        font-size: 24px;
    }
    
    .video-play-button {
        width: 40px;
        height: 40px;
    }
    
    .video-play-button:before {
        border-width: 8px 0 8px 16px;
    }
}

/* Estilo para vídeos em tela cheia */
.video-fullscreen {
    width: 100vw;
    height: 100vh;
    object-fit: contain;
}
/* Estilos para as setas do Swiper */
.swiper-button-next,
.swiper-button-prev {
    top: 50%; /* Posiciona as setas no meio da altura */
    transform: translateY(-50%); /* Centraliza verticalmente */
    z-index: 10; /* Garante que as setas fiquem acima dos botões */
    background-color: rgba(0, 0, 0, 0.5); /* Define a cor de fundo das setas */
    color: white; /* Define a cor do texto das setas */
    padding: 10px; /* Define o espaçamento interno das setas */
    border-radius: 50%; /* Define o formato arredondado das setas */
    cursor: pointer; /* Define o cursor do mouse como ponteiro */
}

/* Posiciona as setas fora do conteúdo */
.swiper-button-next {
    right: 20px; /* Ajusta a distância da borda direita */
}

.swiper-button-prev {
    left: 20px; /* Ajusta a distância da borda esquerda */
}

video.gma-material-image {
    object-fit: contain; /* Adjust as needed for video */
}
  /* Estilos para os botões de ação em mobile */
@media (max-width: 768px) {
    .gma-acoes {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        padding: 15px;
        display: flex;
        justify-content: space-around;
        backdrop-filter: blur(10px);
        z-index: 100;
        border-top: 1px solid rgba(0,0,0,0.1);
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }

    .gma-acoes button {
        min-width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 10px;
        transition: all 0.3s ease;
    }

    .gma-acoes button i {
        font-size: 24px;
        margin-bottom: 5px;
    }

    .gma-acoes button span {
        font-size: 12px;
    }

    /* Cores específicas para cada botão */
    .gma-aprovar {
        background-color: #4CAF50;
        color: white;
    }

    .gma-reprovar {
        background-color: #f44336;
        color: white;
    }

    .gma-editar {
        background-color: #2196F3;
        color: white;
    }

    /* Ajuste do conteúdo principal para não ficar escondido pelos botões fixos */
    .gma-material {
        padding-bottom: 100px;
    }
}
  
  .gma-material-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    cursor: default;
    position: absolute;
    top: 0;
    left: 0;
}

.gma-material-video:hover {
    cursor: default;
}
  
  /* Adicione este código ao seu CSS */
.gma-copy {
    pointer-events: none; /* Impede eventos de clique */
    user-select: text; /* Permite que o texto seja selecionado */
}

.gma-copy-container {
    cursor: default; /* Remove o cursor de pointer se estiver aplicado */
}
  /* Estilos base para o vídeo */
.gma-material-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    position: absolute;
    top: 0;
    left: 0;
    max-width: 100%;
    max-height: 100%;
}

.gma-material-image-container {
    width: 100%;
    position: relative;
    padding-top: 56.25%; /* Mantém proporção 16:9 */
    background: #000; /* Fundo preto para melhor visualização */
}

/* Estilos específicos para mobile */
@media (max-width: 768px) {
    .gma-material-video {
        position: absolute;
        width: 100% !important;
        height: 100% !important;
        top: 0;
        left: 0;
        object-fit: contain;
    }
    
    .gma-material-image-container {
        padding-top: 56.25%; /* Mantém a mesma proporção do desktop */
    }

    video::-webkit-media-controls {
        display: flex !important;
        opacity: 1 !important;
    }
}
  .video-indicator-pulse {
    position: absolute;
    bottom: 10px;
    right: 10px;
    z-index: 2;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 1.5s infinite;
}

.video-indicator-pulse i {
    font-size: 16px;
    color: #fff;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
    }
    
    50% {
        transform: scale(1.1);
        opacity: 0.8;
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
    }
    
    100% {
        transform: scale(1);
        opacity: 1;
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
    }
}

/* Estilos para mobile */
@media (max-width: 768px) {
    .video-indicator-pulse {
        width: 30px;
        height: 30px;
    }

    .video-indicator-pulse i {
        font-size: 14px;
    }
}

/* Esconde o indicador quando em tela cheia */
.video-wrapper:fullscreen .video-indicator-pulse {
    display: none;
}
</style>

<script>
    //Existing Javascript from both snippets combined
    document.addEventListener('DOMContentLoaded', function() {
        

        // Lightbox functionality
const lightbox = document.getElementById('imageLightbox');
const lightboxImage = document.getElementById('lightboxImage');
const lightboxTriggers = document.querySelectorAll('.lightbox-trigger');

lightboxTriggers.forEach(trigger => {
    // Only add click event for elements with both 'lightbox-trigger' class and 'img' tag
    if (trigger.tagName.toLowerCase() === 'img') {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            lightboxImage.src = trigger.src;
            lightbox.style.display = 'block';
        });
    }
});

// Close lightbox when clicking the close button
document.querySelector('.close-lightbox').addEventListener('click', () => {
    lightbox.style.display = 'none';
});

// Close lightbox when clicking outside the image
lightbox.addEventListener('click', (event) => {
    if (event.target === lightbox) {
        lightbox.style.display = 'none';
    }
});

// Close lightbox with ESC key
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && lightbox.style.display === 'block') {
        lightbox.style.display = 'none';
    }
});

        document.querySelector('.close-lightbox').addEventListener('click', () => {
            lightbox.style.display = 'none';
        });

        // Handle Edit/Save/Cancel
        document.querySelectorAll('.gma-editar').forEach(button => {
            button.addEventListener('click', () => {
                const edicao = button.closest('.gma-material').querySelector('.gma-edicao');
                edicao.style.display = 'block';
            });
        });

        document.querySelectorAll('.gma-cancelar-edicao').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.gma-edicao').style.display = 'none';
            });
        });

        document.querySelectorAll('.gma-salvar-edicao').forEach(button => {
            button.addEventListener('click', () => {
                // Add your AJAX save logic here
                const materialId = button.dataset.materialId;
                const alteracaoArte = button.closest('.gma-edicao').querySelector('.gma-alteracao-arte').value;
                const copyEdit = button.closest('.gma-edicao').querySelector('.gma-copy-edit').value;

                // AJAX call to save data
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: `action=gma_salvar_edicao&material_id=${materialId}&alteracao_arte=${encodeURIComponent(alteracaoArte)}©_edit=${encodeURIComponent(copyEdit)}&_wpnonce=${gma_nonce}` // Assuming gma_nonce is available
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI or show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Material atualizado com sucesso!',
                        });
                        button.closest('.gma-edicao').style.display = 'none';
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: data.data,
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Ocorreu um erro ao atualizar o material. Por favor, tente novamente mais tarde.',
                    });
                });
            });
        });
    });
  
  function confirmarAcao(button, acao) {
        const $button = $(button);
        const $material = $button.closest('.gma-material');
        const materialId = $material.data('material-id');
        
        let titulo, texto, icone;
        
        switch(acao) {
            case 'aprovar':
                titulo = 'Confirmar Aprovação';
                texto = 'Deseja realmente aprovar este material?';
                icone = 'success';
                break;
            case 'reprovar':
                titulo = 'Confirmar Reprovação';
                texto = 'Deseja realmente reprovar este material?';
                icone = 'warning';
                break;
            case 'editar':
                titulo = 'Confirmar Edição';
                texto = 'Deseja editar este material?';
                icone = 'info';
                break;
        }

        Swal.fire({
            title: titulo,
            text: texto,
            icon: icone,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                if (acao === 'editar') {
                    const $edicao = $material.find('.gma-edicao');
                    $edicao.slideDown(300);
                } else {
                    executarAcao($material, materialId, acao);
                }
            }
        });
    }

    // Função para executar a ação após confirmação
    function executarAcao($material, materialId, acao) {
        $.ajax({
            url: gmaAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'gma_' + acao + '_material',
                material_id: materialId,
                nonce: gmaAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Material ' + (acao === 'aprovar' ? 'aprovado' : 'reprovado') + ' com sucesso!',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    $material.removeClass('status-aprovado status-reprovado status-pendente')
                            .addClass('status-' + acao);
                    $material.find('.gma-status')
                            .text(acao.charAt(0).toUpperCase() + acao.slice(1));
                    
                    $material.find('.gma-' + acao).prop('disabled', true)
                            .siblings().prop('disabled', false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: response.data.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao processar a solicitação. Tente novamente.'
                });
            }
        });
    }

    // Event listeners para os botões
    $(document).on('click', '.gma-aprovar', function(e) {
        e.preventDefault();
        confirmarAcao(this, 'aprovar');
    });

    $(document).on('click', '.gma-reprovar', function(e) {
        e.preventDefault();
        confirmarAcao(this, 'reprovar');
    });

    $(document).on('click', '.gma-editar', function(e) {
        e.preventDefault();
        confirmarAcao(this, 'editar');
    });

    // Confirmação para salvar edições
    $(document).on('click', '.gma-salvar-edicao', function(e) {
        e.preventDefault();
        const $material = $(this).closest('.gma-material');
        const materialId = $material.data('material-id');
        const alteracaoArte = $material.find('.gma-alteracao-arte').val();
        const novaCopy = $material.find('.gma-copy-edit').val();

        Swal.fire({
            title: 'Confirmar Alterações',
            text: 'Deseja salvar as alterações realizadas?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: gmaAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gma_editar_material',
                        material_id: materialId,
                        alteracao_arte: alteracaoArte,
                        nova_copy: novaCopy,
                        nonce: gmaAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Alterações salvas com sucesso!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            $material.find('.gma-edicao').slideUp(300);
                            $material.find('.gma-copy').text(novaCopy);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: response.data.message
                            });
                        }
                    }
                });
            }
        });
    });
});
  document.addEventListener('DOMContentLoaded', function() {
    // Função para lidar com cliques em vídeos
    const videos = document.querySelectorAll('.gma-material-image-container video');
    
    videos.forEach(video => {
        // Criar botão de play personalizado
        const playButton = document.createElement('div');
        playButton.className = 'video-play-button';
        video.parentElement.appendChild(playButton);
        
        // Mostrar/ocultar botão de play
        video.addEventListener('play', () => {
            playButton.style.display = 'none';
        });
        
        video.addEventListener('pause', () => {
            playButton.style.display = 'flex';
        });
        
        // Lidar com clique no botão de play
        playButton.addEventListener('click', () => {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        });
        
        // Lidar com clique no vídeo
        video.addEventListener('click', () => {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        });
        
        // Habilitar controles nativos em tela cheia
        video.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                video.controls = true;
                video.classList.add('video-fullscreen');
            } else {
                video.controls = false;
                video.classList.remove('video-fullscreen');
            }
        });
        
        // Duplo clique para tela cheia
        video.addEventListener('dblclick', () => {
            if (!document.fullscreenElement) {
                video.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });
    });

    // Lightbox apenas para imagens
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxTriggers = document.querySelectorAll('.lightbox-trigger');

    lightboxTriggers.forEach(trigger => {
        if (trigger.tagName.toLowerCase() === 'img') {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                lightboxImage.src = trigger.src;
                lightbox.style.display = 'block';
            });
        }
    });

    // Fechar lightbox
    document.querySelector('.close-lightbox').addEventListener('click', () => {
        lightbox.style.display = 'none';
    });

    // Fechar lightbox clicando fora
    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox) {
            lightbox.style.display = 'none';
        }
    });

    // Fechar com ESC
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && lightbox.style.display === 'block') {
            lightbox.style.display = 'none';
        }
    });
});
 document.addEventListener('DOMContentLoaded', function() {
    const videos = document.querySelectorAll('.gma-material-video');
    
    videos.forEach(video => {
        // Configurações iniciais
        video.controls = true;
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.setAttribute('x-webkit-airplay', 'allow');
        
        // Força recarregamento dos controles
        video.addEventListener('loadedmetadata', function() {
            if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
                // Força a exibição dos controles em dispositivos móveis
                video.removeAttribute('controls');
                setTimeout(() => {
                    video.setAttribute('controls', 'controls');
                }, 100);
            }
        });

        // Garante que os controles permaneçam visíveis
        video.addEventListener('play', function() {
            this.controls = true;
        });

        // Trata o clique no vídeo
        video.addEventListener('click', function(e) {
            if (this.paused) {
                this.play();
            } else {
                // Evita pausar se o clique foi nos controles
                if (e.target === this) {
                    this.pause();
                }
            }
        });
    });
});
  
</script>


<?php
endif;
get_footer();

?>

