<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeExamPeriod;
use App\Models\Referees\RefereeMedicalExam;
use App\Models\Referees\RefereePhysicalTest;
use App\Models\Referees\RefereeRole;
use App\Models\Referees\RefereeSeason;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard reports seasonal readiness and designations', function () {
    $league = League::create([
        'name' => 'Ligue Dashboard',
        'slug' => 'ligue-dashboard',
        'code' => 'LDB',
        'province' => 'Kinshasa',
    ]);
    $category = RefereeCategory::create([
        'name' => 'Nationale Dashboard',
        'slug' => 'nationale-dashboard',
    ]);
    $role = RefereeRole::create([
        'name' => 'Arbitre',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);

    $eligible = Referee::create([
        'league_id' => $league->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $role->id,
        'person_id' => 'DASH-001',
        'last_name' => 'Eligible',
        'first_name' => 'Arbitre',
        'is_active' => true,
    ]);
    Referee::create([
        'league_id' => $league->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $role->id,
        'person_id' => 'DASH-002',
        'last_name' => 'En attente',
        'first_name' => 'Arbitre',
        'is_active' => true,
    ]);

    $eligible->medicalExams()->create([
        'season_year' => 2026,
        'exam_date' => today(),
        'result' => RefereeMedicalExam::RESULT_PASSED,
    ]);
    $eligible->physicalTests()->create([
        'season_year' => 2026,
        'test_date' => today(),
        'result' => RefereePhysicalTest::RESULT_PASSED,
    ]);
    $eligible->seasons()->create([
        'season_year' => 2026,
        'competition' => RefereeSeason::COMPETITION_LIGUE_1,
        'status' => 'active',
    ]);
    RefereeExamPeriod::create([
        'season_year' => 2026,
        'opens_at' => today()->subDay(),
        'closes_at' => today()->addDay(),
    ]);

    $this->actingAs(User::factory()->create());

    $component = Volt::test('dashboard')->set('seasonYear', 2026);

    expect($component->viewData('totalReferees'))->toBe(2)
        ->and($component->viewData('activeOfficialsCount'))->toBe(2)
        ->and($component->viewData('medicalPassedCount'))->toBe(1)
        ->and($component->viewData('physicalPassedCount'))->toBe(1)
        ->and($component->viewData('eligibleCount'))->toBe(1)
        ->and($component->viewData('designatedLigueOneCount'))->toBe(1)
        ->and($component->viewData('designatedLigueTwoCount'))->toBe(0)
        ->and($component->viewData('medicalNotValidatedCount'))->toBe(1)
        ->and($component->viewData('physicalNotValidatedCount'))->toBe(1)
        ->and($component->viewData('eligibleNotDesignatedCount'))->toBe(0)
        ->and($component->viewData('examPeriodState')['label'])->toBe(__('Open'));
});

test('administrator dashboard uses the shared seasonal dashboard', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('admin_access', 'web');
    $user->givePermissionTo('admin_access');
    $this->actingAs($user);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('Seasonal overview of referee readiness and designations.'));
});
