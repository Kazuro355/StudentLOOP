<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'nim' => (string) random_int(2600000000, 2699999999),
            'jurusan' => 'Cyber Security',
            'angkatan' => '2023',
            'kampus' => 'Kemanggisan',
            'profile_completed_at' => now(),
        ]);
    }

    private function admin(): User
    {
        $admin = $this->member();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    public function test_admin_panel_is_for_admin_accounts_only(): void
    {
        $member = $this->member();
        $admin = $this->admin();

        $this->actingAs($member)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Ruang admin');
        $this->actingAs($admin)->get('/admin/laporan')->assertOk();
        $this->actingAs($admin)->get('/admin/pengguna')->assertOk();
    }

    public function test_admin_can_hide_a_reported_item_from_the_marketplace(): void
    {
        $admin = $this->admin();
        $reporter = $this->member();
        $owner = $this->member();
        $category = Category::firstOrCreate(['slug' => 'elektronik'], ['name' => 'Elektronik']);
        $item = Item::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'type' => 'beli',
            'title' => 'Barang yang dilaporkan',
            'description' => 'Deskripsi barang.',
            'condition' => 'Baik',
            'price' => 10000,
            'deposit' => 0,
            'min_days' => 1,
            'max_days' => 14,
            'pickup_method' => 'ambil_kampus',
            'kampus' => 'Kemanggisan',
            'status' => 'tersedia',
        ]);
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'target_type' => 'item',
            'target_id' => $item->id,
            'reason' => Report::REASONS[0],
        ]);

        $this->actingAs($admin)->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Barang yang dilaporkan');

        $this->actingAs($admin)->patch(route('admin.reports.resolve', $report), [
            'action' => 'hide_item',
        ])->assertRedirect();

        $this->assertNotNull($item->fresh()->moderated_at);
        $this->assertSame('selesai', $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->reviewed_by);
        $this->assertNull(Item::browsable()->find($item->id));
        $this->actingAs($reporter)->get('/barang/' . $item->id)->assertNotFound();
    }

    public function test_suspended_users_are_signed_out_and_cannot_sign_in_again(): void
    {
        $admin = $this->admin();
        $member = $this->member(['password' => 'password123']);

        $this->actingAs($admin)->patch(route('admin.users.suspension', $member), [
            'suspended' => '1',
        ])->assertRedirect();

        $this->actingAs($member->fresh())->get('/beranda')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/masuk', [
            'email' => $member->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }
}
