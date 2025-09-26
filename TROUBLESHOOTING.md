# SCN Membership Plugin - Troubleshooting Guide

## Authentication URLs Not Working

If you can't access `/member-login/` or other authentication URLs, try these solutions:

### **Solution 1: Flush Rewrite Rules**

#### Via WordPress Admin:
1. Go to **WordPress Admin > Settings > Permalinks**
2. Click **"Save Changes"** (don't change anything, just save)

#### Via WP-CLI:
```bash
wp rewrite flush
```

#### Via Code:
Run this script in your browser:
```
http://yoursite.com/wp-content/plugins/scn-membership/flush-rewrite-rules.php
```

### **Solution 2: Deactivate and Reactivate Plugin**

1. Go to **WordPress Admin > Plugins**
2. **Deactivate** the SCN Membership plugin
3. **Reactivate** the SCN Membership plugin

### **Solution 3: Check Plugin Status**

Run this script to check if everything is working:
```
http://yoursite.com/wp-content/plugins/scn-membership/test-urls.php
```

### **Solution 4: Manual URL Testing**

Try accessing these URLs directly:
- `http://yoursite.com/member-login/`
- `http://yoursite.com/member-register/`
- `http://yoursite.com/member-dashboard/`
- `http://yoursite.com/test-auth/`

### **Solution 5: Check .htaccess**

Make sure your `.htaccess` file has the WordPress rewrite rules:
```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

## Common Issues

### **404 Error on Authentication Pages**
- **Cause**: Rewrite rules not flushed
- **Fix**: Use Solution 1 above

### **Database Connection Error**
- **Cause**: WordPress can't connect to database
- **Fix**: Check `wp-config.php` database settings

### **Plugin Not Loading**
- **Cause**: PHP errors or missing dependencies
- **Fix**: Check WordPress error logs

### **Templates Not Found**
- **Cause**: File permissions or missing files
- **Fix**: Check file permissions and ensure all template files exist

## Testing the Authentication System

### **Step 1: Create Test User**
1. Go to `/test-auth/`
2. Click "Create Test User" (admin only)
3. Or use WP-CLI:
   ```bash
   wp user create testmember test@scn-membership.com --role=subscriber --first_name="Test" --last_name="Member" --user_pass="TestMember123!"
   ```

### **Step 2: Test Login**
1. Go to `/member-login/`
2. Use credentials: `testmember` / `TestMember123!`
3. Should redirect to `/member-dashboard/`

### **Step 3: Test Registration**
1. Go to `/member-register/`
2. Fill out the form
3. Should create user, profile, and redirect to dashboard

## Debug Information

### **Check Plugin Status**
```php
// Add this to functions.php temporarily
add_action('wp_footer', function() {
    if (current_user_can('administrator')) {
        echo '<!-- SCN Plugin Status: ' . (class_exists('SCN\\Membership\\Core\\Plugin') ? 'Loaded' : 'Not Loaded') . ' -->';
        echo '<!-- Auth URLs: ' . home_url('/member-login/') . ' -->';
    }
});
```

### **Enable Debug Mode**
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### **Check Rewrite Rules**
```php
// Add this to functions.php temporarily
add_action('wp_footer', function() {
    if (current_user_can('administrator')) {
        $rules = get_option('rewrite_rules');
        echo '<!-- Rewrite Rules: ' . print_r($rules, true) . ' -->';
    }
});
```

## Getting Help

If none of these solutions work:

1. **Check WordPress Error Logs**
2. **Verify Plugin Files Are Complete**
3. **Test with Default WordPress Theme**
4. **Disable Other Plugins Temporarily**
5. **Check Server Error Logs**

## Quick Fixes Summary

| Problem | Quick Fix |
|---------|-----------|
| URLs return 404 | Flush rewrite rules |
| Plugin not loading | Reactivate plugin |
| Database errors | Check wp-config.php |
| Template errors | Check file permissions |
| Authentication fails | Create test user first |

## Support Information

- **Plugin Version**: 1.0.0
- **WordPress Version**: 6.5+
- **PHP Version**: 8.1+
- **Required**: Custom post types support
- **Optional**: WP-CLI for testing
