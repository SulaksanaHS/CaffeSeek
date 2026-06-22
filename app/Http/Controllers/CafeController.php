<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use App\Models\CafeMenu;
use App\Models\CafePhoto;
use App\Models\CafeTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CafeController extends Controller
{
    public function index(Request $request)
    {
        $query = Cafe::with(['photos', 'menus', 'tables']); 

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $facilityFilters = collect($request->input('facilities', []))->filter();
        
        $facilityMap = [
            'wifi' => 'has_wifi',
            'colokan' => 'has_colokan',
            'meeting_room' => 'meeting_room_available',
            'indoor' => 'has_indoor',
            'outdoor' => 'has_outdoor',
            'smoking_area' => 'has_smoking_area',
        ];

        foreach ($facilityFilters as $facilityKey) {
            if (isset($facilityMap[$facilityKey])) {
                $query->where($facilityMap[$facilityKey], true);
            }
        }

        $cafes = $query->latest()->paginate(5)->withQueryString();

        $cafeItems = collect($cafes->items())->map(fn ($cafe) => self::transformCafe($cafe))->values()->all();

        if ($request->wantsJson()) {
            return response()->json([
                'cafes' => $cafeItems,
                'pagination' => $this->paginationMeta($cafes),
            ]);
        }

        return Inertia::render('admin/cafes/index', [
            'cafes' => $cafeItems,
            'filters' => [
                'search' => $request->input('search', ''),
                'facilities' => $facilityFilters->values()->all(),
            ],
            'pagination' => $this->paginationMeta($cafes),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCafe($request);

        DB::transaction(function () use ($request, $validated) {
            $cafe = Cafe::create($this->buildCafePayload($request));

            $this->handleMapsEmbedUpdate($request, $cafe);
            
            if ($request->hasFile('video')) {
                $this->handleVideoUpload($request, $cafe);
            }
            
            $this->handlePhotoUploads($request, $cafe);
            $this->syncMenus($request, $cafe);
            $this->syncTables($request, $cafe);
        });

        return redirect()->back()->with('success', 'Caffe & Resto created successfully.');
    }

    public function show(Cafe $cafe)
    {
        if (request()->wantsJson()) {
            $cafe->load(['photos', 'menus', 'tables']); 

            return response()->json([
                'cafe' => self::transformCafe($cafe),
            ]);
        }

        abort(404);
    }

    public function update(Request $request, Cafe $cafe)
    {
        $validated = $this->validateCafe($request, $cafe->id);

        DB::transaction(function () use ($request, $cafe) {
            $cafe->update($this->buildCafePayload($request));

            $this->handleMapsEmbedUpdate($request, $cafe);

            if ($request->hasFile('video')) {
                $this->deleteStoredFile($cafe->video_url);
                $this->handleVideoUpload($request, $cafe);
            }

            $this->handlePhotoUploads($request, $cafe);
            $this->removeMarkedPhotos($request, $cafe);

            $this->syncMenus($request, $cafe);
            $this->syncTables($request, $cafe);
        });

        return redirect()->back()->with('success', 'Caffe & Resto updated successfully.');
    }

    public function destroy(Cafe $cafe)
    {
        $cafe->delete();
        return redirect()->route('cafes.index')->with('success', 'Caffe & Resto removed.');
    }

    private function validateCafe(Request $request, ?int $cafeId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'maps_embed_url' => 'nullable|string',
            'whatsapp' => 'required|string', 
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:51200',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120',
            
            // Validasi format JSON dari React
            'operational_hours_json' => 'nullable|string',
            'menus_json' => 'nullable|string',
            'tables_json' => 'nullable|string',
        ];

        if ($request->boolean('meeting_room_available')) {
            $rules['meeting_room_capacity'] = 'required|integer|min:1';
        }

        return $request->validate($rules);
    }

    private function buildCafePayload(Request $request): array
    {
        // Decode JSON operational hours dari React
        $operationalHours = [];
        if ($request->filled('operational_hours_json')) {
            $operationalHours = json_decode($request->input('operational_hours_json'), true);
        }
        if (empty($operationalHours)) {
            $operationalHours = [
                'monday' => '24 jam', 'tuesday' => '24 jam', 'wednesday' => '24 jam',
                'thursday' => '24 jam', 'friday' => '24 jam', 'saturday' => '24 jam', 'sunday' => '24 jam'
            ];
        }

        return [
            'name' => $request->input('name'),
            'kategori' => $request->input('kategori'),
            'description' => $request->input('description'),
            'location' => $request->input('location'),
            'whatsapp' => $request->input('whatsapp'),
            'operational_hours' => $operationalHours, 
            'has_colokan' => $request->boolean('has_colokan'),
            'has_wifi' => $request->boolean('has_wifi'),
            'has_indoor' => $request->boolean('has_indoor'),
            'has_outdoor' => $request->boolean('has_outdoor'),
            'has_smoking_area' => $request->boolean('has_smoking_area'),
            'meeting_room_available' => $request->boolean('meeting_room_available'),
            'meeting_room_capacity' => $request->filled('meeting_room_capacity') ? $request->input('meeting_room_capacity') : null,
        ];
    }

    private function handleMapsEmbedUpdate(Request $request, Cafe $cafe): void
    {
        $cafe->update([
            'maps_embed_url' => $request->input('maps_embed_url') ?? null,
        ]);
    }

    private function handleVideoUpload(Request $request, Cafe $cafe): void
    {
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('cafes/videos', 'public');
            $cafe->update([
                'video_url' => Storage::url($videoPath),
            ]);
        }
    }

    private function handlePhotoUploads(Request $request, Cafe $cafe): void
    {
        if (!$request->hasFile('photos')) {
            return;
        }

        $sortStart = ($cafe->photos()->max('sort_order') ?? -1) + 1;
        $hasExistingPhotos = $cafe->photos()->exists();

        foreach ($request->file('photos') as $index => $photo) {
            $path = $photo->store('cafes/photos', 'public');

            CafePhoto::create([
                'cafe_id' => $cafe->id,
                'url' => Storage::url($path),
                'is_primary' => !$hasExistingPhotos && $index === 0,
                'sort_order' => $sortStart + $index,
            ]);
        }
    }

    private function removeMarkedPhotos(Request $request, Cafe $cafe): void
    {
        $photoIds = [];
        if ($request->filled('removed_photo_ids_json')) {
            $photoIds = json_decode($request->input('removed_photo_ids_json'), true);
        }

        if (empty($photoIds)) return;

        $photos = CafePhoto::whereIn('id', $photoIds)->where('cafe_id', $cafe->id)->get();

        foreach ($photos as $photo) {
            $this->deleteStoredFile($photo->url);
            $photo->delete();
        }

        if (!$cafe->photos()->where('is_primary', true)->exists()) {
            $nextPrimary = $cafe->photos()->orderBy('sort_order')->first();
            if ($nextPrimary) {
                $nextPrimary->update(['is_primary' => true]);
            }
        }
    }

    private function syncMenus(Request $request, Cafe $cafe): void
    {
        // Handle Hapus Menu yang ditandai (Saat Edit)
        if ($request->filled('removed_menu_ids_json')) {
            $removedMenuIds = json_decode($request->input('removed_menu_ids_json'), true);
            if (!empty($removedMenuIds)) {
                $menusToDelete = CafeMenu::whereIn('id', $removedMenuIds)->where('cafe_id', $cafe->id)->get();
                foreach ($menusToDelete as $mDelete) {
                    $this->deleteStoredFile($mDelete->photo_url);
                    $mDelete->delete();
                }
            }
        }

        // Handle Create / Update Menu dari JSON
        if (!$request->filled('menus_json')) return;
        $menus = json_decode($request->input('menus_json'), true);
        if (empty($menus)) return;

        foreach ($menus as $index => $menuData) {
            if (empty($menuData['name'])) continue; 

            $menu = null;
            if (!empty($menuData['id'])) {
                $menu = CafeMenu::where('cafe_id', $cafe->id)->where('id', $menuData['id'])->first();
            }

            if (!$menu) {
                $menu = new CafeMenu(['cafe_id' => $cafe->id]);
            }

            $menu->name = $menuData['name'] ?? '';
            $menu->category = $menuData['category'] ?? '';
            $menu->price = $menuData['price'] ?? 0;

            // Cek file upload gambar menu yang dicocokkan dengan index penamaan di React
            if ($request->hasFile("menu_photo_{$index}")) {
                $this->deleteStoredFile($menu->photo_url);
                $photoPath = $request->file("menu_photo_{$index}")->store('cafes/menus', 'public');
                $menu->photo_url = Storage::url($photoPath);
            }

            $menu->save();
        }
    }
    
    private function syncTables(Request $request, Cafe $cafe): void
    {
        // Handle Hapus Meja yang ditandai (Saat Edit)
        if ($request->filled('removed_table_ids_json')) {
            $removedTableIds = json_decode($request->input('removed_table_ids_json'), true);
            if (!empty($removedTableIds)) {
                CafeTable::whereIn('id', $removedTableIds)->where('cafe_id', $cafe->id)->delete();
            }
        }

        // Handle Create / Update Meja dari JSON
        if (!$request->filled('tables_json')) return;
        $tables = json_decode($request->input('tables_json'), true);
        if (empty($tables)) return;

        foreach ($tables as $tableData) {
            if (empty($tableData['table_number'])) continue; 

            $table = null;
            if (!empty($tableData['id'])) {
                $table = CafeTable::where('cafe_id', $cafe->id)->where('id', $tableData['id'])->first();
            }

            if (!$table) {
                $table = new CafeTable(['cafe_id' => $cafe->id]);
            }
            
            $table->table_number = $tableData['table_number'] ?? 0;
            $table->capacity = $tableData['capacity'] ?? 0;
            $table->save();
        }
    }

    private function deleteStoredFile(?string $publicUrl): void
    {
        if (!$publicUrl) return;
        $path = parse_url($publicUrl, PHP_URL_PATH);
        $path = ltrim(str_replace('/storage/', '', $path), '/');
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function transformCafe(Cafe $cafe): array
    {
        return [
            'id' => $cafe->id,
            'name' => $cafe->name,
            'kategori' => $cafe->kategori,
            'description' => $cafe->description,
            'location' => $cafe->location,
            'maps_embed_url' => $cafe->maps_embed_url ?? null,
            'whatsapp' => $cafe->whatsapp,
            'video_url' => $cafe->video_url,
            'operational_hours' => $cafe->operational_hours,
            'facilities' => [
                'colokan' => $cafe->has_colokan,
                'wifi' => $cafe->has_wifi,
                'indoor' => $cafe->has_indoor,
                'outdoor' => $cafe->has_outdoor,
                'smoking_area' => $cafe->has_smoking_area,
                'meeting_room' => [
                    'available' => $cafe->meeting_room_available,
                    'capacity' => $cafe->meeting_room_capacity,
                ],
            ],
            'photos' => $cafe->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'is_primary' => $photo->is_primary,
            ])->values()->all(),
            'menus' => $cafe->menus->map(fn ($menu) => [
                'id' => $menu->id,
                'name' => $menu->name,
                'category' => $menu->category,
                'price' => $menu->price,
                'photo_url' => $menu->photo_url,
            ])->values()->all(),
            'tables' => $cafe->tables->map(fn ($table) => [
                'id' => $table->id,
                'table_number' => $table->table_number,
                'capacity' => $table->capacity,
            ])->values()->all(),
        ];
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}