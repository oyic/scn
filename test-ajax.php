<?php
/**
 * Simple AJAX test for SCN Membership Settings
 * This file can be used to test AJAX endpoints directly
 */

// Test the AJAX endpoints
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('scn_settings_nonce');

echo "<h2>SCN Membership AJAX Test</h2>";
echo "<p>AJAX URL: " . $ajax_url . "</p>";
echo "<p>Nonce: " . $nonce . "</p>";

// Test data
$test_data = [
    'action' => 'scn_test_image_upload',
    'nonce' => $nonce
];

echo "<h3>Test Image Upload</h3>";
echo "<p>POST data: " . json_encode($test_data) . "</p>";

// Test clear cache
$cache_data = [
    'action' => 'scn_clear_cache',
    'nonce' => $nonce
];

echo "<h3>Clear Cache</h3>";
echo "<p>POST data: " . json_encode($cache_data) . "</p>";

echo "<h3>JavaScript Test</h3>";
echo "<script>
jQuery(document).ready(function($) {
    console.log('Testing AJAX...');
    
    // Test image upload
    $.ajax({
        url: '" . $ajax_url . "',
        type: 'POST',
        data: " . json_encode($test_data) . ",
        success: function(response) {
            console.log('Test Image Upload Response:', response);
        },
        error: function(xhr, status, error) {
            console.log('Test Image Upload Error:', xhr, status, error);
        }
    });
    
    // Test clear cache
    $.ajax({
        url: '" . $ajax_url . "',
        type: 'POST',
        data: " . json_encode($cache_data) . ",
        success: function(response) {
            console.log('Clear Cache Response:', response);
        },
        error: function(xhr, status, error) {
            console.log('Clear Cache Error:', xhr, status, error);
        }
    });
});
</script>";
?>




