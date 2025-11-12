<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config/config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario  = trim($_POST["usuario"]);
    $nombre   = trim($_POST["nombre"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

    $sql = "INSERT INTO QRusuarios (usuario, password, nombre) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error SQL: " . $conn->error);
    }

    $stmt->bind_param("sss", $usuario, $password, $nombre);

    if ($stmt->execute()) {
        // ✅ Redirección coherente con login.php
        header("Location: index.php?url=login&success=1");
        exit;
    } else {
        $error = "Error al registrar: " . $conn->error;
    }
}
?>

<?php include "app/views/layout/header.php"; ?>

<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card p-4 shadow">
      <h3 class="text-center mb-3">Registro de Usuario</h3>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text" name="usuario" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Registrar</button>
      </form>

      <p class="mt-3 text-center">
        ¿Ya tienes cuenta?
        <a href="index.php?url=login">Inicia sesión</a>
      </p>
    </div>
  </div>
</div>

<?php include "app/views/layout/footer.php"; ?>
