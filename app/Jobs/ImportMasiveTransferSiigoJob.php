<?php

namespace App\Jobs;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Mail\MasiveTransferSiigoMail;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoLinks;
use App\Services\Siigo\SiigoRetryPolicy;
use App\Services\SiigoTransferNTPdfService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportMasiveTransferSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    // Rutas (relativas al host de servicios definido en config/siigo.php).
    private const PATH_TRANSFER_SAVE = '/ACEntryApi/api/v1/WarehouseTransfer/Save/';
    private const PATH_TRANSFER_VIEW = '/ACEntryApi/api/v1/WarehouseTransfer/GetDataView';

    /** Disco privado y carpeta temporal de los archivos que solo viajan adjuntos en el correo. */
    private const TMP_DISK = 'local';
    private const TMP_PATH = 'tmp/siigo';

    private const PAGINAS_BUSQUEDA_PRODUCTO = 3;

    private const VISTA_SALIDA = 'email.masive_transfer.exit';
    private const VISTA_ENTRADA = 'email.masive_transfer.entrance';

    private const BODEGAS = [
        'DIRECTO' => [
            -1 => ['name' => 'SIN ASIGNAR', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            2  => ['name' => 'P R I N C I P A L', 'transito' => 67, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            3  => ['name' => 'ALEGRA', 'transito' => 70, 'users' => [597, 1089, 941, 735, 742, 816, 823, 824, 873, 875, 878, 879, 880, 883, 884, 957, 972, 975, 979, 997, 1002, 1003, 1049, 1062, 1065, 1068, 1114, 1128, 1163, 1164, 1182, 1223, 1237, 1238, 1242, 1251, 1279, 1280, 1314, 1348, 1350, 1362, 11571, 11579, 11581, 11591], 'emails' => ['parquealegra']],
            4  => ['name' => 'PUNTO DE VENTA', 'transito' => null, 'users' => [597, 1089, 941, 877], 'emails' => ['operaciones']],
            9  => ['name' => 'MAYALES', 'transito' => 71, 'users' => [597, 1089, 941, 948, 951, 952, 978, 1005, 1016, 1059, 1115, 1116, 1126, 1127, 1171, 1175, 1176, 1177, 1179, 1216, 1258, 1291, 1309, 1310, 1311, 1361, 1370, 1372, 1391, 1452, 1471, 1472, 1520, 11578], 'emails' => ['mayales']],
            13 => ['name' => 'PROD FABRICA', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            15 => ['name' => 'REVENT', 'transito' => 68, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            16 => ['name' => 'MATERIA PRIMA', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            17 => ['name' => 'OCEAN MALL', 'transito' => 72, 'users' => [597, 1089, 941, 1087, 1183, 1197, 1200, 1201, 1224, 1263, 1264, 1390, 1442, 1530, 11572, 11573], 'emails' => ['reventoceanmall@gmail.com', 'oceanmall']],
            19 => ['name' => 'NUESTRO MONTERIA', 'transito' => 73, 'users' => [597, 1089, 941, 982, 983, 984, 985, 987, 989, 994, 1015, 1017, 1026, 1029, 1056, 1057, 1074, 1095, 1096, 1098, 1099, 1104, 1117, 1121, 1135, 1159, 1217, 1219, 1347, 1455, 1470, 11559, 11560, 11602], 'emails' => ['nuestro']],
            22 => ['name' => 'ALAMEDAS', 'transito' => 74, 'users' => [597, 1089, 941, 986, 992, 1034, 1061, 1064, 1092, 1097, 1157, 1162, 1165, 1232, 1288, 1373, 1382, 1408, 1409, 1469, 1533, 11561], 'emails' => ['alamedas']],
            24 => ['name' => 'PORTAL', 'transito' => 75, 'users' => [597, 1089, 941, 732, 792, 865, 871, 887, 888, 891, 893, 894, 896, 897, 898, 899, 938, 959, 971, 998, 1001, 1027, 1063, 1072, 1123, 1131, 1132, 1180, 1188, 1226, 1228, 1278, 1295, 1296, 1315, 1318, 1319, 1331, 1351, 1364, 1365, 1378, 1461, 1462, 1463, 1481, 1497, 1508, 1512, 1523, 11574, 11575, 11576, 11598], 'emails' => ['portaldelprado']],
            27 => ['name' => 'CARIBE PLAZA 1', 'transito' => null, 'users' => [597, 1089, 941, 1366], 'emails' => ['caribeplaza']],
            28 => ['name' => 'PLAZA DEL SOL', 'transito' => 76, 'users' => [597, 1089, 941, 780, 930, 1181, 1221, 1261, 1268, 1298, 1328, 1385, 1400, 1430, 1438, 1454, 1467, 1480, 11592], 'emails' => ['plazadelsol']],
            31 => ['name' => 'WEB', 'transito' => 10094, 'users' => [597, 1089, 941, 940, 980, 1146], 'emails' => ['paginawebrevent@gmail.com']],
            32 => ['name' => 'CASTELLANA', 'transito' => 77, 'users' => [597, 1089, 941, 926, 1021, 1035, 1040, 1042, 1044, 1046, 1051, 1052, 1053, 1054, 1055, 1058, 1066, 1070, 1073, 1075, 1082, 1085, 1091, 1102, 1106, 1133, 1134, 1142, 1152, 1153, 1154, 1167, 1173, 1174, 1184, 1185, 1187, 1195, 1196, 1202, 1225, 1227, 1229, 1230, 1265, 1266, 1267, 1275, 1276, 1277, 1299, 1307, 1308, 1317, 1352, 1419, 1444, 1483, 1488, 1492, 1494, 1515, 1516, 1517, 1534, 11569, 11570], 'emails' => ['castellana']],
            33 => ['name' => 'UNICO BQ', 'transito' => 78, 'users' => [597, 1089, 941, 1124, 740, 813, 882, 1018, 1122, 1212, 1220, 1222, 1252, 1256, 1259, 1260, 1273, 1274, 1281, 1282, 1283, 1284, 1285, 1286, 1289, 1305, 1312, 1313, 1320, 1321, 1357, 1360, 1375, 1379, 1403, 1424, 1449, 1451, 1460, 1511, 1514, 1524, 11562], 'emails' => ['unico']],
            34 => ['name' => 'CREAR ENSAMBLES', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            35 => ['name' => 'Credito de Calzado', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            36 => ['name' => 'ECOMMERCE', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            37 => ['name' => 'CARNAVAL', 'transito' => 79, 'users' => [597, 1089, 941, 810, 886, 1145, 1147, 1148, 1150, 1151, 1160, 1203, 1233, 1257, 1297, 1324, 1325, 1429, 1466, 1532, 11563, 11565], 'emails' => ['carnaval']],
            44 => ['name' => 'INSTAGRAM', 'transito' => null, 'users' => [597, 1089, 941, 1547], 'emails' => ['operaciones']],
            45 => ['name' => 'GARANTIAS', 'transito' => 10095, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            46 => ['name' => 'GUATAPURI', 'transito' => 80, 'users' => [597, 1089, 941, 1037, 1060, 1166, 1170, 1178, 1255, 1271, 1292, 1304, 1329, 1330, 1356, 1384, 1439, 1445, 1473, 1474, 1475, 1504, 11580], 'emails' => ['guatapuri']],
            47 => ['name' => 'TEMPORADA 2025', 'transito' => null, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            49 => ['name' => 'VENTURA PLAZA', 'transito' => 81, 'users' => [597, 1192, 1089, 941, 1344, 1345, 1371, 1383, 1495, 1556, 11556], 'emails' => ['venturaplaza']],
            50 => ['name' => 'FABRICATO', 'transito' => 82, 'users' => [597, 1089, 941, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1346, 1349, 1380, 1387, 1388, 1416, 1418, 1423, 1443, 1468, 1476, 1489, 1490, 1509, 1526, 1527, 1535, 1538, 11557, 11597, 1089, 941], 'emails' => ['fabricato']],
            51 => ['name' => 'UNICO CALI', 'transito' => 83, 'users' => [597, 1089, 941, 1205, 1206, 1207, 1209, 1210, 1211, 1213, 1235, 1239, 1240, 1241, 1244, 1245, 1269, 1270, 1300, 1322, 1353, 1359, 1386, 1411, 1412, 1413, 1421, 1433, 1435, 1477, 1496, 1536, 11590], 'emails' => ['unicocali']],
            56 => ['name' => 'CARIBE PLAZA 2', 'transito' => 84, 'users' => [597, 1089, 941, 1366], 'emails' => ['caribeplaza']],
            57 => ['name' => 'MAYORCA', 'transito' => 85, 'users' => [597, 1089, 941, 1537, 1542, 1543, 1544, 1545, 1546, 1548, 1549, 1552, 1554, 1555, 11558, 11568, 11577, 11582], 'emails' => ['mayorca']],
            58 => ['name' => 'GRAN MANZANA', 'transito' => 86, 'users' => [597, 1089, 941, 1498, 1500, 1501, 1502, 1503, 1513, 1531, 11584], 'emails' => ['granmanzana']],
            59 => ['name' => 'NUESTRO ATLANTICO', 'transito' => 87, 'users' => [597, 1089, 941, 885, 11585, 11586, 11587, 11588, 11589, 11595], 'emails' => ['nuestroatlantico']],
            62 => ['name' => 'NUESTRO URABÁ', 'transito' => 88, 'users' => [597, 1089, 941, 21648], 'emails' => ['nuestrouraba']],
            63 => ['name' => 'NUESTRO CARTAGO', 'transito' => 89, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            66 => ['name' => 'GUACARI SINCELEJO', 'transito' => 90, 'users' => [597, 1089, 941, 21649], 'emails' => ['guacari']],
            69 => ['name' => 'NUESTRO BOGOTÁ', 'transito' => 91, 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            93 => ['name' => 'MALL PLAZA CARTAGENA', 'transito' => 94, 'users' => [597], 'emails' => ['operaciones']],
            10093 => ['name' => 'IMPORTADO', 'transito' => null, 'users' => [597], 'emails' => ['operaciones']],
        ],
        'TRANSITO' => [
            67 => ['name' => 'TRANSITO P R I N C I P A L', 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            68 => ['name' => 'TRANSITO REVENT', 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            70 => ['name' => 'TRANSITO ALEGRA', 'users' => [597, 1089, 941, 735, 742, 816, 823, 824, 873, 875, 878, 879, 880, 883, 884, 957, 972, 975, 979, 997, 1002, 1003, 1049, 1062, 1065, 1068, 1114, 1128, 1163, 1164, 1182, 1223, 1237, 1238, 1242, 1251, 1279, 1280, 1314, 1348, 1350, 1362, 11571, 11579, 11581, 11591], 'emails' => ['parquealegra']],
            71 => ['name' => 'TRANSITO MAYALES', 'users' => [597, 1089, 941, 948, 951, 952, 978, 1005, 1016, 1059, 1115, 1116, 1126, 1127, 1171, 1175, 1176, 1177, 1179, 1216, 1258, 1291, 1309, 1310, 1311, 1361, 1370, 1372, 1391, 1452, 1471, 1472, 1520, 11578], 'emails' => ['mayales']],
            72 => ['name' => 'TRANSITO OCEAN MALL', 'users' => [597, 1089, 941, 1087, 1183, 1197, 1200, 1201, 1224, 1263, 1264, 1390, 1442, 1530, 11572, 11573], 'emails' => ['reventoceanmall@gmail.com', 'oceanmall']],
            73 => ['name' => 'TRANSITO NUESTRO MONTERIA', 'users' => [597, 1089, 941, 982, 983, 984, 985, 987, 989, 994, 1015, 1017, 1026, 1029, 1056, 1057, 1074, 1095, 1096, 1098, 1099, 1104, 1117, 1121, 1135, 1159, 1217, 1219, 1347, 1455, 1470, 11559, 11560, 11602], 'emails' => ['nuestro']],
            74 => ['name' => 'TRANSITO ALAMEDAS', 'users' => [597, 1089, 941, 986, 992, 1034, 1061, 1064, 1092, 1097, 1157, 1162, 1165, 1232, 1288, 1373, 1382, 1408, 1409, 1469, 1533, 11561], 'emails' => ['alamedas']],
            75 => ['name' => 'TRANSITO PORTAL', 'users' => [597, 1089, 941, 732, 792, 865, 871, 887, 888, 891, 893, 894, 896, 897, 898, 899, 938, 959, 971, 998, 1001, 1027, 1063, 1072, 1123, 1131, 1132, 1180, 1188, 1226, 1228, 1278, 1295, 1296, 1315, 1318, 1319, 1331, 1351, 1364, 1365, 1378, 1461, 1462, 1463, 1481, 1497, 1508, 1512, 1523, 11574, 11575, 11576, 11598], 'emails' => ['portaldelprado']],
            76 => ['name' => 'TRANSITO PLAZA DEL SOL', 'users' => [597, 1089, 941, 780, 930, 1181, 1221, 1261, 1268, 1298, 1328, 1385, 1400, 1430, 1438, 1454, 1467, 1480, 11592], 'emails' => ['plazadelsol']],
            77 => ['name' => 'TRANSITO CASTELLANA', 'users' => [597, 1089, 941, 926, 1021, 1035, 1040, 1042, 1044, 1046, 1051, 1052, 1053, 1054, 1055, 1058, 1066, 1070, 1073, 1075, 1082, 1085, 1091, 1102, 1106, 1133, 1134, 1142, 1152, 1153, 1154, 1167, 1173, 1174, 1184, 1185, 1187, 1195, 1196, 1202, 1225, 1227, 1229, 1230, 1265, 1266, 1267, 1275, 1276, 1277, 1299, 1307, 1308, 1317, 1352, 1419, 1444, 1483, 1488, 1492, 1494, 1515, 1516, 1517, 1534, 11569, 11570], 'emails' => ['castellana']],
            78 => ['name' => 'TRANSITO UNICO BQ', 'users' => [597, 1089, 941, 1124, 740, 813, 882, 1018, 1122, 1212, 1220, 1222, 1252, 1256, 1259, 1260, 1273, 1274, 1281, 1282, 1283, 1284, 1285, 1286, 1289, 1305, 1312, 1313, 1320, 1321, 1357, 1360, 1375, 1379, 1403, 1424, 1449, 1451, 1460, 1511, 1514, 1524, 11562], 'emails' => ['unico']],
            79 => ['name' => 'TRANSITO CARNAVAL', 'users' => [597, 1089, 941, 810, 886, 1145, 1147, 1148, 1150, 1151, 1160, 1203, 1233, 1257, 1297, 1324, 1325, 1429, 1466, 1532, 11563, 11565], 'emails' => ['carnaval']],
            80 => ['name' => 'TRANSITO GUATAPURI', 'users' => [597, 1089, 941, 1037, 1060, 1166, 1170, 1178, 1255, 1271, 1292, 1304, 1329, 1330, 1356, 1384, 1439, 1445, 1473, 1474, 1475, 1504, 11580], 'emails' => ['guatapuri']],
            81 => ['name' => 'TRANSITO VENTURA PLAZA', 'users' => [597, 1192, 1089, 941, 1344, 1345, 1371, 1383, 1495, 1556, 11556], 'emails' => ['venturaplaza']],
            82 => ['name' => 'TRANSITO FABRICATO', 'users' => [597, 1089, 941, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1346, 1349, 1380, 1387, 1388, 1416, 1418, 1423, 1443, 1468, 1476, 1489, 1490, 1509, 1526, 1527, 1535, 1538, 11557, 11597, 1089, 941], 'emails' => ['fabricato']],
            83 => ['name' => 'TRANSITO UNICO CALI', 'users' => [597, 1089, 941, 1205, 1206, 1207, 1209, 1210, 1211, 1213, 1235, 1239, 1240, 1241, 1244, 1245, 1269, 1270, 1300, 1322, 1353, 1359, 1386, 1411, 1412, 1413, 1421, 1433, 1435, 1477, 1496, 1536, 11590], 'emails' => ['unicocali']],
            84 => ['name' => 'TRANSITO CARIBE PLAZA 2', 'users' => [597, 1089, 941, 1366], 'emails' => ['caribeplaza']],
            85 => ['name' => 'TRANSITO MAYORCA', 'users' => [597, 1089, 941, 1537, 1542, 1543, 1544, 1545, 1546, 1548, 1549, 1552, 1554, 1555, 11558, 11568, 11577, 11582], 'emails' => ['mayorca']],
            86 => ['name' => 'TRANSITO GRAN MANZANA', 'users' => [597, 1089, 941, 1498, 1500, 1501, 1502, 1503, 1513, 1531, 11584], 'emails' => ['granmanzana']],
            87 => ['name' => 'TRANSITO NUESTRO ATLANTICO', 'users' => [597, 1089, 941, 885, 11585, 11586, 11587, 11588, 11589, 11595], 'emails' => ['nuestroatlantico']],
            88 => ['name' => 'TRANSITO NUESTRO URABÁ', 'users' => [597, 1089, 941, 21648], 'emails' => ['nuestrouraba']],
            89 => ['name' => 'TRANSITO NUESTRO CARTAGO', 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            90 => ['name' => 'TRANSITO GUACARI SINCELEJO', 'users' => [597, 1089, 941, 21649], 'emails' => ['guacari']],
            91 => ['name' => 'TRANSITO NUESTRO BOGOTÁ', 'users' => [597, 1089, 941], 'emails' => ['operaciones']],
            94 => ['name' => 'TRANSITO MALL PLAZA CARTAGENA', 'users' => [597], 'emails' => ['operaciones']],
            10094 => ['name' => 'TRANSITO WEB', 'users' => [597, 1089, 941, 940, 980, 1146], 'emails' => ['paginawebrevent@gmail.com']],
            10095 => ['name' => 'TRANSITO GARANTIAS', 'users' => [597, 1089, 941], 'emails' => ['operaciones']]
        ]
    ];

    public function __construct(private Collection $transfer, private string $email)
    {
        $this->onQueue('masive-transfers');
    }

    /** Traslados creados en esta ejecución; sus archivos temporales se borran siempre al terminar. */
    private array $traslados_generados = [];

    public function handle(): void
    {
        try {
            $this->procesar();
        } finally {
            // Los archivos (PDF y Excel) solo existen para adjuntarlos al correo: se borran aunque algo falle.
            $this->eliminar_archivos_temporales();
        }
    }

    private function procesar(): void
    {
        $traslados = [];

        [$config, $errors] = $this->validar_configuracion($this->transfer['traslado']->first()?->toArray() ?? []);

        if (!empty($errors)) {
            $this->notificar_resultado($traslados, $errors);
            return;
        }

        $token = $config['token'];

        [$user, $errors] = $this->obtener_datos_usuario($token);

        if (!empty($errors)) {
            $this->notificar_resultado($traslados, $errors);
            return;
        }

        $detalles = $this->agrupar_duplicados($this->transfer['traslado_detalles']->toArray());

        $errors = array_merge($errors, $this->validar_permiso_bodega($detalles, $user));

        [$detalles, $validate] = $this->buscar_producto($token, $detalles);
        $errors = array_merge($errors, $validate);

        $errors = array_merge($errors, $this->validar_bodega_salida($token, $detalles, $config));
        $errors = array_merge($errors, $this->validar_bodega_entrada($token, $detalles, $config));

        if ($config['tipo'] == 'TRANSITO') {
            [$detalles, $validate] = $this->validar_bodega_transito($detalles);
            $errors = array_merge($errors, $validate);
        }

        if (empty($errors)) {
            foreach ($this->separar_traslados($detalles) as $grupo) {
                [$traslado, $validate] = $this->traslado($token, $grupo, $config);

                if (!empty($validate)) {
                    $errors = array_merge($errors, $validate);
                    continue;
                }

                $traslados[] = $traslado;
                $this->traslados_generados[] = $traslado;

                $this->notificar_bodega($traslado['data']['bodega_salida_data'], $traslado, self::VISTA_SALIDA);
                $this->notificar_bodega($traslado['data']['bodega_ingreso_data'], $traslado, self::VISTA_ENTRADA, adjuntar_excel: true);

                // Siigo marca como duplicado dos creaciones seguidas: se espera antes del siguiente traslado.
                app(SiigoRetryPolicy::class)->pauseBetweenDocuments();
            }
        }

        $this->notificar_resultado($traslados, $errors);
    }

    /** Borra los PDF y Excel temporales de todos los traslados creados en esta ejecución. */
    private function eliminar_archivos_temporales(): void
    {
        foreach ($this->traslados_generados as $traslado) {
            foreach ([$traslado['pdf'] ?? null, $traslado['approved'] ?? null] as $archivo) {
                if (!empty($archivo['disk']) && !empty($archivo['path'])) {
                    Storage::disk($archivo['disk'])->delete($archivo['path']);
                }
            }
        }

        $this->traslados_generados = [];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportMasiveTransferSiigoJob falló', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);

        $this->notificar_resultado([], [
            $this->error_general('ERROR DESCONOCIDO', 'Ocurrió un error inesperado procesando el traslado masivo: ' . $exception->getMessage()),
        ]);
    }

    /* -----------------------------------------------------------------
     | Notificaciones
     | ----------------------------------------------------------------- */

    private function notificar_resultado(array $traslados, array $errors): void
    {
        Mail::to([$this->email])->send(new MasiveTransferSiigoMail(
            traslados: $traslados,
            errors: $errors,
            adjuntar_excel: true,
        ));
    }

    private function notificar_bodega(?array $bodega, array $traslado, string $vista, bool $adjuntar_excel = false): void
    {
        $emails = $bodega['emails'] ?? [];

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(new MasiveTransferSiigoMail(
            traslados: [$traslado],
            template_view: $vista,
            adjuntar_pdf: true,
            adjuntar_excel: $adjuntar_excel,
        ));
    }

    /* -----------------------------------------------------------------
     | Validación de la hoja "traslado"
     | ----------------------------------------------------------------- */

    private function validar_configuracion(array $config): array
    {
        $errors = [];

        if (empty($config['token'] ?? null)) {
            $errors[] = $this->error_general('TOKEN', 'El token es obligatorio');
        }

        if (isset($config['fecha']) && $config['fecha'] != '') {
            $config['fecha'] = $this->normalizar_fecha($config['fecha']);

            if (Carbon::parse($config['fecha'])->gt(Carbon::today())) {
                $errors[] = $this->error_general('FECHA', 'La fecha no puede ser mayor a la fecha actual');
            }
        } else {
            $errors[] = $this->error_general('FECHA', 'La fecha es obligatoria');
        }

        if (!in_array($config['validar_disponible'] ?? null, ['SI', 'NO'], true)) {
            $errors[] = $this->error_general('VALIDAR DISPONIBLE', 'El campo validar disponible debe ser SI o NO');
        }

        if (!in_array($config['tipo'] ?? null, ['DIRECTO', 'TRANSITO'], true)) {
            $errors[] = $this->error_general('TIPO', 'El tipo de traslado debe ser DIRECTO o TRANSITO');
        }

        return [$config, $errors];
    }

    private function normalizar_fecha($fecha): string
    {
        return is_numeric($fecha)
            ? Date::excelToDateTimeObject($fecha)->format('Y-m-d')
            : Carbon::parse($fecha)->format('Y-m-d');
    }

    /* -----------------------------------------------------------------
     | Hoja "traslado_detalles"
     | ----------------------------------------------------------------- */

    private function agrupar_duplicados(array $detalles): array
    {
        return collect($detalles)
            ->groupBy(fn ($item) => $item['codigo'] . '|' . $item['bodega_salida'] . '|' . $item['bodega_entrada'])
            ->map(function ($items) {
                $detalle = $items->first();
                $detalle['cantidad'] = $items->sum('cantidad');

                return $detalle;
            })
            ->values()
            ->all();
    }

    private function validar_permiso_bodega(array $detalles, array $user): array
    {
        $validate = [];

        foreach ($detalles as $row => $detalle) {
            $bodega = $this->bodega($detalle['bodega_salida']);

            if (!empty($bodega) && in_array($user['id'], $bodega['users'] ?? [])) {
                continue;
            }

            $validate[] = [
                'Row'           => $row + 1,
                'ProductCode'   => $detalle['codigo'],
                'Description'   => $detalle['codigo'],
                'WarehouseCode' => $detalle['bodega_salida'] . ' - ' . ($bodega['name'] ?? '#N/A'),
                'Error'         => 'No tiene acceso a la bodega de salida',
            ];
        }

        return $validate;
    }

    private function buscar_producto(string $token, array $detalles): array
    {
        $items = [];
        $validate = [];
        $productos = [];

        foreach ($detalles as $row => $detalle) {
            $codigo = $detalle['codigo'];

            if (empty($codigo)) {
                $validate[] = ['Row' => $row + 1, 'ProductCode' => $codigo, 'Error' => 'El codigo es requerido'];
                continue;
            }

            if (!array_key_exists($codigo, $productos)) {
                [$ok, $resultado] = $this->buscar_con_paginacion($token, (string) $codigo);

                if (!$ok) {
                    return [[], [$this->error_general('ERROR DESCONOCIDO', $resultado)]];
                }

                $productos[$codigo] = $resultado;
            }

            $producto = $productos[$codigo];

            if (!$producto) {
                Log::warning('Traslado masivo: producto no encontrado en Siigo', [
                    'codigo'  => $codigo,
                    'fila'    => $row + 1,
                    'filtro'  => 'IsInventoryControl=1',
                    'paginas' => self::PAGINAS_BUSQUEDA_PRODUCTO,
                ]);
                $validate[] = ['Row' => $row + 1, 'ProductCode' => $codigo, 'Error' => 'El producto no existe o no tiene control de inventario'];
                continue;
            }

            $items[] = [
                'ProductCode'            => $producto['ProductID'],
                'Code'                   => $producto['Code'],
                'Description'            => $producto['Description'],
                'LongDescription'        => $producto['Description'],
                'ProductSubType'         => $producto['ProductType'],
                'ProductUnitMeasurement' => $producto['MeasurementUnit'],
                'EntryItemType'          => $producto['ProductType'],
                'Order'                  => 1,
                'CostCenterCode'         => null,
                'WarehouseCode'          => $detalle['bodega_salida'],
                'DestinationWarehouseCode' => $detalle['bodega_entrada'],
                'AccountCode'            => 0,
                'SalesmanCode'           => null,
                'ConceptCode'            => null,
                'Quantity'               => $detalle['cantidad'],
                'Value'                  => 0,
            ];
        }

        return [$items, $validate];
    }

    /**
     * Devuelve [true, producto|null] o [false, cuerpo del error de Siigo].
     */
    private function buscar_con_paginacion(string $token, string $codigo): array
    {
        for ($pagina = 0; $pagina < self::PAGINAS_BUSQUEDA_PRODUCTO; $pagina++) {
            $response = $this->autocomplete($token, [
                'type'          => 1,
                'browserID'     => '33',
                'query'         => $codigo,
                'filter'        => 'IsInventoryControl=1',
                'numRecordView' => $pagina * 10,
                'tags'          => (object) [],
            ]);

            if (!$response->successful()) {
                return [false, $response->body()];
            }

            $rawBody = trim($response->body());

            if ($rawBody === '""' || $rawBody === '' || $rawBody === 'null') {
                return [true, null];
            }

            $data = json_decode($response->json(), true);

            if (empty($data)) {
                Log::warning('Traslado masivo: autocompletado sin datos legibles', [
                    'codigo' => $codigo,
                    'pagina' => $pagina,
                    'status' => $response->status(),
                    'tipo'   => gettype($response->json()),
                    'body'   => mb_substr($rawBody, 0, 500),
                ]);

                return [true, null];
            }

            $producto = collect($data)->where('Code', $codigo)->first();

            Log::info('Traslado masivo: consulta de producto', [
                'codigo'      => $codigo,
                'pagina'      => $pagina,
                'resultados'  => count($data),
                'codigos'     => collect($data)->pluck('Code')->all(),
                'encontrado'  => (bool) $producto,
            ]);

            if ($producto) {
                return [true, $producto];
            }
        }

        return [true, null];
    }

    private function validar_bodega_salida(string $token, array $detalles, array $config): array
    {
        return $this->validar_existencias($token, $detalles, $config, 'WarehouseCode', 'salida');
    }

    private function validar_bodega_entrada(string $token, array $detalles, array $config): array
    {
        return $this->validar_existencias($token, $detalles, $config, 'DestinationWarehouseCode', 'entrada');
    }

    private function validar_existencias(string $token, array $detalles, array $config, string $campo, string $tipo): array
    {
        $validate = [];
        $fecha = Carbon::parse($config['fecha'])->format('Ymd');
        $esSalida = $campo === 'WarehouseCode';

        foreach ($detalles as $row => $detalle) {
            $productCode = $detalle['ProductCode'];
            $warehouseCode = $detalle[$campo];

            $response = $this->autocomplete($token, [
                'type'          => 1,
                'browserID'     => '67',
                'query'         => '',
                'filter'        => "productcode = {$productCode} AND period < CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'))",
                'filter2'       => "transactiondate >= CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$fecha', 120) AND productcode = {$productCode}",
                'numRecordView' => 0,
                'tags'          => (object) [
                    '{FilterWareHouse}' => "{condition} ProductWarehouseId = {$warehouseCode}",
                ],
            ]);

            if (!$response->successful()) {
                return [$this->error_general('ERROR DESCONOCIDO', $response->body())];
            }

            $data = $response->json();
            $bodegas = !empty($data) ? json_decode($data, true) : [];

            $existencia = collect($bodegas)->where('WarehouseID', $warehouseCode)->first();

            if (!$existencia) {
                $validate[] = $this->error_detalle($row, $detalle, $warehouseCode, "La bodega de {$tipo} no existe");
                continue;
            }

            if ($esSalida && $config['validar_disponible'] == 'SI' && $existencia['Value'] < $detalle['Quantity']) {
                $validate[] = $this->error_detalle($row, $detalle, $warehouseCode . ' - ' . $existencia['Warehouse'], 'Cantidad insuficiente', [
                    'AvailableQuantity' => $existencia['Value'],
                    'RequiredQuantity'  => $detalle['Quantity'],
                ]);
            }
        }

        return $validate;
    }

    private function validar_bodega_transito(array $detalles): array
    {
        $validate = [];

        foreach ($detalles as $row => &$detalle) {
            $destino = $detalle['DestinationWarehouseCode'];
            $bodega = self::BODEGAS['DIRECTO'][$destino] ?? null;

            if (!$bodega) {
                $validate[] = $this->error_detalle($row, $detalle, $destino . ' - #N/A', 'La bodega de entrada no está configurada para traslado');
            } elseif (!$bodega['transito']) {
                $validate[] = $this->error_detalle($row, $detalle, $destino . ' - ' . $bodega['name'], 'La bodega de entrada no tiene bodega de transito configurada');
            } elseif (empty(self::BODEGAS['TRANSITO'][$bodega['transito']])) {
                $validate[] = $this->error_detalle($row, $detalle, $bodega['transito'] . ' - #N/A', 'La bodega de transito configurada no existe');
            } else {
                $detalle['DestinationWarehouseCode'] = $bodega['transito'];
            }
        }
        unset($detalle);

        return [$detalles, $validate];
    }

    private function separar_traslados(array $detalles): array
    {
        return collect($detalles)
            ->groupBy(fn ($detalle) => $detalle['WarehouseCode'] . '-' . $detalle['DestinationWarehouseCode'])
            ->map(fn ($grupo) => $grupo->values()->all())
            ->values()
            ->all();
    }

    /* -----------------------------------------------------------------
     | Creación del traslado en Siigo
     | ----------------------------------------------------------------- */

    private function traslado(string $token, array $detalles, array $config): array
    {
        $body = $this->payload_traslado(
            $detalles,
            Carbon::parse($config['fecha'])->format('Ymd'),
            trim((string) ($config['observacion'] ?? ''))
        );

        $retry = app(SiigoRetryPolicy::class);

        for ($intento = 1; ; $intento++) {
            // Sin reintento HTTP automatico: reenviar un Save podria crear traslados duplicados.
            $response = app(SiigoClient::class)->services($token)
                ->replaceHeaders(['Accept' => '*/*'])
                ->asJson()
                ->connectTimeout(60)
                ->timeout(300)
                ->post(self::PATH_TRANSFER_SAVE, $body);

            if ($response->successful()) {
                break;
            }

            // Falso duplicado: Siigo lo marca si recibe dos creaciones en el mismo segundo.
            if ($retry->shouldRetryDuplicate($response->body(), $intento)) {
                Log::warning('traslado reintentando por posible duplicidad', ['intento' => $intento]);
                $retry->waitBeforeDuplicateRetry();
                continue;
            }

            $intentosTexto = $retry->attemptsText($intento, 'el error persistió.');

            return [[], [$this->error_general('ERROR DESCONOCIDO - TRASLADO', "{$intentosTexto} Respuesta de Siigo: " . $response->body())]];
        }

        $id = $response->json();
        $document = $this->consultar_documento($token, $id);

        $salida = $detalles[0]['WarehouseCode'];
        $ingreso = $detalles[0]['DestinationWarehouseCode'];

        return [
            [
                'preview'          => SiigoLinks::transferNote($id),
                'preview_download' => SiigoLinks::transferNote($id) . '/true',
                'approved'         => $config['tipo'] == 'TRANSITO'
                    ? $this->generar_excel_aprobar_traslado_transito($document['DocName'], $detalles)
                    : null,
                'pdf'              => $this->generar_pdf_traslado($document['DocName'], $id),
                'data'             => [
                    'id'                  => $id,
                    'document'            => $document['DocName'],
                    'user'                => $document['CreatedByUser'],
                    'created_at'          => $document['CreatedByDate'],
                    'date'                => $document['DocDate'],
                    'observation'         => $body['Entry']->Observations,
                    'bodega_salida'       => $salida,
                    'bodega_ingreso'      => $ingreso,
                    'bodega_salida_data'  => $this->bodega($salida),
                    'bodega_ingreso_data' => $this->bodega($ingreso),
                ],
            ],
            [],
        ];
    }

    private function payload_traslado(array $detalles, string $fecha, string $observacion): array
    {
        return [
            'Items'       => $detalles,
            'AttachFiles' => [],
            'EntryType'   => (object) [
                'ERPDocumentTypeID'          => 23215,
                'Name'                       => 'Nota de traslado entre bodegas',
                'Class'                      => 'NT',
                'Code'                       => 'NT',
                'IsAutomaticEnum'            => true,
                'TemplateName'               => 'TransferVoucher',
                'InternalDescription'        => '',
                'ACAccountCode'              => -1,
                'UseCostCenter'              => false,
                'CostCenterDefault'          => null,
                'CostCenterMandatory'        => false,
                'AttachmentsFSItemsGUID'     => null,
                'AllowDecimals'              => false,
                'IsDiscountValue'            => false,
                'IsDiscountPercentaje'       => false,
                'EmailBody'                  => null,
                'EmailHeader'                => null,
                'EmailCommercialConditions'  => null,
            ],
            'Entry'       => (object) [
                'SalesmanCode'           => null,
                'DocName'                => '',
                'DocDate'                => $fecha,
                'DocNumber'              => null,
                'CostCenterCode'         => null,
                'ForeignMoneyCode'       => null,
                'ExchangeValue'          => null,
                'ExchangePersonalized'   => null,
                'Observations'           => $observacion,
                'AccountCode'            => 0,
                'ContactCode'            => null,
                'Header'                 => null,
                'CommercialConditions'   => null,
                'ACEntryCode'            => -1,
                'ERPDocumentTypeCode'    => 23215,
                'IsAllowDecimals'        => null,
                'AttachmentsFSItemsGUID' => '',
            ],
            'ModelType'   => 23,
        ];
    }

    private function generar_excel_aprobar_traslado_transito(string $document, array $detalles): array
    {
        $bodega_salida = $detalles[0]['DestinationWarehouseCode'];
        $bodega_entrada = collect(self::BODEGAS['DIRECTO'])->search(fn ($bodega) => $bodega['transito'] == $bodega_salida);

        $traslado = [[
            'token'              => '',
            'fecha'              => '',
            'validar_disponible' => 'SI',
            'tipo'               => 'DIRECTO',
            'observacion'        => "APROBACION DE TRASLADO DE TRANSITO: {$document}",
        ]];

        $traslado_detalles = array_map(fn ($detalle) => [
            'codigo'         => $detalle['Code'],
            'descripcion'    => $detalle['LongDescription'],
            'bodega_salida'  => $bodega_salida,
            'bodega_entrada' => $bodega_entrada,
            'cantidad'       => $detalle['Quantity'],
        ], $detalles);

        $filename = "transfer_{$document}_" . now()->format('Y_m_d_His') . '.xlsx';
        $path = self::TMP_PATH . "/{$filename}";

        // Archivo temporal en disco privado: solo se adjunta al correo y luego se borra.
        Excel::store(new MasiveTransferSiigoMultiSheetExport($traslado, $traslado_detalles), $path, self::TMP_DISK);

        return [
            'name'         => $bodega_salida . ' - ' . self::BODEGAS['TRANSITO'][$bodega_salida]['name'] . ' a ' . $bodega_entrada . ' - ' . self::BODEGAS['DIRECTO'][$bodega_entrada]['name'],
            // Ya no se publica en storage/public: el Excel viaja solo como adjunto.
            'download_url' => null,
            'disk'         => self::TMP_DISK,
            'path'         => $path,
            'file'         => $filename,
        ];
    }

    /**
     * Genera el mismo PDF de la ruta siigo/search_document/NT/pdf y lo guarda para adjuntarlo en los correos.
     * Si falla no detiene el traslado (ya quedó creado en Siigo): el correo sale sin el PDF.
     */
    private function generar_pdf_traslado(string $document, string|int $id): ?array
    {
        try {
            return app(SiigoTransferNTPdfService::class)->store_transfer_note_pdf($document, $id);
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar el PDF del traslado', [
                'document' => $document,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /* -----------------------------------------------------------------
     | Consultas a Siigo
     | ----------------------------------------------------------------- */

    private function autocomplete(string $token, array $body)
    {
        // Sin Accept: application/json: con esa cabecera Siigo devuelve la lista codificada dos veces
        // y json_decode() ya no la lee (todos los productos salian como inexistentes).
        return $this->siigo($token)
            ->replaceHeaders(['Accept' => '*/*'])
            ->timeout(120)
            ->post(config('siigo.endpoints.services.autocomplete'), $body);
    }

    private function consultar_documento(string $token, string|int $id): array
    {
        $response = $this->siigo($token)
            ->get(self::PATH_TRANSFER_VIEW, ['id' => $id]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    private function obtener_datos_usuario(string $token): array
    {
        $response = app(SiigoClient::class)->userSettings($token);

        if (!$response->successful()) {
            return [[], [$this->error_general('TOKEN', 'El token no sirve o expiró, genera uno nuevo')]];
        }

        $data = $response->json();

        return [['id' => $data['userID'], 'name' => $data['userName']], []];
    }

    private function siigo(string $token): PendingRequest
    {
        return app(SiigoClient::class)->services($token)->retry(3, 3000)->asJson();
    }

    /* -----------------------------------------------------------------
     | Utilidades
     | ----------------------------------------------------------------- */

    private function bodega($codigo): ?array
    {
        $bodega = self::BODEGAS['DIRECTO'][$codigo] ?? self::BODEGAS['TRANSITO'][$codigo] ?? null;

        if ($bodega === null) {
            return null;
        }

        // En BODEGAS los correos del dominio de la empresa se guardan solo con la parte local.
        $domain = config('siigo.mail.domain');
        $bodega['emails'] = array_map(
            fn (string $email) => str_contains($email, '@') ? $email : "{$email}@{$domain}",
            $bodega['emails'] ?? []
        );

        return $bodega;
    }

    private function error_general(string $row, string $error): array
    {
        return ['Row' => $row, 'Error' => $error];
    }

    private function error_detalle(int $row, array $detalle, string $warehouse, string $error, array $extra = []): array
    {
        return array_merge([
            'Row'           => $row + 1,
            'ProductCode'   => $detalle['Description'],
            'Description'   => $detalle['LongDescription'],
            'WarehouseCode' => $warehouse,
        ], $extra, ['Error' => $error]);
    }
}
