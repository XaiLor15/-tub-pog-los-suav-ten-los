<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\FamilyService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Family rules: Phaj = father + 1, no cycles, consecutive birth_order, unlink never deletes. */
class FamilyTreeTest extends AppTestCase
{
    private function family(): FamilyService
    {
        return app(FamilyService::class);
    }

    public function test_generation_is_father_plus_one_and_birth_order_appends(): void
    {
        $root = $this->member('ເຈົ໋');
        $a = $this->member('A', 'male', $root);
        $b = $this->member('B', 'female', $root);

        $this->assertSame(1, $root->generation);
        $this->assertSame(2, $a->generation);
        $this->assertSame([1, 2], [$a->fresh()->birth_order, $b->fresh()->birth_order]);
        $this->assertSame('ລູກສາວຄົນທີ 2', $b->fresh()->childLabel());
    }

    public function test_insert_at_shifts_siblings(): void
    {
        $root = $this->member('Root');
        $a = $this->member('A', 'male', $root);
        $b = $this->member('B', 'male', $root);
        $c = $this->family()->create(['name' => 'C', 'sex' => 'male', 'is_alive' => true], $root->id, 1, null, $this->admin());

        $this->assertSame([1, 2, 3], [$c->birth_order, $a->fresh()->birth_order, $b->fresh()->birth_order]);
    }

    public function test_a_member_cannot_be_their_own_father_or_ancestor(): void
    {
        $root = $this->member('Root');
        $child = $this->member('Child', 'male', $root);
        $grandchild = $this->member('Grand', 'male', $child);
        $admin = $this->admin();

        $this->actingAs($admin)->put('/members/'.$root->id, ['name' => 'Root', 'sex' => 'male', 'is_alive' => 1, 'father_id' => $grandchild->id])
            ->assertSessionHasErrors('father_id');
        $this->actingAs($admin)->put('/members/'.$root->id, ['name' => 'Root', 'sex' => 'male', 'is_alive' => 1, 'father_id' => $root->id])
            ->assertSessionHasErrors('father_id');
        $this->assertNull($root->fresh()->father_id);
    }

    public function test_circular_relationship_is_rejected_by_service(): void
    {
        $root = $this->member('Root');
        $child = $this->member('Child', 'male', $root);
        $this->expectException(ValidationException::class);
        $this->family()->update($root, ['name' => 'Root', 'sex' => 'male'], $child->id, null, null, $this->admin());
    }

    public function test_father_must_be_male(): void
    {
        $mother = $this->member('Mother', 'female');
        $this->actingAs($this->admin())->post('/members', ['name' => 'Kid', 'sex' => 'male', 'is_alive' => 1, 'father_id' => $mother->id])
            ->assertSessionHasErrors('father_id');
        $this->assertDatabaseMissing('members', ['name' => 'Kid']);
    }

    public function test_reorder_keeps_consecutive_birth_order(): void
    {
        $root = $this->member('Root');
        $kids = collect(['A', 'B', 'C', 'D'])->map(fn ($n) => $this->member($n, 'male', $root));

        $this->actingAs($this->admin())->post('/members/reorder', ['father_id' => $root->id, 'ids' => [$kids[3]->id, $kids[1]->id, $kids[0]->id, $kids[2]->id]])->assertRedirect();
        $orders = Member::where('father_id', $root->id)->orderBy('birth_order')->pluck('birth_order', 'name')->all();
        $this->assertSame(['D' => 1, 'B' => 2, 'A' => 3, 'C' => 4], $orders);

        // Partial / foreign id lists are rejected.
        $this->actingAs($this->admin())->post('/members/reorder', ['father_id' => $root->id, 'ids' => [$kids[0]->id]])->assertSessionHasErrors('ids');

        $this->actingAs($this->admin())->post('/members/'.$kids[0]->id.'/move', ['direction' => 'up']);
        $this->assertSame([1, 2, 3, 4], Member::where('father_id', $root->id)->orderBy('birth_order')->pluck('birth_order')->all());
        $this->assertSame(2, $kids[0]->fresh()->birth_order);
    }

