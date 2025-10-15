<?php
session_start();
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'superadmin') {
    header("Location: ../PHP/USUARIOS/acceso_denegado.php");
    exit();
}

$usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../ASSETS/CSS/articulo.css">
    <title>Crear nuevo artículo</title>
</head>
<body>
    <div class="container">
        <h1>Registrar Nuevo Artículo</h1>

        <form action="../PHP/CONTROL_EXISTENCIA/guardar_articulo.php" method="POST" autocomplete="off" id="articuloForm" onsubmit="return validarFormulario()" novalidate>
            <section class="configuracion">

                <div>
                    <label for="nombre">Artículo:</label>
                    <input id="nombre" name="nombre" type="text"
                             required
                             minlength="2" maxlength="100"
                             title="Letras, números, espacios, y los caracteres - . , ( ). No puede empezar ni terminar con guion. Mínimo 2, Máximo 100 caracteres."
                             inputmode="text"
                             placeholder="Ej: Café 250g">
                </div>

                <div>
                    <label for="unidad">Unidad:</label>
                    <input id="unidad" name="unidad" type="text"
                             required
                             minlength="1" maxlength="20"
                             title="Letras, números y espacios. Máx. 20 caracteres."
                             inputmode="text"
                             placeholder="Ej: caja 50">
                </div>

                <div>
                    <label for="localizacion">Localización:</label>
                    <input id="localizacion" name="localizacion" type="text"
                             required
                             minlength="1" maxlength="50"
                             title="Letras, números, espacios, y los caracteres - . , ( ). No puede empezar ni terminar con guion."
                             inputmode="text"
                             placeholder="Ej: Bodega Central A1">
                </div>

                <div>
                    <label for="referencia">Referencia:</label>
                    <input id="referencia" name="referencia" type="text"
                             required
                             minlength="1" maxlength="50"
                             title="Letras, números y guiones. Debe empezar con letra o número. No puede empezar ni terminar con guion. Máx. 50 caracteres."
                             inputmode="text"
                             placeholder="Ej: C001-A2">
                </div>

                <div>
                    <label for="proveedores">Proveedores:</label>
                    <input id="proveedores" name="proveedores" type="text"
                             required
                             minlength="2" maxlength="200"
                             title="Letras, números, espacios, comas, puntos o guiones. No puede empezar ni terminar con guion."
                             inputmode="text"
                             placeholder="Ej: Proveedor S.A. 45">
                </div>

                <div>
                    <label for="minimo">Mínimo:</label>
                    <input id="minimo" name="minimo" type="number"
                             required
                             step="0.01" min="0.01" max="9999999.99"
                             inputmode="decimal"
                             title="Cantidad mínima debe ser mayor que 0 y menor o igual al máximo."
                             placeholder="0.01">
                </div>

                <div>
                    <label for="maximo">Máximo:</label>
                    <input id="maximo" name="maximo" type="number"
                             required
                             step="0.01" min="0.01" max="9999999.99"
                             inputmode="decimal"
                             title="Cantidad máxima debe ser mayor o igual al mínimo."
                             placeholder="0.01">
                </div>

            </section>

            <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuario_id, ENT_QUOTES, 'UTF-8') ?>">

            <div class="actions">
                <button class="btn-save" type="submit">Guardar Artículo</button>
                <a href="control_existencia.php" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const minimoInput = document.getElementById('minimo');
            const maximoInput = document.getElementById('maximo');

            // Prevenir el carácter 'e' en campos numéricos
            [minimoInput, maximoInput].forEach(input => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'e' || e.key === 'E') {
                        e.preventDefault();
                    }
                });
            });
        });

        // FUNCIÓN PRINCIPAL DE VALIDACIÓN DE FORMULARIO
        function validarFormulario() {
            // 1. Validar Mínimo vs Máximo
            const minimoInput = document.getElementById('minimo');
            const maximoInput = document.getElementById('maximo');
            const minimo = parseFloat(minimoInput.value);
            const maximo = parseFloat(maximoInput.value);

            if (minimo > maximo) {
                alert('ERROR de Mínimo/Máximo: La cantidad Mínima (' + minimo.toFixed(2) + ') no puede ser mayor que la cantidad Máxima (' + maximo.toFixed(2) + ').');
                minimoInput.focus();
                return false;
            }

            // 2. Definición de las reglas de validación de campos de texto
            const camposConReglas = [
                // Regla 1: Contiene letras/números/símbolos. Prohibido guion al inicio/fin.
                { id: 'nombre', nombre: 'Artículo', regex: /^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñüÜ\s\-\.,()]+$/, minLength: 2, permiteGuion: true },
                { id: 'localizacion', nombre: 'Localización', regex: /^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñüÜ\s\-\.,()]+$/, minLength: 1, permiteGuion: true },
                { id: 'proveedores', nombre: 'Proveedores', regex: /^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñüÜ\,\s\.\-]+$/, minLength: 2, permiteGuion: true },
                
                // Regla 2: Contiene Letras/Números/Espacios. SIN guiones o puntos (Unidad)
                { id: 'unidad', nombre: 'Unidad', regex: /^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñüÜ\s]+$/, minLength: 1, permiteGuion: false },

                // Regla 3: Referencia (Solo letras, números y guiones. Prohibido guion al inicio/fin)
                { id: 'referencia', nombre: 'Referencia', regex: /^[A-Za-z0-9-]+$/, minLength: 1, permiteGuion: true }
            ];
            
            for (const campo of camposConReglas) {
                const input = document.getElementById(campo.id);
                const valor = input.value;
                const valorTrimmed = valor.trim();

                // a) Validar Longitud Mínima
                if (valor.length < campo.minLength) {
                    alert('ERROR: El campo "' + campo.nombre + '" requiere al menos ' + campo.minLength + ' caracteres.');
                    input.focus();
                    return false;
                }
                
                // b) Validar Contenido de Caracteres (Letras y Números Obligatorios)
                if (!/[A-Za-z0-9]/.test(valor)) {
                    alert('ERROR: El campo "' + campo.nombre + '" debe contener al menos una letra o un número.');
                    input.focus();
                    return false;
                }

                // c) Validar Caracteres Permitidos
                 if (!campo.regex.test(valor)) {
                    alert('ERROR: El campo "' + campo.nombre + '" contiene caracteres no permitidos.');
                    input.focus();
                    return false;
                }

                // d) Validar Guion al Inicio/Fin (Solo si el campo permite guiones)
                if (campo.permiteGuion) {
                    if (valorTrimmed.startsWith('-')) {
                        alert('ERROR: El campo "' + campo.nombre + '" no puede comenzar con un guion (-).');
                        input.focus();
                        return false;
                    }
                    if (valorTrimmed.endsWith('-')) {
                        alert('ERROR: El campo "' + campo.nombre + '" no puede terminar con un guion (-).');
                        input.focus();
                        return false;
                    }
                }
            }

            return true; // Si todas las validaciones pasan
        }
    </script>
</body>
</html>