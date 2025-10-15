<?php
session_start();
date_default_timezone_set('America/Bogota');

$conn = new mysqli("localhost", "root", "", "harmony");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (isset($_SESSION['user_rol'])) {
    $user_rol = $_SESSION['user_rol'];
    if ($user_rol === 'operador') {
        header("Location: ../PHP/USUARIOS/acceso_denegado.php");
        exit();
    }
} else {
    header("Location: ../PHP/USUARIOS/acceso_denegado.php");
    exit();
}

$config = [];
$has_records = false;
$has_config = false;

if ($confRes = $conn->query("SELECT * FROM configuracion_frs LIMIT 1")) {
    $config = $confRes->fetch_assoc() ?: [];
    $confRes->free();
    if (!empty($config)) {
        $has_config = true;
    }
}

$current_config_id = $config['id'] ?? null;

if ($current_config_id) {
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total_registros FROM registros_frs WHERE configuracion_id = ?");
    if ($stmt_count) {
        $stmt_count->bind_param("i", $current_config_id);
        $stmt_count->execute();
        $count_res = $stmt_count->get_result();
        $row = $count_res->fetch_assoc();
        
        if ($row['total_registros'] > 0) {
            $has_records = true;
        }
        $stmt_count->close();
    }
}

$sql = "
SELECT 
    id, fecha, tipoA, tipoB, tipoC, tipoD,
    contingencia AS cont,
    resp_ie      AS respIE,
    resp_asoc    AS respAsoc,
    resp_int     AS respInt
FROM registros_frs
WHERE configuracion_id = ?
ORDER BY fecha ASC
";
$registros = [];
if ($current_config_id) {
    $stmt_reg = $conn->prepare($sql);
    if ($stmt_reg) {
        $stmt_reg->bind_param("i", $current_config_id);
        $stmt_reg->execute();
        $regRes = $stmt_reg->get_result();
        $registros = $regRes->fetch_all(MYSQLI_ASSOC);
        $regRes->free();
        $stmt_reg->close();
    }
}

$hoy = date('Y-m-d');
$conn->close();

$reset_js_lock = false;
if ($current_config_id && empty($registros)) {
    $reset_js_lock = true; 
} else if (!$current_config_id) {
    $reset_js_lock = true;
}

