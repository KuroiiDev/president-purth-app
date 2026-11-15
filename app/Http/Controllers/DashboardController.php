<?php

namespace App\Http\Controllers;

use App\Models\ContentRevision;
use App\Models\HomeDoctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userCount = User::count();
        $doctorCount = HomeDoctor::count();

        // 5 Batch revisi terbaru untuk widget aktivitas
        $latestBatchIds = ContentRevision::select('batch_id', DB::raw('MAX(created_at) as latest_created_at'))
            ->groupBy('batch_id')
            ->orderBy('latest_created_at', 'desc')
            ->limit(5)
            ->pluck('batch_id');

        $latestBatches = ContentRevision::with('user')
            ->whereIn('batch_id', $latestBatchIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('batch_id');

        return view('admin.dashboard', compact('userCount', 'doctorCount', 'latestBatches', 'latestBatchIds'));
    }
}
