<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\Country\CountryAllRequest;
use App\Http\Requests\Location\Country\CountryFindRequest;
use App\Http\Resources\Location\Country\CountryCollection;
use App\Http\Resources\Location\Country\CountryResource;
use App\Models\Country;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Location - Countries",
 *     description="Endpoints para gestionar países"
 * )
 *
 * @OA\Schema(
 *     schema="Country",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=48),
 *     @OA\Property(property="region_id", type="integer", example=8),
 *     @OA\Property(property="name", type="string", example="Colombia"),
 *     @OA\Property(property="iso3", type="string", example="COL"),
 *     @OA\Property(property="iso2", type="string", example="CO"),
 *     @OA\Property(property="numeric_code", type="string", example="170"),
 *     @OA\Property(property="phone_code", type="string", example="57"),
 *     @OA\Property(property="currency", type="string", example="COP"),
 *     @OA\Property(property="currency_name", type="string", example="Colombian peso"),
 *     @OA\Property(property="currency_symbol", type="string", example="$"),
 *     @OA\Property(property="tld", type="string", example=".co"),
 *     @OA\Property(property="native", type="string", example="Colombia"),
 *     @OA\Property(property="nationality", type="string", example="Colombian"),
 *     @OA\Property(property="latitude", type="string", example="4.00000000"),
 *     @OA\Property(property="longitude", type="string", example="-72.00000000"),
 *     @OA\Property(property="emoji", type="string", example="🇨🇴"),
 *     @OA\Property(property="emojiU", type="string", example="U+1F1E8 U+1F1F4"),
 *     @OA\Property(
 *         property="translations",
 *         type="object",
 *         description="Traducciones del nombre del país por idioma.",
 *         example={
 *             "ko":"콜롬비아",
 *             "pt-BR":"Colômbia",
 *             "pt":"Colômbia",
 *             "nl":"Colombia",
 *             "hr":"Kolumbija",
 *             "fa":"کلمبیا",
 *             "de":"Kolumbien",
 *             "es":"Colombia",
 *             "fr":"Colombie",
 *             "ja":"コロンビア",
 *             "it":"Colombia",
 *             "zh-CN":"哥伦比亚",
 *             "tr":"Kolombiya",
 *             "ru":"Колумбия",
 *             "uk":"Колумбія",
 *             "pl":"Kolumbia"
 *         }
 *     ),
 *     @OA\Property(property="settings", type="object", nullable=true, example={}),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(
 *         property="region",
 *         type="object",
 *         ref="#/components/schemas/Region"
 *     )
 * )
 */
class CountryController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/location/countries/all",
     *     tags={"Location - Countries"},
     *     summary="Listar los países",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de Busqueda.",
     *         required=false,
     *         @OA\Schema(type="string", example="juan")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id","name","created_at", "updated_at"}, example="name")
     *     ),
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Orden de datos.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc","desc"}, example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Registros por página.",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de países cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="countries",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Country")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="total", type="integer", example=1),
     *                         @OA\Property(property="count", type="integer", example=1),
     *                         @OA\Property(property="per_page", type="integer", example=1),
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(property="total_pages", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function all(CountryAllRequest $request)
    {
        try {
            $countries = Country::with(['region', 'departments'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $countries = $countries->paginate($request->integer('per_page', $countries->count()));

            return $this->successResponse(
                new CountryCollection($countries),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/location/countries/find/{id}",
     *     tags={"Location - Countries"},
     *     summary="Obtener un país específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del país",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="country",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Country")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del país no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=1)
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function find(CountryFindRequest $request, $id)
    {
        try {
            $country = Country::with(['region', 'departments'])->findOrFail($id);

            return $this->successResponse(
                new CountryResource($country),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
