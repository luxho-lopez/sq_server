<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('invoices', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Activar/Desactivar Factura
if ($action === 'toggle_state' && $invoice_id && hasPermission('invoices', 'edit')) {
    $stmt = $pdo->prepare("SELECT state FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $current_state = $stmt->fetchColumn();
    if ($current_state) {
        $new_state = $current_state === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE invoices SET state = ? WHERE id = ?");
        try {
            $stmt->execute([$new_state, $invoice_id]);
            $success = "Factura " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
            header('Location: /main/index.php?page=invoices');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    } else {
        $error = 'Factura no encontrado.';
    }
}

$invoices = $pdo->query("SELECT i.*, s.name AS supplier_name 
                        FROM invoices i
                        INNER JOIN suppliers s ON i.supplier_id = s.id
                        WHERE i.state = 'active'
                        ORDER BY created_at DESC")->fetchAll();
?>

<div class="container-header">
    <h2>Facturas</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (hasPermission('suppliers', 'edit')): ?>
        <a href="/main/index.php?page=new_invoice" class="add-button">+</a>
    <?php endif; ?>
</div>


<div class="table-controls">
    <div>
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
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Codigo de factura</th>
            <th>Proveedor</th>
            <th>Acciones</th>
        </tr>
        <?php $row_count = 1; ?>
        <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td data-label="ID"><?php echo $row_count++; ?></td>
                <td data-label="Fecha"><?php echo htmlspecialchars($invoice['created_at']); ?></td>
                <td data-label="Factura"><?php echo htmlspecialchars($invoice['invoice_code']); ?></td>
                <td data-label="Proveedor"><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_invoice&invoice_id=<?php echo $invoice['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('invoices', 'edit')): ?>
                            <a href="/main/index.php?page=edit_invoice&invoice_id=<?php echo $invoice['id']; ?>" title="Editar">
                                <i class="icon fas fa-edit"></i>
                            </a>
                            <a href="/main/index.php?page=invoices&action=toggle_state&invoice_id=<?php echo $invoice['id']; ?>" title="<?php echo $invoice['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $invoice['state'] === 'active' ? 'desactivar' : 'activar'; ?> esta Factura?');">
                                <i class="icon fas <?php echo $invoice['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>