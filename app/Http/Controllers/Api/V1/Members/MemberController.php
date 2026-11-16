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
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Str;

class MemberController extends BaseApiController
{

    /**
     * GET /api/v1/members
     */
  
    public function index(Request $request): JsonResponse
{
    $perPage = (int) $request->query('per_page', 25);

    // -----------------------------
    // SAFE SORTING (whitelist map)
    // -----------------------------
    $sortMap = [
        'last_name'         => 'last_name',
        'first_name'        => 'first_name',
        'membership_number' => 'membership_number',
        'date_joined'       => 'date_joined',
        'created_at'        => 'created_at',
    ];

    $sort = $sortMap[$request->query('sort', 'last_name')] ?? 'last_name';
    $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

    // -----------------------------
    // BASE QUERY (optimized eager loading)
    // -----------------------------
    $query = Member::query()->with([
        'contactDetail:id,member_id,primary_phone,email',
        'family:id,name',
        'zone:id,name',
        'societies:id,name',
    ]);

    // -----------------------------
    // SEARCH FILTER
    // -----------------------------
    if ($search = $request->query('search')) {
        $search = '%' . $search . '%';

        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', $search)
              ->orWhere('last_name', 'like', $search)
              ->orWhere('other_name', 'like', $search)
              ->orWhere('membership_number', 'like', $search)
              ->orWhereHas('contactDetail', function ($c) use ($search) {
                  $c->where('primary_phone', 'like', $search)
                    ->orWhere('email', 'like', $search);
              });
        });
    }

    // -----------------------------
    // SOCIETY FILTER
    // -----------------------------
    if ($request->filled('society_id')) {
        $query->whereHas('societies', function ($q) use ($request) {
            $q->where('societies.id', $request->society_id);
        });
    }

    // -----------------------------
    // SIMPLE COLUMN FILTERS
    // -----------------------------
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('zone_id')) {
        $query->where('zone_id', $request->zone_id);
    }

    // -----------------------------
    // DATE RANGE FILTER
    // -----------------------------
    if ($request->filled('from')) {
        $query->whereDate('date_joined', '>=', $request->from);
    }

    if ($request->filled('to')) {
        $query->whereDate('date_joined', '<=', $request->to);
    }

    // -----------------------------
    // ORDERING
    // -----------------------------
    $query->orderBy($sort, $direction);

    // -----------------------------
    // PAGINATION
    // -----------------------------
    $paginator = $query->paginate($perPage);

    return $this->paginated(
        MemberResource::collection($paginator),
        $paginator->toArray(),
        'Members retrieved successfully.'
    );
}

    /**
     * POST /api/v1/members
     */
