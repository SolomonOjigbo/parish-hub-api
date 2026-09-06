<?php

namespace App\Http\Controllers\Api\V1\Communications;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Bulletin;
use App\Resources\Api\V1\BulletinResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulletinController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:communications.view', only: ['index', 'show', 'preview']),
            new Middleware('permission:communications.send', only: ['store', 'update', 'export']),
        ];
    }

    /**
     * Display a listing of bulletins.
     */
    public function index(Request $request): JsonResponse
    {
        $bulletins = Bulletin::orderBy('sunday_date', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            BulletinResource::collection($bulletins),
            paginator_meta($bulletins),
        );
    }

    /**
     * Store a newly created bulletin.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sunday_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $bulletin = Bulletin::create(array_merge($data, [
            'generated_by' => $request->user()->id,
        ]));

        return $this->success(new BulletinResource($bulletin), 'Bulletin created successfully', 201);
    }

    /**
     * Display the specified bulletin.
     */
    public function show(Bulletin $bulletin): JsonResponse
    {
        return $this->success(new BulletinResource($bulletin));
    }

    /**
     * Update the specified bulletin.
     */
    public function update(Request $request, Bulletin $bulletin): JsonResponse
    {
        $data = $request->validate([
            'sunday_date' => ['sometimes', 'date'],
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
        ]);

        $bulletin->update($data);

        return $this->success(new BulletinResource($bulletin), 'Bulletin updated successfully');
    }

    /**
     * Preview the bulletin HTML.
     */
    public function preview(Bulletin $bulletin): JsonResponse
    {
        return $this->success([
            'html' => view('bulletins.preview', [
                'bulletin' => $bulletin,
                'parish_name' => 'St. Ferdinand Catholic Church, Lagos',
            ])->render(),
        ]);
    }

    /**
     * Export the bulletin as PDF.
     */
    public function export(Bulletin $bulletin): BinaryFileResponse
    {
        $pdf = Pdf::loadView('bulletins.preview', [
            'bulletin' => $bulletin,
            'parish_name' => 'St. Ferdinand Catholic Church, Lagos',
        ]);

        return $pdf->download("bulletin-{$bulletin->sunday_date}.pdf");
    }
}
