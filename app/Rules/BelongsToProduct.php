<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class BelongsToProduct implements ValidationRule
{
    protected string $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }


    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // detect index from "field.index" or "field.index.index"
        $parts = explode('.', $attribute);
        $productIndex = $parts[1] ?? null;

        $request = request();
        $productId = $request->product_ids[$productIndex] ?? null;

        if (!$productId) {
            $fail("Product not found for validation.");
            return;
        }

        $exists = DB::table($this->table)
            ->where('id', $value)
            ->where('product_id', $productId)
            ->exists();

        if (!$exists) {
            $fail("The selected $this->table ID ($value) is not valid for product ID $productId.");
        }
    }
}
