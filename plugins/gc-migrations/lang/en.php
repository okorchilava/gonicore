<?php

declare(strict_types=1);

/**
 * GC Migrations — English pack (plugin-owned; independent of the engine pack).
 */
return [
    'title'             => 'GC Migrations',
    'intro'             => 'Import posts, pages and categories from ANY database — another GoniCore site, a WordPress site, or any other/unknown engine via custom column mapping. Only content tables are written; users, settings and roles are never touched.',

    // Engine
    'engine'              => 'Source engine',
    'engine_auto'         => 'Auto-detect',
    'engine_auto_d'       => 'Inspect the schema and choose automatically.',
    'engine_gonicore'     => 'GoniCore',
    'engine_gonicore_d'   => 'Another GoniCore site.',
    'engine_wordpress'    => 'WordPress',
    'engine_wordpress_d'  => 'wp_posts / wp_terms … (set the prefix).',
    'engine_custom'       => 'Custom mapping',
    'engine_custom_d'     => 'Any other / unknown engine.',

    // Source connection
    'source'            => 'Source database',
    'source_hint'       => 'Connection details for the database you are importing FROM.',
    'host'              => 'Host',
    'port'              => 'Port',
    'dbname'            => 'Database name',
    'username'          => 'Username',
    'password'          => 'Password',
    'password_hint'     => 'After a successful test you can leave this blank — the password you just entered is reused for the import (it is never echoed back).',
    'prefix'            => 'Table prefix',
    'prefix_hint'       => 'Optional. WordPress is usually "wp_"; standard GoniCore has none.',
    'test'              => 'Test connection & preview',

    // Preview
    'preview_title'     => 'Found in the source database',
    'c_posts'           => 'Posts',
    'c_pages'           => 'Pages',
    'c_categories'      => 'Categories',
    'c_translations'    => 'Translations',
    'c_rows'            => 'Rows in table',

    // Custom mapping
    'map_title_h'         => 'Custom column mapping',
    'map_connect_first'   => 'Click “Test connection & preview” first to load the source tables.',
    'map_table'           => 'Source table',
    'map_f_title'         => 'Title',
    'map_f_content'       => 'Content',
    'map_f_slug'          => 'Slug',
    'map_f_excerpt'       => 'Excerpt',
    'map_f_status'        => 'Status',
    'map_f_type'          => 'Type (post/page)',
    'map_f_created'       => 'Created date',
    'map_published_value' => 'Published value',
    'map_published_hint'  => 'The status value that means “published” (everything else becomes draft). e.g. publish, 1, active.',
    'map_default_type'    => 'Default type',

    // Import options
    'import_title'      => 'What to import',
    'opt_posts'         => 'Posts',
    'opt_pages'         => 'Pages',
    'opt_categories'    => 'Categories',
    'opt_cats_note'     => 'GoniCore & WordPress',
    'opt_translations'  => 'Post translations',
    'opt_trans_note'    => 'GoniCore only',
    'dup_title'         => 'If a slug already exists',
    'dup_skip'          => 'Skip it (keep the local copy)',
    'dup_rename'        => 'Import as a renamed copy',

    'trust_title'       => 'Trust & safety',
    'trust_label'       => 'I confirm the source database is trusted. Content is imported as raw HTML.',
    'import'            => 'Run import',

    // Report
    'report_title'      => 'Import complete',
    'r_categories'      => 'Categories imported',
    'r_posts'           => 'Posts imported',
    'r_pages'           => 'Pages imported',
    'r_translations'    => 'Translations imported',
    'r_skipped'         => 'Skipped (duplicates)',

    'safety_note'       => 'Authors are reassigned to you; users, settings, roles and permissions are never read or modified.',
];
