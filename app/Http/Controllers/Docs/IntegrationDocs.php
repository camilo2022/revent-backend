<?php

namespace App\Http\Controllers\Docs;

/**
 * Documentación Swagger del módulo Integraciones (/integration): una operación por ruta, agrupadas por submódulo.
 * Solo describe las rutas (la lógica está en App\Http\Controllers\Api\Integration); no se ejecuta.
 * Se generó a partir de routes/api.php y de los FormRequest de cada acción; al agregar o cambiar una ruta, actualízala aquí.
 */
class IntegrationDocs
{
    /**
     * @OA\Post(
     *     path="/integration/transfer_approval/confirm",
     *     tags={"Integración - Aprobación de traslados"},
     *     summary="Confirm",
     *     description="Permiso requerido: integration.transfer_approval.confirm. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"token","query","items"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="query", type="string"),
     *         @OA\Property(property="items", type="array", @OA\Items(type="string"))
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc44() {}

    /**
     * @OA\Post(
     *     path="/integration/transfer_approval/search",
     *     tags={"Integración - Aprobación de traslados"},
     *     summary="Search",
     *     description="Permiso requerido: integration.transfer_approval.search.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"token","query"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="query", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc43() {}

    /**
     * @OA\Post(
     *     path="/integration/search_document/purchase_order",
     *     tags={"Integración - Buscar documento"},
     *     summary="Purchase order",
     *     description="Permiso requerido: integration.search_document.purchase_order.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"query","cookie"},
     *         @OA\Property(property="query", type="string"),
     *         @OA\Property(property="cookie", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc49() {}

    /**
     * @OA\Post(
     *     path="/integration/search_document/purchase_order_pdf",
     *     tags={"Integración - Buscar documento"},
     *     summary="Purchase order pdf",
     *     description="Permiso requerido: integration.search_document.purchase_order_pdf.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"query","cookie"},
     *         @OA\Property(property="query", type="string"),
     *         @OA\Property(property="cookie", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc51() {}

    /**
     * @OA\Post(
     *     path="/integration/search_document/transfer_note",
     *     tags={"Integración - Buscar documento"},
     *     summary="Transfer note",
     *     description="Permiso requerido: integration.search_document.transfer_note.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"query","token"},
     *         @OA\Property(property="query", type="string"),
     *         @OA\Property(property="token", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc48() {}

    /**
     * @OA\Post(
     *     path="/integration/search_document/transfer_note_pdf",
     *     tags={"Integración - Buscar documento"},
     *     summary="Transfer note pdf",
     *     description="Permiso requerido: integration.search_document.transfer_note_pdf.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"query","token"},
     *         @OA\Property(property="query", type="string"),
     *         @OA\Property(property="token", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc50() {}

    /**
     * @OA\Get(
     *     path="/integration/search_document/types",
     *     tags={"Integración - Buscar documento"},
     *     summary="Types",
     *     description="Permiso requerido: integration.search_document.types.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc47() {}

    /**
     * @OA\Get(
     *     path="/integration/customer_sales/cost_centers",
     *     tags={"Integración - Clientes por venta"},
     *     summary="Cost centers",
     *     description="Permiso requerido: integration.customer_sales.cost_centers.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc45() {}

    /**
     * @OA\Post(
     *     path="/integration/customer_sales/search",
     *     tags={"Integración - Clientes por venta"},
     *     summary="Search",
     *     description="Permiso requerido: integration.customer_sales.search. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"token","date"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="date", type="string"),
     *         @OA\Property(property="cost_center", type="integer")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc46() {}

    /**
     * @OA\Post(
     *     path="/integration/credentials/check",
     *     tags={"Integración - Credenciales de Siigo"},
     *     summary="Check",
     *     description="Permiso requerido: integration.credentials.check.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object",
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="cookie", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc1() {}

    /**
     * @OA\Post(
     *     path="/integration/accounts_payable/advance",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Advance store",
     *     description="Permiso requerido: integration.accounts_payable.advance_store. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"proveedor","proveedor.AccountID","proveedor.MsThirdPartyID","tipo","accion","origen","fecha","valor"},
     *         @OA\Property(property="proveedor", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="proveedor.AccountID", type="integer"),
     *         @OA\Property(property="proveedor.MsThirdPartyID", type="string"),
     *         @OA\Property(property="tipo", type="integer"),
     *         @OA\Property(property="accion", type="string"),
     *         @OA\Property(property="origen", type="integer"),
     *         @OA\Property(property="fecha", type="string", format="date"),
     *         @OA\Property(property="observaciones", type="string"),
     *         @OA\Property(property="valor", type="number"),
     *         @OA\Property(property="comprobante", type="string", format="binary")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc33() {}

    /**
     * @OA\Post(
     *     path="/integration/accounts_payable/conciliation",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Conciliation store",
     *     description="Permiso requerido: integration.accounts_payable.conciliation_store. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"proveedor","proveedor.AccountID","proveedor.MsThirdPartyID","tipo","fecha","documentos","recibos"},
     *         @OA\Property(property="proveedor", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="proveedor.AccountID", type="integer"),
     *         @OA\Property(property="proveedor.MsThirdPartyID", type="string"),
     *         @OA\Property(property="tipo", type="integer"),
     *         @OA\Property(property="fecha", type="string", format="date"),
     *         @OA\Property(property="observaciones", type="string"),
     *         @OA\Property(property="documentos", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="recibos", type="array", @OA\Items(type="string"))
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc34() {}

    /**
     * @OA\Get(
     *     path="/integration/accounts_payable/documents/{accountId}",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Documents",
     *     description="Permiso requerido: integration.accounts_payable.documents.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="accountId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="accountId", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="MsThirdPartyID", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="all_data", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc28() {}

    /**
     * @OA\Post(
     *     path="/integration/accounts_payable/payment",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Payment store",
     *     description="Permiso requerido: integration.accounts_payable.payment_store. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"proveedor","proveedor.AccountID","proveedor.MsThirdPartyID","tipo","accion","origen","fecha","valor","documentos"},
     *         @OA\Property(property="proveedor", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="proveedor.AccountID", type="integer"),
     *         @OA\Property(property="proveedor.MsThirdPartyID", type="string"),
     *         @OA\Property(property="tipo", type="integer"),
     *         @OA\Property(property="accion", type="string"),
     *         @OA\Property(property="origen", type="integer"),
     *         @OA\Property(property="fecha", type="string", format="date"),
     *         @OA\Property(property="observaciones", type="string"),
     *         @OA\Property(property="valor", type="number"),
     *         @OA\Property(property="documentos", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="comprobante", type="string", format="binary")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc32() {}

    /**
     * @OA\Get(
     *     path="/integration/accounts_payable/payment_html/{acEntryId}",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Payment html",
     *     description="Permiso requerido: integration.accounts_payable.payment_html.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="acEntryId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="acEntryId", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc31() {}

    /**
     * @OA\Get(
     *     path="/integration/accounts_payable/payments/{accountId}",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Payments",
     *     description="Permiso requerido: integration.accounts_payable.payments.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="accountId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="accountId", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc29() {}

    /**
     * @OA\Get(
     *     path="/integration/accounts_payable/payments_weekly",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Payments weekly",
     *     description="Permiso requerido: integration.accounts_payable.payments_weekly.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc30() {}

    /**
     * @OA\Get(
     *     path="/integration/accounts_payable/providers",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Providers",
     *     description="Permiso requerido: integration.accounts_payable.providers.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc26() {}

    /**
     * @OA\Post(
     *     path="/integration/accounts_payable/providers_sync",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Providers sync",
     *     description="Permiso requerido: integration.accounts_payable.providers_sync.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc27() {}

    /**
     * @OA\Get(
     *     path="/integration/accounts_payable/settings",
     *     tags={"Integración - Cuentas por pagar"},
     *     summary="Settings",
     *     description="Permiso requerido: integration.accounts_payable.settings.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc25() {}

    /**
     * @OA\Post(
     *     path="/integration/exports/inventory",
     *     tags={"Integración - Exportes"},
     *     summary="Send",
     *     description="Permiso requerido: integration.exports_inventory.send. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object",
     *         @OA\Property(property="emails", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="inventory_type", type="string", enum={"positivo","negativo"}),
     *         @OA\Property(property="page_size", type="integer"),
     *         @OA\Property(property="type", type="string", enum={"Product","Variant"})
     *     ))),
     *     @OA\Response(response=202, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc2() {}

    /**
     * @OA\Post(
     *     path="/integration/exports/invoice",
     *     tags={"Integración - Exportes"},
     *     summary="Send",
     *     description="Permiso requerido: integration.exports_invoice.send. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object",
     *         @OA\Property(property="emails", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="month", type="string"),
     *         @OA\Property(property="created_start", type="string", format="date"),
     *         @OA\Property(property="created_end", type="string", format="date"),
     *         @OA\Property(property="page_size", type="integer")
     *     ))),
     *     @OA\Response(response=202, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc3() {}

    /**
     * @OA\Post(
     *     path="/integration/exports/invoice_360",
     *     tags={"Integración - Exportes"},
     *     summary="Send",
     *     description="Permiso requerido: integration.exports_invoice_360.send. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object",
     *         @OA\Property(property="emails", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="month", type="string"),
     *         @OA\Property(property="created_start", type="string", format="date"),
     *         @OA\Property(property="created_end", type="string", format="date"),
     *         @OA\Property(property="page_size", type="integer")
     *     ))),
     *     @OA\Response(response=202, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc4() {}

    /**
     * @OA\Post(
     *     path="/integration/exports/purchase",
     *     tags={"Integración - Exportes"},
     *     summary="Send",
     *     description="Permiso requerido: integration.exports_purchase.send. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object",
     *         @OA\Property(property="emails", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="month", type="string"),
     *         @OA\Property(property="created_start", type="string", format="date"),
     *         @OA\Property(property="created_end", type="string", format="date"),
     *         @OA\Property(property="page_size", type="integer")
     *     ))),
     *     @OA\Response(response=202, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc5() {}

    /**
     * @OA\Get(
     *     path="/integration/inventory_filter/all_warehouses",
     *     tags={"Integración - Filtro de inventario"},
     *     summary="All warehouses",
     *     description="Permiso requerido: integration.inventory_filter.all_warehouses.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="referencia", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc37() {}

    /**
     * @OA\Get(
     *     path="/integration/inventory_filter/catalogs",
     *     tags={"Integración - Filtro de inventario"},
     *     summary="Catalogs",
     *     description="Permiso requerido: integration.inventory_filter.catalogs.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc35() {}

    /**
     * @OA\Get(
     *     path="/integration/inventory_filter/images",
     *     tags={"Integración - Filtro de inventario"},
     *     summary="Images",
     *     description="Permiso requerido: integration.inventory_filter.images.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="referencia", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc38() {}

    /**
     * @OA\Get(
     *     path="/integration/inventory_filter/search",
     *     tags={"Integración - Filtro de inventario"},
     *     summary="Search",
     *     description="Permiso requerido: integration.inventory_filter.search.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="warehouse_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc36() {}

    /**
     * @OA\Post(
     *     path="/integration/product_photos/bulk_upload",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Bulk upload",
     *     description="Permiso requerido: integration.product_photos.bulk_upload. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"zip","zip_mode"},
     *         @OA\Property(property="zip", type="string", format="binary"),
     *         @OA\Property(property="zip_mode", type="string", enum={"folders","filenames"})
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc20() {}

    /**
     * @OA\Delete(
     *     path="/integration/product_photos/delete",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Delete",
     *     description="Permiso requerido: integration.product_photos.delete.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"referencia","filename"},
     *         @OA\Property(property="referencia", type="string"),
     *         @OA\Property(property="filename", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc19() {}

    /**
     * @OA\Get(
     *     path="/integration/product_photos/explorer",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Explorer",
     *     description="Permiso requerido: integration.product_photos.explorer.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="path", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc21() {}

    /**
     * @OA\Delete(
     *     path="/integration/product_photos/explorer/delete",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Explorer delete",
     *     description="Permiso requerido: integration.product_photos.explorer_delete.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"path","filename"},
     *         @OA\Property(property="path", type="string"),
     *         @OA\Property(property="filename", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc24() {}

    /**
     * @OA\Post(
     *     path="/integration/product_photos/explorer/folder",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Explorer folder",
     *     description="Permiso requerido: integration.product_photos.explorer_folder.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"name"},
     *         @OA\Property(property="path", type="string"),
     *         @OA\Property(property="name", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc22() {}

    /**
     * @OA\Post(
     *     path="/integration/product_photos/explorer/upload",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Explorer upload",
     *     description="Permiso requerido: integration.product_photos.explorer_upload. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"token","path","photos"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="path", type="string"),
     *         @OA\Property(property="photos", type="array", @OA\Items(type="string"))
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc23() {}

    /**
     * @OA\Get(
     *     path="/integration/product_photos/search",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Search",
     *     description="Permiso requerido: integration.product_photos.search.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="referencia", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc17() {}

    /**
     * @OA\Post(
     *     path="/integration/product_photos/upload",
     *     tags={"Integración - Fotos de producto"},
     *     summary="Upload",
     *     description="Permiso requerido: integration.product_photos.upload. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"referencia","photos"},
     *         @OA\Property(property="referencia", type="string"),
     *         @OA\Property(property="photos", type="array", @OA\Items(type="string"))
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc18() {}

    /**
     * @OA\Post(
     *     path="/integration/purchase_order_reception/confirm",
     *     tags={"Integración - Recepción de órdenes de compra"},
     *     summary="Confirm",
     *     description="Permiso requerido: integration.purchase_order_reception.confirm. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"token","receiving","receiving.Order","receiving.Order.ACEntryID","receiving.Order.DocName","receiving.Order.DocDate","receiving.Checklist","receiving.Checklist.cajas_master","receiving.Checklist.sellos","receiving.Checklist.estado_cajas","receiving.Checklist.documentos","receiving.Checklist.empaque","receiving.Checklist.stickers","receiving.Checklist.producto","receiving.Checklist.exactitud","receiving.Checklist.variacion_diseno","receiving.Checklist.firma","receiving.Checklist.reporte_whatsapp","receiving.Checklist.respaldo_whatsapp","receiving.Checklist.alerta","receiving.Checklist.venta","receiving.Checklist.observaciones","receiving.Checklist.responsable","receiving.Checklist.responsable.nombre","receiving.Checklist.responsable.cargo","receiving.Checklist.responsable.departamento","imagenes"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="receiving", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="receiving.Order", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="receiving.Order.ACEntryID", type="string"),
     *         @OA\Property(property="receiving.Order.DocName", type="string"),
     *         @OA\Property(property="receiving.Order.DocDate", type="string"),
     *         @OA\Property(property="receiving.Order.Observations", type="string"),
     *         @OA\Property(property="receiving.Order.Warehouse", type="string"),
     *         @OA\Property(property="receiving.Items", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="receiving.Totals", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="receiving.Totals.Requested", type="number"),
     *         @OA\Property(property="receiving.Totals.Receiving", type="number"),
     *         @OA\Property(property="receiving.Totals.Pending", type="number"),
     *         @OA\Property(property="receiving.Checklist", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="receiving.Checklist.cajas_master", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.sellos", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.estado_cajas", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.documentos", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.empaque", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.stickers", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.producto", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.exactitud", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.variacion_diseno", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.firma", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.reporte_whatsapp", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.respaldo_whatsapp", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.alerta", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.venta", type="boolean"),
     *         @OA\Property(property="receiving.Checklist.referencias_recibidas", type="string"),
     *         @OA\Property(property="receiving.Checklist.observaciones", type="string"),
     *         @OA\Property(property="receiving.Checklist.responsable", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="receiving.Checklist.responsable.nombre", type="string"),
     *         @OA\Property(property="receiving.Checklist.responsable.cargo", type="string"),
     *         @OA\Property(property="receiving.Checklist.responsable.departamento", type="string"),
     *         @OA\Property(property="receiving.Checklist.responsable.celular", type="string"),
     *         @OA\Property(property="imagenes", type="array", @OA\Items(type="string"))
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc40() {}

    /**
     * @OA\Post(
     *     path="/integration/purchase_order_reception/search",
     *     tags={"Integración - Recepción de órdenes de compra"},
     *     summary="Search",
     *     description="Permiso requerido: integration.purchase_order_reception.search.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"token","query"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="query", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc39() {}

    /**
     * @OA\Get(
     *     path="/integration/purchase_order_tracking/documents",
     *     tags={"Integración - Seguimiento de órdenes de compra"},
     *     summary="Documents",
     *     description="Permiso requerido: integration.purchase_order_tracking.documents.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc41() {}

    /**
     * @OA\Get(
     *     path="/integration/purchase_order_tracking/missing_download",
     *     tags={"Integración - Seguimiento de órdenes de compra"},
     *     summary="Missing download",
     *     description="Permiso requerido: integration.purchase_order_tracking.missing_download.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc42() {}

    /**
     * @OA\Post(
     *     path="/integration/sync_unico/run",
     *     tags={"Integración - Sincronización con Único"},
     *     summary="Run",
     *     description="Permiso requerido: integration.sync_unico.run. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"mode"},
     *         @OA\Property(property="mode", type="string", enum={"today","yesterday","range"}),
     *         @OA\Property(property="created_start", type="string"),
     *         @OA\Property(property="created_end", type="string")
     *     ))),
     *     @OA\Response(response=202, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc7() {}

    /**
     * @OA\Get(
     *     path="/integration/sync_unico/runs",
     *     tags={"Integración - Sincronización con Único"},
     *     summary="Runs",
     *     description="Permiso requerido: integration.sync_unico.runs.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc6() {}

    /**
     * @OA\Get(
     *     path="/integration/masive_transfers/format",
     *     tags={"Integración - Traslados masivos"},
     *     summary="Format",
     *     description="Permiso requerido: integration.masive_transfers.format.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc8() {}

    /**
     * @OA\Post(
     *     path="/integration/masive_transfers/upload",
     *     tags={"Integración - Traslados masivos"},
     *     summary="Upload",
     *     description="Permiso requerido: integration.masive_transfers.upload. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file","email"},
     *         @OA\Property(property="file", type="string", format="binary"),
     *         @OA\Property(property="email", type="string", format="email")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc9() {}

    /**
     * @OA\Post(
     *     path="/integration/product_traceability/download",
     *     tags={"Integración - Trazabilidad de productos"},
     *     summary="Download",
     *     description="Permiso requerido: integration.product_traceability.download.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"warehouse_id","start_date","file"},
     *         @OA\Property(property="warehouse_id", type="string"),
     *         @OA\Property(property="start_date", type="string", format="date"),
     *         @OA\Property(property="file", type="string", format="binary")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc16() {}

    /**
     * @OA\Get(
     *     path="/integration/product_traceability/format",
     *     tags={"Integración - Trazabilidad de productos"},
     *     summary="Format",
     *     description="Permiso requerido: integration.product_traceability.format.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc14() {}

    /**
     * @OA\Post(
     *     path="/integration/product_traceability/search",
     *     tags={"Integración - Trazabilidad de productos"},
     *     summary="Search",
     *     description="Permiso requerido: integration.product_traceability.search.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"warehouse_id","start_date","file"},
     *         @OA\Property(property="warehouse_id", type="string"),
     *         @OA\Property(property="start_date", type="string", format="date"),
     *         @OA\Property(property="file", type="string", format="binary")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc15() {}

    /**
     * @OA\Get(
     *     path="/integration/product_traceability/warehouses",
     *     tags={"Integración - Trazabilidad de productos"},
     *     summary="Warehouses",
     *     description="Permiso requerido: integration.product_traceability.warehouses.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc13() {}

    /**
     * @OA\Post(
     *     path="/integration/masive_purchase_orders/format",
     *     tags={"Integración - Órdenes de compra masivas"},
     *     summary="Format",
     *     description="Permiso requerido: integration.masive_purchase_orders.format.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"cookie","purchase_order_type_id"},
     *         @OA\Property(property="cookie", type="string"),
     *         @OA\Property(property="purchase_order_type_id", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc11() {}

    /**
     * @OA\Get(
     *     path="/integration/masive_purchase_orders/types",
     *     tags={"Integración - Órdenes de compra masivas"},
     *     summary="Types",
     *     description="Permiso requerido: integration.masive_purchase_orders.types.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc10() {}

    /**
     * @OA\Post(
     *     path="/integration/masive_purchase_orders/upload",
     *     tags={"Integración - Órdenes de compra masivas"},
     *     summary="Upload",
     *     description="Permiso requerido: integration.masive_purchase_orders.upload. Límite de escrituras: 10 por minuto.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file","email","referencia"},
     *         @OA\Property(property="file", type="string", format="binary"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="referencia", type="string")
     *     ))),
     *     @OA\Response(response=200, description="Operación completada con éxito", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"), @OA\Property(property="error", type="boolean", example=false))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso para esta acción"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=429, description="Demasiadas peticiones")
     * )
     */
    public function doc12() {}
}
