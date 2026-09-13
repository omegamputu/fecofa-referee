<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeExamPeriod;
use App\Models\Referees\RefereeMedicalExam;
use App\Models\Referees\RefereePhysicalTest;
use App\Models\Referees\RefereeRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

function makeRefereeForAptitudes(): Referee
{
    $league = League::create([
        'name' => 'Ligue test',
        'slug' => 'ligue-aptitudes',
        'code' => 'APT',
        'province' => 'Kinshasa',
    ]);
    $category = RefereeCategory::create([
        'name' => 'Nationale',
        'slug' => 'nationale-aptitudes',
    ]);
    $role = RefereeRole::create([
        'name' => 'Arbitre',
        'slug' => RefereeRole::CENTRAL_SLUG,
    ]);

    return Referee::create([
        'league_id' => $league->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $role->id,
        'last_name' => 'NDALA',
        'first_name' => 'Jean',
        'person_id' => 'APT-000001',
        'gender' => 'male',
    ]);
}

function userEditingReferees(): User
{
    $user = User::factory()->create();
    Permission::findOrCreate('edit_referee', 'web');
    $user->givePermissionTo('edit_referee');

    return $user;
}

function openRefereeExamPeriod(int $seasonYear = 2026): RefereeExamPeriod
{
    return RefereeExamPeriod::create([
        'season_year' => $seasonYear,
        'opens_at' => today()->subDay(),
        'closes_at' => today()->addDay(),
    ]);
}

test('medical and physical aptitudes can be recorded with supporting documents', function () {
    Storage::fake('public');

    $referee = makeRefereeForAptitudes();
    openRefereeExamPeriod();
    $user = userEditingReferees();
    $this->actingAs($user);

    Volt::test('referees.edit', ['referee' => $referee])
        ->set('medical_exam_date', today()->subDay()->format('Y-m-d'))
        ->set('medical_result', RefereeMedicalExam::RESULT_PASSED)
        ->set('medical_notes', 'Apte à arbitrer')
        ->set('medical_certificate', UploadedFile::fake()->create('certificat.pdf', 100, 'application/pdf'))
        ->set('physical_test_date', today()->format('Y-m-d'))
        ->set('physical_result', RefereePhysicalTest::RESULT_PASSED)
        ->set('physical_level', 'Test FIFA haute intensité')
        ->set('physical_notes', 'Barème validé')
        ->set('physical_report', UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf'))
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('referees.index'));

    $medicalExam = $referee->medicalExams()->sole();
    $physicalTest = $referee->physicalTests()->sole();

    expect($referee->fresh())
        ->has_medical_clearance->toBeTrue()
        ->has_physical_clearance->toBeTrue();

    $this->assertDatabaseHas(RefereeMedicalExam::class, [
        'referee_id' => $referee->id,
        'season_year' => 2026,
        'result' => RefereeMedicalExam::RESULT_PASSED,
        'notes' => 'Apte à arbitrer',
        'recorded_by' => $user->id,
    ]);
    $this->assertDatabaseHas(RefereePhysicalTest::class, [
        'referee_id' => $referee->id,
        'season_year' => 2026,
        'result' => RefereePhysicalTest::RESULT_PASSED,
        'level' => 'Test FIFA haute intensité',
    ]);
    Storage::disk('public')->assertExists($medicalExam->file_path);
    Storage::disk('public')->assertExists($physicalTest->file_path);
});

test('saving again during the open period updates the seasonal results without duplicates', function () {
    $referee = makeRefereeForAptitudes();
    openRefereeExamPeriod();
    $referee->medicalExams()->create([
        'season_year' => 2026,
        'exam_date' => today(),
        'result' => RefereeMedicalExam::RESULT_PASSED,
    ]);
    $referee->physicalTests()->create([
        'season_year' => 2026,
        'test_date' => today(),
        'result' => RefereePhysicalTest::RESULT_PASSED,
    ]);

    $this->actingAs(userEditingReferees());

    Volt::test('referees.edit', ['referee' => $referee])
        ->set('medical_exam_date', today()->subMonth()->format('Y-m-d'))
        ->set('medical_result', RefereeMedicalExam::RESULT_FAILED)
        ->set('physical_test_date', today()->subMonth()->format('Y-m-d'))
        ->set('physical_result', RefereePhysicalTest::RESULT_FAILED)
        ->call('update')
        ->assertHasNoErrors();

    expect($referee->fresh())
        ->has_medical_clearance->toBeFalse()
        ->has_physical_clearance->toBeFalse()
        ->and($referee->medicalExams()->count())->toBe(1)
        ->and($referee->physicalTests()->count())->toBe(1)
        ->and($referee->medicalExams()->sole()->result)->toBe(RefereeMedicalExam::RESULT_FAILED)
        ->and($referee->physicalTests()->sole()->result)->toBe(RefereePhysicalTest::RESULT_FAILED);
});

test('a user without edit permission cannot record an aptitude', function () {
    $referee = makeRefereeForAptitudes();
    $this->actingAs(User::factory()->create());

    Volt::test('referees.edit', ['referee' => $referee])
        ->set('medical_exam_date', today()->format('Y-m-d'))
        ->set('medical_result', RefereeMedicalExam::RESULT_PASSED)
        ->call('update')
        ->assertForbidden();

    expect($referee->medicalExams()->count())->toBe(0);
});

test('saving profile changes does not create untouched aptitude records', function () {
    $referee = makeRefereeForAptitudes();
    $this->actingAs(userEditingReferees());

    Volt::test('referees.edit', ['referee' => $referee])
        ->set('first_name', 'Jacques')
        ->call('update')
        ->assertHasNoErrors();

    expect($referee->fresh()->first_name)->toBe('Jacques')
        ->and($referee->medicalExams()->count())->toBe(0)
        ->and($referee->physicalTests()->count())->toBe(0);
});

test('aptitude results cannot be recorded outside an open examination period', function () {
    $referee = makeRefereeForAptitudes();
    RefereeExamPeriod::create([
        'season_year' => 2026,
        'opens_at' => today()->subMonth(),
        'closes_at' => today()->subDay(),
    ]);
    $this->actingAs(userEditingReferees());

    Volt::test('referees.edit', ['referee' => $referee])
        ->assertSet('aptitudePeriodOpen', false)
        ->set('aptitudeSeasonYear', 2026)
        ->set('medical_exam_date', today()->format('Y-m-d'))
        ->set('medical_result', RefereeMedicalExam::RESULT_PASSED)
        ->call('update')
        ->assertHasErrors('aptitude_period');

    expect($referee->medicalExams()->count())->toBe(0);
});
