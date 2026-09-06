<?php

namespace App\Http\Controllers\Api\V1\Members;

use App\Exports\MembersExport;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Members\StoreMemberRequest;
use App\Http\Requests\Api\V1\Members\UpdateMemberRequest;
use App\Models\AuditLog;
use App\Models\Member;
use App\Resources\Api\V1\MemberResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:members.view',   only: ['index', 'show', 'societies', 'attendance', 'communications', 'auditLog', 'sacramentCertificate']),
            new Middleware('permission:members.create', only: ['store', 'import']),
            new Middleware('permission:members.edit',   only: ['update', 'uploadPhoto', 'storeSacrament', 'updateSacrament']),
            new Middleware('permission:members.delete', only: ['destroy']),
            new Middleware('permission:members.export', only: ['export']),
            new Middleware('permission:finance.view',   only: ['giving', 'givingStatement']),
        ];
    }

    /**
     * GET /api/v1/members
     */
    public function index(Request $request): JsonResponse
    {
        $perPage   = (int) $request->query('per_page', 25);
        $sort      = (string) $request->query('sort', 'last_name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['last_name', 'first_name', 'membership_number', 'date_joined', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'last_name';
        }

        $query = Member::query()
            ->with(['contactDetail', 'family', 'zone', 'societies', 'sacramentalRecords'])
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = '%' . $request->query('search') . '%';
                $q->where(function ($inner) use ($search): void {
                    $inner->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('other_name', 'like', $search)
                        ->orWhere('membership_number', 'like', $search)
                        ->orWhereHas('contactDetail', function ($c) use ($search): void {
                            $c->where('primary_phone', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->when($request->filled('society_id'), function ($q) use ($request): void {
                $q->whereHas('societies', function ($s) use ($request): void {
                    $s->where('societies.id', $request->query('society_id'));
                });
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('zone_id'), fn($q) => $q->where('zone_id', $request->query('zone_id')))
            ->when($request->filled('from'), fn($q) => $q->whereDate('date_joined', '>=', $request->query('from')))
            ->when($request->filled('to'), fn($q) => $q->whereDate('date_joined', '<=', $request->query('to')))
            ->orderBy($sort, $direction);

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            MemberResource::collection($paginator),
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Members retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/members
     */
    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $member = DB::transaction(function () use ($data, $request): Member {
            $member = Member::create([
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'other_name'     => $data['other_name']     ?? null,
                'baptismal_name' => $data['baptismal_name'] ?? null,
                'date_of_birth'  => $data['date_of_birth']  ?? null,
                'gender'         => $data['gender'],
                'marital_status' => $data['marital_status'],
                'occupation'     => $data['occupation']     ?? null,
                'family_id'      => $data['family_id']      ?? null,
                'is_family_head' => $data['is_family_head'] ?? false,
                'zone_id'        => $data['zone_id']        ?? null,
                'status'         => $data['status']         ?? 'active',
                'date_joined'    => $data['date_joined']    ?? now()->toDateString(),
                'notes'          => $data['notes']          ?? null,
            ]);

            $member->contactDetail()->create([
                'primary_phone'   => $data['primary_phone'],
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'email'           => $data['email']           ?? null,
                'address_line1'   => $data['address_line1']   ?? null,
                'address_line2'   => $data['address_line2']   ?? null,
                'lga'             => $data['lga']             ?? null,
            ]);

            if (!empty($data['society_ids'])) {
                $sync = [];
                foreach ($data['society_ids'] as $sid) {
                    $sync[$sid] = [
                        'role'      => 'member',
                        'joined_at' => now()->toDateString(),
                        'is_active' => true,
                    ];
                }
                $member->societies()->sync($sync);
            }

            if (!empty($data['sacraments'])) {
                foreach ($data['sacraments'] as $sac) {
                    $member->sacramentalRecords()->create([
                        'type'        => $sac['type'],
                        'date'        => $sac['date']        ?? null,
                        'church'      => $sac['church']      ?? null,
                        'spouse_name' => $sac['spouse_name'] ?? null,
                    ]);
                }
            }

            AuditLog::create([
                'user_id'        => $request->user()?->id,
                'action'         => 'member.created',
                'auditable_type' => Member::class,
                'auditable_id'   => $member->id,
                'old_values'     => null,
                'new_values'     => $member->toArray(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);

            return $member;
        });

        $member->load(['contactDetail', 'family', 'zone', 'societies', 'sacramentalRecords']);

        return $this->success(
            new MemberResource($member),
            'Member created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/v1/members/{id}
     */
    public function show(int $id): JsonResponse
    {
        $member = Member::with([
            'contactDetail',
            'family',
            'zone',
            'societies',
            'sacramentalRecords',
            'eventAttendances.event',
        ])->findOrFail($id);

        return $this->success(
            new MemberResource($member),
            'Member retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/members/{id}
     */
    public function update(UpdateMemberRequest $request, int $id): JsonResponse
    {
        $member = Member::findOrFail($id);
        $data   = $request->validated();

        $member = DB::transaction(function () use ($member, $data, $request): Member {
            $oldValues = $member->toArray();

            $memberFields = array_intersect_key($data, array_flip([
                'first_name', 'last_name', 'other_name', 'baptismal_name',
                'date_of_birth', 'gender', 'marital_status', 'occupation',
                'family_id', 'is_family_head', 'zone_id', 'status',
                'date_joined', 'notes',
            ]));

            if (!empty($memberFields)) {
                $member->update($memberFields);
            }

            $contactFields = array_intersect_key($data, array_flip([
                'primary_phone', 'whatsapp_number', 'email',
                'address_line1', 'address_line2', 'lga',
            ]));

            if (!empty($contactFields)) {
                $member->contactDetail()->updateOrCreate(
                    ['member_id' => $member->id],
                    $contactFields
                );
            }

            if (array_key_exists('society_ids', $data) && is_array($data['society_ids'])) {
                $sync = [];
                foreach ($data['society_ids'] as $sid) {
                    $sync[$sid] = [
                        'role'      => 'member',
                        'joined_at' => now()->toDateString(),
                        'is_active' => true,
                    ];
                }
                $member->societies()->sync($sync);
            }

            if (!empty($data['sacraments'])) {
                foreach ($data['sacraments'] as $sac) {
                    $member->sacramentalRecords()->updateOrCreate(
                        ['type' => $sac['type']],
                        [
                            'date'        => $sac['date']        ?? null,
                            'church'      => $sac['church']      ?? null,
                            'spouse_name' => $sac['spouse_name'] ?? null,
                        ]
                    );
                }
            }

            AuditLog::create([
                'user_id'        => $request->user()?->id,
                'action'         => 'member.updated',
                'auditable_type' => Member::class,
                'auditable_id'   => $member->id,
                'old_values'     => $oldValues,
                'new_values'     => $member->fresh()->toArray(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);

            return $member;
        });

        $member->load(['contactDetail', 'family', 'zone', 'societies', 'sacramentalRecords']);

        return $this->success(
            new MemberResource($member),
            'Member updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/members/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        DB::transaction(function () use ($member, $request): void {
            $oldValues = $member->toArray();

            $member->delete();

            AuditLog::create([
                'user_id'        => $request->user()?->id,
                'action'         => 'member.deleted',
                'auditable_type' => Member::class,
                'auditable_id'   => $member->id,
                'old_values'     => $oldValues,
                'new_values'     => null,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        });

        return $this->success(null, 'Member deleted', 200);
    }

    /**
     * POST /api/v1/members/{id}/photo
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $member = Member::findOrFail($id);

        $path  = "photos/members/{$member->id}.jpg";
        $image = Image::read($request->file('photo')->getRealPath());
        $image->scaleDown(width: 800, height: 800);

        Storage::disk('public')->put($path, (string) $image->toJpeg());

        $member->update(['photo_path' => $path]);

        return $this->success(
            [
                'photo_url'  => Storage::disk('public')->url($path),
                'photo_path' => $path,
            ],
            'Photo uploaded successfully.'
        );
    }

    /**
     * GET /api/v1/members/{id}/giving
     */
    public function giving(int $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $offerings = \App\Models\Offering::where('member_id', $member->id)
            ->orderByDesc('collection_date')->get();
        $tithes    = \App\Models\Tithe::where('member_id', $member->id)
            ->orderByDesc('payment_date')->get();
        $pledges   = \App\Models\Pledge::where('member_id', $member->id)
            ->orderByDesc('start_date')->get();
        $donations = \App\Models\Donation::where('member_id', $member->id)
            ->orderByDesc('donation_date')->get();

        $year = now()->year;

        $sumYear = (float) $offerings->whereBetween('collection_date', [$year . '-01-01', $year . '-12-31'])->sum('amount')
            + (float) $tithes->where('period_year', $year)->sum('amount')
            + (float) $donations->whereBetween('donation_date', [$year . '-01-01', $year . '-12-31'])->sum('amount')
            + (float) $pledges->sum('amount_paid');

        $sumAll = (float) $offerings->sum('amount')
            + (float) $tithes->sum('amount')
            + (float) $donations->sum('amount')
            + (float) $pledges->sum('amount_paid');

        return $this->success(
            [
                'offerings' => $offerings,
                'tithes'    => $tithes,
                'pledges'   => $pledges,
                'donations' => $donations,
                'summary'   => [
                    'total_this_year' => round($sumYear, 2),
                    'total_all_time'  => round($sumAll, 2),
                ],
            ],
            'Member giving retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/members/{id}/societies
     */
    public function societies(int $id): JsonResponse
    {
        $member = Member::with(['societies' => fn($q) => $q->orderBy('name')])->findOrFail($id);

        $societies = $member->societies->map(fn($society) => [
            'id'        => $society->id,
            'name'      => $society->name,
            'slug'      => $society->slug,
            'colour'    => $society->colour,
            'role'      => $society->pivot->role,
            'joined_at' => $society->pivot->joined_at,
            'is_active' => (bool) $society->pivot->is_active,
        ]);

        return $this->success($societies, 'Member societies retrieved successfully.');
    }

    /**
     * GET /api/v1/members/{id}/attendance
     */
    public function attendance(int $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $records = $member->eventAttendances()
            ->with('event:id,title,type,start_datetime,location')
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(fn($attendance) => [
                'id'            => $attendance->id,
                'event_id'      => $attendance->event_id,
                'event_title'   => $attendance->event?->title,
                'event_type'    => $attendance->event?->type,
                'event_date'    => $attendance->event?->start_datetime?->toDateString(),
                'location'      => $attendance->event?->location,
                'checked_in_at' => $attendance->checked_in_at,
            ]);

        return $this->success($records, 'Member attendance retrieved successfully.');
    }

    /**
     * GET /api/v1/members/{id}/communications
     */
    public function communications(int $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $logs = \App\Models\CommunicationLog::query()
            ->where(function ($q) use ($member): void {
                $q->whereJsonContains('recipient_ids', $member->id)
                    ->orWhereJsonContains('recipient_ids', (string) $member->id);
            })
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'type'       => $log->type,
                'subject'    => $log->subject,
                'message'    => $log->message,
                'status'     => $log->status,
                'sent_by'    => $log->sender?->name,
                'sent_at'    => $log->sent_at,
                'created_at' => $log->created_at,
            ]);

        return $this->success($logs, 'Member communications retrieved successfully.');
    }

    /**
     * POST /api/v1/members/{id}/sacraments
     */
    public function storeSacrament(Request $request, int $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $data = $request->validate([
            'type'        => ['required', 'in:baptism,first_communion,confirmation,marriage,holy_orders'],
            'date'        => ['nullable', 'date'],
            'church'      => ['nullable', 'string', 'max:255'],
            'minister'    => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
        ]);

        $record = $member->sacramentalRecords()->create($data);

        return $this->success(
            new \App\Resources\Api\V1\SacramentalRecordResource($record),
            'Sacramental record created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * PUT /api/v1/members/{id}/sacraments/{sacrament}
     */
    public function updateSacrament(Request $request, int $id, int $sacrament): JsonResponse
    {
        $member = Member::findOrFail($id);
        $record = $member->sacramentalRecords()->findOrFail($sacrament);

        $data = $request->validate([
            'type'        => ['sometimes', 'in:baptism,first_communion,confirmation,marriage,holy_orders'],
            'date'        => ['nullable', 'date'],
            'church'      => ['nullable', 'string', 'max:255'],
            'minister'    => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
        ]);

        $record->update($data);

        return $this->success(
            new \App\Resources\Api\V1\SacramentalRecordResource($record),
            'Sacramental record updated successfully.'
        );
    }

    /**
     * GET /api/v1/members/{id}/audit-log
     */
    public function auditLog(int $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $logs = AuditLog::where('auditable_type', Member::class)
            ->where('auditable_id', $member->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($logs, 'Audit log retrieved successfully.');
    }

    /**
     * GET /api/v1/members/{id}/sacraments/{sacrament}/certificate
     */
    public function sacramentCertificate(int $id, int $sacrament): \Illuminate\Http\Response
    {
        $member = Member::with('contactDetail')->findOrFail($id);
        $record = $member->sacramentalRecords()->findOrFail($sacrament);

        $pdf = Pdf::loadView('certificates.sacrament', [
            'member'      => $member,
            'record'      => $record,
            'parish_name' => \App\Models\Setting::get('parish_name', 'St. Ferdinand Catholic Church'),
            'diocese'     => \App\Models\Setting::get('diocese', 'Catholic Archdiocese of Lagos'),
            'address'     => \App\Models\Setting::get('parish_address', 'Boys Town, Ipaja, Lagos'),
        ]);

        $type = str_replace('_', '-', $record->type);

        return $pdf->download("{$type}-certificate-{$member->membership_number}.pdf");
    }

    /**
     * GET /api/v1/members/{id}/giving/statement?year=YYYY
     */
    public function givingStatement(Request $request, int $id): \Illuminate\Http\Response
    {
        $member = Member::with('contactDetail')->findOrFail($id);
        $year = (int) $request->query('year', now()->year);

        $pdf = Pdf::loadView('reports.giving-statement', array_merge(
            ['member' => $member],
            static::buildGivingStatementData($member, $year)
        ));

        return $pdf->download("giving-statement-{$member->membership_number}-{$year}.pdf");
    }

    /**
     * Shared by the staff and portal statement endpoints.
     *
     * @return array<string, mixed>
     */
    public static function buildGivingStatementData(Member $member, int $year): array
    {
        $offerings = \App\Models\Offering::where('member_id', $member->id)
            ->whereYear('collection_date', $year)->orderBy('collection_date')->get();
        $tithes = \App\Models\Tithe::where('member_id', $member->id)
            ->where('period_year', $year)->orderBy('payment_date')->get();
        $donations = \App\Models\Donation::where('member_id', $member->id)
            ->whereYear('donation_date', $year)->orderBy('donation_date')->get();
        $pledgePayments = \App\Models\PledgePayment::whereHas('pledge', fn($q) => $q->where('member_id', $member->id))
            ->whereYear('payment_date', $year)->orderBy('payment_date')->with('pledge')->get();

        return [
            'year'            => $year,
            'offerings'       => $offerings,
            'tithes'          => $tithes,
            'donations'       => $donations,
            'pledge_payments' => $pledgePayments,
            'total'           => (float) $offerings->sum('amount')
                + (float) $tithes->sum('amount')
                + (float) $donations->sum('amount')
                + (float) $pledgePayments->sum('amount'),
            'parish_name'     => \App\Models\Setting::get('parish_name', 'St. Ferdinand Catholic Church'),
            'diocese'         => \App\Models\Setting::get('diocese', 'Catholic Archdiocese of Lagos'),
        ];
    }

    /**
     * POST /api/v1/members/import — CSV/Excel roster import.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $import = new \App\Imports\MembersImport();
        Excel::import($import, $request->file('file'));

        return $this->success(
            [
                'imported' => $import->imported,
                'skipped'  => $import->skipped,
            ],
            "Imported {$import->imported} members" . ($import->skipped ? ", skipped {$import->skipped} rows" : '') . '.'
        );
    }

    /**
     * GET /api/v1/members/export
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $format = strtolower((string) $request->query('format', 'excel'));

        $members = Member::with(['contactDetail', 'zone', 'societies'])
            ->orderBy('last_name')
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.members-directory', [
                'members'      => $members,
                'generated_at' => now()->format('Y-m-d H:i'),
                'parish_name'  => 'St. Ferdinand Catholic Church, Lagos',
            ]);

            return response()->streamDownload(
                fn() => print($pdf->output()),
                'members-directory.pdf',
                ['Content-Type' => 'application/pdf']
            );
        }

        return Excel::download(new MembersExport($members), 'members.xlsx');
    }
}
