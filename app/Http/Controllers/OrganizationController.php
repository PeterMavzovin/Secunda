<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Building;
use App\Models\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Организации",
 *     description="Управление организациями и поиск по геолокации"
 * )
 */
class OrganizationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/organizations",
     *     summary="Получить список всех организаций",
     *     tags={"Организации"},
     *     @OA\Response(
     *         response=200,
     *         description="Список организаций",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Organization"))
     *     )
     * )
    */
    public function index()
    {
        return Organization::with(['phones', 'activities', 'building'])->get();
    }

    /**
     * @OA\Get(
     *     path="/api/organizations/{id}",
     *     summary="Получить организацию по ID",
     *     tags={"Организации"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID организации",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Информация об организации"),
     *     @OA\Response(response=404, description="Организация не найдена")
     * )
    */
    public function show($id)
    {
        return Organization::with(['phones', 'activities', 'building'])->findOrFail($id);
    }

    /**
     * @OA\Post(
     *     path="/api/organizations",
     *     summary="Создать новую организацию",
     *     tags={"Организации"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "building_id"},
     *             @OA\Property(property="name", type="string", example="ООО Рога и Копыта"),
     *             @OA\Property(property="building_id", type="integer", example=3),
     *             @OA\Property(property="phones", type="array", @OA\Items(type="string", example="+7 999 123-45-67")),
     *             @OA\Property(property="activities", type="array", @OA\Items(type="integer", example=5))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Организация успешно создана"),
     *     @OA\Response(response=422, description="Ошибка валидации данных")
     * )
    */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'building_id' => 'required|exists:buildings,id',
            'phones' => 'array',
            'phones.*' => 'string',
            'activities' => 'array',
            'activities.*' => 'exists:activities,id',
        ]);

        $organization = Organization::create([
            'name' => $data['name'],
            'building_id' => $data['building_id'],
        ]);

        if (!empty($data['phones'])) {
            foreach ($data['phones'] as $phone) {
                $organization->phones()->create(['number' => $phone]);
            }
        }

        if (!empty($data['activities'])) {
            $organization->activities()->sync($data['activities']);
        }

        return response()->json($organization->load(['phones', 'activities', 'building']), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/organizations/{id}",
     *     summary="Обновить организацию по ID",
     *     tags={"Организации"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID организации",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="ООО Обновлённое Имя"),
     *             @OA\Property(property="building_id", type="integer", example=2),
     *             @OA\Property(property="phones", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="activities", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Организация обновлена"),
     *     @OA\Response(response=404, description="Организация не найдена")
     * )
    */
    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string',
            'building_id' => 'sometimes|exists:buildings,id',
            'phones' => 'array',
            'phones.*' => 'string',
            'activities' => 'array',
            'activities.*' => 'exists:activities,id',
        ]);

        $organization->update($data);

        if (isset($data['phones'])) {
            $organization->phones()->delete();
            foreach ($data['phones'] as $phone) {
                $organization->phones()->create(['number' => $phone]);
            }
        }

        if (isset($data['activities'])) {
            $organization->activities()->sync($data['activities']);
        }

        return $organization->load(['phones', 'activities', 'building']);
    }

    /**
     * @OA\Delete(
     *     path="/api/organizations/{id}",
     *     summary="Удалить организацию по ID",
     *     tags={"Организации"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID организации",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Организация удалена"),
     *     @OA\Response(response=404, description="Организация не найдена")
     * )
    */
    public function destroy($id)
    {
        $organization = Organization::findOrFail($id);
        $organization->delete();

        return response()->json(null, 204);
    }
    
    /**
     * @OA\Get(
     *     path="/api/organizations/nearby",
     *     summary="Найти организации поблизости",
     *     tags={"Организации"},
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         required=true,
     *         description="Широта",
     *         @OA\Schema(type="number", example=55.751244)
     *     ),
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         required=true,
     *         description="Долгота",
     *         @OA\Schema(type="number", example=37.618423)
     *     ),
     *     @OA\Parameter(
     *         name="radius",
     *         in="query",
     *         required=false,
     *         description="Радиус поиска (в км, по умолчанию 5)",
     *         @OA\Schema(type="number", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Организации поблизости",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Organization")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="radius_km", type="number"),
     *                 @OA\Property(property="found_buildings", type="integer"),
     *                 @OA\Property(property="found_organizations", type="integer")
     *             )
     *         )
     *     )
     * )
    */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric', // км
        ]);

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5; // 5 км по умолчанию

        // Получаем здания в радиусе
        $buildings = DB::table(DB::raw("(SELECT *, 
            (6371 * acos(
                    cos(radians($lat)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians($lng)) +
                    sin(radians($lat)) * sin(radians(latitude))
                )) AS distance
                FROM buildings
            ) AS b"))
        ->where('distance', '<=', $radius)
        ->orderBy('distance')
        ->get();

        // Извлекаем id зданий
        $buildingIds = $buildings->pluck('id');

        // Получаем организации в этих зданиях
        $organizations = Organization::whereIn('building_id', $buildingIds)->get();

        return response()->json([
            'data' => $organizations,
            'meta' => [
                'radius_km' => $radius,
                'found_buildings' => $buildings->count(),
                'found_organizations' => $organizations->count(),
            ]
        ]);
    }

    
    /**
     * @OA\Get(
     *     path="/api/organizations/search",
     *     summary="Поиск организаций по названию",
     *     tags={"Организации"},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Поисковая строка (часть названия организации)",
     *         @OA\Schema(type="string", example="Рога")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Результаты поиска",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Organization"))
     *     ),
     *     @OA\Response(response=400, description="Не указана поисковая строка")
     * )
    */
    public function search(Request $request)
    {
        $query = $request->input('query'); // например ?query=Рога

        if (!$query) {
            return response()->json(['error' => 'Search query is required'], 400);
        }

        $organizations = Organization::with(['building', 'activities', 'phones'])
            ->where('name', 'ILIKE', "%{$query}%") // ILIKE для PostgreSQL (регистронезависимый)
            ->get();

        return response()->json($organizations);
    }

}