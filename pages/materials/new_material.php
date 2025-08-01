<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('materials', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

// Obtener categorías desde la base de datos
$categories = [];
$stmt = $pdo->query("SELECT id, name FROM material_categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener unidades de medida
$unidades = [];
$stmt = $pdo->query("SELECT id, name, abbreviation FROM measurement_units ORDER BY name ASC");
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_code = trim($_POST['material_code']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : 1;

    $has_serial = isset($_POST['has_serial']) ? 1 : 0;
    $state = 'active';

    // Validación básica
    if (empty($material_code) || empty($name) || empty($description) || empty($category_id)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        // Verificar si el material_code ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE material_code = ?");
        $stmt->execute([$material_code]);
        if ($stmt->fetchColumn() > 0) {
            $error = "El código de material '$material_code' ya existe.";
        } else {
            // Insertar material
            $stmt = $pdo->prepare("INSERT INTO materials (material_code, name, description, category_id, unit_id, has_serial, state) VALUES (?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$material_code, $name, $description, $category_id, $unit_id, $has_serial, $state]);
                $materialId = $pdo->lastInsertId();

                // Subida de archivos
                if (!empty($_FILES['files']['name'][0])) {
                    $uploadBase = $_SERVER['DOCUMENT_ROOT'] . "/main/assets/uploads/materials/$materialId/";
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
                            $relativePath = "/main/assets/uploads/materials/$materialId/$uniqueName";

                            move_uploaded_file($tmpName, $destination);

                            $insertFile = $pdo->prepare("INSERT INTO material_files (material_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
                            $insertFile->execute([$materialId, $originalName, $relativePath, $fileType]);
                        }
                    }
                }

                header('Location: /main/index.php?page=materials');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al crear el material: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-header">
    <h2>Nuevo Material</h2>
    <a class="delete-button" href="/main/index.php?page=materials"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Código de material:</label>
    <input type="text" name="material_code" placeholder="Código único del material" required>
    
    <label>Nombre:</label>
    <input type="text" name="name" placeholder="Nombre del material" required>

    <label>Descripción:</label>
    <textarea name="description" placeholder="Descripción del material" required></textarea>

    <label>Categoría:</label>
    <select name="category_id" required>
        <option value="">Seleccione una categoría</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <label>Medida:</label>
    <select name="unit_id" required>
        <?php foreach ($unidades as $unit): ?>
            <option value="<?php echo $unit['id']; ?>" <?php echo $unit['id'] == 1 ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($unit['name']) . " ({$unit['abbreviation']})"; ?>
            </option>
        <?php endforeach; ?>
    </select><br>

    <label>
        <input type="checkbox" name="has_serial" value="1"> ¿Tiene número de serie?
    </label><br>

    <label>Archivos (imágenes o documentos):</label>
    <input type="file" name="files[]" multiple>

    <input type="submit" value="Crear Material">
</form>