<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Information Resource Center (ARM) facts
    |--------------------------------------------------------------------------
    |
    | Figures shown on the public statistics page that are not derived from the
    | database. Override them in .env rather than editing this file.
    |
    */

    'founded_year' => (int) env('ARM_FOUNDED_YEAR', 2015),

    'reading_room_seats' => (int) env('ARM_READING_ROOM_SEATS', 120),

];
