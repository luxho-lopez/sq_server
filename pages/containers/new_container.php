<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('containers', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

// Obtener los usuarios para asignar un responsable
$users = [];
$stmt = $pdo->query("SELECT id, username, first_name, last_name FROM users ORDER BY username ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $responsible = trim($_POST['responsible']);

    if (empty($name) || empty($location)) {
        $error = 'Todos los datos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO containers (name, location, user_id) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $location, $responsible]);
            $containerId = $pdo->lastInsertId();
            $success = 'Almacen creado exitosamente.';
            header('Location: /main/index.php?page=containers');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al crear el Almacen: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-header">
    <h2>Nuevo Almacen</h2>
    <a class="delete-button" href="/main/index.php?page=containers"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Nombre" required>
    <input type="text" name="location" placeholder="Ubicacion" required>
    <label for="responsible">Responsable:</label>
    <select name="responsible" id="responsible" required>
        <option value="">Seleccione un responsable</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?> - <?php echo htmlspecialchars($user['first_name']); ?> <?php echo htmlspecialchars($user['last_name']); ?></option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Crear Almacen">
</form>
