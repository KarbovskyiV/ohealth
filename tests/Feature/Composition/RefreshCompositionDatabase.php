<?php

declare(strict_types=1);

namespace Tests\Feature\Composition;

use Illuminate\Foundation\Testing\DatabaseTransactions;

trait RefreshCompositionDatabase
{
    // Keep the historical trait name for callers; never rebuild a shared database.
    use DatabaseTransactions;
    use UsesCompositionDictionaries;

}
