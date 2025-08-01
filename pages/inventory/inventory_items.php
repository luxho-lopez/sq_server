<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/db.php';

// 1. Materiales sin número de serie (datos agregados de inventory_items)
$sql_quantity = "SELECT ini.*, m.material_code, m.name, m.state, i.invoice_code, mu.abbreviation AS unit_abbreviation, c.name AS container_name,
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
                FROM inventory_items ini
                INNER JOIN materials m ON ini.material_id = m.id
                LEFT JOIN invoices i ON ini.invoice_id = i.id
                LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                LEFT JOIN containers c ON ini.container_id = c.id
                WHERE m.has_serial = 0 AND ini.quantity > 0
                ORDER BY m.name ASC";

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
                <th>Material</th>
                <th>Stock</th>
                <th>Costo</th>
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
                    <td><?= htmlspecialchars($mat['quantity']) ?> <?= htmlspecialchars($mat['unit_abbreviation']) ?></td>
                    <td><?= htmlspecialchars($mat['cost']) ?></td>
                    <td><?= htmlspecialchars($mat['container_name']) ?></td>
                    <td><?= htmlspecialchars($mat['entry_date']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
