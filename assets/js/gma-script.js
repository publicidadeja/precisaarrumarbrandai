(function($) {
    $(document).ready(function() {
        if (typeof $ === 'undefined' || typeof Swiper === 'undefined' || typeof gsap === 'undefined' || typeof Swal === 'undefined') {
            console.error('jQuery, Swiper, GSAP ou SweetAlert2 não estão carregados corretamente.');
            return;
        }

        if (typeof gmaAjax === 'undefined') {
            console.error('O objeto gmaAjax não está definido. Verifique se wp_localize_script está sendo chamado corretamente.');
            return;
        }

        var swiper = new Swiper('.swiper-container', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            coverflowEffect: {
                rotate: 50,
                stretch: 0,
                depth: 100,
                modifier: 1,
                slideShadows: true,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });

        // Função para lidar com cliques/toques nos botões
        function handleButtonClick(event) {
            var $button = $(this);
            var $material = $button.closest('.gma-material');
            var materialId = $material.data('material-id');
            var acao = $button.hasClass('gma-aprovar') ? 'aprovar' : 'reprovar';
            
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

                        $material.removeClass('status-aprovado status-reprovado status-pendente').addClass('status-' + acao);
                        $material.find('.gma-status').text('Status: ' + acao.charAt(0).toUpperCase() + acao.slice(1));
                        $button.prop('disabled', true).siblings().prop('disabled', false);
                        
                        gsap.to($material, {
                            duration: 0.3,
                            scale: 1.05,
                            yoyo: true,
                            repeat: 1,
                            ease: "power2.inOut",
                            onComplete: function() {
                                swiper.slideNext();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Erro: ' + response.data.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Erro ao processar a solicitação. Por favor, tente novamente.'
                    });
                }
            });
        }

        // Atribuir a função aos eventos 'click' e 'touchstart' usando delegação
        $(document).on('click touchstart', '.gma-aprovar, .gma-reprovar', handleButtonClick);

        $(document).on('click touchstart', '.gma-editar', function(e) {
    e.preventDefault(); // Previne propagação do evento
    e.stopPropagation(); // Para a propagação do evento
    
    var $material = $(this).closest('.gma-material');
    var $edicao = $material.find('.gma-edicao');
    
    $edicao.slideDown(300); // Força abertura ao invés de toggle
});

        $(document).on('click touchstart', '.gma-cancelar-edicao', function() {
            var $material = $(this).closest('.gma-material');
            var $edicao = $material.find('.gma-edicao');
            
            $edicao.slideUp(300);
        });

        $(document).on('click touchstart', '.gma-salvar-edicao', function() {
            var $material = $(this).closest('.gma-material');
            var materialId = $material.data('material-id');
            var alteracaoArte = $material.find('.gma-alteracao-arte').val();
            var novaCopy = $material.find('.gma-copy-edit').val();
            
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
                            text: 'Material editado com sucesso!',
                            showConfirmButton: false,
                            timer: 1500
                        });

                        $material.find('.gma-edicao').slideUp(300);
                        $material.find('.gma-copy').text(novaCopy);
                        $material.removeClass('status-aprovado status-reprovado status-pendente').addClass('status-pendente');
                        $material.find('.gma-status').text('Status: Pendente');
                        $material.find('.gma-aprovar, .gma-reprovar').prop('disabled', false);
                        
                        gsap.from($material.find('.gma-copy'), {
                            duration: 0.5,
                            opacity: 0,
                            y: 10,
                            ease: "power2.out"
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Erro: ' + response.data.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Erro ao processar a solicitação. Por favor, tente novamente.'
                    });
                }
            });
        });

        // Abrir o lightbox ao clicar na imagem
        // Modifique o event listener do lightbox
$(document).on('click touchstart', '.lightbox-trigger', function(e) {
    if (!$(this).is('img')) return; // Só continua se for uma imagem
    e.preventDefault();
    e.stopPropagation();
    var imageUrl = $(this).attr('src');
    $('#lightboxImage').attr('src', imageUrl);
    $('#imageLightbox').fadeIn('fast');
});

        // Fechar o lightbox ao clicar no botão de fechar ou fora da imagem
        $(document).on('click', '.close-lightbox, .lightbox', function() {
            $('#imageLightbox').fadeOut('fast');
        });

        // Adicionar evento de redimensionamento da janela
        $(window).on('resize', function() {
            swiper.update();
        });

        // Desabilitar o swipe quando estiver editando
        $(document).on('focus', '.gma-alteracao-arte, .gma-copy-edit', function() {
            swiper.allowTouchMove = false;
        });

        $(document).on('blur', '.gma-alteracao-arte, .gma-copy-edit', function() {
            swiper.allowTouchMove = true;
        });
    });
})(jQuery);
// Adicione este código no gma-script.js

$(document).ready(function() {
    let isDragging = false;
    let startX, startY;
    
    // Detectar início do toque/arraste
    $('.lightbox-trigger').on('mousedown touchstart', function(e) {
        isDragging = false;
        if (e.type === 'touchstart') {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        } else {
            startX = e.clientX;
            startY = e.clientY;
        }
    });

    // Detectar movimento
    $('.lightbox-trigger').on('mousemove touchmove', function(e) {
        if (e.type === 'touchmove') {
            let moveX = Math.abs(e.touches[0].clientX - startX);
            let moveY = Math.abs(e.touches[0].clientY - startY);
            if (moveX > 10 || moveY > 10) {
                isDragging = true;
            }
        } else {
            isDragging = true;
        }
    });

    // Abrir lightbox apenas se não estiver arrastando
    $('.lightbox-trigger').on('mouseup touchend', function(e) {
        if (!isDragging) {
            // Seu código para abrir o lightbox aqui
            const imageUrl = $(this).attr('src');
            Swal.fire({
                imageUrl: imageUrl,
                imageAlt: 'Material',
                width: '80%',
                showConfirmButton: false,
                background: 'rgba(0,0,0,0.9)',
                customClass: {
                    container: 'gma-lightbox'
                }
            });
        }
        isDragging = false;
    });
});

// Manipulação de vídeos
document.querySelectorAll('.gma-material-video').forEach(video => {
    const container = video.closest('.gma-material-image-container');
    const playButton = document.createElement('div');
    playButton.className = 'video-play-button';
    container.appendChild(playButton);

    // Mostrar/ocultar botão de play
    video.addEventListener('play', () => {
        playButton.style.display = 'none';
    });

    video.addEventListener('pause', () => {
        playButton.style.display = 'flex';
    });

    // Controle de play/pause
    playButton.addEventListener('click', () => {
        if (video.paused) {
            video.play();
        } else {
            video.pause();
        }
    });
});