    public function test_unlink_sets_father_null_and_never_deletes(): void
    {
        $root = $this->member('Root');
        $a = $this->member('A', 'male', $root);
        $b = $this->member('B', 'male', $root);
        $c = $this->member('C', 'male', $root);
        $before = Member::withTrashed()->count();

        $this->actingAs($this->user('member'))->post('/members/'.$b->id.'/unlink')->assertRedirect();

        $this->assertSame($before, Member::withTrashed()->count());
        $this->assertNotNull(Member::find($b->id));
        $this->assertNull($b->fresh()->father_id);
        $this->assertSame([1, 2], [$a->fresh()->birth_order, $c->fresh()->birth_order]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_unlink', 'entity_id' => $b->id]);
    }

    public function test_moving_a_branch_recomputes_generations(): void
    {
        $r1 = $this->member('R1');
        $r2 = $this->member('R2');
        $x = $this->member('X', 'male', $r1);
        $y = $this->member('Y', 'male', $x);
        $mid = $this->member('Mid', 'male', $r2);

        $this->family()->update($x, ['name' => 'X', 'sex' => 'male'], $mid->id, null, null, $this->admin());

        $this->assertSame(3, $x->fresh()->generation);
        $this->assertSame(4, $y->fresh()->generation);
        $this->assertSame(1, $x->fresh()->birth_order);
    }

    public function test_cannot_make_a_father_female(): void
    {
        $root = $this->member('Root');
        $this->member('Kid', 'male', $root);
        $this->expectException(ValidationException::class);
        $this->family()->update($root, ['name' => 'Root', 'sex' => 'female'], null, null, 1, $this->admin());
    }

    public function test_soft_delete_is_admin_only_and_keeps_history(): void
    {
        $root = $this->member('Root');
        $a = $this->member('A', 'male', $root);
        $b = $this->member('B', 'male', $root);

        $this->actingAs($this->user('member'))->delete('/members/'.$a->id)->assertForbidden();
        $this->actingAs($this->admin())->delete('/members/'.$root->id)->assertSessionHasErrors('member'); // has children
        $this->actingAs($this->admin())->delete('/members/'.$a->id)->assertRedirect();

        $this->assertSoftDeleted('members', ['id' => $a->id]);
        $this->assertSame(1, $b->fresh()->birth_order);
    }

    public function test_duplicate_requires_confirmation(): void
    {
        $this->member('ທອງ ຊົ່ງ', 'male', null, ['birth_date' => '1983-05-08']);
        $admin = $this->admin();
        $payload = ['name' => 'ທອງ ຊົ່ງ', 'sex' => 'male', 'is_alive' => 1, 'birth_date' => '1983-05-08', 'generation' => 1];

        $this->actingAs($admin)->post('/members', $payload)->assertSessionHasErrors('duplicate_confirmed');
        $this->assertSame(1, Member::where('name', 'ທອງ ຊົ່ງ')->count());
        $this->actingAs($admin)->post('/members', $payload + ['duplicate_confirmed' => 1])->assertRedirect();
        $this->assertSame(2, Member::where('name', 'ທອງ ຊົ່ງ')->count());
    }

    public function test_phone_is_normalised_and_validated(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/members', ['name' => 'P', 'sex' => 'male', 'is_alive' => 1, 'generation' => 1, 'phone' => '020 5577 8899'])->assertRedirect();
        $this->assertSame('+8562055778899', Member::where('name', 'P')->value('phone'));
        $this->actingAs($admin)->post('/members', ['name' => 'Q', 'sex' => 'male', 'is_alive' => 1, 'generation' => 1, 'phone' => '12345'])->assertSessionHasErrors('phone');
    }

    public function test_ancestors_report_phaj_continuity(): void
    {
        $r = $this->member('R');
        $a = $this->member('A', 'male', $r);
        $b = $this->member('B', 'male', $a);
        $chain = $this->family()->ancestors($b);
        $this->assertSame(['R', 'A', 'B'], $chain->pluck('member.name')->all());
        $this->assertTrue($chain->every(fn ($x) => $x['phaj_ok']));

        $json = $this->getJson('/members/tree-data')->assertOk()->json('nodes');
        $this->assertCount(3, $json);
    }

    public function test_sibling_labels_are_computed_not_stored(): void
    {
        $root = $this->member('Root');
        $older = $this->member('Sister', 'female', $root);
        $me = $this->member('Me', 'male', $root);
        $younger = $this->member('Bro', 'male', $root);

        $this->assertSame('ເອື້ອຍ', $me->fresh()->siblingLabelFor($older->fresh()));
        $this->assertSame('ນ້ອງຊາຍ', $me->fresh()->siblingLabelFor($younger->fresh()));
        $this->assertSame('ຕົນເອງ', $me->fresh()->siblingLabelFor($me->fresh()));
        $this->assertFalse(Schema::hasColumn('members', 'sibling_label'));
    }
}
