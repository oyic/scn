# ACF JSON Field Groups

This directory contains ACF field group definitions in JSON format.

## Field Groups

1. **group_scn_profile_fields.json** - Profile Information fields
2. **group_scn_profile_main.json** - Member Information fields (enhanced profile)
3. **group_scn_course_fields.json** - Course Information fields
4. **group_scn_event_fields.json** - Event Information fields

## How It Works

ACF automatically loads field groups from JSON files when:
- The `acf/settings/load_json` filter points to this directory
- The plugin configures this in `src/ACF/ACFFieldGroups.php`

## Benefits

- **Version Control**: Field definitions tracked in Git
- **Easy Import/Export**: Copy JSON files between environments
- **Auto-Sync**: ACF detects changes and offers to sync

## Making Changes

Edit field groups in WordPress admin (Custom Fields). ACF will automatically update the JSON files when you save.

## Importing to Another Site

1. Copy JSON files to the `acf-json` directory
2. Go to Custom Fields in WordPress admin
3. ACF will show a "Sync available" message
4. Click sync to import the changes

