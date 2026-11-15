<?php

namespace App\Http\Controllers;

use App\Models\ContentRevision;
use App\Models\HomeCard;
use App\Models\HomeDoctor;
use App\Models\HomeTestimony;
use App\Models\HomeText;
use App\Models\User;
use App\Services\RollbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentHistoryController extends Controller
{
    public function __construct(
        protected RollbackService $rollbackService,
    ) {}

    public function index(Request $request)
    {
        $query = ContentRevision::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $typeMap = [
                'doctors' => HomeDoctor::class,
                'cards' => HomeCard::class,
                'testimonies' => HomeTestimony::class,
                'texts' => HomeText::class,
            ];
            if (isset($typeMap[$request->type])) {
                $query->where('revisionable_type', $typeMap[$request->type]);
            }
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date.' 00:00:00');
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date.' 23:59:59');
        }

        $batchQuery = (clone $query)->select('batch_id', DB::raw('MAX(created_at) as latest_created_at'))
            ->groupBy('batch_id')
            ->orderBy('latest_created_at', 'desc');

        $batches = $batchQuery->paginate(20)->withQueryString();

        $batchIds = $batches->pluck('batch_id');
        $revisionsByBatch = ContentRevision::with('user')
            ->whereIn('batch_id', $batchIds)
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('batch_id');

        $users = User::orderBy('name')->get();

        return view('admin.history.index', compact('batches', 'revisionsByBatch', 'users'));
    }

    public function show(string $batch)
    {
        $revisions = ContentRevision::with('user')
            ->where('batch_id', $batch)
            ->orderBy('id', 'asc')
            ->get();

        if ($revisions->isEmpty()) {
            abort(404, 'Batch riwayat tidak ditemukan.');
        }

        $conflicts = $this->rollbackService->detectConflicts($batch);

        return view('admin.history.show', compact('revisions', 'batch', 'conflicts'));
    }

    public function rollback(Request $request, string $batch)
    {
        $force = (bool) $request->input('confirm_conflict', false);

        $result = $this->rollbackService->rollback($batch, $force);

        if (! $result['success']) {
            return redirect()->route('admin.history.show', $batch)
                ->with('error_conflicts', $result['conflicts'])
                ->with('error', 'Terdapat konflik pada data. Harap tinjau dan konfirmasi rollback paksa jika Anda yakin.');
        }

        $message = 'Rollback berhasil diterapkan sebagai batch baru!';
        if (! empty($result['warnings'])) {
            $message .= ' Catatan: '.implode(' ', $result['warnings']);
        }

        return redirect()->route('admin.history.index')
            ->with('success', $message);
    }
}
