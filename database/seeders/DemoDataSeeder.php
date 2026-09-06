<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\Member;
use App\Models\MemberContactDetail;
use App\Models\Offering;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Models\Society;
use App\Models\SocietyMember;
use App\Models\Tithe;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private array $zoneIds = [];
    private array $societyIds = [];
    private array $familyIds = [];
    private array $memberIds = [];

    public function run(): void
    {
        $this->zoneIds = Zone::pluck('id')->toArray();
        $this->societyIds = Society::pluck('id')->toArray();

        $this->seedFamilies();
        $this->seedMembers();
        $this->seedFamilyHeads();
        $this->seedSocietyAssignments();
        $this->seedEvents();
        $this->seedOfferings();
        $this->seedTithes();
        $this->seedPledges();
        $this->seedDonations();
        $this->seedCommittees();
        $this->seedStaffProfiles();
    }

    private function seedFamilyHeads(): void
    {
        Family::all()->each(function (Family $family): void {
            $head = Member::where('family_id', $family->id)
                ->orderByDesc('is_family_head')
                ->orderBy('id')
                ->first();
            if ($head) {
                $family->update(['head_member_id' => $head->id]);
            }
        });
    }

    private function seedCommittees(): void
    {
        $committees = [
            ['name' => 'Building Committee', 'description' => 'Oversees the parish building project and maintenance.', 'items' => [
                ['title' => 'Confirm contractor quotes', 'days' => 5],
                ['title' => 'File quarterly progress report to the Archdiocese', 'days' => 14],
            ]],
            ['name' => 'Harvest & Bazaar Committee', 'description' => 'Plans the annual harvest thanksgiving and bazaar.', 'items' => [
                ['title' => 'Print harvest pledge cards', 'days' => 7],
                ['title' => 'Book canopies and chairs for bazaar day', 'days' => 21],
            ]],
            ['name' => 'Liturgy Committee', 'description' => 'Coordinates Mass schedules, readers and liturgical seasons.', 'items' => [
                ['title' => 'Publish October rosters for readers', 'days' => 10],
            ]],
        ];

        foreach ($committees as $i => $c) {
            $chairId = $this->memberIds[$i % count($this->memberIds)];
            $committee = \App\Models\Committee::create([
                'name' => $c['name'],
                'description' => $c['description'],
                'chair_member_id' => $chairId,
            ]);

            $memberPool = array_slice($this->memberIds, $i * 4, 6);
            foreach (array_unique(array_merge([$chairId], $memberPool)) as $mid) {
                \App\Models\CommitteeMember::create([
                    'committee_id' => $committee->id,
                    'member_id' => $mid,
                    'role' => $mid === $chairId ? 'Chair' : null,
                ]);
            }

            foreach ($c['items'] as $item) {
                \App\Models\CommitteeActionItem::create([
                    'committee_id' => $committee->id,
                    'title' => $item['title'],
                    'due_date' => Carbon::now()->addDays($item['days']),
                    'assigned_to_member_id' => $memberPool[0] ?? $chairId,
                    'is_completed' => false,
                ]);
            }
        }
    }

    private function seedStaffProfiles(): void
    {
        $staff = [
            ['name' => 'Fr. Patrick Okeke', 'email' => 'frpatrick@stferdinand.com', 'role' => 'priest', 'title' => 'Parish Priest', 'type' => 'employed'],
            ['name' => 'Mary Okonkwo', 'email' => 'secretary@stferdinand.com', 'role' => 'secretary', 'title' => 'Parish Secretary', 'type' => 'employed'],
            ['name' => 'James Eze', 'email' => 'finance@stferdinand.com', 'role' => 'finance_officer', 'title' => 'Finance Officer', 'type' => 'volunteer'],
        ];

        foreach ($staff as $s) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => bcrypt('Password#123'),
                    'is_active' => true,
                    'must_change_password' => true,
                ]
            );
            $user->syncRoles([$s['role']]);

            \App\Models\StaffProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'job_title' => $s['title'],
                    'employment_type' => $s['type'],
                    'start_date' => Carbon::now()->subYears(2),
                ]
            );
        }
    }

    private function seedFamilies(): void
    {
        $families = [
            'Okafor Family', 'Adeyemi Family', 'Nwachukwu Family',
            'Babatunde Family', 'Eze Family', 'Chukwu Family',
            'Adesanya Family', 'Obiora Family', 'Fashola Family',
            'Dike Family', 'Okonkwo Family', 'Adeleke Family',
            'Emeka Family', 'Olawale Family', 'Bello Family',
        ];

        foreach ($families as $name) {
            $family = Family::create([
                'name' => $name,
                'zone_id' => $this->zoneIds[array_rand($this->zoneIds)],
            ]);
            $this->familyIds[] = $family->id;
        }
    }

    private function seedMembers(): void
    {
        $members = [
            ['first' => 'Chukwudi', 'last' => 'Okafor', 'other' => 'Emeka', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Engineer', 'lga' => 'Ikeja', 'status' => 'active'],
            ['first' => 'Ngozi', 'last' => 'Okafor', 'other' => 'Chiamaka', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Teacher', 'lga' => 'Ikeja', 'status' => 'active'],
            ['first' => 'Oluwaseun', 'last' => 'Adeyemi', 'other' => null, 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Civil Servant', 'lga' => 'Alimosho', 'status' => 'active'],
            ['first' => 'Folake', 'last' => 'Adeyemi', 'other' => 'Titilayo', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Nurse', 'lga' => 'Alimosho', 'status' => 'active'],
            ['first' => 'Ifeanyi', 'last' => 'Nwachukwu', 'other' => 'Chibueze', 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Accountant', 'lga' => 'Agege', 'status' => 'active'],
            ['first' => 'Amarachi', 'last' => 'Nwachukwu', 'other' => null, 'gender' => 'female', 'marital' => 'single', 'occupation' => 'Student', 'lga' => 'Agege', 'status' => 'active'],
            ['first' => 'Adebayo', 'last' => 'Babatunde', 'other' => 'Oluwafemi', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Trader', 'lga' => 'Ipaja', 'status' => 'active'],
            ['first' => 'Bose', 'last' => 'Babatunde', 'other' => 'Abosede', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Trader', 'lga' => 'Ipaja', 'status' => 'active'],
            ['first' => 'Chinedu', 'last' => 'Eze', 'other' => 'Obinna', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Doctor', 'lga' => 'Iyana-Ipaja', 'status' => 'active'],
            ['first' => 'Chinwe', 'last' => 'Eze', 'other' => 'Nkechi', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Pharmacist', 'lga' => 'Iyana-Ipaja', 'status' => 'active'],
            ['first' => 'Emeka', 'last' => 'Chukwu', 'other' => null, 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Software Developer', 'lga' => 'Abesan', 'status' => 'active'],
            ['first' => 'Adaeze', 'last' => 'Chukwu', 'other' => 'Nnenna', 'gender' => 'female', 'marital' => 'single', 'occupation' => 'Lawyer', 'lga' => 'Abesan', 'status' => 'active'],
            ['first' => 'Tunde', 'last' => 'Adesanya', 'other' => 'Babatunde', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Banker', 'lga' => 'Satellite Town', 'status' => 'active'],
            ['first' => 'Yemisi', 'last' => 'Adesanya', 'other' => null, 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Business Owner', 'lga' => 'Satellite Town', 'status' => 'active'],
            ['first' => 'Obinna', 'last' => 'Obiora', 'other' => 'Chukwuma', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Lecturer', 'lga' => 'Ikeja', 'status' => 'active'],
            ['first' => 'Nkechi', 'last' => 'Obiora', 'other' => 'Adaobi', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Teacher', 'lga' => 'Ikeja', 'status' => 'active'],
            ['first' => 'Femi', 'last' => 'Fashola', 'other' => 'Oluwasegun', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Architect', 'lga' => 'Alimosho', 'status' => 'active'],
            ['first' => 'Bimpe', 'last' => 'Fashola', 'other' => null, 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Civil Servant', 'lga' => 'Alimosho', 'status' => 'active'],
            ['first' => 'Chibuzor', 'last' => 'Dike', 'other' => 'Ikenna', 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Electrician', 'lga' => 'Agege', 'status' => 'active'],
            ['first' => 'Uchenna', 'last' => 'Dike', 'other' => null, 'gender' => 'female', 'marital' => 'single', 'occupation' => 'Hairdresser', 'lga' => 'Agege', 'status' => 'active'],
            ['first' => 'Chukwuma', 'last' => 'Okonkwo', 'other' => 'Ifeanyi', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Businessman', 'lga' => 'Ipaja', 'status' => 'active'],
            ['first' => 'Ifeoma', 'last' => 'Okonkwo', 'other' => 'Chinyere', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Nurse', 'lga' => 'Ipaja', 'status' => 'active'],
            ['first' => 'Kayode', 'last' => 'Adeleke', 'other' => 'Oluwaseyi', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Contractor', 'lga' => 'Iyana-Ipaja', 'status' => 'active'],
            ['first' => 'Ronke', 'last' => 'Adeleke', 'other' => null, 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Caterer', 'lga' => 'Iyana-Ipaja', 'status' => 'active'],
            ['first' => 'Ikenna', 'last' => 'Emeka', 'other' => 'Chukwudi', 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Mechanic', 'lga' => 'Abesan', 'status' => 'active'],
            ['first' => 'Chisom', 'last' => 'Emeka', 'other' => null, 'gender' => 'female', 'marital' => 'single', 'occupation' => 'Student', 'lga' => 'Abesan', 'status' => 'active'],
            ['first' => 'Rasheed', 'last' => 'Olawale', 'other' => 'Abiodun', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Driver', 'lga' => 'Satellite Town', 'status' => 'active'],
            ['first' => 'Fatima', 'last' => 'Olawale', 'other' => null, 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Tailor', 'lga' => 'Satellite Town', 'status' => 'active'],
            ['first' => 'Musa', 'last' => 'Bello', 'other' => 'Ibrahim', 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Security Guard', 'lga' => 'Ikeja', 'status' => 'active'],
            ['first' => 'Aisha', 'last' => 'Bello', 'other' => null, 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Cleaner', 'lga' => 'Ikeja', 'status' => 'active'],
            ['first' => 'Chijioke', 'last' => 'Anyanwu', 'other' => null, 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Journalist', 'lga' => 'Alimosho', 'status' => 'active'],
            ['first' => 'Nnenna', 'last' => 'Anyanwu', 'other' => 'Adaku', 'gender' => 'female', 'marital' => 'single', 'occupation' => 'Fashion Designer', 'lga' => 'Alimosho', 'status' => 'active'],
            ['first' => 'Olumide', 'last' => 'Ogunleye', 'other' => null, 'gender' => 'male', 'marital' => 'married', 'occupation' => 'Pastor', 'lga' => 'Agege', 'status' => 'active'],
            ['first' => 'Grace', 'last' => 'Ogunleye', 'other' => 'Oluwatosin', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Teacher', 'lga' => 'Agege', 'status' => 'active'],
            ['first' => 'Ebuka', 'last' => 'Maduka', 'other' => null, 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Graphic Designer', 'lga' => 'Ipaja', 'status' => 'active'],
            ['first' => 'Chiamaka', 'last' => 'Maduka', 'other' => 'Precious', 'gender' => 'female', 'marital' => 'single', 'occupation' => 'Makeup Artist', 'lga' => 'Ipaja', 'status' => 'active'],
            ['first' => 'Segun', 'last' => 'Oladipo', 'other' => 'Adewale', 'gender' => 'male', 'marital' => 'widowed', 'occupation' => 'Retired Teacher', 'lga' => 'Iyana-Ipaja', 'status' => 'inactive'],
            ['first' => 'Nnamdi', 'last' => 'Okeke', 'other' => null, 'gender' => 'male', 'marital' => 'single', 'occupation' => 'Pharmacist', 'lga' => 'Abesan', 'status' => 'inactive'],
            ['first' => 'Chioma', 'last' => 'Nwosu', 'other' => 'Ebere', 'gender' => 'female', 'marital' => 'married', 'occupation' => 'Banker', 'lga' => 'Satellite Town', 'status' => 'transferred'],
            ['first' => 'Pa Michael', 'last' => 'Obi', 'other' => null, 'gender' => 'male', 'marital' => 'widowed', 'occupation' => 'Retired Civil Servant', 'lga' => 'Ikeja', 'status' => 'deceased'],
        ];

        $familyIdx = 0;

        foreach ($members as $i => $m) {
            $familyId = $this->familyIds[$familyIdx % count($this->familyIds)];

            // membership_number is generated by MemberObserver (SFC-{YEAR}-{NNNN}).
            $member = Member::create([
                'first_name' => $m['first'],
                'last_name' => $m['last'],
                'other_name' => $m['other'],
                'baptismal_name' => $m['first'],
                'date_of_birth' => Carbon::now()->subYears(rand(18, 75))->subMonths(rand(0, 11)),
                'gender' => $m['gender'],
                'marital_status' => $m['marital'],
                'occupation' => $m['occupation'],
                'family_id' => $familyId,
                'is_family_head' => ($i % 2 === 0),
                'zone_id' => $this->zoneIds[array_rand($this->zoneIds)],
                'status' => $m['status'],
                'date_joined' => Carbon::now()->subYears(rand(1, 20)),
            ]);

            $this->memberIds[] = $member->id;

            MemberContactDetail::create([
                'member_id' => $member->id,
                'primary_phone' => '080' . rand(20000000, 99999999),
                'whatsapp_number' => '080' . rand(20000000, 99999999),
                'email' => strtolower($m['first'] . '.' . $m['last'] . '@email.com'),
                'address_line1' => rand(1, 200) . ' ' . $this->streetName() . ' Street',
                'lga' => $m['lga'],
                'state' => 'Lagos',
            ]);

            $familyIdx++;
        }
    }

    private function streetName(): string
    {
        $names = ['Church', 'Market', 'Hospital', 'School', 'Mosque', 'Palace', 'Station', 'College', 'Oba', 'Community'];
        return $names[array_rand($names)];
    }

    private function seedSocietyAssignments(): void
    {
        foreach ($this->memberIds as $memberId) {
            $numSocieties = rand(1, 2);
            $assigned = array_rand(array_flip($this->societyIds), $numSocieties);
            $assigned = is_array($assigned) ? $assigned : [$assigned];

            foreach ($assigned as $societyId) {
                SocietyMember::create([
                    'society_id' => $societyId,
                    'member_id' => $memberId,
                    'role' => 'member',
                    'joined_at' => Carbon::now()->subMonths(rand(1, 60)),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedEvents(): void
    {
        $adminId = \App\Models\User::where('email', 'admin@stferdinand.com')->value('id') ?? 1;

        // 4 Sunday Masses
        for ($i = 0; $i < 4; $i++) {
            $sunday = Carbon::now()->next(Carbon::SUNDAY)->addWeeks($i);
            Event::create([
                'title' => 'Sunday Mass',
                'type' => 'mass',
                'description' => 'Holy Eucharistic Celebration',
                'start_datetime' => $sunday->copy()->setHour(7)->setMinute(0),
                'end_datetime' => $sunday->copy()->setHour(9)->setMinute(0),
                'location' => 'St. Ferdinand Catholic Church',
                'include_in_bulletin' => true,
                'created_by' => $adminId,
            ]);
        }

        // CWO Monthly Meeting
        $cwoMeeting = Carbon::now()->addDays(rand(5, 20))->setHour(16)->setMinute(0);
        Event::create([
            'title' => 'CWO Monthly Meeting',
            'type' => 'society_meeting',
            'description' => 'Monthly meeting of the Catholic Women Organisation',
            'start_datetime' => $cwoMeeting,
            'end_datetime' => $cwoMeeting->copy()->addHours(2),
            'location' => 'Parish Hall',
            'created_by' => $adminId,
        ]);

        // Annual Retreat
        $retreatStart = Carbon::now()->addDays(rand(10, 30))->setHour(9)->setMinute(0);
        Event::create([
            'title' => 'Annual Parish Retreat',
            'type' => 'retreat',
            'description' => 'Three-day spiritual retreat for all parishioners',
            'start_datetime' => $retreatStart,
            'end_datetime' => $retreatStart->copy()->addDays(3)->setHour(17)->setMinute(0),
            'location' => 'Divine Mercy Retreat Centre',
            'requires_registration' => true,
            'is_retreat' => true,
            'retreat_fee' => 5000.00,
            'accommodation_notes' => 'Shared rooms available. Bring personal toiletries and Bible.',
            'created_by' => $adminId,
        ]);

        // Feast of St. Ferdinand
        Event::create([
            'title' => 'Feast of St. Ferdinand',
            'type' => 'feast_day',
            'description' => 'Celebration of our patron saint',
            'start_datetime' => Carbon::createFromDate(Carbon::now()->year, 5, 30)->setHour(10)->setMinute(0),
            'end_datetime' => Carbon::createFromDate(Carbon::now()->year, 5, 30)->setHour(16)->setMinute(0),
            'location' => 'St. Ferdinand Catholic Church',
            'include_in_bulletin' => true,
            'created_by' => $adminId,
        ]);

        // Youth Vigil
        $vigil = Carbon::now()->addDays(rand(7, 21))->setHour(22)->setMinute(0);
        Event::create([
            'title' => 'Youth Vigil Night',
            'type' => 'other',
            'description' => 'All-night prayer vigil organised by the Youth Ministry',
            'start_datetime' => $vigil,
            'end_datetime' => $vigil->copy()->addHours(8),
            'location' => 'Church Premises',
            'created_by' => $adminId,
        ]);

        // First Holy Communion
        $communion = Carbon::now()->addDays(rand(14, 45))->setHour(9)->setMinute(0);
        Event::create([
            'title' => "Children's First Holy Communion",
            'type' => 'other',
            'description' => 'First Holy Communion Mass for children',
            'start_datetime' => $communion,
            'end_datetime' => $communion->copy()->addHours(2),
            'location' => 'St. Ferdinand Catholic Church',
            'include_in_bulletin' => true,
            'created_by' => $adminId,
        ]);
    }

    private function seedOfferings(): void
    {
        $adminId = \App\Models\User::where('email', 'admin@stferdinand.com')->value('id') ?? 1;

        for ($i = 0; $i < 12; $i++) {
            $sunday = Carbon::now()->previous(Carbon::SUNDAY)->subWeeks($i);

            Offering::create([
                'collection_date' => $sunday,
                'amount' => rand(200000, 650000) + (rand(0, 99) / 100),
                'payment_method' => 'cash',
                'is_anonymous' => false,
                'recorded_by' => $adminId,
                'notes' => 'Sunday collection',
            ]);

            // Additional envelope records for some Sundays
            if ($i % 2 === 0) {
                $memberId = $this->memberIds[array_rand($this->memberIds)];
                Offering::create([
                    'collection_date' => $sunday,
                    'member_id' => $memberId,
                    'envelope_number' => 'ENV-' . str_pad((string) rand(1, 500), 4, '0', STR_PAD_LEFT),
                    'amount' => rand(5000, 50000) + (rand(0, 99) / 100),
                    'payment_method' => 'cash',
                    'is_anonymous' => false,
                    'recorded_by' => $adminId,
                ]);
            }
        }
    }

    private function seedTithes(): void
    {
        $adminId = \App\Models\User::where('email', 'admin@stferdinand.com')->value('id') ?? 1;

        for ($i = 0; $i < 20; $i++) {
            $memberId = $this->memberIds[array_rand($this->memberIds)];
            $date = Carbon::now()->subMonths(rand(0, 5))->startOfMonth()->addDays(rand(1, 28));

            Tithe::create([
                'member_id' => $memberId,
                'period_month' => $date->month,
                'period_year' => $date->year,
                'amount' => rand(5000, 100000) + (rand(0, 99) / 100),
                'payment_method' => ['cash', 'bank_transfer', 'pos'][array_rand(['cash', 'bank_transfer', 'pos'])],
                'transfer_reference' => rand(0, 1) ? 'TRF-' . rand(100000, 999999) : null,
                'payment_date' => $date,
                'recorded_by' => $adminId,
            ]);
        }
    }

    private function seedPledges(): void
    {
        $adminId = \App\Models\User::where('email', 'admin@stferdinand.com')->value('id') ?? 1;

        $pledgeConfigs = [
            ['purpose' => 'Building Fund', 'total' => 500000, 'paidPct' => 95],
            ['purpose' => 'Renovation', 'total' => 200000, 'paidPct' => 60],
            ['purpose' => 'Building Fund', 'total' => 1000000, 'paidPct' => 10],
            ['purpose' => 'Renovation', 'total' => 300000, 'paidPct' => 80],
            ['purpose' => 'Building Fund', 'total' => 750000, 'paidPct' => 40],
            ['purpose' => 'Renovation', 'total' => 150000, 'paidPct' => 25],
            ['purpose' => 'Building Fund', 'total' => 250000, 'paidPct' => 70],
            ['purpose' => 'Renovation', 'total' => 400000, 'paidPct' => 15],
        ];

        foreach ($pledgeConfigs as $config) {
            $memberId = $this->memberIds[array_rand($this->memberIds)];
            $paidAmount = round($config['total'] * ($config['paidPct'] / 100), 2);
            $status = $config['paidPct'] >= 100 ? 'completed' : 'active';

            $pledge = Pledge::create([
                'member_id' => $memberId,
                'purpose' => $config['purpose'],
                'description' => 'Pledge towards ' . $config['purpose'],
                'total_amount' => $config['total'],
                'amount_paid' => $paidAmount,
                'payment_frequency' => 'monthly',
                'start_date' => Carbon::now()->subMonths(rand(1, 12)),
                'end_date' => Carbon::now()->addMonths(rand(3, 18)),
                'status' => $status,
                'recorded_by' => $adminId,
            ]);

            // Create 1-3 payments for each pledge
            $numPayments = rand(1, 3);
            $remainingPaid = $paidAmount;
            for ($p = 0; $p < $numPayments; $p++) {
                $paymentAmount = $p === $numPayments - 1
                    ? $remainingPaid
                    : round($remainingPaid * (rand(30, 60) / 100), 2);

                if ($paymentAmount <= 0) break;

                PledgePayment::create([
                    'pledge_id' => $pledge->id,
                    'amount' => $paymentAmount,
                    'payment_date' => Carbon::now()->subDays(rand(1, 180)),
                    'payment_method' => ['cash', 'bank_transfer'][array_rand(['cash', 'bank_transfer'])],
                    'transfer_reference' => rand(0, 1) ? 'TRF-' . rand(100000, 999999) : null,
                    'recorded_by' => $adminId,
                ]);

                $remainingPaid -= $paymentAmount;
            }
        }
    }

    private function seedDonations(): void
    {
        $adminId = \App\Models\User::where('email', 'admin@stferdinand.com')->value('id') ?? 1;

        $purposes = ['Church Maintenance', 'Youth Programmes', 'Outreach', 'Altar Flowers', 'Music Ministry', 'General'];

        for ($i = 0; $i < 12; $i++) {
            $isAnonymous = rand(0, 1) === 1;
            $memberId = $isAnonymous ? null : $this->memberIds[array_rand($this->memberIds)];

            Donation::create([
                'donor_name' => $isAnonymous ? null : null,
                'member_id' => $memberId,
                'is_anonymous' => $isAnonymous,
                'amount' => rand(10000, 200000) + (rand(0, 99) / 100),
                'purpose' => $purposes[array_rand($purposes)],
                'donation_date' => Carbon::now()->subDays(rand(1, 90)),
                'payment_method' => ['cash', 'bank_transfer', 'pos', 'cheque'][array_rand(['cash', 'bank_transfer', 'pos', 'cheque'])],
                'transfer_reference' => rand(0, 1) ? 'TRF-' . rand(100000, 999999) : null,
                'recorded_by' => $adminId,
            ]);
        }
    }
}
