<?php

declare(strict_types=1);

namespace StageArtPlugIn\Infrastructure\Schema;

final class Schema
{
    public static function activate(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // StageArtPlugIn uses the WordPress site itself as the organization context.
        // Member custom-field definitions are site-wide and member values are stored
        // independently from the field definition so fields can be deactivated and reused.
        $fields_table = $wpdb->prefix . 'stageart_plugin_member_fields';
        $values_table = $wpdb->prefix . 'stageart_plugin_member_field_values';

        $fields_sql = "CREATE TABLE {$fields_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            display_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY is_active_display_order (is_active, display_order)
        ) {$charset};";

        $values_sql = "CREATE TABLE {$values_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT UNSIGNED NOT NULL,
            field_id BIGINT UNSIGNED NOT NULL,
            value LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY member_field (member_id, field_id),
            KEY field_id (field_id),
            KEY member_id (member_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($fields_sql);
        dbDelta($values_sql);
    }
}
