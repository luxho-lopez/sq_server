<?php
// Iniciar el buffer de salida
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
$page = $_GET['page'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/main/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="icon" href="/main/assets/img/favicon.png" type="image/x-icon">
</head>

<body>
    <button class="hamburger">☰</button>
    <aside class="sidebar">
        <div class="sidebar-brand">Seramaq</div>
        <?php
        $allowed_pages = getAllowedPages();
        ?>

        <ul class="sidebar-nav">
            <?php if (hasPermission('dash', 'view') || isSuperAdmin()): ?>
                <li>
                    <a href="/main/index.php?page=dash" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'dash') ? 'active' : ''; ?>">Inicio</a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('projects', 'view') || isSuperAdmin()): ?>
                <li>
                    <a href="/main/index.php?page=projects" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'projects') ? 'active' : ''; ?>">Proyectos</a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('materials', 'view') || isSuperAdmin()): ?>
                <li>
                    <a href="/main/index.php?page=materials" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'materials') ? 'active' : ''; ?>">Materiales</a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('inventory', 'view') || isSuperAdmin()): ?>
                <li class="has-submenu <?php echo (in_array($page, ['inventory', 'inventory_items', 'inventory_serials', 'record'])) ? 'active' : ''; ?>">
                    <a href="javascript:void(0);" class="submenu-toggle">
                        Inventario <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="submenu" style="<?php echo (in_array($page, ['inventory', 'inventory_items', 'inventory_serials', 'record'])) ? 'display: block;' : ''; ?>">
                        <li>
                            <a href="/main/index.php?page=inventory_items" class="<?php echo ($page === 'inventory_items') ? 'active' : ''; ?>">Items de Inventario</a>
                        </li>
                        <li>
                            <a href="/main/index.php?page=inventory_serials" class="<?php echo ($page === 'inventory_serials') ? 'active' : ''; ?>">Series de Inventario</a>
                        </li>
                        <li>
                            <a href="/main/index.php?page=record" class="<?php echo ($page === 'record') ? 'active' : ''; ?>">Registros</a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('suppliers', 'view') || isSuperAdmin()): ?>
                <li class="has-submenu <?php echo (in_array($page, ['suppliers', 'suppliers', 'invoices'])) ? 'active' : ''; ?>">
                    <a href="javascript:void(0);" class="submenu-toggle">
                        Compras <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="submenu" style="<?php echo (in_array($page, ['suppliers', 'suppliers', 'invoices'])) ? 'display: block;' : ''; ?>">
                        <li>
                            <a href="/main/index.php?page=suppliers" class="<?php echo ($page === 'suppliers') ? 'active' : ''; ?>">Proveedores</a>
                        </li>
                        <li>
                            <a href="/main/index.php?page=invoices" class="<?php echo ($page === 'invoices') ? 'active' : ''; ?>">Facturas</a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('containers', 'view') || isSuperAdmin()): ?>
                <li>
                    <a href="/main/index.php?page=containers" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'containers') ? 'active' : ''; ?>">Almacen</a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('workers', 'view') || isSuperAdmin()): ?>
                <li>
                    <a href="/main/index.php?page=workers" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'workers') ? 'active' : ''; ?>">Empleados</a>
                </li>
            <?php endif; ?>

            <?php if (isSuperAdmin()): ?>
                <li>
                    <a href="/main/index.php?page=users" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'users') ? 'active' : ''; ?>">Usuarios</a>
                </li>
                <li>
                    <a href="/main/index.php?page=admin_permissions" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'admin_permissions') ? 'active' : ''; ?>">Permisos</a>
                </li>
                <li>
                    <a href="/main/index.php?page=company_data" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'company_data') ? 'active' : ''; ?>">Compañia</a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Sidebar Footer con username y logout -->
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile picture" class="profile-picture" style="width: 40px; height: 40px; border-radius: 50%;">
                <a href="/main/index.php?page=profile" style="color: inherit; text-decoration: none;">
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario');?>
                </a>
            </div>
            <a href="/main/logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>

    </aside>
    <div class="container">
