<?php

use App\Models\Instructors\Instructor;
use App\Models\Instructors\InstructorRole;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

test('an instructor profile photo can be updated', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Permission::findOrCreate('edit_instructor');
    $user->givePermissionTo('edit_instructor');
    $category = RefereeCategory::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);
    $refereeRole = RefereeRole::create([
        'name' => 'Test Referee',
        'slug' => 'test-referee',
    ]);
    $instructorRole = InstructorRole::create([
        'name' => 'Technique',
        'slug' => 'technique',
    ]);
    $instructor = Instructor::create([
        'instructor_role_id' => $instructorRole->id,
        'referee_category_id' => $category->id,
        'referee_role_id' => $refereeRole->id,
        'last_name' => 'DOE',
        'first_name' => 'Jane',
        'gender' => 'female',
    ]);

    $this->actingAs($user);

    Volt::test('instructors.edit', ['instructor' => $instructor])
        ->set('profile_photo', UploadedFile::fake()->image('profile.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $path = $instructor->fresh()->profile_photo_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});
