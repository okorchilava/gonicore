<?php

declare(strict_types=1);

/**
 * GC Lazy Loader — English pack (plugin-owned; independent of the engine pack).
 */
return [
    'title'           => 'GC Lazy Loader',
    'intro'           => 'Images and iframes load only as they scroll into view, with a smooth fade-in — faster pages, less bandwidth.',

    'options'         => 'Options',
    'images'          => 'Lazy-load images',
    'images_hint'     => 'Add loading="lazy" to content & theme images.',
    'iframes'         => 'Lazy-load iframes',
    'iframes_hint'    => 'Defer embedded videos & maps until visible.',
    'fade'            => 'Fade-in effect',
    'fade_hint'       => 'Smoothly fade media in once it finishes loading.',
    'pageloader'      => 'Page transition loader',
    'pageloader_hint' => 'Show a loading animation while navigating page to page.',

    'appearance'      => 'Loader appearance',
    'style'           => 'Loader style',
    'style_hint'      => 'Thin top progress bar, or a full-screen centered spinner.',
    'spinner'         => 'Spinner animation',
    'spinner_hint'    => 'Animation used by the spinner.',
    'color'           => 'Accent color',
    'color_hint'      => 'Color of the progress bar & spinner.',

    'save'            => 'Save',

    // Loader style options
    'style_bar'       => 'Top progress bar',
    'style_overlay'   => 'Full-screen spinner',

    // Spinner animation options
    'spin_ring'       => 'Ring',
    'spin_dual'       => 'Dual ring',
    'spin_dots'       => 'Dots',
    'spin_pulse'      => 'Pulse',
    'spin_bars'       => 'Bars',
];
