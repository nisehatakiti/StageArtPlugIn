<?php

declare(strict_types=1);

namespace StageArtPlugIn\Infrastructure\Schema;

final class Schema
{
    public static function activate(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'stageart_plugin_organizations';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            description TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            owner_user_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY owner_user_id (owner_user_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
