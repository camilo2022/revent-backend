<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\Api\Integration\CredentialsController;
use App\Http\Controllers\Api\Integration\AccountPayableController;
use App\Http\Controllers\Api\Integration\SearchDocumentController;
use App\Http\Controllers\Api\Integration\TransferApprovalController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CompensationFundController;
use App\Http\Controllers\ContinentController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\HealthEntityController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PensionFundController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonTypeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\TechnicalSheetController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Models\Role;
use App\Models\Permission;
use App\Models\FileType;
use App\Models\PersonType;
use App\Models\DocumentType;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\Employee;
use App\Models\Gender;
use App\Models\HealthEntity;
use App\Models\Module;
use App\Models\Submodule;
use App\Models\Area;
use App\Models\Position;
use App\Models\PensionFund;
use App\Models\Person;
use App\Models\RiskManager;
use App\Models\User;
use App\Models\Trademark;
use App\Models\Color;
use App\Models\Size;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\Product;
use App\Models\TechnicalSheet;
use App\Models\ProductionOrder;

Route::prefix('/reports')->controller(ReportController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:reports.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:reports.find']);
    Route::post('/generate/{id}', 'generate')->middleware(['auth:sanctum', 'can:reports.generate']);
    Route::post('/export/{id}', 'export')->middleware(['auth:sanctum', 'can:reports.export']);
});

Route::prefix('/audits')->controller(AuditController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:audits.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:audits.find']);
});

Route::prefix('/auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->middleware('throttle:10,1');
    Route::post('/logout', 'logout')->middleware(['auth:sanctum']);
    Route::get('/user', 'user')->middleware(['auth:sanctum']);
});

Route::prefix('/authorization')->group(function () {
    Route::prefix('/roles')->controller(RoleController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.roles.all|navegation.modules.submodules.store|navegation.modules.submodules.update|users.authorization.assign|users.authorization.remove|authorization.roles.permissions.store|authorization.roles.permissions.update|organizational_structure.areas.positions.authorization.assign|organizational_structure.areas.positions.authorization.remove']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.roles.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.roles.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.roles.update']);

        Route::prefix('/permissions')->controller(PermissionController::class)->group(function () {
            Route::get('/all/{role_id}', 'all')->middleware(['auth:sanctum', 'can:authorization.roles.permissions.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.roles.permissions.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.roles.permissions.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.roles.permissions.update']);
        });
    });

});

Route::prefix('/location')->group(function () {
    Route::prefix('/continents')->controller(ContinentController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
    });

    Route::prefix('/regions')->controller(RegionController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
    });

    Route::prefix('/countries')->controller(CountryController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
    });

    Route::prefix('/departments')->controller(DepartmentController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
    });

    Route::prefix('/cities')->controller(CityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum']);
    });
});

Route::prefix('/file_types')->controller(FileTypeController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:file_types.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:file_types.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:file_types.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:file_types.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:file_types.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:file_types.restore']);
});

Route::prefix('/identification')->group(function () {
    Route::prefix('/person_types')->controller(PersonTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:identification.person_types.all|people.store|people.update|suppliers.store|suppliers.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:identification.person_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:identification.person_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:identification.person_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:identification.person_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:identification.person_types.restore']);

        Route::prefix('/document_types')->controller(DocumentTypeController::class)->group(function () {
            Route::get('/all/{person_type_id}', 'all')->middleware(['auth:sanctum', 'can:identification.person_types.document_types.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:identification.person_types.document_types.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:identification.person_types.document_types.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:identification.person_types.document_types.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:identification.person_types.document_types.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:identification.person_types.document_types.restore']);
        });
    });

});

Route::prefix('/blood_types')->controller(BloodTypeController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:blood_types.all|people.store|people.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:blood_types.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:blood_types.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:blood_types.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:blood_types.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:blood_types.restore']);
});

Route::prefix('/compensation_funds')->controller(CompensationFundController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:compensation_funds.all|employees.store|employees.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:compensation_funds.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:compensation_funds.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:compensation_funds.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:compensation_funds.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:compensation_funds.restore']);
});

Route::prefix('/employees')->controller(EmployeeController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:employees.all|users.store']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:employees.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:employees.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:employees.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:employees.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:employees.restore']);
});

