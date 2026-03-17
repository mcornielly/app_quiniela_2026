<?php

return [
    'world_cup_2026' => [
        /*
        |--------------------------------------------------------------------------
        | Third Place Combination Matrix
        |--------------------------------------------------------------------------
        |
        | Key format:
        |   - sorted group letters of the 8 qualified third-place teams
        | Value format:
        |   - match_number => group_letter assigned to the 3-XXXXX slot
        |
        | Example:
        | 'ACDEFHIJ' => [
        |     74 => 'D',
        |     77 => 'C',
        |     79 => 'I',
        |     80 => 'H',
        |     81 => 'F',
        |     82 => 'A',
        |     85 => 'E',
        |     87 => 'J',
        | ],
        |
        */
        'third_place_matrix' => require __DIR__ . '/generated/world_cup_2026_third_place_matrix.php',
    ],
];
