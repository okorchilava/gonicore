<?php

declare(strict_types=1);

/**
 * GC Migrations — Georgian pack (plugin-owned; independent of the engine pack).
 */
return [
    'title'             => 'GC მიგრაცია',
    'intro'             => 'დააიმპორტირე პოსტები, გვერდები და კატეგორიები ნებისმიერი ბაზიდან — სხვა GoniCore საიტიდან, WordPress-იდან, ან ნებისმიერი სხვა/უცნობი ძრავიდან სვეტების ხელით მიბმით. იწერება მხოლოდ კონტენტის ცხრილები; მომხმარებლები, პარამეტრები და როლები არ იცვლება.',

    // Engine
    'engine'              => 'წყაროს ძრავა',
    'engine_auto'         => 'ავტო-დადგენა',
    'engine_auto_d'       => 'სქემის შემოწმება და ავტომატური არჩევა.',
    'engine_gonicore'     => 'GoniCore',
    'engine_gonicore_d'   => 'სხვა GoniCore საიტი.',
    'engine_wordpress'    => 'WordPress',
    'engine_wordpress_d'  => 'wp_posts / wp_terms … (მიუთითე პრეფიქსი).',
    'engine_custom'       => 'ხელით მიბმა',
    'engine_custom_d'     => 'ნებისმიერი სხვა / უცნობი ძრავა.',

    // Source connection
    'source'            => 'წყარო ბაზა',
    'source_hint'       => 'იმ ბაზის მონაცემები, საიდანაც აიმპორტებ.',
    'host'              => 'ჰოსტი',
    'port'              => 'პორტი',
    'dbname'            => 'ბაზის სახელი',
    'username'          => 'მომხმარებელი',
    'password'          => 'პაროლი',
    'password_hint'     => 'წარმატებული შემოწმების შემდეგ შეგიძლია ცარიელი დატოვო — ახლახან შეყვანილი პაროლი იმპორტისთვის ხელახლა გამოიყენება (უკან არ ბრუნდება).',
    'prefix'            => 'ცხრილის პრეფიქსი',
    'prefix_hint'       => 'არასავალდებულო. WordPress ჩვეულებრივ "wp_"; სტანდარტულ GoniCore-ს არ აქვს.',
    'test'              => 'კავშირის შემოწმება და გადახედვა',

    // Preview
    'preview_title'     => 'ნაპოვნია წყარო ბაზაში',
    'c_posts'           => 'პოსტები',
    'c_pages'           => 'გვერდები',
    'c_categories'      => 'კატეგორიები',
    'c_translations'    => 'თარგმანები',
    'c_rows'            => 'ჩანაწერი ცხრილში',

    // Custom mapping
    'map_title_h'         => 'სვეტების ხელით მიბმა',
    'map_connect_first'   => 'ჯერ დააჭირე „კავშირის შემოწმება და გადახედვა“, რომ წყაროს ცხრილები ჩაიტვირთოს.',
    'map_table'           => 'წყაროს ცხრილი',
    'map_f_title'         => 'სათაური',
    'map_f_content'       => 'კონტენტი',
    'map_f_slug'          => 'Slug',
    'map_f_excerpt'       => 'ამონარიდი',
    'map_f_status'        => 'სტატუსი',
    'map_f_type'          => 'ტიპი (post/page)',
    'map_f_created'       => 'შექმნის თარიღი',
    'map_published_value' => 'გამოქვეყნების მნიშვნელობა',
    'map_published_hint'  => 'სტატუსის მნიშვნელობა, რომელიც ნიშნავს „გამოქვეყნებულს“ (დანარჩენი → დრაფტი). მაგ.: publish, 1, active.',
    'map_default_type'    => 'ნაგულისხმევი ტიპი',

    // Import options
    'import_title'      => 'რა დააიმპორტო',
    'opt_posts'         => 'პოსტები',
    'opt_pages'         => 'გვერდები',
    'opt_categories'    => 'კატეგორიები',
    'opt_cats_note'     => 'GoniCore და WordPress',
    'opt_translations'  => 'პოსტების თარგმანები',
    'opt_trans_note'    => 'მხოლოდ GoniCore',
    'dup_title'         => 'თუ slug უკვე არსებობს',
    'dup_skip'          => 'გამოტოვება (ლოკალური ვერსია რჩება)',
    'dup_rename'        => 'იმპორტი გადარქმეული ასლით',

    'trust_title'       => 'ნდობა და უსაფრთხოება',
    'trust_label'       => 'ვადასტურებ, რომ წყარო ბაზა სანდოა. კონტენტი იმპორტდება raw HTML-ად.',
    'import'            => 'იმპორტის გაშვება',

    // Report
    'report_title'      => 'იმპორტი დასრულდა',
    'r_categories'      => 'დაიმპორტდა კატეგორია',
    'r_posts'           => 'დაიმპორტდა პოსტი',
    'r_pages'           => 'დაიმპორტდა გვერდი',
    'r_translations'    => 'დაიმპორტდა თარგმანი',
    'r_skipped'         => 'გამოტოვდა (დუბლიკატი)',

    'safety_note'       => 'ავტორები გადმოგებინდება შენზე; მომხმარებლები, პარამეტრები, როლები და უფლებები არ იკითხება/იცვლება.',
];
