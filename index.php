<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/header.php';

$page = $_GET['page'] ?? 'dash';

// Mapeo de páginas y sus permisos
$pages = [
    'dash' => ['path' => '/main/pages/dash.php', 'permission' => 'dash', 'action' => 'view'],
    'company_data' => ['path' => '/main/pages/company_data.php', 'permission' => null, 'action' => null],
    
    'projects' => ['path' => '/main/pages/projects/projects.php', 'permission' => 'projects', 'action' => 'view'],
    'new_project' => ['path' => '/main/pages/projects/new_project.php', 'permission' => 'projects', 'action' => 'edit'],
    'edit_project' => ['path' => '/main/pages/projects/edit_project.php', 'permission' => 'projects', 'action' => 'edit'],
    'view_project' => ['path' => '/main/pages/projects/view_project.php', 'permission' => 'projects', 'action' => 'view'],
    'list_project_files' => ['path' => '/main/pages/projects/list_project_files.php', 'permission' => 'projects', 'action' => 'view'],

    'materials' => ['path' => '/main/pages/materials/materials.php', 'permission' => 'materials', 'action' => 'view'],
    'new_material' => ['path' => '/main/pages/materials/new_material.php', 'permission' => 'materials', 'action' => 'edit'],
    'edit_material' => ['path' => '/main/pages/materials/edit_material.php', 'permission' => 'materials', 'action' => 'edit'],
    'view_material' => ['path' => '/main/pages/materials/view_material.php', 'permission' => 'materials', 'action' => 'view'],
    'list_material_files' => ['path' => '/main/pages/list_material_files.php', 'permission' => 'materials', 'action' => 'view'],
    
    'inventory_items' => ['path' => '/main/pages/inventory/inventory_items.php', 'permission' => 'inventory', 'action' => 'view'],
    'inventory_serials' => ['path' => '/main/pages/inventory/inventory_serials.php', 'permission' => 'inventory', 'action' => 'view'],
    'add_entry' => ['path' => '/main/pages/inventory/add_entry.php', 'permission' => 'inventory', 'action' => 'view'],
    'record' => ['path' => '/main/pages/inventory/record.php', 'permission' => 'inventory', 'action' => 'view'],
    
    'users' => ['path' => '/main/pages/users/users.php', 'permission' => null, 'action' => null],
    'new_user' => ['path' => '/main/pages/users/new_user.php', 'permission' => null, 'action' => null],
    'edit_user' => ['path' => '/main/pages/users/edit_user.php', 'permission' => null, 'action' => null],
    'view_user' => ['path' => '/main/pages/users/view_user.php', 'permission' => null, 'action' => null],
    'edit_permissions' => ['path' => '/main/pages/users/edit_permissions.php', 'permission' => null, 'action' => null],
    'profile' => ['path' => '/main/pages/users/profile.php', 'permission' => null, 'action' => null],

    'suppliers' => ['path' => '/main/pages/suppliers/suppliers.php', 'permission' => 'suppliers', 'action' => 'view'],
    'new_supplier' => ['path' => '/main/pages/suppliers/new_supplier.php', 'permission' => 'suppliers', 'action' => 'edit'],
    'edit_supplier' => ['path' => '/main/pages/suppliers/edit_supplier.php', 'permission' => 'suppliers', 'action' => 'edit'],
    'view_supplier' => ['path' => '/main/pages/suppliers/view_supplier.php', 'permission' => 'suppliers', 'action' => 'view'],
    
    'workers' => ['path' => '/main/pages/workers/workers.php', 'permission' => 'workers', 'action' => 'view'],
    'new_worker' => ['path' => '/main/pages/workers/new_worker.php', 'permission' => 'workers', 'action' => 'edit'],
    'edit_worker' => ['path' => '/main/pages/workers/edit_worker.php', 'permission' => 'workers', 'action' => 'edit'],
    'view_worker' => ['path' => '/main/pages/workers/view_worker.php', 'permission' => 'workers', 'action' => 'view'],

    'containers' => ['path' => '/main/pages/containers/containers.php', 'permission' => 'containers', 'action' => 'view'],
    'new_container' => ['path' => '/main/pages/containers/new_container.php', 'permission' => 'containers', 'action' => 'edit'],
    'edit_container' => ['path' => '/main/pages/containers/edit_container.php', 'permission' => 'containers', 'action' => 'edit'],
    'view_container' => ['path' => '/main/pages/containers/view_container.php', 'permission' => 'containers', 'action' => 'view'],
    
    'invoices' => ['path' => '/main/pages/invoices/invoices.php', 'permission' => 'invoices', 'action' => 'view'],
    'new_invoice' => ['path' => '/main/pages/invoices/new_invoice.php', 'permission' => 'invoices', 'action' => 'edit'],
    'edit_invoice' => ['path' => '/main/pages/invoices/edit_invoice.php', 'permission' => 'invoices', 'action' => 'edit'],
    'view_invoice' => ['path' => '/main/pages/invoices/view_invoice.php', 'permission' => 'invoices', 'action' => 'view'],

    
    'admin_permissions' => ['path' => '/main/pages/admin_permissions.php', 'permission' => null, 'action' => null],
    
];

if (array_key_exists($page, $pages)) {
    $permission = $pages[$page]['permission'];
    $action = $pages[$page]['action'];

    // Verificar permisos
    if ($permission === null || 
        ($action === 'view' && (hasPermission($permission, 'view') || isSuperAdmin())) || 
        ($action === 'edit' && (hasPermission($permission, 'edit') || isSuperAdmin())) || 
        ($page === 'profile' && isLoggedIn())) {
        
        include $_SERVER['DOCUMENT_ROOT'] . $pages[$page]['path'];
    } else {
        $_SESSION['error'] = 'No tienes permiso para ver esta página o la página no existe.';
        error_log('index.php: Access denied for page ' . $page . ', user_id: ' . ($_SESSION['user_id'] ?? 'unknown'));
        header('Location: /main/index.php');
        exit;
    }
} else {
    $_SESSION['error'] = 'No tienes permiso para ver esta página o la página no existe.';
    error_log('index.php: Access denied for page ' . $page . ', user_id: ' . ($_SESSION['user_id'] ?? 'unknown'));
    header('Location: /main/index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/footer.php';
?>
