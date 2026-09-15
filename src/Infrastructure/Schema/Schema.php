<?php

declare(strict_types=1);

namespace StageArtPlugIn\Infrastructure\Schema;

final class Schema
{
    public const DB_VERSION = '0.3.0';

    public static function activate(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $members = $wpdb->prefix . 'stageart_plugin_members';
        $roles = $wpdb->prefix . 'stageart_plugin_member_roles';
        $social = $wpdb->prefix . 'stageart_plugin_member_social_links';
        $fields = $wpdb->prefix . 'stageart_plugin_member_fields';
        $values = $wpdb->prefix . 'stageart_plugin_member_field_values';
        $history = $wpdb->prefix . 'stageart_plugin_member_slug_history';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$members} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            photo_id BIGINT UNSIGNED NULL,
            profile LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            display_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status_display_order (status, display_order)
        ) {$charset};");

        dbDelta("CREATE TABLE {$roles} (
            member_id BIGINT UNSIGNED NOT NULL,
            role_key VARCHAR(40) NOT NULL,
            PRIMARY KEY (member_id, role_key),
            KEY role_key (role_key)
        ) {$charset};");

        dbDelta("CREATE TABLE {$social} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT UNSIGNED NOT NULL,
            platform VARCHAR(30) NOT NULL,
            url VARCHAR(2048) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY member_platform (member_id, platform),
            KEY member_id (member_id)
        ) {$charset};");

        dbDelta("CREATE TABLE {$fields} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            field_key VARCHAR(191) NOT NULL,
            name VARCHAR(191) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            display_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY field_key (field_key),
            KEY is_active_display_order (is_active, display_order)
        ) {$charset};");

        dbDelta("CREATE TABLE {$values} (
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
        ) {$charset};");

        dbDelta("CREATE TABLE {$history} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT UNSIGNED NOT NULL,
            slug VARCHAR(191) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY member_id (member_id)
        ) {$charset};");

        update_option('stageart_plugin_db_version', self::DB_VERSION, false);
    }
}
