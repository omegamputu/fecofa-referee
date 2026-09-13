<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use App\Models\Referees\RefereeSeason;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

function makeDesignationReferee(RefereeRole $role, array $overrides = []): Referee
{
    $league = League::query()->firstOrCreate(
        ['slug' => 'ligue-test'],
        [
            'name' => 'Ligue test',
            'code' => 'TEST',
            'province' => 'Kinshasa',
        ],
    );
    $category = RefereeCategory::query()->firstOrCreate(
        ['slug' => 'nationale-test'],
        ['name' => 'Nationale test'],
    );

    $referee = Referee::create(array_merge([
        'league_id' => $league->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $role->id,
        'last_name' => 'NDALA',
        'first_name' => 'Jean',
        'person_id' => 'TEST-'.str()->random(8),
        'gender' => 'male',
        'is_active' => true,
        'has_medical_clearance' => true,
        'has_physical_clearance' => true,
    ], $overrides));

    $referee->medicalExams()->create([
        'season_year' => 2026,
        'exam_date' => today(),
        'result' => $referee->has_medical_clearance ? 'passed' : 'failed',
    ]);
    $referee->physicalTests()->create([
        'season_year' => 2026,
        'test_date' => today(),
        'result' => $referee->has_physical_clearance ? 'passed' : 'failed',
    ]);

    return $referee;
}

function userManagingSeasons(): User
{
    $user = User::factory()->create([
        'password_set_at' => now(),
        'email_verified_at' => now(),
    ]);
    Permission::findOrCreate('manage_seasons', 'web');
    $user->givePermissionTo('manage_seasons');

    return $user;
}

test('eligible referees and assistants can be designated for Ligue 1 and Ligue 2', function () {
    $centralRole = RefereeRole::create([
        'name' => 'Arbitre',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);
    $assistantRole = RefereeRole::create([
        'name' => 'Arbitre assistant',
        'slug' => RefereeRole::ASSISTANT_SLUG,
    ]);
    $central = makeDesignationReferee($centralRole);
    $assistant = makeDesignationReferee($assistantRole, [
        'last_name' => 'KABONGO',
        'person_id' => 'TEST-ASSISTANT',
    ]);
    $user = userManagingSeasons();

    $this->actingAs($user);

    $component = Volt::test('referees.designations.index')
        ->set('seasonYear', 2026)
        ->set('competition', RefereeSeason::COMPETITION_LIGUE_1)
        ->call('toggleDesignation', $central->id)
        ->call('toggleDesignation', $assistant->id)
        ->assertHasNoErrors();

    $component
        ->set('competition', RefereeSeason::COMPETITION_LIGUE_2)
        ->call('toggleDesignation', $central->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas(RefereeSeason::class, [
        'referee_id' => $central->id,
        'season_year' => 2026,
        'competition' => RefereeSeason::COMPETITION_LIGUE_1,
        'status' => 'active',
        'designated_by' => $user->id,
    ]);
    $this->assertDatabaseHas(RefereeSeason::class, [
        'referee_id' => $assistant->id,
        'season_year' => 2026,
        'competition' => RefereeSeason::COMPETITION_LIGUE_1,
        'status' => 'active',
    ]);
    $this->assertDatabaseHas(RefereeSeason::class, [
        'referee_id' => $central->id,
        'season_year' => 2026,
        'competition' => RefereeSeason::COMPETITION_LIGUE_2,
        'status' => 'active',
    ]);
    expect(RefereeSeason::query()->count())->toBe(3);
});

test('an ineligible referee cannot be designated', function () {
    $role = RefereeRole::create([
        'name' => 'Arbitre',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);
    $referee = makeDesignationReferee($role, ['has_physical_clearance' => false]);

    $this->actingAs(userManagingSeasons());

    Volt::test('referees.designations.index')
        ->set('seasonYear', 2026)
        ->assertSee('NDALA Jean')
        ->call('toggleDesignation', $referee->id)
        ->assertHasErrors('designation');

    $this->assertDatabaseMissing(RefereeSeason::class, [
        'referee_id' => $referee->id,
        'season_year' => 2026,
    ]);
});

test('an existing designation can be removed after the referee becomes ineligible', function () {
    $role = RefereeRole::create([
        'name' => 'Arbitre',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);
    $referee = makeDesignationReferee($role);

    $this->actingAs(userManagingSeasons());

    $component = Volt::test('referees.designations.index')
        ->set('seasonYear', 2026)
        ->call('toggleDesignation', $referee->id)
        ->assertHasNoErrors();

    $referee->update(['is_active' => false]);

    $component
        ->call('toggleDesignation', $referee->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas(RefereeSeason::class, [
        'referee_id' => $referee->id,
        'season_year' => 2026,
        'competition' => RefereeSeason::COMPETITION_LIGUE_1,
        'status' => 'inactive',
    ]);
});

test('a user without season permission cannot access designations', function () {
    $user = User::factory()->create([
        'password_set_at' => now(),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('referees.designations.index'))
        ->assertForbidden();
});

test('the eligible referee list can be exported as a PDF for a season', function () {
    $role = RefereeRole::create([
        'name' => 'Arbitre',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);
    makeDesignationReferee($role);
    $user = userManagingSeasons();
    Permission::findOrCreate('export_referee_data', 'web');
    $user->givePermissionTo('export_referee_data');

    $response = $this->actingAs($user)
        ->get(route('referees.eligible.export', ['season' => 2026]));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain('fecofa_arbitres_eligibles_2026-2027.pdf')
        ->and(strlen($response->getContent()))->toBeGreaterThan(1000);
});
