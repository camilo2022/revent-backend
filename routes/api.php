<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\RiskManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodTypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CompensationFundController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\HealthEntityController;
use App\Http\Controllers\Integration\InventorySiigoController;
use App\Http\Controllers\Integration\SiigoController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PensionFundController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubmoduleController;
use App\Http\Controllers\TrademarkController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/integrations')->group(function () {
    Route::prefix('/siigo')->group(function () {
        Route::controller(SiigoController::class)->group(function () {
            Route::get('/sync', 'sync');
        });
        Route::controller(InventorySiigoController::class)->group(function () {
            Route::get('/inventory/export', 'export_inventory');
        });
    });
});

Route::prefix('/audits')->group(function () {
    Route::controller(AuditController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:audits.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:audits.find']);
    });
});

Route::prefix('/auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware(['auth:sanctum']);
        Route::get('/user', 'user')->middleware(['auth:sanctum']);
    });
});

Route::prefix('/authorization')->group(function () {
    Route::prefix('/roles')->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.roles.all|navegation.modules.submodules.store|navegation.modules.submodules.update|users.authorization.assign|users.authorization.remove|authorization.permissions.store|authorization.permissions.update|organizational_structure.areas.positions.authorization.assign|organizational_structure.areas.positions.authorization.remove']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.roles.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.roles.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.roles.update']);
        });
    });

    Route::prefix('/permissions')->group(function () {
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:authorization.permissions.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:authorization.permissions.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:authorization.permissions.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:authorization.permissions.update']);
        });
    });
});

Route::prefix('/blood_types')->group(function () {
    Route::controller(BloodTypeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:blood_types.all|people.store|people.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:blood_types.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:blood_types.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:blood_types.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:blood_types.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:blood_types.restore']);
    });
});

Route::prefix('/compensation_funds')->group(function () {
    Route::controller(CompensationFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:compensation_funds.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:compensation_funds.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:compensation_funds.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:compensation_funds.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:compensation_funds.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:compensation_funds.restore']);
    });
});

Route::prefix('/employees')->group(function () {
    Route::controller(EmployeeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:employees.all|users.store']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:employees.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:employees.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:employees.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:employees.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:employees.restore']);
    });
});

Route::prefix('/genders')->group(function () {
    Route::controller(GenderController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:genders.all|people.store|people.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:genders.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:genders.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:genders.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:genders.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:genders.restore']);
    });
});

Route::prefix('/health_entities')->group(function () {
    Route::controller(HealthEntityController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:health_entities.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:health_entities.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:health_entities.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:health_entities.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:health_entities.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:health_entities.restore']);
    });
});

Route::prefix('/navegation')->group(function () {
    Route::prefix('/modules')->group(function () {
        Route::controller(ModuleController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.find|navegation.modules.update']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.restore']);
        });

        Route::prefix('/submodules')->group(function () {
            Route::controller(SubmoduleController::class)->group(function () {
                Route::get('/all/{module_id}', 'all')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.all']);
                Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.find|navegation.modules.submodules.update']);
                Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.store']);
                Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.update']);
                Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.delete']);
                Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:navegation.modules.submodules.restore']);
            });
        });
    });
});

Route::prefix('/organizational_structure')->group(function () {
    Route::prefix('/areas')->group(function () {
        Route::controller(AreaController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:organizational_structure.areas.all|employees.store|employees.update']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:organizational_structure.areas.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:organizational_structure.areas.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:organizational_structure.areas.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:organizational_structure.areas.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:organizational_structure.areas.restore']);
        });

        Route::prefix('/positions')->group(function () {
            Route::controller(PositionController::class)->group(function () {
                Route::get('/all/{area_id}', 'all')->middleware(['auth:sanctum', 'can:organizational_structure.areas.positions.all|employees.store|employees.update']);
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
    });
});

Route::prefix('/pension_funds')->group(function () {
    Route::controller(PensionFundController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:pension_funds.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:pension_funds.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:pension_funds.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:pension_funds.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:pension_funds.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:pension_funds.restore']);
    });
});

Route::prefix('/people')->group(function () {
    Route::controller(PersonController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:people.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:people.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:people.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:people.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:people.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:people.restore']);
        Route::get('/pdf', 'pdf')->middleware(['auth:sanctum', 'can:people.all']);
        Route::get('/excel', 'excel')->middleware(['auth:sanctum', 'can:people.all']);
        Route::post('/import', 'import')->middleware(['auth:sanctum', 'can:people.all']);
    });
});

Route::prefix('/risk_managers')->group(function () {
    Route::controller(RiskManagerController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:risk_managers.all|employees.store|employees.update']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:risk_managers.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:risk_managers.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:risk_managers.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:risk_managers.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:risk_managers.restore']);
    });
});

Route::prefix('/users')->group(function () {
    Route::controller(UserController::class)->group(function () {
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
});

Route::prefix('/trademarks')->group(function () {
    Route::controller(TrademarkController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:trademarks.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:trademarks.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:trademarks.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:trademarks.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:trademarks.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:trademarks.restore']);
    });
});

Route::prefix('/colors')->group(function () {
    Route::controller(ColorController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:colors.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:colors.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:colors.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:colors.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:colors.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:colors.restore']);
    });
});

Route::prefix('/sizes')->group(function () {
    Route::controller(SizeController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:sizes.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:sizes.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:sizes.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:sizes.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:sizes.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:sizes.restore']);
    });
});

Route::prefix('/classification')->group(function () {
    Route::prefix('/categories')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.categories.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.categories.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.categories.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.categories.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.categories.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.categories.restore']);
        });
    });
    Route::prefix('/subcategories')->group(function () {
        Route::controller(SubcategoryController::class)->group(function () {
            Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:classification.subcategories.all']);
            Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:classification.subcategories.find']);
            Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:classification.subcategories.store']);
            Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:classification.subcategories.update']);
            Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:classification.subcategories.delete']);
            Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:classification.subcategories.restore']);
        });
    });
});

/*Route::prefix('/products')->group(function () {
    Route::controller(ProductController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:products.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:products.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:products.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:products.update']);
    });
});*/

/*Route::prefix('/stores')->group(function () {
    Route::controller(StoreController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:stores.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:stores.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:stores.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:stores.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:stores.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:stores.restore']);
    });
});

Route::prefix('/providers')->group(function () {
    Route::controller(ProviderController::class)->group(function () {
        Route::get('/all', 'all')->middleware(['auth:sanctum', 'can:providers.all']);
        Route::get('/find/{id}', 'find')->middleware(['auth:sanctum', 'can:providers.find']);
        Route::post('/store', 'store')->middleware(['auth:sanctum', 'can:providers.store']);
        Route::put('/update/{id}', 'update')->middleware(['auth:sanctum', 'can:providers.update']);
        Route::delete('/delete/{id}', 'delete')->middleware(['auth:sanctum', 'can:providers.delete']);
        Route::patch('/restore/{id}', 'restore')->middleware(['auth:sanctum', 'can:providers.restore']);
    });
});*/
