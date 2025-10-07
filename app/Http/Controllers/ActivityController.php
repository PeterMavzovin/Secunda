<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Деятельности",
 *     description="Управление видами деятельности и связанные организации"
 * )
 */
class ActivityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/activities",
     *     summary="Получить список всех видов деятельности",
     *     tags={"Деятельности"},
     *     @OA\Response(
     *         response=200,
     *         description="Список всех видов деятельности",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Activity"))
     *     )
     * )
     */
    public function index()
    {
        return Activity::get();
    }

    /**
     * @OA\Get(
     *     path="/api/activities/{id}",
     *     summary="Получить вид деятельности по ID",
     *     tags={"Деятельности"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID деятельности",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Информация о деятельности"),
     *     @OA\Response(response=404, description="Деятельность не найдена")
     * )
     */
    public function show($id)
    {
        return Activity::findOrFail($id);
    }

    /**
     * @OA\Post(
     *     path="/api/activities",
     *     summary="Создать новую деятельность",
     *     tags={"Деятельности"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Молочная продукция"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=5)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Деятельность создана"),
     *     @OA\Response(response=422, description="Нельзя создать деятельность глубже 3-го уровня")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:activities,id',
        ]);

        // Проверка глубины дерева
        if (!empty($data['parent_id'])) {
            $parent = Activity::find($data['parent_id']);
            if ($parent && $parent->ancestors()->count() >= 2) {
                return response()->json([
                    'message' => 'Нельзя создать деятельность глубже 3-го уровня'
                ], 422);
            }
        }

        $activity = Activity::create($data);
        return response()->json($activity, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/activities/{id}",
     *     summary="Обновить деятельность по ID",
     *     tags={"Деятельности"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID деятельности",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Еда и напитки")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Деятельность обновлена"),
     *     @OA\Response(response=404, description="Деятельность не найдена")
     * )
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $activity->update($data);

        return response()->json($activity, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/activities/{id}",
     *     summary="Удалить деятельность по ID",
     *     tags={"Деятельности"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID деятельности",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Деятельность удалена"),
     *     @OA\Response(response=404, description="Деятельность не найдена")
     * )
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/activities/{id}/organizations",
     *     summary="Получить организации, связанные с данной деятельностью",
     *     tags={"Деятельности"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID деятельности",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Список организаций, связанных с деятельностью"),
     *     @OA\Response(response=404, description="Деятельность не найдена")
     * )
     */
    public function organizations($id)
    {
        $activity = Activity::with('organizations')->findOrFail($id);

        return response()->json([
            'activity' => $activity->name,
            'organizations' => $activity->organizations
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/activities/{id}/organizations-with-descendants",
     *     summary="Получить организации, связанные с данной деятельностью и её потомками",
     *     tags={"Деятельности"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID деятельности",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Организации, связанные с деятельностью и её потомками"),
     *     @OA\Response(response=404, description="Деятельность не найдена")
     * )
     */
    public function organizationsWithDescendants($id)
    {
        $activity = Activity::findOrFail($id);

        $activityIds = Activity::where('_lft', '>=', $activity->_lft)
            ->where('_rgt', '<=', $activity->_rgt)
            ->pluck('id');

        $organizations = \App\Models\Organization::whereHas('activities', function ($query) use ($activityIds) {
            $query->whereIn('activities.id', $activityIds);
        })->with('activities')->get();

        return response()->json([
            'activity' => $activity->name,
            'organizations' => $organizations
        ]);
    }
}