Route::prefix('/genders')->controller(GenderController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:genders.all|people.store|people.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:genders.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:genders.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:genders.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:genders.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:genders.restore']);
});

Route::prefix('/health_entities')->controller(HealthEntityController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:health_entities.all|employees.store|employees.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:health_entities.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:health_entities.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:health_entities.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:health_entities.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:health_entities.restore']);
});

Route::prefix('/navegation')->group(function () {
    Route::prefix('/modules')->controller(ModuleController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.find|navegation.modules.update']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.restore']);
    });

    Route::prefix('/submodules')->controller(SubmoduleController::class)->group(function () {
        Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.find|navegation.modules.submodules.update']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.restore']);
    });

});

Route::prefix('/organizational_structure')->group(function () {
    Route::prefix('/areas')->controller(AreaController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:organizational_structure.areas.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure.areas.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure.areas.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure.areas.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure.areas.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure.areas.restore']);
    });

    Route::prefix('/positions')->controller(PositionController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.restore']);

        Route::prefix('/authorization')->group(function () {
            Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.authorization.assign']);
            Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.authorization.remove']);
        });
    });
});

Route::prefix('/pension_funds')->controller(PensionFundController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:pension_funds.all|employees.store|employees.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:pension_funds.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:pension_funds.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:pension_funds.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:pension_funds.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:pension_funds.restore']);
});

Route::prefix('/people')->controller(PersonController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:people.all|employees.store|employees.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:people.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:people.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:people.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:people.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:people.restore']);
});

Route::prefix('/risk_managers')->controller(RiskManagerController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:risk_managers.all|employees.store|employees.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:risk_managers.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:risk_managers.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:risk_managers.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:risk_managers.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:risk_managers.restore']);
});

Route::prefix('/users')->controller(UserController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:users.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:users.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:users.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:users.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:users.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:users.restore']);

    Route::prefix('/authorization')->group(function () {
        Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:users.authorization.assign']);
        Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:users.authorization.remove']);
    });

    // Tiendas (bodegas de Siigo) a las que el usuario tiene acceso
    Route::prefix('/stores')->group(function () {
        Route::get('/all/{id}', 'storesAll')->middleware(['auth:sanctum', 'can:users.stores.all']);
        Route::post('/assign/{id}', 'storesAssign')->middleware(['auth:sanctum', 'can:users.stores.assign']);
        Route::post('/remove/{id}', 'storesRemove')->middleware(['auth:sanctum', 'can:users.stores.remove']);
    });
});

Route::prefix('/trademarks')->controller(TrademarkController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:trademarks.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:trademarks.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:trademarks.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:trademarks.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:trademarks.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:trademarks.restore']);
});

Route::prefix('/colors')->controller(ColorController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:colors.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:colors.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:colors.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:colors.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:colors.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:colors.restore']);
});

Route::prefix('/sizes')->controller(SizeController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:sizes.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:sizes.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:sizes.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:sizes.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:sizes.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:sizes.restore']);
});

Route::prefix('/categorization')->group(function () {
    Route::prefix('/categories')->controller(CategoryController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:categorization.categories.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization.categories.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization.categories.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization.categories.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization.categories.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization.categories.restore']);

        Route::prefix('/subcategories')->controller(SubcategoryController::class)->group(function () {
            Route::get('/all/{category_id}', 'all')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:categorization.categories.subcategories.restore']);
        });
    });
});

Route::prefix('/collections')->controller(CollectionController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:collections.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:collections.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:collections.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:collections.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:collections.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:collections.restore']);
});

Route::prefix('/suppliers')->controller(SupplierController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:suppliers.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:suppliers.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:suppliers.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:suppliers.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:suppliers.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:suppliers.restore']);
});

Route::prefix('/stores')->controller(StoreController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:stores.all']);
    Route::get('/siigo_warehouses', 'siigoWarehouses')->middleware(['auth:sanctum', 'can:stores.siigo_warehouses']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:stores.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:stores.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:stores.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:stores.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:stores.restore']);
});

Route::prefix('/products')->controller(ProductController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:products.all|technical_sheets.store']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:products.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:products.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:products.update']);
});

