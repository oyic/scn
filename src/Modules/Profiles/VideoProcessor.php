<?php

namespace SCN\Membership\Modules\Profiles;

class VideoProcessor {
    public function register() {
        add_action('wp_ajax_scn_validate_video_url', [$this, 'validateVideoUrl']);
        add_action('wp_ajax_scn_get_video_thumbnail', [$this, 'getVideoThumbnail']);
    }

    public function validateVideoUrl($url = null) {
        if (!$url && isset($_POST['url'])) {
            $url = sanitize_text_field($_POST['url']);
        }

        if (!$url) {
            return false;
        }

        $platform = $this->getVideoPlatform($url);
        if (!$platform) {
            return false;
        }

        $video_id = $this->extractVideoId($url, $platform);
        if (!$video_id) {
            return false;
        }

        return [
            'valid' => true,
            'platform' => $platform,
            'video_id' => $video_id,
            'thumbnail' => $this->getThumbnailUrl($platform, $video_id),
        ];
    }

    public function getVideoThumbnail() {
        if (!isset($_POST['url'])) {
            wp_die('Invalid request');
        }

        $url = sanitize_text_field($_POST['url']);
        $result = $this->validateVideoUrl($url);

        if ($result && $result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Invalid video URL. Please enter a valid YouTube or Vimeo URL.', 'scn-membership'));
        }
    }

    public function getVideoEmbedCode($url, $width = 560, $height = 315) {
        $validation = $this->validateVideoUrl($url);
        if (!$validation || !$validation['valid']) {
            return false;
        }

        $platform = $validation['platform'];
        $video_id = $validation['video_id'];

        if ($platform === 'youtube') {
            return $this->getYouTubeEmbedCode($video_id, $width, $height);
        } elseif ($platform === 'vimeo') {
            return $this->getVimeoEmbedCode($video_id, $width, $height);
        }

        return false;
    }

    public function getVideoThumbnailUrl($url) {
        $validation = $this->validateVideoUrl($url);
        if (!$validation || !$validation['valid']) {
            return false;
        }

        return $validation['thumbnail'];
    }

    private function getVideoPlatform($url) {
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        } elseif (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        }
        return false;
    }

    private function extractVideoId($url, $platform) {
        if ($platform === 'youtube') {
            $patterns = [
                '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
            ];
        } elseif ($platform === 'vimeo') {
            $patterns = [
                '/(?:vimeo\.com\/)([0-9]+)/',
            ];
        } else {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }

    private function getThumbnailUrl($platform, $video_id) {
        if ($platform === 'youtube') {
            return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        } elseif ($platform === 'vimeo') {
            return $this->getVimeoThumbnail($video_id);
        }

        return false;
    }

    private function getVimeoThumbnail($video_id) {
        $response = wp_remote_get("https://vimeo.com/api/v2/video/{$video_id}.json");
        
        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data[0]['thumbnail_large'])) {
            return $data[0]['thumbnail_large'];
        } elseif (!empty($data[0]['thumbnail_medium'])) {
            return $data[0]['thumbnail_medium'];
        }

        return false;
    }

    private function getYouTubeEmbedCode($video_id, $width, $height) {
        $width = absint($width);
        $height = absint($height);
        
        return sprintf(
            '<iframe width="%d" height="%d" src="https://www.youtube.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
            $width,
            $height,
            esc_attr($video_id)
        );
    }

    private function getVimeoEmbedCode($video_id, $width, $height) {
        $width = absint($width);
        $height = absint($height);
        
        return sprintf(
            '<iframe width="%d" height="%d" src="https://player.vimeo.com/video/%s" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>',
            $width,
            $height,
            esc_attr($video_id)
        );
    }

    public function getVideoInfo($url) {
        $validation = $this->validateVideoUrl($url);
        if (!$validation || !$validation['valid']) {
            return false;
        }

        $platform = $validation['platform'];
        $video_id = $validation['video_id'];

        $info = [
            'platform' => $platform,
            'video_id' => $video_id,
            'thumbnail' => $validation['thumbnail'],
            'embed_code' => $this->getVideoEmbedCode($url),
        ];

        // Get additional video info if possible
        if ($platform === 'youtube') {
            $info = array_merge($info, $this->getYouTubeVideoInfo($video_id));
        } elseif ($platform === 'vimeo') {
            $info = array_merge($info, $this->getVimeoVideoInfo($video_id));
        }

        return $info;
    }

    private function getYouTubeVideoInfo($video_id) {
        // Note: This would require YouTube API key for full info
        // For now, return basic info
        return [
            'title' => '',
            'description' => '',
            'duration' => '',
        ];
    }

    private function getVimeoVideoInfo($video_id) {
        $response = wp_remote_get("https://vimeo.com/api/v2/video/{$video_id}.json");
        
        if (is_wp_error($response)) {
            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data[0])) {
            return [];
        }

        return [
            'title' => $data[0]['title'] ?? '',
            'description' => $data[0]['description'] ?? '',
            'duration' => $data[0]['duration'] ?? '',
        ];
    }
}


