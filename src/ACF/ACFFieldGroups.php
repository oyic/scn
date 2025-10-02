<?php

namespace SCN\Membership\ACF;

/**
 * ACF Field Groups
 * 
 * Simple check for ACF Pro availability.
 * JSON loading is temporarily disabled to allow database editing.
 */
class ACFFieldGroups
{
    public function __construct()
    {
        // Check if ACF is available
        if ($this->isACFAvailable()) {
            // TEMPORARILY DISABLED JSON LOADING - Edit field groups in ACF UI, then re-enable
            // add_filter('acf/settings/save_json', [$this, 'setACFJsonSavePath']);
            // add_filter('acf/settings/load_json', [$this, 'setACFJsonLoadPath']);
            
            error_log('SCN: ACF Pro is available (JSON loading disabled for database editing)');
        } else {
            error_log('SCN: ACF Pro is not available');
        }
    }
    
    /**
     * Check if ACF Pro is available
     */
    public function isACFAvailable()
    {
        return class_exists('ACF') && function_exists('acf_get_field_groups');
    }
    
    /**
     * Set the folder where ACF will save JSON files
     */
    public function setACFJsonSavePath($path)
    {
        return dirname(dirname(__DIR__)) . '/acf-json';
    }
    
    /**
     * Set the folders where ACF will load JSON files from
     */
    public function setACFJsonLoadPath($paths)
    {
        // Add our plugin's acf-json folder
        $paths[] = dirname(dirname(__DIR__)) . '/acf-json';
        return $paths;
    }
}

