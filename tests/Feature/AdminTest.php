<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Services\UserAdminService;
use Illuminate\Validation\ValidationException;

class AdminTest extends AppTestCase
{
    public function test_admin_approves_pending_user_and_links_member(): void
    {
        $admin = $this->admin();
        $pending = $this->user('pending');
        $m = $this->member('ທອງ ຊົ່ງ');

        $this->actingAs($admin)->post('/admin/users/'.$pending->id.'/approve', ['member_id' => $m->id])->assertRedirect();

        $pending->refresh();
        $this->assertSame('member', $pending->role);
        $this->assertSame($m->id, $pending->member_id);
        $this->assertSame($admin->id, $pending->approved_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_approve', 'entity_id' => $pending->id, 'actor_id' => $admin->id]);
    }

    public function test_reject_disables_account(): void
    {
        $pending = $this->user('pending');
        $this->actingAs($this->admin())->post('/admin/users/'.$pending->id.'/reject')->assertRedirect();
        $this->assertNotNull($pending->fresh()->rejected_at);
        $this->assertFalse($pending->fresh()->isActive());
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->admin();
        $this->admin(); // a second admin exists, so only the self-rule applies
        $this->actingAs($admin)->put('/admin/users/'.$admin->id.'/role', ['role' => 'member'])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_last_admin_is_protected(): void
    {
        $a1 = $this->admin();
        $a2 = $this->admin();
        // a2 demotes a1 → allowed (a2 remains).
        $this->actingAs($a2)->put('/admin/users/'.$a1->id.'/role', ['role' => 'member'])->assertSessionHasNoErrors();
        $this->assertSame('member', $a1->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role_change', 'entity_id' => $a1->id]);

        // a2 is now the only admin: disabling or demoting it via another admin path must fail.
        $svc = app(UserAdminService::class);
        $a3 = $this->admin();
        $svc->changeRole($a3, 'member', $a2);
        $this->expectException(ValidationException::class);
        $svc->toggleDisabled($a2, $a3); // a3 is no longer admin in DB, a2 is the last one
    }

    public function test_user_cannot_be_linked_to_member_already_linked(): void
    {
        $m = $this->member('ເປັງ');
        $this->user('member', ['member_id' => $m->id]);
        $u2 = $this->user('member');
        $this->actingAs($this->admin())->put('/admin/users/'.$u2->id.'/member', ['member_id' => $m->id])->assertSessionHasErrors('member_id');
        $this->assertNull($u2->fresh()->member_id);
    }

    public function test_admin_page_renders_audit_log(): void
    {
        $admin = $this->admin();
        $this->member('ສະມາຊິກ');
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Audit Log');
        $this->actingAs($admin)->get('/admin?tab=audit')->assertOk()->assertSee('ເພີ່ມສະມາຊິກ');
        $this->assertGreaterThan(0, AuditLog::count());
    }
}
