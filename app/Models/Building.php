<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Building",
 *     title="Здание",
 *     description="Здание, где расположена организация",
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="address", type="string", example="г. Москва, ул. Ленина, 10"),
 *     @OA\Property(property="latitude", type="number", format="float", example=55.7558),
 *     @OA\Property(property="longitude", type="number", format="float", example=37.6173)
 * )
 */
class Building extends Model
{
    protected $fillable = ['address', 'latitude', 'longitude'];

    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }
}