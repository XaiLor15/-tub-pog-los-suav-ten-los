<?php

namespace Tests\Feature;

use App\Models\MediaItem;
use App\Models\News;
use App\Models\NewsComment;
use Illuminate\Support\Facades\Storage;

/**
 * Permission matrix (Dev Handoff "ສິດຕາມ Role") — enforced server-side, not only hidden in the UI.
 */
class AccessControlTest extends AppTestCase
{
    public function test_guest_cannot_see_private_phone_numbers_anywhere(): void
    {
        $m = $this->member('ເປັງ ຊົ່ງ', 'male', null, ['phone' => '+8562055123344', 'birth_date' => '1978-03-12', 'village' => 'ບ້ານລັບ']);

        $this->get('/members')->assertOk()->assertDontSee('5512')->assertDontSee('12/03/1978')->assertSee('1978');
        $this->get('/members/'.$m->id)->assertOk()->assertDontSee('5512')->assertDontSee('ບ້ານລັບ');
        $this->getJson('/members/'.$m->id.'/lineage')->assertOk()->assertJsonPath('member.phone', null)->assertJsonPath('member.birth_date', null);
        $this->getJson('/search?q=ເປັງ')->assertOk()->assertDontSee('5512');
        $this->get('/members/export.csv')->assertRedirect();
    }

    public function test_pending_user_sees_same_as_guest_and_cannot_write(): void
    {
        $this->member('ເປັງ ຊົ່ງ', 'male', null, ['phone' => '+8562055123344']);
        $pending = $this->user('pending');

        $this->actingAs($pending)->get('/members')->assertOk()->assertDontSee('5512');
        $this->actingAs($pending)->get('/profile')->assertOk()->assertSee('ລໍຖ້າອະນຸມັດ');
        $this->actingAs($pending)->post('/members', ['name' => 'X', 'sex' => 'male', 'is_alive' => 1, 'generation' => 1])->assertForbidden();
        $this->actingAs($pending)->get('/finance')->assertRedirect('/unauthorized?from=%2Ffinance&reason=pending');
        $this->assertDatabaseMissing('members', ['name' => 'X']);
    }

    public function test_guest_is_sent_to_unauthorized_with_return_path(): void
    {
        $this->get('/finance')->assertRedirect('/unauthorized?from=%2Ffinance&reason=guest');
        $this->post('/finance/transactions', [])->assertStatus(401);
        $this->get('/unauthorized?from=/finance&reason=guest')->assertOk()->assertSee('ກະລຸນາເຂົ້າສູ່ລະບົບ')->assertSee('redirect=%2Ffinance', false);
    }

    public function test_member_cannot_use_admin_functions(): void
    {
        $member = $this->user('member');
        $other = $this->user('pending');

        $this->actingAs($member)->get('/admin')->assertRedirect('/unauthorized?from=%2Fadmin&reason=role');
        $this->actingAs($member)->post('/admin/users/'.$other->id.'/approve')->assertForbidden();
        $this->actingAs($member)->put('/admin/users/'.$member->id.'/role', ['role' => 'admin'])->assertForbidden();
        $this->actingAs($member)->post('/finance/transactions', ['date' => now()->toDateString(), 'description' => 'x', 'type' => 'income', 'category' => 'ຄ່າບຳລຸງ', 'amount' => 1000])->assertForbidden();
        $this->actingAs($member)->post('/leadership', [])->assertForbidden();

        $this->assertSame('member', $member->fresh()->role);
        $this->assertSame('pending', $other->fresh()->role);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_member_can_read_finance_but_sees_read_only_banner(): void
    {
        $this->actingAs($this->user('member'))->get('/finance')->assertOk()->assertSee('ທ່ານເບິ່ງໄດ້ຢ່າງດຽວ')->assertDontSee('data-modal-open="tx-form"', false);
        $this->actingAs($this->admin())->get('/finance')->assertOk()->assertSee('data-modal-open="tx-form"', false);
    }

    public function test_private_events_are_hidden_from_guests(): void
    {
        $private = $this->event(['title' => 'ກອງປະຊຸມລັບ', 'is_public' => false]);
        $public = $this->event(['title' => 'ບຸນສາທາລະນະ', 'is_public' => true]);

        $this->get('/activities')->assertOk()->assertSee('ບຸນສາທາລະນະ')->assertDontSee('ກອງປະຊຸມລັບ');
        $this->get('/activities/'.$private->id)->assertForbidden();
        $this->get('/activities/'.$public->id.'?tab=donations')->assertOk()->assertDontSee('ຍອດບໍລິຈາກ');
        $this->actingAs($this->user('member'))->get('/activities/'.$private->id)->assertOk()->assertSee('ກອງປະຊຸມລັບ');
    }

    public function test_draft_news_visible_only_to_admin(): void
    {
        $n = new News(['title' => 'ຮ່າງລັບ', 'body' => 'x', 'category' => 'ປະກາດ']);
        $n->save();

        $this->get('/news/'.$n->id)->assertForbidden();
        $this->actingAs($this->user('member'))->get('/news/'.$n->id)->assertForbidden();
        $this->actingAs($this->admin())->get('/news/'.$n->id)->assertOk()->assertSee('ຮ່າງລັບ');
    }

    public function test_comments_only_by_members_and_editable_only_by_author(): void
    {
        $n = new News(['title' => 'ຂ່າວ', 'body' => 'x', 'category' => 'ປະກາດ']);
        $n->status = 'published';
        $n->published_at = now();
        $n->save();
        $author = $this->user('member');
        $other = $this->user('member');

        $this->post('/news/'.$n->id.'/comments', ['body' => 'hi'])->assertStatus(401);
        $this->actingAs($this->user('pending'))->post('/news/'.$n->id.'/comments', ['body' => 'hi'])->assertForbidden();
        $this->actingAs($author)->post('/news/'.$n->id.'/comments', ['body' => 'ສະບາຍດີ'])->assertRedirect();
        $c = NewsComment::firstOrFail();

        $this->actingAs($other)->put('/comments/'.$c->id, ['body' => 'hack'])->assertForbidden();
        $this->actingAs($other)->delete('/comments/'.$c->id)->assertForbidden();
        $this->actingAs($author)->put('/comments/'.$c->id, ['body' => 'ແກ້ແລ້ວ'])->assertRedirect();
        $this->assertSame('ແກ້ແລ້ວ', $c->fresh()->body);
        $this->actingAs($this->admin())->delete('/comments/'.$c->id)->assertRedirect();
        $this->assertDatabaseCount('news_comments', 0);
    }

    public function test_media_streams_respect_event_visibility(): void
    {
        $private = $this->event(['is_public' => false]);
        Storage::disk('local')->put('events/x.webp', 'img');
        $item = MediaItem::create(['module' => 'event', 'record_id' => $private->id, 'path' => 'events/x.webp', 'mime' => 'image/webp', 'size' => 3]);

        $this->get('/media/item/'.$item->id)->assertNotFound();
        $this->actingAs($this->user('member'))->get('/media/item/'.$item->id)->assertOk();
    }
}
