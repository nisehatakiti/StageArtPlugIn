<?php

declare(strict_types=1);

namespace StageArtPlugIn\Domain\Survey;

final class SurveyRepository
{
    private \wpdb $db;
    private string $surveys;
    private string $questions;
    private string $responses;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $p = $this->db->prefix;
        $this->surveys = $p . 'stageart_plugin_production_surveys';
        $this->questions = $p . 'stageart_plugin_production_survey_questions';
        $this->responses = $p . 'stageart_plugin_production_survey_responses';
    }

    public function findByProduction(int $productionId): ?array
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->surveys} WHERE production_id=%d LIMIT 1", $productionId), ARRAY_A);
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->surveys} WHERE slug=%s LIMIT 1", $slug), ARRAY_A);
        return $row ?: null;
    }

    public function questions(int $surveyId): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM {$this->questions} WHERE survey_id=%d ORDER BY display_order,id", $surveyId), ARRAY_A) ?: [];
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['survey_id'] = (int) $row['survey_id'];
            $row['is_required'] = (bool) $row['is_required'];
            $decoded = json_decode((string) ($row['options_json'] ?? ''), true);
            $row['options'] = is_array($decoded) ? $decoded : [];
            unset($row['options_json']);
        }
        return $rows;
    }

    public function save(int $productionId, array $data, array $questions): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $existing = $this->findByProduction($productionId);
        $payload = [
            'production_id' => $productionId,
            'title' => sanitize_text_field((string) ($data['title'] ?? 'アンケート')),
            'description' => wp_kses_post((string) ($data['description'] ?? '')),
            'slug' => sanitize_title((string) ($data['slug'] ?? 'questionnaire')) ?: 'questionnaire',
            'release_at' => $data['release_at'] ?? null,
            'status' => in_array(($data['status'] ?? 'draft'), ['draft', 'publish'], true) ? $data['status'] : 'draft',
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->update($this->surveys, $payload, ['id' => (int) $existing['id']], ['%d', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);
            $surveyId = (int) $existing['id'];
        } else {
            $payload['created_at'] = $now;
            $this->db->insert($this->surveys, $payload, ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);
            $surveyId = (int) $this->db->insert_id;
        }

        $old = $this->questions($surveyId);
        $keep = [];
        foreach (array_values($questions) as $i => $question) {
            if (!is_array($question)) {
                continue;
            }
            $label = trim((string) ($question['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $id = (int) ($question['id'] ?? 0);
            $type = sanitize_key((string) ($question['type'] ?? 'textarea'));
            $allowedTypes = ['text', 'textarea', 'radio', 'checkbox', 'select', 'rating'];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'textarea';
            }
            $options = array_values(array_filter(array_map(static fn($v) => sanitize_text_field((string) $v), (array) ($question['options'] ?? [])), static fn($v) => $v !== ''));
            $row = [
                'survey_id' => $surveyId,
                'type' => $type,
                'label' => sanitize_text_field($label),
                'description' => sanitize_textarea_field((string) ($question['description'] ?? '')),
                'is_required' => !empty($question['is_required']) ? 1 : 0,
                'display_order' => $i,
                'options_json' => wp_json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ];
            if ($id > 0) {
                $this->db->update($this->questions, $row, ['id' => $id, 'survey_id' => $surveyId], ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s'], ['%d', '%d']);
            } else {
                $row['created_at'] = $now;
                $this->db->insert($this->questions, $row, ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']);
                $id = (int) $this->db->insert_id;
            }
            $keep[] = $id;
        }
        foreach ($old as $oldRow) {
            $oldId = (int) $oldRow['id'];
            if (!in_array($oldId, $keep, true)) {
                $this->db->delete($this->questions, ['id' => $oldId, 'survey_id' => $surveyId], ['%d', '%d']);
            }
        }
        return $surveyId;
    }

    public function responseCount(int $surveyId): int
    {
        return (int) $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM {$this->responses} WHERE survey_id=%d", $surveyId));
    }

    public function saveResponse(int $surveyId, array $answers): int
    {
        $this->db->insert($this->responses, [
            'survey_id' => $surveyId,
            'submitted_at' => gmdate('Y-m-d H:i:s'),
            'answers_json' => wp_json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], ['%d', '%s', '%s']);
        return (int) $this->db->insert_id;
    }
}
