<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Exceptions\DomainOccupiedByOtherTenantException;

/**
 * @property string $domain
 * @property string $tenant_id
 *
 * @property-read Tenant|Model $tenant
 */
class Domain extends Model implements Contracts\Domain
{
    use CentralConnection;

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        $ensureDomainIsNotOccupied = function (self $self) {
            if ($domain = self::where('domain', $self->domain)->first()) {
                if ($domain->getKey() !== $self->getKey()) {
                    throw new DomainOccupiedByOtherTenantException($self->domain);
                }
            }
        };

        static::saving($ensureDomainIsNotOccupied);
    }

    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    public $dispatchesEvents = [
        'saved' => Events\DomainSaved::class,
        'saving' => Events\SavingDomain::class,
        'created' => Events\DomainCreated::class,
        'creating' => Events\CreatingDomain::class,
        'updated' => Events\DomainUpdated::class,
        'updating' => Events\UpdatingDomain::class,
        'deleted' => Events\DomainDeleted::class,
        'deleting' => Events\DeletingDomain::class,
    ];
}
