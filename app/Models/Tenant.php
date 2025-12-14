<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasStrictTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property string $database
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Tenant extends BaseTenant
{
    use HasFactory;
    use HasStrictTypes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'database',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the database name for this tenant.
     */
    public function getDatabaseName(): string
    {
        return $this->database ?? config('database.connections.tenant.database');
    }
}