Route::prefix('/technical_sheets')->controller(TechnicalSheetController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:technical_sheets.all|production_orders.store|production_orders.update']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:technical_sheets.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:technical_sheets.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:technical_sheets.update']);
    Route::patch('/reviewed/{id}', 'reviewed')->middleware(['auth:sanctum', 'can:technical_sheets.reviewed']);
    Route::patch('/approved/{id}', 'approved')->middleware(['auth:sanctum', 'can:technical_sheets.approved']);
    Route::patch('/canceled/{id}', 'canceled')->middleware(['auth:sanctum', 'can:technical_sheets.canceled']);
});

Route::prefix('/production_orders')->controller(ProductionOrderController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:production_orders.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:production_orders.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:production_orders.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:production_orders.update']);
    Route::patch('/approved/{id}', 'approved')->middleware(['auth:sanctum', 'can:production_orders.approved']);
    Route::patch('/canceled/{id}', 'canceled')->middleware(['auth:sanctum', 'can:production_orders.canceled']);
});

// Integraciones (Siigo): todo el grupo exige sesión (Sanctum) y cada ruta su permiso `integration.{submodulo}.{accion}`.
// Cada submódulo tiene su propio bloque entre los marcadores `=== nombre ===` y `=== fin nombre ===`.
Route::prefix('/integration')->middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/credentials/check', [CredentialsController::class, 'check'])->middleware('can:integration.credentials.check');

    // === exports_inventory ===
    Route::post('/exports/inventory', [\App\Http\Controllers\Api\Integration\ExportController::class, 'inventory'])->middleware(['can:integration.exports_inventory.send', 'throttle:10,1,integration-write']);
    // === fin exports_inventory ===

    // === exports_invoice ===
    Route::post('/exports/invoice', [\App\Http\Controllers\Api\Integration\ExportController::class, 'invoice'])->middleware(['can:integration.exports_invoice.send', 'throttle:10,1,integration-write']);
    // === fin exports_invoice ===

    // === exports_invoice_360 ===
    Route::post('/exports/invoice_360', [\App\Http\Controllers\Api\Integration\ExportController::class, 'invoice360'])->middleware(['can:integration.exports_invoice_360.send', 'throttle:10,1,integration-write']);
    // === fin exports_invoice_360 ===

    // === exports_purchase ===
    Route::post('/exports/purchase', [\App\Http\Controllers\Api\Integration\ExportController::class, 'purchase'])->middleware(['can:integration.exports_purchase.send', 'throttle:10,1,integration-write']);
    // === fin exports_purchase ===

    // === sync_unico ===
    Route::get('/sync_unico/runs', [\App\Http\Controllers\Api\Integration\SyncUnicoController::class, 'runs'])->middleware('can:integration.sync_unico.runs');
    Route::post('/sync_unico/run', [\App\Http\Controllers\Api\Integration\SyncUnicoController::class, 'run'])->middleware(['can:integration.sync_unico.run', 'throttle:10,1,integration-write']);
    // === fin sync_unico ===

    // === masive_transfers ===
    Route::get('/masive_transfers/format', [\App\Http\Controllers\Api\Integration\MasiveTransferController::class, 'format'])->middleware('can:integration.masive_transfers.format');
    Route::post('/masive_transfers/upload', [\App\Http\Controllers\Api\Integration\MasiveTransferController::class, 'upload'])->middleware(['can:integration.masive_transfers.upload', 'throttle:10,1,integration-write']);
    // === fin masive_transfers ===

    // === masive_purchase_orders ===
    Route::get('/masive_purchase_orders/types', [\App\Http\Controllers\Api\Integration\MasivePurchaseOrderController::class, 'types'])->middleware('can:integration.masive_purchase_orders.types');
    Route::post('/masive_purchase_orders/format', [\App\Http\Controllers\Api\Integration\MasivePurchaseOrderController::class, 'format'])->middleware('can:integration.masive_purchase_orders.format');
    Route::post('/masive_purchase_orders/upload', [\App\Http\Controllers\Api\Integration\MasivePurchaseOrderController::class, 'upload'])->middleware(['can:integration.masive_purchase_orders.upload', 'throttle:10,1,integration-write']);
    // === fin masive_purchase_orders ===

    // === product_traceability ===
    Route::get('/product_traceability/warehouses', [\App\Http\Controllers\Api\Integration\ProductTraceabilityController::class, 'warehouses'])->middleware('can:integration.product_traceability.warehouses');
    Route::get('/product_traceability/format', [\App\Http\Controllers\Api\Integration\ProductTraceabilityController::class, 'format'])->middleware('can:integration.product_traceability.format');
    Route::post('/product_traceability/search', [\App\Http\Controllers\Api\Integration\ProductTraceabilityController::class, 'search'])->middleware('can:integration.product_traceability.search');
    Route::post('/product_traceability/download', [\App\Http\Controllers\Api\Integration\ProductTraceabilityController::class, 'download'])->middleware('can:integration.product_traceability.download');
    // === fin product_traceability ===

    // === product_photos ===
    Route::get('/product_photos/search', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'search'])->middleware('can:integration.product_photos.search');
    Route::post('/product_photos/upload', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'upload'])->middleware(['can:integration.product_photos.upload', 'throttle:10,1,integration-write']);
    Route::delete('/product_photos/delete', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'destroy'])->middleware('can:integration.product_photos.delete');
    Route::post('/product_photos/bulk_upload', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'bulkUpload'])->middleware(['can:integration.product_photos.bulk_upload', 'throttle:10,1,integration-write']);
    Route::get('/product_photos/explorer', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'explorer'])->middleware('can:integration.product_photos.explorer');
    Route::post('/product_photos/explorer/folder', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'explorerFolder'])->middleware('can:integration.product_photos.explorer_folder');
    Route::post('/product_photos/explorer/upload', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'explorerUpload'])->middleware(['can:integration.product_photos.explorer_upload', 'throttle:10,1,integration-write']);
    Route::delete('/product_photos/explorer/delete', [\App\Http\Controllers\Api\Integration\ProductPhotoController::class, 'explorerDelete'])->middleware('can:integration.product_photos.explorer_delete');
    // === fin product_photos ===

    // === accounts_payable ===
    Route::prefix('/accounts_payable')->controller(AccountPayableController::class)->group(function () {
        Route::get('/settings', 'settings')->middleware('can:integration.accounts_payable.settings');
        Route::get('/providers', 'providers')->middleware('can:integration.accounts_payable.providers');
        Route::post('/providers_sync', 'providersSync')->middleware('can:integration.accounts_payable.providers_sync');
        Route::get('/documents/{accountId}', 'documents')->whereNumber('accountId')->middleware('can:integration.accounts_payable.documents');
        Route::get('/payments/{accountId}', 'payments')->whereNumber('accountId')->middleware('can:integration.accounts_payable.payments');
        Route::get('/payments_weekly', 'paymentsWeekly')->middleware('can:integration.accounts_payable.payments_weekly');
        Route::get('/payment_html/{acEntryId}', 'paymentHtml')->whereNumber('acEntryId')->middleware('can:integration.accounts_payable.payment_html');
        Route::post('/payment', 'paymentStore')->middleware(['throttle:10,1,integration-write', 'can:integration.accounts_payable.payment_store']);
        Route::post('/advance', 'advanceStore')->middleware(['throttle:10,1,integration-write', 'can:integration.accounts_payable.advance_store']);
        Route::post('/conciliation', 'conciliationStore')->middleware(['throttle:10,1,integration-write', 'can:integration.accounts_payable.conciliation_store']);
    });
    // === fin accounts_payable ===

    // === inventory_filter ===
    Route::get('/inventory_filter/catalogs', [\App\Http\Controllers\Api\Integration\InventoryFilterController::class, 'catalogs'])->middleware('can:integration.inventory_filter.catalogs');
    Route::get('/inventory_filter/search', [\App\Http\Controllers\Api\Integration\InventoryFilterController::class, 'search'])->middleware('can:integration.inventory_filter.search');
    Route::get('/inventory_filter/all_warehouses', [\App\Http\Controllers\Api\Integration\InventoryFilterController::class, 'allWarehouses'])->middleware('can:integration.inventory_filter.all_warehouses');
    Route::get('/inventory_filter/images', [\App\Http\Controllers\Api\Integration\InventoryFilterController::class, 'images'])->middleware('can:integration.inventory_filter.images');
    // === fin inventory_filter ===

    // === purchase_order_reception ===
    Route::post('/purchase_order_reception/search', [\App\Http\Controllers\Api\Integration\PurchaseOrderReceptionController::class, 'search'])->middleware('can:integration.purchase_order_reception.search');
    Route::post('/purchase_order_reception/confirm', [\App\Http\Controllers\Api\Integration\PurchaseOrderReceptionController::class, 'confirm'])->middleware(['can:integration.purchase_order_reception.confirm', 'throttle:10,1,integration-write']);
    // === fin purchase_order_reception ===

    // === purchase_order_tracking ===
    Route::get('/purchase_order_tracking/documents', [\App\Http\Controllers\Api\Integration\PurchaseOrderTrackingController::class, 'documents'])->middleware('can:integration.purchase_order_tracking.documents');
    Route::get('/purchase_order_tracking/missing_download', [\App\Http\Controllers\Api\Integration\PurchaseOrderTrackingController::class, 'missingDownload'])->middleware('can:integration.purchase_order_tracking.missing_download');
    // === fin purchase_order_tracking ===

    // === transfer_approval ===
    Route::prefix('/transfer_approval')->controller(TransferApprovalController::class)->group(function () {
        Route::post('/search', 'search')->middleware('can:integration.transfer_approval.search');
        Route::post('/confirm', 'confirm')->middleware(['throttle:10,1,integration-write', 'can:integration.transfer_approval.confirm']);
    });
    // === fin transfer_approval ===

    // === customer_sales ===
    Route::get('/customer_sales/cost_centers', [\App\Http\Controllers\Api\Integration\CustomerSalesController::class, 'costCenters'])->middleware('can:integration.customer_sales.cost_centers');
    Route::post('/customer_sales/search', [\App\Http\Controllers\Api\Integration\CustomerSalesController::class, 'search'])->middleware(['can:integration.customer_sales.search', 'throttle:10,1,integration-write']);
    // === fin customer_sales ===

    // === search_document ===
    Route::prefix('/search_document')->controller(SearchDocumentController::class)->group(function () {
        Route::get('/types', 'types')->middleware('can:integration.search_document.types');
        Route::post('/transfer_note', 'transferNote')->middleware('can:integration.search_document.transfer_note');
        Route::post('/purchase_order', 'purchaseOrder')->middleware('can:integration.search_document.purchase_order');
        Route::post('/transfer_note_pdf', 'transferNotePdf')->middleware('can:integration.search_document.transfer_note_pdf');
        Route::post('/purchase_order_pdf', 'purchaseOrderPdf')->middleware('can:integration.search_document.purchase_order_pdf');
    });
    // === fin search_document ===
});

