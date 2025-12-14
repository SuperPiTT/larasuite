<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait for enforcing strict types in Eloquent models.
 *
 * @mixin Model
 */
trait HasStrictTypes
{
    /**
     * Initialize the trait.
     */
    protected function initializeHasStrictTypes(): void
    {
        // Ensure dates are always cast properly
        $this->mergeCasts([
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ]);
    }
}
