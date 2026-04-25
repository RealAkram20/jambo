<?php

namespace Modules\Pages\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $page = $this->route('page');
        $pageId = is_object($page) ? $page->id : null;

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('pages', 'slug')->ignore($pageId),
            ],
            'content' => 'nullable|string',
            'featured_image_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/'],
            'meta_description' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',

            // Contact-page meta (only present when editing contact-us).
            // The whole `meta` payload is loose by design — different
            // page types add different keys; the admin form decides
            // which to render.
            'meta' => 'nullable|array',
            'meta.page_heading' => 'nullable|string|max:255',
            'meta.cards' => 'nullable|array',
            'meta.cards.*.title' => 'nullable|string|max:255',
            'meta.cards.*.desc' => 'nullable|string|max:1000',
            'meta.cards.*.icon' => 'nullable|string|max:100',
            'meta.cards.*.link_type' => 'nullable|in:email,phone,url',
            'meta.cards.*.link_value' => 'nullable|string|max:255',
            'meta.form_heading' => 'nullable|string|max:255',
            'meta.form_subheading' => 'nullable|string|max:1000',
            'meta.form_recipient_email' => 'nullable|email|max:255',
            'meta.form_button_label' => 'nullable|string|max:100',
            'meta.visit_us_heading' => 'nullable|string|max:255',
            'meta.visit_us_intro' => 'nullable|string|max:500',
            'meta.address_label' => 'nullable|string|max:100',
            'meta.address_lines' => 'nullable|string|max:1000',
            'meta.business_heading' => 'nullable|string|max:255',
            'meta.business_body' => 'nullable|string|max:1000',
            'meta.business_email' => 'nullable|email|max:255',
            'meta.follow_label' => 'nullable|string|max:100',
            'meta.facebook_url' => 'nullable|url|max:500',
            'meta.twitter_url' => 'nullable|url|max:500',
            'meta.youtube_url' => 'nullable|url|max:500',
            'meta.map_embed_url' => 'nullable|url|max:2000',
            'meta.map_height' => 'nullable|integer|min:100|max:1200',

            // FAQ-page meta (only present when editing faqs).
            'meta.questions' => 'nullable|array',
            'meta.questions.*.q' => 'nullable|string|max:500',
            'meta.questions.*.a' => 'nullable|string|max:5000',

            // Footer-page meta.
            'meta.contact' => 'nullable|array',
            'meta.contact.email_label' => 'nullable|string|max:100',
            'meta.contact.email_address' => 'nullable|string|max:255',
            'meta.contact.helpline_label' => 'nullable|string|max:100',
            'meta.contact.helpline_phone' => 'nullable|string|max:50',
            'meta.columns' => 'nullable|array',
            'meta.columns.*.title' => 'nullable|string|max:100',
            'meta.columns.*.links' => 'nullable|array',
            'meta.columns.*.links.*.label' => 'nullable|string|max:100',
            'meta.columns.*.links.*.url' => 'nullable|string|max:500',
            'meta.newsletter' => 'nullable|array',
            'meta.newsletter.title' => 'nullable|string|max:100',
            'meta.newsletter.placeholder' => 'nullable|string|max:100',
            'meta.newsletter.button_label' => 'nullable|string|max:50',
            'meta.newsletter.enabled' => 'nullable|boolean',
            'meta.follow_label' => 'nullable|string|max:100',
            'meta.socials' => 'nullable|array',
            'meta.socials.*.icon' => 'nullable|string|max:100',
            'meta.socials.*.url' => 'nullable|string|max:500',
            'meta.copyright' => 'nullable|string|max:1000',
            'meta.download_app_title' => 'nullable|string|max:100',
            'meta.play_store_url' => 'nullable|url|max:500',
            'meta.app_store_url' => 'nullable|url|max:500',
        ];
    }
}
