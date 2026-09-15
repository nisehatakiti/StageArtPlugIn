<?php

declare(strict_types=1);

namespace StageArtPlugIn\Presentation\PublicSite;

use StageArtPlugIn\Domain\Member\MemberRepository;

final class MemberRouter
{
    public function register(): void
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'query_vars']);
        add_action('template_redirect', [$this, 'template_redirect'], 1);
        add_filter('redirect_canonical', [$this, 'disable_conflicting_canonical'], 10, 2);
    }

    public function add_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^member/([^/]+)/?$',
            'index.php?stageart_member_slug=$matches[1]',
            'top'
        );
    }

    public function query_vars(array $vars): array
    {
        $vars[] = 'stageart_member_slug';
        return $vars;
    }

    public function template_redirect(): void
    {
        $slug = get_query_var('stageart_member_slug');
        if (!is_string($slug) || $slug === '') {
            return;
        }

        $repo = new MemberRepository();
        $member = $repo->findBySlug($slug);

        if ($member === null) {
            $member = $repo->findBySlugHistory($slug);
            if ($member !== null) {
                $currentSlug = $repo->currentSlug((int) $member['id']);
                if ($currentSlug !== null && $currentSlug !== $slug) {
                    wp_safe_redirect($this->memberUrl($currentSlug), 301);
                    exit;
                }
            }
            $this->notFound();
            return;
        }

        if (($member['status'] ?? '') !== 'published') {
            $this->notFound();
            return;
        }

        $this->render($member);
    }

    public function disable_conflicting_canonical($redirect, $requested):
    {
        if (get_query_var('stageart_member_slug')) {
            return false;
        }
        return $redirect;
    }

    private function memberUrl(string $slug): string
    {
        return home_url('/member/' . rawurlencode($slug) . '/');
    }

    private function render(array $member): void
    {
        status_header(200);
        nocache_headers();

        get_header();
        echo '<main class="stageart-member">';
        echo '<article class="stageart-member__article">';
        echo '<h1>' . esc_html((string) $member['name']) . '</h1>';

        if (!empty($member['photo_id'])) {
            echo wp_get_attachment_image((int) $member['photo_id'], 'large', false, ['class' => 'stageart-member__photo']);
        }

        $roles = $member['roles'] ?? [];
        if ($roles) {
            echo '<p class="stageart-member__roles">';
            echo esc_html(implode(' / ', array_map(static fn(string $role): string => MemberRepository::ROLES[$role] ?? $role, $roles)));
            echo '</p>';
        }

        if (!empty($member['profile'])) {
            echo '<div class="stageart-member__profile">' . wp_kses_post((string) $member['profile']) . '</div>';
        }

        $social = $member['social'] ?? [];
        if ($social) {
            echo '<ul class="stageart-member__social">';
            foreach ($social as $platform => $url) {
                $label = MemberRepository::SOCIAL_PLATFORMS[$platform] ?? $platform;
                echo '<li><a href="' . esc_url($url) . '" rel="noopener noreferrer" target="_blank">' . esc_html($label) . '</a></li>';
            }
            echo '</ul>';
        }

        $fields = $member['custom_fields'] ?? [];
        $definitions = $repo = new MemberRepository();
        $definitions = $repo->fields(true);
        foreach ($definitions as $field) {
            $fieldId = (int) $field['id'];
            if (!array_key_exists($fieldId, $fields) || trim((string) $fields[$fieldId]) === '') {
                continue;
            }
            echo '<section class="stageart-member__custom-field">';
            echo '<h2>' . esc_html((string) $field['name']) . '</h2>';
            echo '<div>' . nl2br(esc_html((string) $fields[$fieldId])) . '</div>';
            echo '</section>';
        }

        echo '</article>';
        echo '</main>';
        get_footer();
    }

    private function notFound(): void
    {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        $template = get_404_template();
        if ($template) {
            include $template;
        }
        exit;
    }
}
