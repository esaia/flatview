<?php

return [
    // Exclude Filament admin routes — they are not needed by public-site JS
    // and were adding ~21 KB of inline JSON to every public HTML response.
    'except' => ['filament.*'],

    // The route() helper function is already bundled in app.js (via ZiggyVue).
    // Setting this to true prevents the @routes directive from inlining the
    // same ~21 KB route.umd.js a second time in every HTML response.
    'skip-route-function' => true,
];
