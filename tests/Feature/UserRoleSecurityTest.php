<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_not_mass_assignable(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $user->update(['name' => 'New Name', 'role' => UserRole::Admin]);

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertTrue($user->fresh()->role === UserRole::User);
        $this->assertFalse($user->fresh()->isAdmin());
    }
}