// Auditoría por registro: GET /{módulo}/audits/{id} devuelve quién hizo cada cambio de ese registro, cuándo y desde dónde
foreach ([
    'roles' => Role::class, 'permissions' => Permission::class, 'file_types' => FileType::class, 'person_types' => PersonType::class,
    'document_types' => DocumentType::class, 'blood_types' => BloodType::class, 'compensation_funds' => CompensationFund::class,
    'employees' => Employee::class, 'genders' => Gender::class, 'health_entities' => HealthEntity::class, 'modules' => Module::class,
    'submodules' => Submodule::class, 'areas' => Area::class, 'positions' => Position::class, 'pension_funds' => PensionFund::class,
    'people' => Person::class, 'risk_managers' => RiskManager::class, 'users' => User::class, 'trademarks' => Trademark::class,
    'colors' => Color::class, 'sizes' => Size::class, 'categories' => Category::class, 'collections' => Collection::class,
    'subcategories' => Subcategory::class, 'suppliers' => Supplier::class, 'stores' => Store::class, 'products' => Product::class,
    'technical_sheets' => TechnicalSheet::class, 'production_orders' => ProductionOrder::class,
] as $prefix => $model) {
    Route::get("/{$prefix}/audits/{id}", [AuditController::class, 'record'])
        ->defaults('model', $model)
        ->whereNumber('id')
        ->middleware(['auth:sanctum', 'can:audits.find']);
}
