<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

test('creating a referee preserves the start year', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('create_referee');
    $user->givePermissionTo('create_referee');
    $league = League::create([
        'name' => 'Test League',
        'slug' => 'test-league',
        'code' => 'TEST',
        'province' => 'Kinshasa',
    ]);
    $category = RefereeCategory::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);
    $role = RefereeRole::create([
        'name' => 'Test Referee',
        'slug' => 'test-referee',
    ]);

    $this->actingAs($user);

    Volt::test('referees.create')
        ->set('last_name', 'NDALA')
        ->set('first_name', 'Jean')
        ->set('gender', 'male')
        ->set('league_id', $league->id)
        ->set('start_year', 2008)
        ->set('referee_category_id', $category->id)
        ->set('referee_role_id', $role->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas(Referee::class, [
        'last_name' => 'NDALA',
        'start_year' => 2008,
        'person_id' => 'TEST-000001',
    ]);
});

test('a user without edit permission cannot mutate referee clearances', function () {
    $user = User::factory()->create();
    $league = League::create([
        'name' => 'Test League',
        'slug' => 'test-league',
        'code' => 'TEST',
        'province' => 'Kinshasa',
    ]);
    $category = RefereeCategory::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);
    $role = RefereeRole::create([
        'name' => 'Test Referee',
        'slug' => 'test-referee',
    ]);
    $referee = Referee::create([
        'league_id' => $league->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $role->id,
        'last_name' => 'NDALA',
        'first_name' => 'Jean',
        'gender' => 'male',
    ]);

    $this->actingAs($user);

    Volt::test('referees.index')
        ->call('toggleMedical', $referee->id)
        ->assertForbidden();

    expect($referee->fresh()->has_medical_clearance)->toBeFalse();
});