public function store(StoreMemberRequest $request): JsonResponse
{
    $data = $request->validated();

    try {
        $member = DB::transaction(function () use ($data, $request) {

            // -------------------------
            // SAFE MEMBERSHIP NUMBER
            // -------------------------
            $member = Member::create([
                'membership_number' => $data['membership_number'] ?? ('MEM-' . strtoupper(uniqid())),
                'first_name'        => $data['first_name'],
                'last_name'         => $data['last_name'],
                'other_name'        => $data['other_name'] ?? null,
                'baptismal_name'    => $data['baptismal_name'] ?? null,
                'date_of_birth'     => $data['date_of_birth'] ?? null,
                'gender'            => $data['gender'],
                'marital_status'    => $data['marital_status'],
                'occupation'        => $data['occupation'] ?? null,
                'family_id'         => $data['family_id'] ?? null,
                'is_family_head'    => $data['is_family_head'] ?? false,
                'zone_id'           => $data['zone_id'] ?? null,
                'status'            => $data['status'] ?? 'active',
                'date_joined'       => $data['date_joined'] ?? now()->toDateString(),
                'notes'             => $data['notes'] ?? null,
            ]);

            // -------------------------
            // CONTACT (SAFE)
            // -------------------------
            $member->contactDetail()->create([
                'primary_phone'   => $data['primary_phone'],
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'email'           => $data['email'] ?? null,
                'address_line1'   => $data['address_line1'] ?? null,
                'address_line2'   => $data['address_line2'] ?? null,
                'lga'             => $data['lga'] ?? null,
            ]);

            // -------------------------
            // SOCIETIES (SAFE SYNC)
            // -------------------------
       // -------------------------
// SOCIETIES (FIXED UUID SAFE)
// -------------------------
if (!empty($data['society_ids'])) {

    $validSocieties = \App\Models\Society::whereIn('id', $data['society_ids'])
        ->pluck('id');

    $member->societies()->detach();

    foreach ($validSocieties as $societyId) {
        $member->societies()->attach($societyId, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'member',
            'joined_at' => now(),
            'is_active' => true,
        ]);
    }
}

            // -------------------------
            // SACRAMENTS (SAFE LOOP)
            // -------------------------
            if (!empty($data['sacraments'])) {
                foreach ($data['sacraments'] as $sacrament) {
                    $member->sacramentalRecords()->create([
                        'type'   => $sacrament['type'] ?? null,
                        'date'   => $sacrament['date'] ?? null,
                        'church' => $sacrament['church'] ?? null,
                    ]);
                }
            }

            // -------------------------
            // AUDIT LOG (SAFE JSON)
            // -------------------------
            AuditLog::create([
                'user_id'        => $request->user()?->id,
                'action'         => 'member.created',
                'auditable_type' => Member::class,
                'auditable_id'   => $member->id,
                'old_values'     => null,
                'new_values'     => $member->fresh()->toArray(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);

            return $member;
        });

        $member->load([
            'contactDetail',
            'family',
            'zone',
            'societies',
            'sacramentalRecords'
        ]);

        return $this->success(
            new MemberResource($member),
            'Member created successfully.',
            Response::HTTP_CREATED
        );

    } catch (\Throwable $e) {
         return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], 500);
    }
}

    /**
     * GET /api/v1/members/{id}
     */
    public function show(string $id): JsonResponse
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
   public function update(UpdateMemberRequest $request, string $id): JsonResponse
{
    try {
        $member = Member::findOrFail($id);
        $data   = $request->validated();

        $member = DB::transaction(function () use ($member, $data, $request): Member {

            $oldValues = $member->toArray();

            // -------------------------
            // MEMBER UPDATE
            // -------------------------
            $memberFields = collect($data)->only([
                'first_name',
                'last_name',
                'other_name',
                'baptismal_name',
                'date_of_birth',
                'gender',
                'marital_status',
                'occupation',
                'family_id',
                'is_family_head',
                'zone_id',
                'status',
                'date_joined',
                'notes',
            ])->toArray();

            if (!empty($memberFields)) {
                $member->update($memberFields);
            }

            // -------------------------
            // CONTACT UPDATE
            // -------------------------
            $contactFields = collect($data)->only([
                'primary_phone',
                'whatsapp_number',
                'email',
                'address_line1',
                'address_line2',
                'lga',
            ])->toArray();

            if (!empty($contactFields)) {
                $member->contactDetail()->updateOrCreate(
                    ['member_id' => $member->id],
                    $contactFields
                );
            }

            // -------------------------
            // SOCIETIES (UUID SAFE)
            // -------------------------
            if (array_key_exists('society_ids', $data)) {

                $validSocieties = \App\Models\Society::whereIn('id', $data['society_ids'] ?? [])
                    ->pluck('id');

                // clear old
                $member->societies()->detach();

                foreach ($validSocieties as $sid) {
                    $member->societies()->attach($sid, [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'role' => 'member',
                        'joined_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }

            // -------------------------
            // SACRAMENTS (UUID SAFE)
            // -------------------------
            if (!empty($data['sacraments'])) {
                foreach ($data['sacraments'] as $sac) {
                    $member->sacramentalRecords()->create([
                        'type'   => $sac['type'] ?? null,
                        'date'   => $sac['date'] ?? null,
                        'church' => $sac['church'] ?? null,
                    ]);
                }
            }

            // -------------------------
            // AUDIT
            // -------------------------
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

        $member->load([
            'contactDetail',
            'family',
            'zone',
            'societies',
            'sacramentalRecords'
        ]);

        return $this->success(
            new MemberResource($member),
            'Member updated successfully.'
        );

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
}

    /**
     * DELETE /api/v1/members/{id}
     */
 public function destroy(Request $request, string $id): JsonResponse
{
    try {
        $member = Member::findOrFail($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete member with event attendances.',
            ], 400);
        }
        

        DB::transaction(function () use ($member, $request) {

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

        return $this->success(null, 'Member deleted successfully');

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * POST /api/v1/members/{id}/photo
     */

public function uploadPhoto(Request $request, string $id): JsonResponse
{
    $request->validate([
        'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
    ]);

    DB::beginTransaction();

    try {
        $member = Member::findOrFail($id);

        // -------------------------
        // DELETE OLD IMAGE
        // -------------------------
        if ($member->photo_path && file_exists(public_path($member->photo_path))) {
            unlink(public_path($member->photo_path));
        }

        // -------------------------
        // CREATE FILE NAME
        // -------------------------
        $file = $request->file('photo');

        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        $folder = public_path('upload/member/photos');

        // -------------------------
        // CREATE DIRECTORY IF NOT EXISTS
        // -------------------------
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        // -------------------------
        // MOVE FILE (LIKE YOUR REGISTER METHOD)
        // -------------------------
        $file->move($folder, $filename);

        $relativePath = 'upload/member/photos/' . $filename;

        // -------------------------
        // UPDATE DB
        // -------------------------
        $member->update([
            'photo_path' => $relativePath,
        ]);

        DB::commit();

        return $this->success([
            'photo_url'  => asset($relativePath),
            'photo_path' => $relativePath,
        ], 'Photo uploaded successfully.');

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'Upload failed',
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
}

    /**
     * GET /api/v1/members/{id}/giving
     */
    public function giving(string $id): JsonResponse
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
     * GET /api/v1/members/{id}/audit-log
     */
    public function auditLog(string $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $logs = AuditLog::where('auditable_type', Member::class)
            ->where('auditable_id', $member->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($logs, 'Audit log retrieved successfully.');
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
