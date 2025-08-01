<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!isSuperAdmin()) {
    header('Location: /main/index.php');
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$error = '';

if (!$user_id) {
    $error = 'ID de usuario inválido.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Usuario no encontrado.';
    }
}
?>

<div class="container-header">
<h2>Detalles del Usuario</h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php else: ?>
    <a class="delete-button" href="/main/index.php?page=users"><i class="fa-solid fa-arrow-left"></i></a>
</div>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <td data-label="ID"><?php echo $user['id']; ?></td>
            </tr>
            <tr>
                <th>Nombre</th>
                <td data-label="Nombre"><?php echo htmlspecialchars($user['first_name']); ?></td>
            </tr>
            <tr>
                <th>Apellido</th>
                <td data-label="Apellido"><?php echo htmlspecialchars($user['last_name']); ?></td>
            </tr>
            <tr>
                <th>Correo</th>
                <td data-label="Correo"><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <th>Teléfono</th>
                <td data-label="Teléfono"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
            </tr>
            <tr>
                <th>Usuario</th>
                <td data-label="Usuario"><?php echo htmlspecialchars($user['username']); ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo $user['state'] === 'active' ? 'Activo' : 'Inactivo'; ?></td>
            </tr>
            <tr>
                <th>Rol</th>
                <td data-label="Rol"><?php echo $user['role'] === 'superadmin' ? 'Superadministrador' : 'Usuario'; ?></td>
            </tr>
        </table>
    </div>
<?php endif; ?>