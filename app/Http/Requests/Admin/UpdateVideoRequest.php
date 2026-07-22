<?php

namespace App\Http\Requests\Admin;

class UpdateVideoRequest extends StoreVideoRequest
{
    // Same rules as store — cover stays optional (only replaced when re-uploaded).
}
