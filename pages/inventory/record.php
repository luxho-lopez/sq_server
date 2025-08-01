<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/db.php';

// 1. Materiales sin número de serie (datos agregados de inventory_items)
$sql_quantity = "SELECT inm.*, m.material_code, m.name, i.invoice_code, mu.abbreviation AS unit_abbreviation, c.name AS container_name,
                COALESCE(
                    (
                        SELECT mf.file_path 
                        FROM material_files mf 
                        WHERE mf.material_id = m.id AND mf.file_type = 'image' 
                        ORDER BY mf.uploaded_at DESC 
                        LIMIT 1
                    ),
                    '/main/assets/uploads/materials/default_material.png'
                ) AS image_path 
                FROM inventory_movements inm
                INNER JOIN materials m ON inm.material_id = m.id
                LEFT JOIN invoices i ON inm.invoice_id = i.id
                LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                LEFT JOIN containers c ON inm.container_id = c.id
                ORDER BY inm.movement_date DESC";

// Ejecutar ambas consultas
$stmt_quantity = $pdo->query($sql_quantity);
$materials_quantity = $stmt_quantity->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-header">
    <h2>Items</h2>
</div>

<div class="table-controls">
    <div class="controls">
        <label>
            Mostrar registros
            <select id="rowsPerPage" onchange="changeRowsPerPage(this)">
                <option value="10">10</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Todos</option>
            </select>
        </label>
    </div>
    <div>
        <input type="text" id="tableSearch" placeholder="Buscar..." onkeyup="filterTable()" />
    </div>
</div>

<div class="table-wrapper">
    <table id="dataTable">
        <thead>
            <tr>
                <th style="width: 20px">#</th>
                <th>Files</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Factura</th>
                <th>Numero de serie</th>
                <th>Cantidad</th>
                <th>Costo</th>
                <th>Movimiento</th>
                <th>Almacen</th>
                <th>Última actualización</th>
            </tr>
        </thead>
        <tbody>
            <?php $row_count = 1; ?>
            <?php foreach ($materials_quantity as $mat): ?>
                <tr>
                    <td><?= $row_count++ ?></td>
                    <td data-label="Files">
                        <img src="<?php echo htmlspecialchars($mat['image_path']); ?>" alt="Imagen del material" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />
                    </td>
                    <td><?= htmlspecialchars($mat['material_code']) ?></td>
                    <td><?= htmlspecialchars($mat['name']) ?></td>
                    <td><?= htmlspecialchars($mat['invoice_code']) ?></td>
                    <td><?= htmlspecialchars($mat['serial_number']) ?></td>
                    <td><?= htmlspecialchars($mat['quantity']) ?> <?= htmlspecialchars($mat['unit_abbreviation']) ?></td>
                    <td><?= htmlspecialchars($mat['cost']) ?></td>
                    <td><?= htmlspecialchars($mat['type']) ?></td>
                    <td><?= htmlspecialchars($mat['container_name']) ?></td>
                    <td><?= htmlspecialchars($mat['movement_date']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
