<?php

namespace App\Observers;

use App\Models\Reader;

class ReaderObserver
{
    /**
     * New readers get the library's shared sign-in password (hashed by the
     * model cast). Set an individual password later to override it.
     */
    public function creating(Reader $reader): void
    {
        if (empty($reader->password)) {
            $reader->password = config('arm.reader_default_password');
        }
    }
}
