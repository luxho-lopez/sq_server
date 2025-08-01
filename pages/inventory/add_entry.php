<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/db.php';

if (!hasPermission('inventory', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$material = null;
$error = '';
$success = '';

if ($material_id > 0) {
    $stmt = $pdo->prepare("SELECT m.id, m.material_code, m.name, m.description, m.has_serial, mu.abbreviation AS unit_abbreviation
                        FROM materials m
                        LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                        WHERE m.id = ? AND m.state = 'active'");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();
}

if (!$material) {
    $error = 'Material no válido o no encontrado.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = trim($_POST['invoice_id']);
    $container_id = trim($_POST['container_id']); // Obtener el ID del contenedor
    $user_id = $_SESSION['user_id']; // Obtener el ID del usuario logueado

    if ($material['has_serial']) {
        $serial_number = trim($_POST['serial_number']);
        $cost = trim($_POST['cost']);
        if (empty($serial_number)) {
            $error = 'Debe ingresar el número de serie.';
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_serials WHERE material_id = ? AND serial_number = ?");
            $check->execute([$material_id, $serial_number]);
            if ($check->fetchColumn() > 0) {
                $error = 'Este número de serie ya está registrado.';
            } else {
                $insert = $pdo->prepare("INSERT INTO inventory_serials (material_id, serial_number, cost, invoice_id, container_id, user_id, entry_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $insert->execute([$material_id, $serial_number, number_format((float)$cost, 2, '.', ''), $invoice_id, $container_id, $user_id]);

                $log = $pdo->prepare("INSERT INTO inventory_movements (material_id, type, quantity, serial_number, cost, invoice_id, container_id, user_id) VALUES (?, 'entry', 1, ?, ?, ?, ?, ?)");
                $log->execute([$material_id, $serial_number, number_format((float)$cost, 2, '.', ''), $invoice_id, $container_id, $user_id]);

                $success = 'Entrada con número de serie registrada correctamente.';
            }
        }
    } else {
        $quantity = (int)$_POST['quantity'];
        $cost = (float)$_POST['cost']; // Asegúrate de que sea un número decimal
        if ($quantity < 0) {
            $error = 'Cantidad inválida.';
        } else {
            // Verificar si el material ya está registrado en el contenedor
            $check = $pdo->prepare("SELECT quantity, container_id FROM inventory_items WHERE material_id = ? AND container_id = ?");
            $check->execute([$material_id, $container_id]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Si la cantidad es 0, actualiza el registro existente
                if ($quantity == 0) {
                    $update = $pdo->prepare("UPDATE inventory_items SET quantity = 0, cost = ?, entry_date = NOW() WHERE material_id = ? AND container_id = ?");
                    $update->execute([number_format((float)$cost, 2, '.', ''), $material_id, $container_id]);
                } else {
                    // Si el container_id coincide y la cantidad es mayor a 0, actualiza la cantidad y el costo
                    $update = $pdo->prepare("UPDATE inventory_items SET quantity = quantity + ?, cost = ?, entry_date = NOW() WHERE material_id = ? AND container_id = ?");
                    $update->execute([$quantity, number_format((float)$cost, 2, '.', ''), $material_id, $container_id]);
                }
            } else {
                // Si no existe, inserta un nuevo registro
                $insert = $pdo->prepare("INSERT INTO inventory_items (material_id, quantity, cost, container_id, user_id, entry_date) VALUES (?, ?, ?, ?, ?, NOW())");
                $insert->execute([$material_id, $quantity, number_format((float)$cost, 2, '.', ''), $container_id, $user_id]);
            }

            $log = $pdo->prepare("INSERT INTO inventory_movements (material_id, type, quantity, cost, invoice_id, container_id, user_id) VALUES (?, 'entry', ?, ?, ?, ?, ?)");
            $log->execute([$material_id, $quantity, number_format((float)$cost, 2, '.', ''), $invoice_id, $container_id, $user_id]);

            $success = 'Entrada registrada correctamente.';
        }
    }
}
?>

<div class="container-header">
    <h2>Registrar Entrada</h2>

    <a class="delete-button" href="/main/index.php?page=materials"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if ($error): ?>
        <div style="color: #e74c3c; padding: 10px; border-radius: 5px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif ($success): ?>
        <div style="color: #2ecc71; padding: 10px; border-radius: 5px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

</div>

<form method="post">
    <p><strong>Código:</strong> <?= htmlspecialchars($material['material_code']) ?></p>
    <p><strong>Material:</strong> <?= htmlspecialchars($material['name']) ?></p>
    <p><strong>Descripcion:</strong> <?= htmlspecialchars($material['description']) ?></p>
    <p><strong>Medida:</strong> <?= htmlspecialchars($material['unit_abbreviation']) ?></p>

    <?php if ($material['has_serial']): ?>
        <label>N° de Serie:</label>
        <input type="text" name="serial_number" required>
    <?php else: ?>
        <label>Cantidad:</label>
        <input type="number" name="quantity" min="0" required>
    <?php endif; ?>

    <label>Costo:</label>
    <input type="number" name="cost" min="0" step="0.01" required>

    <label>Factura:</label>
    <select name="invoice_id" id="invoice_id">
        <option value="" disabled selected>Seleccione una factura</option>
        <?php
        $invoices = $pdo->query("SELECT id, invoice_code FROM invoices WHERE state = 'active' ORDER BY created_at DESC")->fetchAll();
        foreach ($invoices as $invoice) {
            echo '<option value="' . htmlspecialchars($invoice['id']) . '">' . htmlspecialchars($invoice['invoice_code']) . '</option>';
        }
        ?> 
    </select>

    <label>Almacen:</label>
    <select name="container_id" id="container_id" required>
        <option value="" disabled selected>Seleccione un almacen</option>
        <?php
        // Obtener el ID del usuario logueado
        $user_id = $_SESSION['user_id']; // Asegúrate de que esta variable contenga el ID del usuario logueado
        $containers = $pdo->prepare("SELECT id, name, location FROM containers WHERE state = 'active' AND user_id = ? ORDER BY name ASC");
        $containers->execute([$user_id]);
        $containers = $containers->fetchAll();
        foreach ($containers as $container) {
            echo '<option value="' . htmlspecialchars($container['id']) . '">' . htmlspecialchars($container['name']) . ' - ' . htmlspecialchars($container['location']) . '</option>';
        }
        ?> 
    </select>
    <br>
    <input type="submit" value="Registrar Entrada">
</form>