$js_has_records = $has_records ? 'true' : 'false';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Planilla Mensual - Comedor Escolar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../ASSETS/CSS/estilo_FRS.css" />
</head>
<body>
<div class="container">
    <header>
        <h1>PLANILLA MENSUAL</h1>
        <p>Registro de entrega de Servicios Integrales de Desayunos y Almuerzos Escolares</p>
    </header>

    <form id="form-config" action="../PHP/USUARIOS/guardar_configuracion.php" method="post">
        <section class="configuracion">
            <?php if (isset($config['id'])): ?>
                <input type="hidden" name="config_id" value="<?= htmlspecialchars($config['id'], ENT_QUOTES) ?>">
            <?php endif; ?>

            <?php 
            $fields = [
                ['label'=>'Consecutivo planilla','id'=>'consecutivo','type'=>'text','value'=>$config['consecutivo']??'','readonly'=>true],
                ['label'=>'Periodo / Mes','id'=>'periodo','type'=>'month','value'=>$config['periodo']??'','readonly'=>true],
                ['label'=>'Institución Educativa','id'=>'institucion','type'=>'text','value'=>$config['institucion']??'','readonly'=>true],
                ['label'=>'Dirección sede','id'=>'direccion','type'=>'text','value'=>$config['direccion']??'','readonly'=>true],
                ['label'=>'Localidad / Barrio','id'=>'localidad','type'=>'text','value'=>$config['localidad']??'','readonly'=>true],
                ['label'=>'Tipo de entrega','id'=>'tipo','type'=>'text','value'=>$config['tipo_entrega']??'','readonly'=>true],
                ['label'=>'Año','id'=>'anio','type'=>'number','value'=>$config['anio']??'','readonly'=>true],
                ['label'=>'N° Convenio','id'=>'convenio','type'=>'text','value'=>$config['convenio']??'','readonly'=>true],
                ['label'=>'Operador / Asociado','id'=>'operador','type'=>'text','value'=>$config['operador']??'','readonly'=>true],
                ['label'=>'Rector (Nombre y Teléfono)','id'=>'rector','type'=>'text','value'=>$config['rector']??'','readonly'=>true],
            ];
            foreach ($fields as $f): ?>
                <div>
                    <label for="<?= $f['id'] ?>"><?= $f['label'] ?></label>
                    <input
                        type="<?= $f['type'] ?>"
                        id="<?= $f['id'] ?>"
                        name="<?= $f['id'] ?>"
                        value="<?= htmlspecialchars($f['value'] ?? '', ENT_QUOTES) ?>"
                        <?= $f['readonly'] ? 'readonly' : '' ?>
                        data-original-value="<?= htmlspecialchars($f['value'] ?? '', ENT_QUOTES) ?>"
                    />
                </div>
            <?php endforeach; ?>
        </section>
    </form>

    <div class="botones-superiores">
        <button id="btn-editar-config" class="btn-secondary" <?= $has_records ? 'disabled' : '' ?>>✏️ Editar Configuración</button>
        <button id="btn-registrar" class="btn-primary">🗓 Registrar Asistencia Diaria</button>
        <button id="btn-guardar-planilla-completa" class="btn-success">💾 Guardar Planilla Mensual</button>
    </div>

    <section id="daily-form" style="display:none">
        <form id="form-dia"
              action="../PHP/USUARIOS/guardar_registro.php"
              method="post">
            <input type="hidden" id="id_registro_editar" name="id_registro_editar" value="">
            <div class="field-group">
                <?php if ($current_config_id): ?>
                    <input type="hidden" name="configuracion_id" value="<?= htmlspecialchars($current_config_id, ENT_QUOTES) ?>">
                <?php endif; ?>
                <div>
                    <label for="d_fecha">Fecha</label>
                    <input
                        type="date"
                        id="d_fecha"
                        name="fecha"
                        value="<?= $hoy ?>"
                        readonly
                        required
                    />
                </div>
                <?php foreach (['A','B','C','D'] as $t): ?>
                    <div>
                        <label for="d_tipo<?= $t ?>">Tipo <?= $t ?></label>
                        <input
                            type="number"
                            id="d_tipo<?= $t ?>"
                            name="tipo<?= $t ?>"
                            min="0" step="1"
                            required
                        />
                    </div>
                <?php endforeach; ?>

                <div>
                    <label for="d_cont">Contingencia</label>
                    <input
                        type="checkbox"
                        id="d_cont"
                        name="cont"
                        value="1"
                    />
                </div>

                <?php 
                $respFields = [
                    ['id'=>'d_respIE','label'=>'Resp IE'],
                    ['id'=>'d_respAsoc','label'=>'Resp Asociado'],
                    ['id'=>'d_respInt','label'=>'Resp Int'],
                ];
                foreach ($respFields as $r_field): ?>
                    <div>
                        <label for="<?= $r_field['id'] ?>"><?= $r_field['label'] ?></label>
                        <input
                            type="text"
                            id="<?= $r_field['id'] ?>"
                            name="<?= substr($r_field['id'],2) ?>"
                            required
                            maxlength="100"
                            pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.\(\)]+$"
                            title="Solo letras, espacios y - . ()"
                        />
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="actions">
                <button type="button" class="btn-cancel" onclick="toggleForm(true, <?= $js_has_records ?>)">Cancelar</button>
                <button type="submit" class="btn-save">Guardar Día</button>
            </div>
        </form>
    </section>

    <table id="tabla-dias">
        <thead>
            <tr>
                <th>Fecha</th><th>Tipo A</th><th>Tipo B</th><th>Tipo C</th><th>Tipo D</th>
                <th>Conting.</th><th>Resp. IE</th><th>Resp. Asoc.</th><th>Resp. Int.</th><th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $r):
            $botones = '';
            if ($r['fecha'] === $hoy) { 
                $jsParams = [
                    $r['id'],
                    $r['fecha'],
                    (int)$r['tipoA'], 
                    (int)$r['tipoB'], 
                    (int)$r['tipoC'], 
                    (int)$r['tipoD'], 
                    $r['cont'] ? 'true' : 'false',
                    $r['respIE'], 
                    $r['respAsoc'], 
                    $r['respInt'], 
                ];
                $jsonParamsString = json_encode($jsParams, JSON_HEX_APOS | JSON_HEX_QUOT);

                $botones .= '<button class="btn-edit" '
                    . 'onclick="editarDia(' . htmlspecialchars($jsonParamsString, ENT_QUOTES, 'UTF-8') . ')">Editar</button> ';
                
                $botones .= '<form action="../PHP/USUARIOS/eliminar_registro.php" '
                    . 'method="post" style="display:inline" '
                    . 'onsubmit="return confirm(\'¿Eliminar el registro del ' . htmlspecialchars($r['fecha'], ENT_QUOTES) . ' (ID: ' . htmlspecialchars($r['id'], ENT_QUOTES) . ')?\')">'
                    . '<input type="hidden" name="id_registro_eliminar" value="' . htmlspecialchars($r['id'], ENT_QUOTES) . '">'
                    . '<button type="submit" class="btn-delete">Eliminar</button>'
                    . '</form>';
            }
        ?>
            <tr>
                <td><?= htmlspecialchars($r['fecha']) ?></td>
                <td><?= (int)$r['tipoA'] ?></td>
                <td><?= (int)$r['tipoB'] ?></td>
                <td><?= (int)$r['tipoC'] ?></td>
                <td><?= (int)$r['tipoD'] ?></td>
                <td><?= $r['cont'] ? 'Sí' : 'No' ?></td>
                <td><?= htmlspecialchars($r['respIE']) ?></td>
                <td><?= htmlspecialchars($r['respAsoc']) ?></td>
                <td><?= htmlspecialchars($r['respInt']) ?></td>
                <td><?= $botones ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <?php
            $sumA = $sumB = $sumC = $sumD = 0;
            foreach ($registros as $r) {
                $sumA += (int)$r['tipoA'];
                $sumB += (int)$r['tipoB'];
                $sumC += (int)$r['tipoC'];
                $sumD += (int)$r['tipoD'];
            }
        ?>
            <tr>
                <th>Totales</th>
                <th><?= $sumA ?></th>
                <th><?= $sumB ?></th>
                <th><?= $sumC ?></th>
                <th><?= $sumD ?></th>
                <th colspan="5"></th>
            </tr>
        </tfoot>
    </table>
</div>

<form id="form-guardar-planilla" method="post" action="../PHP/USUARIOS/guardar_planilla.php" style="display:none"></form>

<script>
    <?php if (isset($_SESSION['status'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $_SESSION['status'] ?>',
                title: '<?= $_SESSION['title'] ?>',
                text: '<?= $_SESSION['message'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
        });
        <?php
        unset($_SESSION['status']);
        unset($_SESSION['title']);
        unset($_SESSION['message']);
        ?>
    <?php endif; ?>

    <?php if ($reset_js_lock): ?>
        sessionStorage.setItem('registroAgregado', 'false'); 
    <?php endif; ?>
</script>

<script src="../ASSETS/JS/java FRS.js"></script>

</body>
</html>