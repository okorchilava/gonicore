<?php

declare(strict_types=1);

/**
 * GC Testimonials — English strings (the plugin's OWN pack; never the engine's).
 * Resolved via gc_plugin_translator() in admin + frontend.
 */
return [
    // ── Admin ──
    'admin.title'             => 'Testimonials',
    'admin.add'               => 'Add review',
    'admin.edit'              => 'Edit',
    'admin.delete'            => 'Delete',
    'admin.save'              => 'Save review',
    'admin.back'              => 'Back',
    'admin.tab_reviews'       => 'Reviews',
    'admin.tab_campaigns'     => 'Campaigns',
    'admin.reviews'           => 'Reviews',
    'admin.pending'           => 'Pending',
    'admin.live'              => 'Live',
    'admin.general'           => 'General',
    'admin.hide'              => 'Hide',
    'admin.publish'           => 'Publish',
    'admin.empty_title'       => 'No reviews yet',
    'admin.empty_text'        => 'Add your first review, or let visitors submit one via the form shortcode.',
    'admin.col_author'        => 'Author',
    'admin.col_review'        => 'Review',
    'admin.col_campaign'      => 'Campaign',
    'admin.col_rating'        => 'Rating',
    'admin.col_status'        => 'Status',
    'admin.col_actions'       => 'Actions',
    'admin.confirm_delete'    => 'Delete this review? This cannot be undone.',
    'admin.add_title'         => 'New review',
    'admin.edit_title'        => 'Edit review',
    'admin.f_name'            => 'Author name',
    'admin.f_role'            => 'Role / company',
    'admin.f_role_ph'         => 'e.g. CEO at Acme',
    'admin.f_campaign'        => 'Campaign',
    'admin.f_general'         => '— General (everywhere) —',
    'admin.f_rating'          => 'Rating',
    'admin.f_text'            => 'Review text',
    'admin.f_public'          => 'Published (visible on the site)',
    'admin.campaigns_title'   => 'Campaigns',
    'admin.new_campaign'      => 'New campaign',
    'admin.campaign_name'     => 'Campaign name',
    'admin.campaign_ph'       => 'e.g. Home page',
    'admin.add_campaign'      => 'Add campaign',
    'admin.campaign_help'     => 'A campaign groups reviews for a specific page. Use its shortcodes to show the list, slider, or submission form.',
    'admin.existing_campaigns'=> 'Existing campaigns',
    'admin.no_campaigns'      => 'No campaigns yet. Add one to get its shortcodes.',
    'admin.confirm_delete_campaign' => 'Delete this campaign? Its reviews are kept and moved to General.',
    'admin.copied'            => 'Shortcode copied',

    // ── Frontend ──
    'front.none'       => 'No reviews to show.',
    'front.empty'      => 'No reviews yet — be the first to leave one!',
    'front.form_intro' => 'Share your experience — it means a lot to us.',
    'front.name_ph'    => 'Your name *',
    'front.text_ph'    => 'Write your review (min. 10 characters) *',
    'front.submit'     => 'Submit review',
    'front.thanks'     => 'Thank you! Your review was submitted and will appear after approval.',
    'front.error'      => 'Something went wrong. Please try again.',
    'front.invalid'    => 'Please enter your name and a review of at least 10 characters.',
];
