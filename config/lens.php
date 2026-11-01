<?php

return [
    /*
    | Public marketplace site gate. When true, visitors see Coming Soon on
    | the marketing routes until launch_at (Africa/Cairo).
    */
    'coming_soon' => (bool) env('LENS_COMING_SOON', true),

    /*
    | Countdown target: day 8 of the current launch month (Nov 8, 2026).
    */
    'launch_at' => env('LENS_LAUNCH_AT', '2026-11-08T00:00:00+02:00'),
];
