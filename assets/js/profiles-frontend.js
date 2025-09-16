/**
 * SCN Profiles Frontend JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initLightbox();
        initVideoEmbeds();
    });

    function initLightbox() {
        // Simple lightbox implementation
        $('[data-lightbox]').on('click', function(e) {
            e.preventDefault();
            
            const imageUrl = $(this).attr('href');
            const imageTitle = $(this).attr('data-title') || '';
            
            showLightbox(imageUrl, imageTitle);
        });

        // Close lightbox on escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                closeLightbox();
            }
        });

        // Close lightbox on background click
        $(document).on('click', '.scn-lightbox-overlay', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
    }

    function showLightbox(imageUrl, imageTitle) {
        const lightboxHtml = `
            <div class="scn-lightbox-overlay">
                <div class="scn-lightbox-content">
                    <button class="scn-lightbox-close" aria-label="Close lightbox">&times;</button>
                    <img src="${imageUrl}" alt="${imageTitle}" />
                    ${imageTitle ? `<div class="scn-lightbox-title">${imageTitle}</div>` : ''}
                </div>
            </div>
        `;
        
        $('body').append(lightboxHtml);
        $('body').addClass('scn-lightbox-open');
        
        // Close on close button click
        $('.scn-lightbox-close').on('click', closeLightbox);
    }

    function closeLightbox() {
        $('.scn-lightbox-overlay').remove();
        $('body').removeClass('scn-lightbox-open');
    }

    function initVideoEmbeds() {
        // Handle video thumbnail clicks
        $('.scn-video-thumbnail a').on('click', function(e) {
            e.preventDefault();
            
            const videoUrl = $(this).attr('href');
            const thumbnail = $(this).find('img');
            const container = $(this).closest('.scn-video-thumbnail');
            
            // Replace thumbnail with embed
            replaceWithEmbed(container, videoUrl);
        });
    }

    function replaceWithEmbed(container, videoUrl) {
        // Simple YouTube/Vimeo embed detection and replacement
        let embedCode = '';
        
        if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
            const videoId = extractYouTubeId(videoUrl);
            if (videoId) {
                embedCode = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
            }
        } else if (videoUrl.includes('vimeo.com')) {
            const videoId = extractVimeoId(videoUrl);
            if (videoId) {
                embedCode = `<iframe width="560" height="315" src="https://player.vimeo.com/video/${videoId}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
            }
        }
        
        if (embedCode) {
            container.html(`<div class="scn-video-embed">${embedCode}</div>`);
        } else {
            // Fallback: open in new tab
            window.open(videoUrl, '_blank', 'noopener');
        }
    }

    function extractYouTubeId(url) {
        const patterns = [
            /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/,
        ];
        
        for (let pattern of patterns) {
            const match = url.match(pattern);
            if (match) {
                return match[1];
            }
        }
        
        return null;
    }

    function extractVimeoId(url) {
        const pattern = /(?:vimeo\.com\/)([0-9]+)/;
        const match = url.match(pattern);
        return match ? match[1] : null;
    }

    // Add lightbox styles dynamically
    const lightboxStyles = `
        <style>
        .scn-lightbox-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .scn-lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        
        .scn-lightbox-content img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
        }
        
        .scn-lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 30px;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .scn-lightbox-title {
            color: white;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .scn-lightbox-open {
            overflow: hidden;
        }
        
        .scn-video-embed {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%;
        }
        
        .scn-video-embed iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        </style>
    `;
    
    $('head').append(lightboxStyles);

})(jQuery);


