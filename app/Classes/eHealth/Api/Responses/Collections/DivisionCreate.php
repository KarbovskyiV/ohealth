<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api\Responses\Collections;

use Illuminate\Support\Collection;

/**
 * Validated single-division response data.
 *
 * @extends Collection<string, mixed>
 */
class DivisionCreate extends Collection
{
    /**
     * @param  iterable<string, mixed>|null  $items
     */
    public function __construct(iterable|null $items = [])
    {
        parent::__construct($items);

        if ($this->has('addresses')) {
            $this->put('addresses', collect($this->get('addresses'))
                ->map(fn (array|Collection $address): AddressCreate => $address instanceof AddressCreate
                    ? $address
                    : new AddressCreate($address))
                ->values()
                ->all());
        }

        if ($this->has('phones')) {
            $this->put('phones', collect($this->get('phones'))
                ->map(fn (array|Collection $phone): PhoneCreate => $phone instanceof PhoneCreate
                    ? $phone
                    : new PhoneCreate($phone))
                ->values()
                ->all());
        }
    }
}
