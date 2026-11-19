<?php

namespace Database\Seeders;

use App\Models\DonationPledge;
use App\Models\Elder;
use App\Models\Event;
use App\Models\FundMember;
use App\Models\Leadership;
use App\Models\Member;
use App\Models\News;
use App\Models\NewsComment;
use App\Models\Participant;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\FinanceService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * LOCAL / QA ONLY — sample family used for screenshots and manual testing.
 * Refuses to run in production. Run: php artisan db:seed --class=DemoSeeder
 * All data goes through the real services (FamilyService / FinanceService).
 */
class DemoSeeder extends Seeder
{
    public function run(FamilyService $family, FinanceService $finance): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder must never run in production.');
        }
        $admin = User::where('role', 'admin')->firstOrFail();
        auth()->setUser($admin);

        $add = function (string $name, string $sex, ?string $birth, ?Member $father, array $extra = []) use ($family, $admin) {
            return $family->create(array_merge([
                'name' => $name, 'sex' => $sex, 'birth_date' => $birth, 'is_alive' => true,
                'province' => 'ຊຽງຂວາງ',
            ], $extra), $father?->id, null, $father ? null : 1, $admin);
        };

        $p1 = $add('ເຈົ໋ ຊົ່ງ', 'male', '1890-01-01', null, ['birth_year_only' => true, 'is_alive' => false, 'death_year' => 1965, 'province' => 'ຫົວພັນ', 'hmong_name' => 'Ntxawg Xyooj']);
        $p2a = $add('ຕົ່ງ ຊົ່ງ', 'male', '1921-01-01', $p1, ['birth_year_only' => true, 'is_alive' => false, 'death_year' => 1998, 'province' => 'ຫົວພັນ']);
        $p2b = $add('ທົ່ງ ຊົ່ງ', 'male', '1926-01-01', $p1, ['birth_year_only' => true, 'is_alive' => false, 'death_year' => 2001, 'province' => 'ຊຽງຂວາງ']);
        $add('ປາງ ລີ', 'female', '1925-01-01', $p1, ['birth_year_only' => true, 'is_alive' => false, 'death_year' => 2004]);
        $je = $add('ເຈ ຊົ່ງ', 'male', '1952-01-02', $p2a, ['phone' => '+8562022334455', 'hmong_name' => 'Tsheej Xyooj', 'village' => 'ບ້ານໂພນສະຫວັນ']);
        $add('ມີ ຊົ່ງ', 'female', '1955-06-14', $p2a, ['phone' => '+8562066771122']);
        $xao = $add('ຊົ່ວ ຊົ່ງ', 'male', '1958-10-10', $p2a, ['phone' => '+856305123456', 'province' => 'ບໍລິຄຳໄຊ', 'village' => 'ບ້ານປາກກະດິງ']);
        $xai = $add('ໄຊ ຊົ່ງ', 'male', '1961-12-18', $p2a, ['phone' => '+8562011223344']);
        $lao = $add('ເລົ່າ ຊົ່ງ', 'male', '1979-03-03', $p2b, ['phone' => '+8562044332211']);
        $peng = $add('ເປັງ ຊົ່ງ', 'male', '1978-03-12', $je, ['phone' => '+8562055123344', 'hmong_name' => 'Peeb Xyooj', 'village' => 'ບ້ານໂພນສະຫວັນ']);
        $mai = $add('ມາຍ ຊົ່ງ', 'female', '1981-08-05', $je, ['phone' => '+8562098761122', 'province' => 'ນະຄອນຫຼວງວຽງຈັນ', 'village' => 'ບ້ານໂພນສະອາດ']);
        $thong = $add('ທອງ ຊົ່ງ', 'male', '1983-05-08', $je, ['phone' => '+8562055778899', 'province' => 'ບໍລິຄຳໄຊ']);
        $ye = $add('ເຢ ຊົ່ງ', 'female', '1985-04-30', $je, ['phone' => '+8562033445566', 'province' => 'ຫຼວງພະບາງ', 'village' => 'ບ້ານຊຽງແມນ']);
        $xe = $add('ເຊ ຊົ່ງ', 'male', '2004-06-21', $peng, ['phone' => '+8562077889900', 'province' => 'ນະຄອນຫຼວງວຽງຈັນ', 'village' => 'ບ້ານດົງໂດກ']);
        $add('ຢົ່ງ ຊົ່ງ', 'female', '2007-09-14', $peng, ['province' => 'ນະຄອນຫຼວງວຽງຈັນ']);
        $add('ປາ ຊົ່ງ', 'female', '2012-02-02', $peng, ['province' => 'ນະຄອນຫຼວງວຽງຈັນ']);
        $add('ກ້ຽງ ຊົ່ງ', 'male', '2025-02-03', $xe, ['province' => 'ນະຄອນຫຼວງວຽງຈັນ']);
        foreach ([['ໄຊທອງ ຊົ່ງ', $xao], ['ກ້ຽງໄຊ ຊົ່ງ', $xai], ['ມາລີ ຊົ່ງ', $lao], ['ປາ ຊົ່ງ', $thong]] as $i => [$n, $f]) {
            $add($n, $i === 2 ? 'female' : 'male', (1985 + $i * 3).'-02-0'.($i + 1), $f, ['province' => ['ຊຽງຂວາງ', 'ວຽງຈັນ', 'ຫຼວງພະບາງ', 'ບໍລິຄຳໄຊ'][$i]]);
        }
        $yer = $add('ໃສ ທໍ່', 'female', '1980-01-01', null, ['birth_year_only' => true, 'phone' => '+8562044551122', 'province' => 'ນະຄອນຫຼວງວຽງຈັນ']);
        $yer->forceFill(['generation' => 4])->save();

        // Leadership 2025–2028
        $lead = [[$je, 'ປະທານຕະກູນ', 'executive'], [$xao, 'ຮອງປະທານ', 'executive'], [$xai, 'ຮອງປະທານ', 'executive'], [$peng, 'ເລຂາທິການ', 'executive'],
            [$mai, 'ຫົວໜ້າຝ່າຍການເງິນ', 'department_head'], [$lao, 'ຫົວໜ້າຝ່າຍວັດທະນະທຳ', 'department_head'], [$ye, 'ຫົວໜ້າຝ່າຍແມ່ຍິງ', 'department_head'], [$xe, 'ຫົວໜ້າຝ່າຍເຍົາວະຊົນ', 'department_head'],
            [$thong, 'ກຳມະການ', 'committee'], [$yer, 'ກຳມະການ', 'committee']];
        foreach ($lead as $i => [$m, $pos, $grp]) {
            $l = new Leadership(['member_id' => $m->id, 'position' => $pos, 'group' => $grp, 'term_start' => '2025-01-01', 'term_end' => '2028-12-31']);
            $l->sort_order = $i + 1;
            $l->save();
        }

        // Elders
        foreach ([[$je, 'ຜູ້ນຳພິທີສູ່ຂວັນ', 'rite', 'ນຳພິທີສູ່ຂວັນ ແລະ ພິທີປີໃໝ່ຂອງຕະກູນມາຫຼາຍກວ່າ 30 ປີ.', ['ສູ່ຂວັນ', 'ພິທີປີໃໝ່', 'ບາສີແຕ່ງງານ'], [$lao, $xe]],
            [$xao, 'ຜູ້ເປົ່າແຄນ', 'qeej', 'ຮັກສາເພງແຄນງານສົບ ແລະ ສອນລູກຫຼານ.', ['ເປົ່າແຄນ'], [$thong]],
            [$xai, 'ຜູ້ໄກ່ເກ່ຍ', 'mediate', 'ຊ່ວຍໄກ່ເກ່ຍບັນຫາໃນຄອບຄົວ.', ['ໄກ່ເກ່ຍ'], []],
            [$p2a, 'ຜູ້ຮູ້ປະຫວັດຕະກູນ', 'history', 'ບັນທຶກປະຫວັດການຍົກຍ້າຍຂອງຕະກູນ.', ['ປະຫວັດ'], []],
            [$yer, 'ຊ່າງປັກແສ່ວ', 'craft', 'ສອນການປັກແສ່ວລາຍມົ້ງ.', ['ປັກແສ່ວ', 'ຕັດຫຍິບ'], [$ye]]] as [$m, $title, $cat, $bio, $skills, $succ]) {
            $e = Elder::create(['member_id' => $m->id, 'role_title' => $title, 'role_category' => $cat, 'bio' => $bio, 'skills' => $skills, 'status' => 'active']);
            $e->successors()->sync(array_map(fn ($s) => $s->id, $succ));
        }

        // Events
        $mk = function (string $title, string $cat, string $start, ?string $end, ?string $t1, ?string $t2, string $loc, bool $public, string $desc) use ($admin) {
            $e = new Event(['title' => $title, 'category' => $cat, 'start_date' => $start, 'end_date' => $end, 'start_time' => $t1, 'end_time' => $t2,
                'location' => $loc, 'organizer' => 'ຄະນະກຳມະການຈັດງານ', 'is_public' => $public, 'description' => $desc]);
            $e->created_by = $admin->id;
            $e->save();

            return $e;
        };
        $d = fn (int $days) => now()->addDays($days)->toDateString();
        $meet = $mk('ກອງປະຊຸມຄະນະນຳປະຈຳເດືອນ', 'ກອງປະຊຸມ', $d(3), null, '09:00', '11:30', 'ຫ້ອງປະຊຸມຕະກູນ, ນະຄອນຫຼວງວຽງຈັນ', false, 'ທົບທວນວຽກງານປະຈຳເດືອນ.');
        $mk('ພິທີສູ່ຂວັນ ລູກຫຼານຮຽນຈົບ', 'ພິທີກຳ', $d(10), null, '08:30', null, 'ແຂວງຊຽງຂວາງ', true, 'ອວຍພອນລູກຫຼານທີ່ຮຽນຈົບ.');
        $njiaj = $mk('ບຸນກິນຈຽງ ປີໃໝ່ມົ້ງ 2027', 'ບຸນປະເພນີ', $d(24), $d(26), '08:00', '18:00', 'ສະໜາມບຸນ, ໂພນສະຫວັນ', true, 'ງານບຸນປີໃໝ່ປະຈຳປີຂອງຕະກູນ: ພິທີບາສີ, ຖິ້ມໝາກຄ່ອນ, ການສະແດງວັດທະນະທຳ ແລະ ກິນລ້ຽງຮ່ວມກັນ.');
        $mk('ກອງປະຊຸມສະຫຼຸບກອງທຶນປະຈຳປີ', 'ກອງປະຊຸມ', $d(31), null, '14:00', null, 'ນະຄອນຫຼວງວຽງຈັນ', false, '');
        $tree = $mk('ປູກຕົ້ນໄມ້ຮ່ວມກັບບ້ານ', 'ຈິດອາສາ', $d(-18), null, '07:00', null, 'ເມືອງແປກ', true, 'ກິດຈະກຳຈິດອາສາ.');

        foreach ([$peng, $mai, $je, $thong, $xe] as $m) {
            Participant::create(['event_id' => $njiaj->id, 'member_id' => $m->id, 'duty' => $m->is($je) ? 'ນຳພິທີ' : null, 'status' => 'confirmed']);
        }
        Participant::create(['event_id' => $tree->id, 'member_id' => $xe->id, 'status' => 'confirmed']);
        Participant::create(['event_id' => $meet->id, 'member_id' => $je->id, 'status' => 'confirmed']);

        // Finance: manual transactions + donation pledges/payments through the service.
        $tx = fn (int $days, string $desc, string $cat, string $type, int $amt, ?Event $e = null) => $finance->createTransaction(
            ['date' => now()->addDays($days)->toDateString(), 'description' => $desc, 'category' => $cat, 'type' => $type, 'amount' => $amt, 'event_id' => $e?->id], null, $admin);
        $tx(-160, 'ຄ່າບຳລຸງສະມາຊິກ ປະຈຳປີ', 'ຄ່າບຳລຸງ', 'income', 9500000);
        $tx(-120, 'ຄ່າບຳລຸງເພີ່ມເຕີມ', 'ຄ່າບຳລຸງ', 'income', 4200000);
        $tx(-95, 'ຊ່ວຍເຫຼືອຄອບຄົວປະສົບໄພນ້ຳຖ້ວມ', 'ສະຫວັດດີການ', 'expense', 1500000);
        $tx(-60, 'ຄ່າບຳລຸງສະມາຊິກ (ລວມ 63 ຄອບຄົວ)', 'ຄ່າບຳລຸງ', 'income', 6300000);
        $tx(-40, 'ຄ່າອາຫານກອງປະຊຸມ', 'ອາຫານ', 'expense', 850000);
        $tx(-14, 'ລະບົບສຽງ ແລະ ເວທີ', 'ກິດຈະກຳ', 'expense', 4500000, $njiaj);
        $tx(-9, 'ວັດຖຸດິບອາຫານ ງວດ 1', 'ອາຫານ', 'expense', 5950000, $njiaj);
        $tx(-7, 'ພິມປ້າຍ ແລະ ບັດເຊີນ', 'ກິດຈະກຳ', 'expense', 2000000, $njiaj);

        foreach ([[$peng, 1000000, [1000000]], [$mai, 500000, [300000]], [$je, 2000000, [1500000, 1000000]], [$xao, 1000000, []],
            [$ye, 500000, [500000]], [$xai, 1500000, [750000]], [$xe, 300000, []], [$thong, null, [200000]]] as $i => [$m, $exp, $pays]) {
            $pledge = DonationPledge::create(['member_id' => $m->id, 'event_id' => $njiaj->id, 'expected_amount' => $exp]);
            foreach ($pays as $j => $amt) {
                $finance->recordPayment($pledge, $amt, now()->subDays(20 - $i * 2 - $j)->toDateString(), $j % 2 ? 'bank' : 'cash', null, null, null, $admin);
            }
        }

        foreach ([[$je, '2023-01-15'], [$peng, '2023-02-01'], [$mai, '2023-05-10'], [$yer, '2024-03-01'], [$ye, '2024-07-20'], [$xao, '2023-09-01'], [$xai, '2025-01-05'], [$xe, now()->toDateString()]] as [$m, $date]) {
            FundMember::create(['member_id' => $m->id, 'joined_at' => $date]);
        }

        // News
        $news = function (string $title, string $cat, int $days, bool $pinned, array $tags, string $body) use ($admin) {
            $n = new News(['title' => $title, 'category' => $cat, 'tags' => $tags, 'body' => $body]);
            $n->status = 'published';
            $n->published_at = now()->subDays($days);
            $n->is_pinned = $pinned;
            $n->author_id = $admin->id;
            $n->save();

            return $n;
        };
        $n1 = $news('ກຳນົດການ ບຸນກິນຈຽງ ປີໃໝ່ມົ້ງ 2027', 'ປະກາດ', 1, true, ['ກິນຈຽງ', 'ປີໃໝ່'], "ງານບຸນຈະຈັດຂຶ້ນທີ່ສະໜາມບຸນ ໂພນສະຫວັນ. ວັນທຳອິດເປັນພິທີບາສີ ແລະ ຕ້ອນຮັບແຂກ, ວັນທີສອງເປັນການຖິ້ມໝາກຄ່ອນ ແລະ ການສະແດງ, ວັນສຸດທ້າຍເປັນກອງປະຊຸມພີ່ນ້ອງ.\nຂໍເຊີນທຸກຄອບຄົວລົງທະບຽນຈຳນວນຄົນກ່ອນງານ.");
        $news('ສະຫຼຸບຍອດບໍລິຈາກ ກອງທຶນຊ່ວຍເຫຼືອການສຶກສາ', 'ກອງທຶນ', 5, false, ['ກອງທຶນ'], 'ຂອບໃຈທຸກທ່ານທີ່ຮ່ວມບໍລິຈາກ.');
        $news('ຍິນດີກັບລູກຫຼານ 7 ຄົນ ທີ່ໄດ້ຮັບທຶນການສຶກສາ', 'ຍິນດີ', 10, false, ['ການສຶກສາ'], 'ຂໍສະແດງຄວາມຍິນດີ.');
        $draft = new News(['title' => 'ຮ່າງ: ລະບຽບກອງທຶນສະບັບປັບປຸງ', 'category' => 'ກອງທຶນ', 'body' => 'ສະບັບຮ່າງ', 'tags' => []]);
        $draft->author_id = $admin->id;
        $draft->save();

        // Accounts: one approved member linked to ເປັງ, two pending sign-ups.
        $u = new User(['display_name' => 'ເປັງ ຊົ່ງ', 'email' => 'peng@example.com', 'password' => 'Password123']);
        $u->role = 'member';
        $u->member_id = $peng->id;
        $u->approved_at = now()->subYears(2);
        $u->approved_by = $admin->id;
        $u->save();
        foreach ([['ທອງ ຊົ່ງ', 'thong@example.com'], ['ນາລີ ຊົ່ງ', 'nali@example.com']] as [$n, $e]) {
            $p = new User(['display_name' => $n, 'email' => $e, 'password' => 'Password123']);
            $p->save();
        }
        $c = new NewsComment(['body' => 'ປີນີ້ຂໍໃຫ້ລູກຫຼານມາພ້ອມກັນຫຼາຍໆ.']);
        $c->news_id = $n1->id;
        $c->author_id = $u->id;
        $c->save();
    }
}
