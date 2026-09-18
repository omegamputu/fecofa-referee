<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $league = League::create([
        'name' => 'Ligue de Kinshasa',
        'slug' => 'ligue-de-kinshasa',
        'code' => 'LIFKIN',
        'province' => 'Kinshasa',
    ]);
    $category = RefereeCategory::create([
        'name' => 'Arbitre fédéral',
        'slug' => 'arbitre-federal',
    ]);
    $role = RefereeRole::create([
        'name' => 'Arbitre central',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);

    $this->referee = Referee::create([
        'league_id' => $league->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $role->id,
        'person_id' => 'LIFKIN-000001',
        'last_name' => 'NDALA',
        'first_name' => 'Jean',
        'gender' => 'male',
        'phone' => '+243810000000',
        'email' => 'jean.ndala@example.test',
        'is_active' => true,
    ]);

    foreach (['view_referee', 'edit_referee', 'manage_seasons'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

test('a referee found in the list links to their profile', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_referee');

    $this->actingAs($user)
        ->get(route('referees.index'))
        ->assertOk()
        ->assertSee('NDALA')
        ->assertSee(route('referees.show', $this->referee), false);
});

test('a user with view permission can see the referee profile', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_referee');

    $this->actingAs($user)
        ->get(route('referees.show', $this->referee))
        ->assertOk()
        ->assertSee('NDALA Jean')
        ->assertSee('LIFKIN-000001')
        ->assertSee('+243810000000')
        ->assertSee('Arbitre central');
});

test('profile action buttons respect edit and designation permissions', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('view_referee');

    $this->actingAs($viewer)
        ->get(route('referees.show', $this->referee))
        ->assertOk()
        ->assertDontSee(route('referees.edit', $this->referee), false)
        ->assertDontSee(route('referees.designations.index', ['search' => $this->referee->person_id]), false);

    $editor = User::factory()->create();
    $editor->givePermissionTo(['view_referee', 'edit_referee']);

    $this->actingAs($editor)
        ->get(route('referees.show', $this->referee))
        ->assertOk()
        ->assertSee(route('referees.edit', $this->referee), false)
        ->assertDontSee(route('referees.designations.index', ['search' => $this->referee->person_id]), false);

    $designationManager = User::factory()->create();
    $designationManager->givePermissionTo(['view_referee', 'manage_seasons']);

    $this->actingAs($designationManager)
        ->get(route('referees.show', $this->referee))
        ->assertOk()
        ->assertDontSee(route('referees.edit', $this->referee), false)
        ->assertSee(route('referees.designations.index', ['search' => $this->referee->person_id]), false);
});

test('a user without view permission cannot access a referee profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('referees.show', $this->referee))
        ->assertForbidden();
});
