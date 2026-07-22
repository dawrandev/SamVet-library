<?php

namespace App\Http\Requests\Admin;

class UpdateAudiobookRequest extends StoreAudiobookRequest
{
    // Same rules as store — cover stays optional (only replaced when re-uploaded).
}
