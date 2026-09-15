<?php

declare(strict_types=1);

namespace StageArtPlugIn\Infrastructure\Schema;

final class SurveyMigration
{
    public static function ensure(): void
    {
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$p}stageart_plugin_production_surveys (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,title VARCHAR(191) NOT NULL,description LONGTEXT NULL,slug VARCHAR(191) NOT NULL,release_at DATETIME NULL,status VARCHAR(20) NOT NULL DEFAULT 'draft',created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY production_id(production_id),UNIQUE KEY slug(slug),KEY production_release(production_id,release_at)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_survey_questions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,survey_id BIGINT UNSIGNED NOT NULL,type VARCHAR(30) NOT NULL,label VARCHAR(255) NOT NULL,description TEXT NULL,is_required TINYINT(1) NOT NULL DEFAULT 0,display_order INT UNSIGNED NOT NULL DEFAULT 0,options_json LONGTEXT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY survey_order(survey_id,display_order)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_survey_responses (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,survey_id BIGINT UNSIGNED NOT NULL,submitted_at DATETIME NOT NULL,answers_json LONGTEXT NOT NULL,PRIMARY KEY(id),KEY survey_submitted(survey_id,submitted_at)) {$c};");
    }
}
