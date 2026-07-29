<?php

namespace App\Http\Requests\Admin;

/**
 * Same rules as create — the unique-per-year rule already excludes self via route binding.
 */
class UpdateSubscriptionCatalogRequest extends StoreSubscriptionCatalogRequest
{
}
