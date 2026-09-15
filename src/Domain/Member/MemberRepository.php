<?php

declare(strict_types=1);

namespace StageArtPlugIn\Domain\Member;

final class MemberRepository
{
    public const ROLES = [
        'actor' => '俳優',
        'director' => '演出',
        'writer' => '脚本',
        'production' => '制作',
        'sound' => '音響',
        'lighting' => '照明',
        'stage_manager' => '舞台監督',
        'representative' => '劇団代表',
    ];

    public const SOCIAL_PLATFORMS = [
        'x' => 'X',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
    ];

    public function all(bool $include_drafts = true): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_members';
        $where = $include_drafts ? '' : $wpdb->prepare(' WHERE status = %s', 'published');
        return $wpdb->get_results("SELECT * FROM {$table}{$where} ORDER BY display_order ASC, id ASC", ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_members';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        if (!$row) return null;
        $row['roles'] = $this->roles($id);
        $row['social_links'] = $this->socialLinks($id);
        $row['custom_fields'] = $this->customValues($id);
        return $row;
    }

    public function findBySlug(string $slug): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_members';
        $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s", $slug));
        return $id ? $this->find($id) : null;
    }

    public function save(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_members';
        $now = current_time('mysql');
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $payload = [
            'name' => sanitize_text_field((string) $data['name']),
            'slug' => sanitize_title((string) $data['slug']),
            'photo_id' => !empty($data['photo_id']) ? (int) $data['photo_id'] : null,
            'profile' => isset($data['profile']) ? wp_kses_post((string) $data['profile']) : null,
            'status' => in_array(($data['status'] ?? 'draft'), ['draft', 'published'], true) ? $data['status'] : 'draft',
            'display_order' => isset($data['display_order']) ? max(0, (int) $data['display_order']) : 0,
        ];
        if ($id) {
            $old = $this->find($id);
            $payload['updated_at'] = $now;
            $wpdb->update($table, $payload, ['id' => $id]);
            if ($old && $old['slug'] !== $payload['slug']) $this->recordSlug($id, $old['slug']);
        } else {
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;
            $wpdb->insert($table, $payload);
            $id = (int) $wpdb->insert_id;
        }
        $this->replaceRoles($id, (array) ($data['roles'] ?? []));
        $this->replaceSocialLinks($id, (array) ($data['social_links'] ?? []));
        $this->replaceCustomValues($id, (array) ($data['custom_fields'] ?? []));
        return $id;
    }

    public function updateOrder(array $ids): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_members';
        foreach (array_values($ids) as $order => $id) {
            $wpdb->update($table, ['display_order' => $order + 1, 'updated_at' => current_time('mysql')], ['id' => (int) $id]);
        }
    }

    public function roles(int $id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_roles';
        return $wpdb->get_col($wpdb->prepare("SELECT role_key FROM {$table} WHERE member_id = %d ORDER BY role_key", $id)) ?: [];
    }

    public function socialLinks(int $id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_social_links';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT platform, url FROM {$table} WHERE member_id = %d", $id), ARRAY_A) ?: [];
        $out = [];
        foreach ($rows as $row) $out[$row['platform']] = $row['url'];
        return $out;
    }

    public function activeFields(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_fields';
        return $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY display_order ASC, id ASC", ARRAY_A) ?: [];
    }

    public function allFields(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_fields';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY display_order ASC, id ASC", ARRAY_A) ?: [];
    }

    public function saveField(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_fields';
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $now = current_time('mysql');
        $payload = [
            'name' => sanitize_text_field((string) $data['name']),
            'description' => sanitize_textarea_field((string) ($data['description'] ?? '')),
            'updated_at' => $now,
        ];
        if ($id) {
            $wpdb->update($table, $payload, ['id' => $id]);
            return $id;
        }
        $payload['field_key'] = sanitize_key((string) ($data['field_key'] ?? 'field_' . wp_generate_password(8, false, false)));
        $payload['is_active'] = 1;
        $payload['display_order'] = count($this->allFields()) + 1;
        $payload['created_at'] = $now;
        $wpdb->insert($table, $payload);
        return (int) $wpdb->insert_id;
    }

    public function setFieldActive(int $id, bool $active): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_fields';
        $wpdb->update($table, ['is_active' => $active ? 1 : 0, 'updated_at' => current_time('mysql')], ['id' => $id]);
    }

    public function updateFieldOrder(array $ids): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_fields';
        foreach (array_values($ids) as $order => $id) $wpdb->update($table, ['display_order' => $order + 1, 'updated_at' => current_time('mysql')], ['id' => (int) $id]);
    }

    private function replaceRoles(int $id, array $roles): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_roles';
        $wpdb->delete($table, ['member_id' => $id]);
        foreach (array_unique($roles) as $role) if (isset(self::ROLES[$role])) $wpdb->insert($table, ['member_id' => $id, 'role_key' => $role]);
    }

    private function replaceSocialLinks(int $id, array $links): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_social_links';
        $wpdb->delete($table, ['member_id' => $id]);
        foreach ($links as $platform => $url) {
            if (!isset(self::SOCIAL_PLATFORMS[$platform]) || !$url) continue;
            $url = esc_url_raw((string) $url);
            if ($url) $wpdb->insert($table, ['member_id' => $id, 'platform' => $platform, 'url' => $url, 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')]);
        }
    }

    private function customValues(int $id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_field_values';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT field_id, value FROM {$table} WHERE member_id = %d", $id), ARRAY_A) ?: [];
        $out = [];
        foreach ($rows as $row) $out[(int) $row['field_id']] = $row['value'];
        return $out;
    }

    private function replaceCustomValues(int $id, array $values): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_field_values';
        $fields = $this->allFields();
        $valid = [];
        foreach ($fields as $field) $valid[(int) $field['id']] = true;
        foreach ($values as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            if (!$fieldId || !isset($valid[$fieldId])) continue;
            $existing = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE member_id = %d AND field_id = %d", $id, $fieldId));
            $data = ['value' => wp_kses_post((string) $value), 'updated_at' => current_time('mysql')];
            if ($existing) $wpdb->update($table, $data, ['id' => $existing]);
            else { $data['member_id'] = $id; $data['field_id'] = $fieldId; $data['created_at'] = current_time('mysql'); $wpdb->insert($table, $data); }
        }
    }

    private function recordSlug(int $memberId, string $slug): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_slug_history';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s", $slug));
        if (!$exists) $wpdb->insert($table, ['member_id' => $memberId, 'slug' => $slug, 'created_at' => current_time('mysql')]);
    }
}
