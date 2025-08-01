<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/db.php';

// 2. Materiales con número de serie (cada registro por separado desde inventory_serials)
$sql_serials = "SELECT ins.*, m.material_code, m.name, c.name AS container_name,
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
                FROM inventory_serials ins
                INNER JOIN materials m ON ins.material_id = m.id
                LEFT JOIN containers c ON ins.container_id = c.id
                WHERE m.has_serial = 1
                ORDER BY m.name ASC, ins.serial_number ASC";

$stmt_serials = $pdo->query($sql_serials);
$materials_serials = $stmt_serials->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-header">
    <h2>Serials</h2>
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
                <th>Número de Serie</th>
                <th>Estado</th>
                <th>Almacen</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php $row_count = 1; ?>
            <?php foreach ($materials_serials as $mat): ?>
                <tr>
                    <td><?= $row_count++ ?></td>
                    <td data-label="Files">
                        <img src="<?php echo htmlspecialchars($mat['image_path']); ?>" alt="Imagen del material" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />
                    </td>
                    <td><?= htmlspecialchars($mat['material_code']) ?></td>
                    <td><?= htmlspecialchars($mat['name']) ?></td>
                    <td><?= htmlspecialchars($mat['serial_number']) ?></td>
                    <td><?= htmlspecialchars($mat['state']) ?></td>
                    <td><?= htmlspecialchars($mat['container_name']) ?></td>
                    <td><?= $mat['entry_date'] ?? 'Sin registro' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
