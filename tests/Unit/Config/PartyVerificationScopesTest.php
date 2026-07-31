<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PartyVerificationScopesTest extends TestCase
{
    #[Test]
    public function legal_entity_type_scopes_do_not_request_party_verification_read(): void
    {
        $scopes = collect(config('ehealth.legal_entity_types'))
            ->flatten()
            ->unique()
            ->values();

        $this->assertFalse(
            $scopes->contains('party_verification:read'),
            'party_verification:read must not be requested for any legal entity type'
        );
        $this->assertTrue($scopes->contains('party_verification:details'));
        $this->assertTrue($scopes->contains('party_verification:write'));
    }

    #[Test]
    public function role_scopes_do_not_include_party_verification_read(): void
    {
        $scopes = collect(config('ehealth.roles'))
            ->flatten()
            ->unique()
            ->values();

        $this->assertFalse(
            $scopes->contains('party_verification:read'),
            'party_verification:read must not be present in role scope configs'
        );
        $this->assertTrue($scopes->contains('party_verification:details'));
        $this->assertTrue($scopes->contains('party_verification:write'));
    }
}
