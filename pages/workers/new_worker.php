<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('workers', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

// Obtener los proyectos para el dropdown
$projects = [];
$stmt = $pdo->query("SELECT id, keyword FROM projects WHERE state = 'active' ORDER BY description ASC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_code = trim($_POST['worker_code']);
    $name = trim($_POST['name']);
    $last_name = trim($_POST['last_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $estado = trim($_POST['estado']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $project_id = (int)$_POST['project_id'];
   
    // Validación básica
    if (empty($worker_code)) {
        $error = 'Los campos son obligatorios.';
    } else {
        // Verificar si el worker_code ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM workers WHERE worker_code = ?");
        $stmt->execute([$worker_code]);
        if ($stmt->fetchColumn() > 0) {
            $error = " El código de empleado '$worker_code' ya existe.";
        } else {
            // Insertar worker
            $stmt = $pdo->prepare("INSERT INTO workers (worker_code, name, last_name, address, city, estado, postal_code, country, phone, email, project_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$worker_code, $name, $last_name, $address, $city, $estado, $postal_code, $country, $phone, $email, $_POST['project_id']]);
                $workerId = $pdo->lastInsertId();

                // Subida de archivos
                if (!empty($_FILES['files']['name'][0])) {
                    $uploadBase = $_SERVER['DOCUMENT_ROOT'] . "/main/assets/uploads/workers/$workerId/";
                    if (!is_dir($uploadBase)) {
                        mkdir($uploadBase, 0777, true);
                    }

                    foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
                        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                            $originalName = basename($_FILES['files']['name'][$i]);
                            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                            $fileType = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'document';
                            $uniqueName = uniqid() . '.' . $ext;
                            $destination = $uploadBase . $uniqueName;
                            $relativePath = "/main/assets/uploads/workers/$workerId/$uniqueName";

                            move_uploaded_file($tmpName, $destination);

                            $insertFile = $pdo->prepare("INSERT INTO worker_files (worker_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
                            $insertFile->execute([$workerId, $originalName, $relativePath, $fileType]);
                        }
                    }
                }

                header('Location: /main/index.php?page=workers');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al crear el worker: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-header">
    <h2>Nuevo Empleado</h2>
    <a class="delete-button" href="/main/index.php?page=workers"><i class="fa-solid fa-arrow-left"></i></a>
    
    <?php if ($error): ?>
        <p style="color: red;"> <?php echo $error; ?></p>
    <?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="worker_code" placeholder="Código único del empleado" required>
    <input type="text" name="name" placeholder="Nombre" required>
    <input type="text" name="last_name" placeholder="Apellido" required>
    <input type="text" name="address" placeholder="Dirección" required>
    <input type="text" name="city" placeholder="Ciudad" required>
    <input type="text" name="estado" placeholder="Estado" required>
    <input type="text" name="postal_code" placeholder="Codigo Postal" required>
    <input type="text" name="country" placeholder="Pais" required>
    <input type="tel" name="phone" placeholder="Telefono">
    <input type="email" name="email" placeholder="Correo">
    <label>Asigne a un proyecto:</label>
    <select name="project_id" required>
        <option value="">Seleccione un proyecto</option>
        <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['keyword']); ?></option>
        <?php endforeach; ?>
    </select><br>

    <label>Archivos (imágenes o documentos):</label>
    <input type="file" name="files[]" multiple>

    <input type="submit" value="Crear registro">
</form>