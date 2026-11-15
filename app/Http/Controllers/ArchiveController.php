<?php

namespace App\Http\Controllers;

use App\Models\HomeCard;
use App\Models\HomeDoctor;
use App\Models\HomeTestimony;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    /**
     * Strict whitelist map of allowed archive types.
     *
     * @var array<string, array{model: class-string, label: string, folder: ?string}>
     */
    protected array $typeMap = [
        'doctors' => [
            'model' => HomeDoctor::class,
            'label' => 'Dokter',
            'folder' => 'profile',
        ],
        'cards' => [
            'model' => HomeCard::class,
            'label' => 'Kartu Layanan',
            'folder' => null,
        ],
        'testimonies' => [
            'model' => HomeTestimony::class,
            'label' => 'Testimoni',
            'folder' => 'testimonials',
        ],
    ];

    public function __construct(
        protected ImageUploader $imageUploader,
    ) {}

    public function index(Request $request)
    {
        $currentTab = $request->get('tab', 'doctors');
        if (! array_key_exists($currentTab, $this->typeMap)) {
            $currentTab = 'doctors';
        }

        $trashedDoctors = HomeDoctor::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(15, ['*'], 'doctors_page');
        $trashedCards = HomeCard::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(15, ['*'], 'cards_page');
        $trashedTestimonies = HomeTestimony::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(15, ['*'], 'testimonies_page');

        return view('admin.archive.index', compact('currentTab', 'trashedDoctors', 'trashedCards', 'trashedTestimonies'));
    }

    public function restore(Request $request, string $type, $id)
    {
        if (! array_key_exists($type, $this->typeMap)) {
            abort(404, 'Tipe arsip tidak valid.');
        }

        $config = $this->typeMap[$type];
        $modelClass = $config['model'];

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        $label = $item->name ?? $item->title ?? ('#'.$item->id);

        return redirect()->route('admin.archive.index', ['tab' => $type])
            ->with('success', "{$config['label']} '{$label}' berhasil dipulihkan dari arsip!");
    }

    public function forceDelete(Request $request, string $type, $id)
    {
        // Only admin can permanently delete
        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Aksi ini hanya diperbolehkan untuk Administrator.');
        }

        if (! array_key_exists($type, $this->typeMap)) {
            abort(404, 'Tipe arsip tidak valid.');
        }

        $config = $this->typeMap[$type];
        $modelClass = $config['model'];

        $item = $modelClass::onlyTrashed()->findOrFail($id);

        // Move image to archive disk upon permanent deletion if local
        if (! empty($config['folder']) && ! empty($item->image) && ! $this->imageUploader->isExternalUrl($item->image)) {
            $this->imageUploader->archive($item->image, $config['folder']);
        }

        $label = $item->name ?? $item->title ?? ('#'.$item->id);
        $item->forceDelete();

        return redirect()->route('admin.archive.index', ['tab' => $type])
            ->with('success', "{$config['label']} '{$label}' berhasil dihapus permanen!");
    }
}
