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

    /*
    |--------------------------------------------------------------------------
    | Reader sign-in
    |--------------------------------------------------------------------------
    |
    | Readers sign in with their ID number (e.g. BT0122001). The library issues
    | one shared password to everyone; it is hashed into readers.password when
    | a reader is created. Change it in .env, then re-issue it to the readers.
    |
    | NOTE: a shared password means anyone who knows another reader's ID number
    | can sign in as them. Give each reader an individual password to fix that —
    | the schema and auth flow already support it.
    |
    */

    'reader_default_password' => env('ARM_READER_PASSWORD', 'arm777'),

];
