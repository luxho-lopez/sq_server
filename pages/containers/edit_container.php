<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('containers', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

// Obtener el ID del contenedor a editar
$container_id = isset($_GET['container_id']) ? (int)$_GET['container_id'] : 0;

// Obtener los usuarios para asignar un responsable
$users = [];
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los datos del contenedor si se proporciona un ID válido
$container = null;
if ($container_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM containers WHERE id = ?");
    $stmt->execute([$container_id]);
    $container = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $responsible = trim($_POST['responsible']);

    if (empty($name) || empty($location)) {
        $error = 'Todos los datos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("UPDATE containers SET name = ?, location = ?, user_id = ? WHERE id = ?");
        try {
            $stmt->execute([$name, $location, $responsible, $container_id]);
            $success = 'Almacen actualizado exitosamente.';
            header('Location: /main/index.php?page=containers');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al actualizar el Almacen: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-header">
    <h2><?php echo $container ? 'Editar Almacen' : 'Nuevo Almacen'; ?></h2>
    <a class="delete-button" href="/main/index.php?page=containers"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Nombre" value="<?php echo htmlspecialchars($container['name'] ?? ''); ?>" required>
    <input type="text" name="location" placeholder="Ubicacion" value="<?php echo htmlspecialchars($container['location'] ?? ''); ?>" required>
    <label for="responsible">Responsable:</label>
    <select name="responsible" id="responsible" required>
        <option value="">Seleccione un responsable</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo $user['id']; ?>" <?php echo (isset($container) && $container['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($user['username']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="<?php echo $container ? 'Actualizar Almacen' : 'Crear Almacen'; ?>">
</form>
