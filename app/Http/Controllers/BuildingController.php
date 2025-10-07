<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Building;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Здания",
 *     description="Управление зданиями и поиск организаций, расположенных в них"
 * )
 */
class BuildingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/buildings",
     *     tags={"Здания"},
     *     summary="Получить список всех зданий",
     *     @OA\Response(
     *         response=200,
     *         description="Список зданий успешно получен",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Building"))
     *     )
     * )
     */
    public function index()
    {
        return Building::get();
    }

    /**
     * @OA\Get(
     *     path="/api/buildings/{id}",
     *     tags={"Здания"},
     *     summary="Получить информацию о конкретном здании",
     *     @OA\Parameter(
     *         name="id",
     *         description="ID здания",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о здании",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=404, description="Здание не найдено")
     * )
     */
    public function show($id)
    {
        return Building::findOrFail($id);
    }

    /**
     * @OA\Post(
     *     path="/api/buildings",
     *     tags={"Здания"},
     *     summary="Создать новое здание",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address"},
     *             @OA\Property(property="address", type="string", maxLength=255, example="г. Москва, ул. Ленина, 10")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Здание успешно создано",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'address' => 'required|string|max:255',
        ]);

        $building = Building::create($data);

        return response()->json($building, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/buildings/{id}",
     *     tags={"Здания"},
     *     summary="Обновить данные здания",
     *     @OA\Parameter(
     *         name="id",
     *         description="ID здания",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address"},
     *             @OA\Property(property="address", type="string", example="г. Санкт-Петербург, Невский пр., 12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Здание успешно обновлено",
     *         @OA\JsonContent(ref="#/components/schemas/Building")
     *     ),
     *     @OA\Response(response=404, description="Здание не найдено")
     * )
     */
    public function update(Request $request, $id)
    {
        $building = Building::findOrFail($id);

        $data = $request->validate([
            'address' => 'required|string|max:255',
        ]);
        $building->update($data);

        return response()->json($building, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/buildings/{id}",
     *     tags={"Здания"},
     *     summary="Удалить здание",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID здания",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=204, description="Здание удалено"),
     *     @OA\Response(response=404, description="Здание не найдено")
     * )
     */
    public function destroy($id)
    {
        $building = Building::findOrFail($id);
        $building->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/buildings/{id}/organizations",
     *     tags={"Здания"},
     *     summary="Получить список организаций, находящихся в здании",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID здания",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Организации успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="building", type="string", example="ул. Ленина, 10"),
     *             @OA\Property(
     *                 property="organizations",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Organization")
     *             )
     *         )
     *     )
     * )
     */
    public function organizations($id)
    {
        $building = Building::with('organizations')->findOrFail($id);

        return response()->json([
            'building' => $building->address,
            'organizations' => $building->organizations
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/buildings/nearby",
     *     tags={"Здания"},
     *     summary="Найти ближайшие здания по координатам",
     *     description="Возвращает список зданий в пределах указанного радиуса (по умолчанию 5 км)",
     *     @OA\Parameter(name="lat", in="query", required=true, description="Широта", @OA\Schema(type="number", example=55.751244)),
     *     @OA\Parameter(name="lng", in="query", required=true, description="Долгота", @OA\Schema(type="number", example=37.618423)),
     *     @OA\Parameter(name="radius", in="query", required=false, description="Радиус поиска (в км)", @OA\Schema(type="number", example=5)),
     *     @OA\Response(
     *         response=200,
     *         description="Ближайшие здания успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Building")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="center", type="object",
     *                     @OA\Property(property="latitude", type="number", example=55.751244),
     *                     @OA\Property(property="longitude", type="number", example=37.618423)
     *                 ),
     *                 @OA\Property(property="radius_km", type="number", example=5),
     *                 @OA\Property(property="found", type="integer", example=3)
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
            'radius' => 'nullable|numeric',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5;

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

        return response()->json([
            'data' => $buildings,
            'meta' => [
                'center' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
                'radius_km' => $radius,
                'found' => $buildings->count(),
            ]
        ]);
    }
}