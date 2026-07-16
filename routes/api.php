<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
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
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/audits')->controller(AuditController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:audits.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:audits.find']);
});

Route::prefix('/auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware(['auth:sanctum']);
    Route::get('/user', 'user')->middleware(['auth:sanctum']);
});

Route::prefix('/authorization')->group(function () {
    Route::prefix('/roles')->controller(RoleController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.roles.all|navegation.modules.submodules.store|navegation.modules.submodules.update|users.authorization.assign|users.authorization.remove|authorization.permissions.store|authorization.permissions.update|organizational_structure.areas.positions.authorization.assign|organizational_structure.areas.positions.authorization.remove']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.roles.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.roles.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.roles.update']);
    });

    Route::prefix('/permissions')->controller(PermissionController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.permissions.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.permissions.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.permissions.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.permissions.update']);
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
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:identification.person_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:identification.person_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:identification.person_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:identification.person_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:identification.person_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:identification.person_types.restore']);
    });

    Route::prefix('/document_types')->controller(DocumentTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:identification.document_types.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:identification.document_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:identification.document_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:identification.document_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:identification.document_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:identification.document_types.restore']);
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

Route::prefix('/classification')->group(function () {
    Route::prefix('/categories')->controller(CategoryController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.categories.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.categories.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.categories.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.categories.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.categories.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.categories.restore']);

        Route::prefix('/subcategories')->group(function () {
            Route::post('/assign/{id}', 'assign')->middleware(['auth:sanctum', 'can:classification.categories.subcategories.assign']);
            Route::post('/remove/{id}', 'remove')->middleware(['auth:sanctum', 'can:classification.categories.subcategories.remove']);
        });
    });

    Route::prefix('/subcategories')->controller(SubcategoryController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.subcategories.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.subcategories.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.subcategories.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.subcategories.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.subcategories.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.subcategories.restore']);
    });
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
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:stores.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:stores.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:stores.update']);
    Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:stores.delete']);
    Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:stores.restore']);
});

Route::prefix('/products')->controller(ProductController::class)->group(function () {
    Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:products.all']);
    Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:products.find']);
    Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:products.store']);
    Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:products.update']);
});
