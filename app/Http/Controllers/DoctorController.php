<?php

namespace App\Http\Controllers;

use App\Models\HomeDoctor;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct(
        protected ImageUploader $imageUploader,
    ) {}

    public function index()
    {
        $doctors = HomeDoctor::orderBy('sort_order')->get();

        return view('admin.doctors.index', compact('doctors'));
    }

    public function sort(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:home_doctors,id',
        ]);

        foreach ($request->ids as $order => $id) {
            $doctor = HomeDoctor::find($id);
            $newOrder = $order + 1;
            if ($doctor && $doctor->sort_order !== $newOrder) {
                $doctor->update(['sort_order' => $newOrder]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function create()
    {
        return view('admin.doctors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'text' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
            'twitter' => 'nullable|url|max:255',
            'fb' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $imageValue = '';
        if ($request->hasFile('image')) {
            $imageValue = $this->imageUploader->store(
                $request->file('image'),
                'profile',
                $request->input('name')
            );
        }

        HomeDoctor::create([
            'image' => $imageValue,
            'name' => $request->name,
            'title' => $request->title,
            'text' => $request->text ?? '',
            'twitter' => $request->twitter,
            'fb' => $request->fb,
            'linkedin' => $request->linkedin,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Dokter berhasil ditambahkan!');
    }

    public function edit(HomeDoctor $doctor)
    {
        return view('admin.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, HomeDoctor $doctor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'text' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
            'twitter' => 'nullable|url|max:255',
            'fb' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $imageValue = $doctor->image;
        if ($request->hasFile('image')) {
            $this->imageUploader->archive($doctor->image, 'profile');
            $imageValue = $this->imageUploader->store(
                $request->file('image'),
                'profile',
                $request->input('name')
            );
        }

        $doctor->update([
            'image' => $imageValue,
            'name' => $request->name,
            'title' => $request->title,
            'text' => $request->text ?? '',
            'twitter' => $request->twitter,
            'fb' => $request->fb,
            'linkedin' => $request->linkedin,
            'sort_order' => $request->sort_order ?? $doctor->sort_order,
        ]);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Dokter berhasil diperbarui!');
    }

    public function destroy(HomeDoctor $doctor)
    {
        $doctor->delete();

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Dokter berhasil dihapus!');
    }
}
