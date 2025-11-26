<?php
// ========================================
// SIEA - Sistema Integral para Estudiantes y Administradores
// Archivo principal index.php
// ========================================

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ocultar errores en producción
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'Admin123');
define('DB_PASS', '1234');
define('DB_NAME', 'siea_db');

// Conexión a la base de datos
$conn = null;

function conectarDB() {
    global $conn;
    if ($conn === null || !$conn->ping()) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Error de conexión: " . $conn->connect_error);
            return false;
        }
        $conn->set_charset("utf8mb4");
    }
    return true;
}

// Procesar solicitudes API
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? $_POST['action'];
    
    if (!conectarDB()) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    switch ($action) {
        // ============ LOGIN ============
        case 'login_admin':
            $usuario = $conn->real_escape_string($_POST['usuario']);
            $password = $conn->real_escape_string($_POST['password']);
            
            $sql = "SELECT * FROM usuarios_admin WHERE usuario = '$usuario' AND password = '$password'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'nombre' => $user['nombre_completo'],
                        'rol' => $user['rol']
                    ]
                ]);
            } else {
                $check = $conn->query("SELECT * FROM usuarios_admin WHERE usuario = '$usuario'");
                if ($check && $check->num_rows > 0) {
                    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                }
            }
            break;
            
        case 'login_student':
            $numero_control = $conn->real_escape_string($_POST['numero_control']);
            $fecha_nacimiento = $conn->real_escape_string($_POST['fecha_nacimiento']);
            
            $sql = "SELECT a.*, e.nombre as especialidad_nombre 
                    FROM alumnos a 
                    LEFT JOIN especialidades e ON a.especialidad_id = e.id 
                    WHERE a.numero_control = '$numero_control' 
                    AND a.fecha_nacimiento = '$fecha_nacimiento'
                    AND a.activo = 1";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $student = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'student' => [
                        'id' => $student['id'],
                        'nombre' => $student['nombre_completo'],
                        'numero_control' => $student['numero_control'],
                        'especialidad' => $student['especialidad_nombre'],
                        'semestre' => $student['semestre_actual']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
            }
            break;
        
        // ============ ALUMNOS ============
        case 'get_students':
            $sql = "SELECT a.*, e.nombre as especialidad_nombre 
                    FROM alumnos a 
                    LEFT JOIN especialidades e ON a.especialidad_id = e.id 
                    WHERE a.activo = 1 
                    ORDER BY a.nombre_completo";
            $result = $conn->query($sql);
            
            $students = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $students]);
            break;
            
        case 'add_student':
            $numero_control = $conn->real_escape_string($_POST['numero_control']);
            $nombre = $conn->real_escape_string($_POST['nombre_completo']);
            $fecha_nac = $conn->real_escape_string($_POST['fecha_nacimiento']);
            $email = $conn->real_escape_string($_POST['email'] ?? '');
            $telefono = $conn->real_escape_string($_POST['telefono'] ?? '');
            $direccion = $conn->real_escape_string($_POST['direccion'] ?? '');
            $especialidad_id = !empty($_POST['especialidad_id']) ? intval($_POST['especialidad_id']) : 'NULL';
            $semestre = intval($_POST['semestre_actual'] ?? 1);
            
            $sql = "INSERT INTO alumnos (numero_control, nombre_completo, fecha_nacimiento, email, telefono, direccion, especialidad_id, semestre_actual) 
                    VALUES ('$numero_control', '$nombre', '$fecha_nac', '$email', '$telefono', '$direccion', $especialidad_id, $semestre)";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Alumno agregado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agregar alumno: ' . $conn->error]);
            }
            break;
            
        case 'update_student':
            $id = intval($_POST['id']);
            $numero_control = $conn->real_escape_string($_POST['numero_control']);
            $nombre = $conn->real_escape_string($_POST['nombre_completo']);
            $fecha_nac = $conn->real_escape_string($_POST['fecha_nacimiento']);
            $email = $conn->real_escape_string($_POST['email'] ?? '');
            $telefono = $conn->real_escape_string($_POST['telefono'] ?? '');
            $direccion = $conn->real_escape_string($_POST['direccion'] ?? '');
            $especialidad_id = !empty($_POST['especialidad_id']) ? intval($_POST['especialidad_id']) : 'NULL';
            $semestre = intval($_POST['semestre_actual'] ?? 1);
            
            $sql = "UPDATE alumnos SET 
                    numero_control = '$numero_control',
                    nombre_completo = '$nombre',
                    fecha_nacimiento = '$fecha_nac',
                    email = '$email',
                    telefono = '$telefono',
                    direccion = '$direccion',
                    especialidad_id = $especialidad_id,
                    semestre_actual = $semestre
                    WHERE id = $id";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Alumno actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar alumno: ' . $conn->error]);
            }
            break;
            
        case 'delete_student':
            $id = intval($_POST['id']);
            $sql = "UPDATE alumnos SET activo = 0 WHERE id = $id";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Alumno eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar alumno']);
            }
            break;
        
        // ============ ESPECIALIDADES ============
        case 'get_specialties':
            $sql = "SELECT * FROM especialidades WHERE activo = 1 ORDER BY nombre";
            $result = $conn->query($sql);
            
            $specialties = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $specialties[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $specialties]);
            break;
            
        case 'add_specialty':
            $nombre = $conn->real_escape_string($_POST['nombre']);
            $descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');
            
            $sql = "INSERT INTO especialidades (nombre, descripcion) VALUES ('$nombre', '$descripcion')";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Especialidad agregada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agregar especialidad']);
            }
            break;
        
        // ============ DOCENTES ============
        case 'get_teachers':
            $sql = "SELECT * FROM docentes WHERE activo = 1 ORDER BY nombre_completo";
            $result = $conn->query($sql);
            
            $teachers = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $teachers[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $teachers]);
            break;
            
        case 'add_teacher':
            $nombre = $conn->real_escape_string($_POST['nombre_completo']);
            $email = $conn->real_escape_string($_POST['email'] ?? '');
            $telefono = $conn->real_escape_string($_POST['telefono'] ?? '');
            $especialidad = $conn->real_escape_string($_POST['especialidad'] ?? '');
            $horario = $conn->real_escape_string($_POST['horario_disponibilidad'] ?? '');
            
            $sql = "INSERT INTO docentes (nombre_completo, email, telefono, especialidad, horario_disponibilidad) 
                    VALUES ('$nombre', '$email', '$telefono', '$especialidad', '$horario')";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Docente agregado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agregar docente']);
            }
            break;
        
        // ============ MATERIAS ============
        case 'get_subjects':
            $sql = "SELECT m.*, e.nombre as especialidad_nombre 
                    FROM materias m 
                    LEFT JOIN especialidades e ON m.especialidad_id = e.id 
                    WHERE m.activo = 1 
                    ORDER BY m.nombre";
            $result = $conn->query($sql);
            
            $subjects = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $subjects[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $subjects]);
            break;
        
        // ============ CALIFICACIONES ============
        case 'get_grades':
            $alumno_id = isset($_GET['alumno_id']) ? intval($_GET['alumno_id']) : 0;
            
            if ($alumno_id > 0) {
                $sql = "SELECT c.*, m.nombre as materia_nombre, d.nombre_completo as docente_nombre 
                        FROM calificaciones c 
                        INNER JOIN materias m ON c.materia_id = m.id 
                        LEFT JOIN docentes d ON c.docente_id = d.id 
                        WHERE c.alumno_id = $alumno_id 
                        ORDER BY c.fecha_registro DESC";
            } else {
                $sql = "SELECT c.*, a.nombre_completo as alumno_nombre, a.numero_control, 
                        m.nombre as materia_nombre, d.nombre_completo as docente_nombre 
                        FROM calificaciones c 
                        INNER JOIN alumnos a ON c.alumno_id = a.id 
                        INNER JOIN materias m ON c.materia_id = m.id 
                        LEFT JOIN docentes d ON c.docente_id = d.id 
                        ORDER BY c.fecha_registro DESC 
                        LIMIT 100";
            }
            
            $result = $conn->query($sql);
            $grades = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $grades[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $grades]);
            break;
            
        case 'add_grade':
            $alumno_id = intval($_POST['alumno_id']);
            $materia_id = intval($_POST['materia_id']);
            $docente_id = !empty($_POST['docente_id']) ? intval($_POST['docente_id']) : 'NULL';
            $calificacion = floatval($_POST['calificacion']);
            $periodo = $conn->real_escape_string($_POST['periodo']);
            
            $sql = "INSERT INTO calificaciones (alumno_id, materia_id, docente_id, calificacion, periodo) 
                    VALUES ($alumno_id, $materia_id, $docente_id, $calificacion, '$periodo')";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Calificación registrada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al registrar calificación']);
            }
            break;
        
        // ============ EVENTOS ============
        case 'get_events':
            $sql = "SELECT * FROM eventos WHERE activo = 1 ORDER BY fecha_evento DESC";
            $result = $conn->query($sql);
            
            $events = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $events[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $events]);
            break;
            
        case 'add_event':
            $titulo = $conn->real_escape_string($_POST['titulo']);
            $descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');
            $fecha = $conn->real_escape_string($_POST['fecha_evento']);
            $hora = $conn->real_escape_string($_POST['hora_evento'] ?? '');
            $lugar = $conn->real_escape_string($_POST['lugar'] ?? '');
            $tipo = $conn->real_escape_string($_POST['tipo'] ?? 'otro');
            
            $sql = "INSERT INTO eventos (titulo, descripcion, fecha_evento, hora_evento, lugar, tipo) 
                    VALUES ('$titulo', '$descripcion', '$fecha', '$hora', '$lugar', '$tipo')";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Evento creado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear evento']);
            }
            break;
        
        // ============ CITAS ============
        case 'get_appointments':
            $alumno_id = isset($_GET['alumno_id']) ? intval($_GET['alumno_id']) : 0;
            
            if ($alumno_id > 0) {
                $sql = "SELECT c.*, d.nombre_completo as docente_nombre 
                        FROM citas c 
                        LEFT JOIN docentes d ON c.docente_id = d.id 
                        WHERE c.alumno_id = $alumno_id 
                        ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
            } else {
                $sql = "SELECT c.*, a.nombre_completo as alumno_nombre, a.numero_control, 
                        d.nombre_completo as docente_nombre 
                        FROM citas c 
                        INNER JOIN alumnos a ON c.alumno_id = a.id 
                        LEFT JOIN docentes d ON c.docente_id = d.id 
                        ORDER BY c.fecha_cita DESC, c.hora_cita DESC 
                        LIMIT 100";
            }
            
            $result = $conn->query($sql);
            $appointments = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $appointments[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $appointments]);
            break;
            
        case 'add_appointment':
            $alumno_id = intval($_POST['alumno_id']);
            $docente_id = !empty($_POST['docente_id']) ? intval($_POST['docente_id']) : 'NULL';
            $fecha = $conn->real_escape_string($_POST['fecha_cita']);
            $hora = $conn->real_escape_string($_POST['hora_cita']);
            $motivo = $conn->real_escape_string($_POST['motivo'] ?? '');
            
            $sql = "INSERT INTO citas (alumno_id, docente_id, fecha_cita, hora_cita, motivo) 
                    VALUES ($alumno_id, $docente_id, '$fecha', '$hora', '$motivo')";
            
            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Cita agendada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agendar cita']);
            }
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIEA - Sistema Integral para Estudiantes y Administradores</title>
        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --gray-900: #111827;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-900);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Página de inicio */
        #home-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .home-content {
            text-align: center;
            color: white;
        }
        
        .home-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .home-content p {
            font-size: 1.25rem;
            margin-bottom: 3rem;
            opacity: 0.95;
        }
        
        .role-cards {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .role-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 280px;
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .role-card h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.75rem;
        }
        
        .role-card p {
            color: var(--gray-700);
            margin-bottom: 1.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #059669;
        }
        
        /* Páginas de login */
        .login-page {
            display: none;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-page.active {
            display: flex;
        }
        
        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .login-box h2 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .login-box p {
            color: var(--gray-700);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Dashboard */
        .dashboard {
            display: none;
            background: var(--gray-50);
            min-height: 100vh;
        }
        
        .dashboard.active {
            display: block;
        }
        
        .dashboard-header {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-header h1 {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-logout {
            background: var(--danger);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-logout:hover {
            background: #dc2626;
        }
        
        .dashboard-content {
            display: flex;
            gap: 0;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            min-height: calc(100vh - 80px);
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 1rem 0;
        }
        
        .sidebar-nav li {
            padding: 0;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 1rem 1.5rem;
            color: var(--gray-700);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav a:hover {
            background: var(--gray-50);
            border-left-color: var(--primary);
            color: var(--primary);
        }
        
        .sidebar-nav a.active {
            background: var(--gray-50);
            border-left-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        
        .module-section {
            display: none;
        }
        
        .module-section.active {
            display: block;
        }
        
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .module-header h2 {
            color: var(--gray-900);
            font-size: 1.75rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            opacity: 0.9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: var(--gray-100);
        }
        
        table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
        }
        
        table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        table tbody tr:hover {
            background: var(--gray-50);
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.875rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            margin-right: 0.5rem;
        }
        
        .btn-edit {
            background: var(--warning);
            color: white;
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
        }
        
        .btn-success {
            background: var(--secondary);
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            color: var(--gray-900);
            font-size: 1.5rem;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-700);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .hidden {
            display: none !important;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                min-height: auto;
            }
            
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
            }
            
            .sidebar-nav li {
                flex-shrink: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .role-cards {
                flex-direction: column;
            }
            
            .home-content h1 {
                font-size: 2rem;
            }
        }
    </style>

</head>
<body>

<!-- Página de inicio -->
<div id="home-page">
    <div class="home-content">
        <h1>SIEA</h1>
        <p>Sistema Integral para Estudiantes y Administradores</p>
        <div class="role-cards">
            <div class="role-card" onclick="showPage('admin-login')">
                <h2>Administrador</h2>
                <p>Gestiona alumnos, docentes, calificaciones y eventos</p>
                <button class="btn btn-primary">Acceder</button>
            </div>
            <div class="role-card" onclick="showPage('student-login')">
                <h2>Estudiante</h2>
                <p>Consulta calificaciones, eventos y agenda citas</p>
                <button class="btn btn-secondary">Acceder</button>
            </div>
        </div>
    </div>
</div>

<!-- Login Administrador -->
<div id="admin-login" class="login-page">
    <div class="login-box">
        <h2>Administrador</h2>
        <p>Ingresa tus credenciales</p>
        <form id="admin-login-form">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Iniciar Sesión</button>
        </form>
        <a href="#" class="back-link" onclick="showPage('home'); return false;">← Volver al inicio</a>
    </div>
</div>

<!-- Login Estudiante -->
<div id="student-login" class="login-page">
    <div class="login-box">
        <h2>Estudiante</h2>
        <p>Ingresa tus credenciales</p>
        <form id="student-login-form">
            <div class="form-group">
                <label>Número de Control</label>
                <input type="text" name="numero_control" required>
            </div>
            <div class="form-group">
                <label>Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" required>
            </div>
            <button type="submit" class="btn btn-secondary" style="width: 100%;">Iniciar Sesión</button>
        </form>
        <a href="#" class="back-link" onclick="showPage('home'); return false;">← Volver al inicio</a>
    </div>
</div>

<!-- Dashboard Administrador -->
<div id="admin-dashboard" class="dashboard">
    <div class="dashboard-header">
        <h1>Panel de Administración - SIEA</h1>
        <div class="user-info">
            <span id="admin-name">Administrador</span>
            <button class="btn-logout" onclick="logout()">Cerrar Sesión</button>
        </div>
    </div>
    <div class="dashboard-content">
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="#" class="active" onclick="showModule('overview'); return false;">Resumen</a></li>
                <li><a href="#" onclick="showModule('students'); return false;">Alumnos</a></li>
                <li><a href="#" onclick="showModule('teachers'); return false;">Docentes</a></li>
                <li><a href="#" onclick="showModule('grades'); return false;">Calificaciones</a></li>
                <li><a href="#" onclick="showModule('events'); return false;">Eventos</a></li>
                <li><a href="#" onclick="showModule('appointments'); return false;">Citas</a></li>
                <li><a href="#" onclick="showModule('specialties'); return false;">Especialidades</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <!-- Módulo Resumen -->
            <section id="module-overview" class="module-section active">
                <h2>Resumen General</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="stat-students">0</h3>
                        <p>Alumnos Activos</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="stat-teachers">0</h3>
                        <p>Docentes</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="stat-events">0</h3>
                        <p>Eventos Próximos</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="stat-appointments">0</h3>
                        <p>Citas Pendientes</p>
                    </div>
                </div>
            </section>
            
            <!-- Módulo Alumnos -->
            <section id="module-students" class="module-section">
                <div class="module-header">
                    <h2>Gestión de Alumnos</h2>
                    <button class="btn btn-primary" onclick="openModal('student-modal')">+ Agregar Alumno</button>
                </div>
                <div class="card">
                    <table id="students-table">
                        <thead>
                            <tr>
                                <th>No. Control</th>
                                <th>Nombre</th>
                                <th>Especialidad</th>
                                <th>Semestre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Docentes -->
            <section id="module-teachers" class="module-section">
                <div class="module-header">
                    <h2>Gestión de Docentes</h2>
                    <button class="btn btn-primary" onclick="openModal('teacher-modal')">+ Agregar Docente</button>
                </div>
                <div class="card">
                    <table id="teachers-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Especialidad</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Calificaciones -->
            <section id="module-grades" class="module-section">
                <div class="module-header">
                    <h2>Gestión de Calificaciones</h2>
                    <button class="btn btn-primary" onclick="openModal('grade-modal')">+ Registrar Calificación</button>
                </div>
                <div class="card">
                    <table id="grades-table">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>No. Control</th>
                                <th>Materia</th>
                                <th>Calificación</th>
                                <th>Periodo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Eventos -->
            <section id="module-events" class="module-section">
                <div class="module-header">
                    <h2>Gestión de Eventos</h2>
                    <button class="btn btn-primary" onclick="openModal('event-modal')">+ Crear Evento</button>
                </div>
                <div class="card">
                    <table id="events-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Lugar</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Citas -->
            <section id="module-appointments" class="module-section">
                <div class="module-header">
                    <h2>Gestión de Citas</h2>
                    <!-- Added button to create appointments -->
                    <button class="btn btn-primary" onclick="openModal('appointment-modal')">+ Agregar Cita</button>
                </div>
                <div class="card">
                    <table id="appointments-table">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Docente</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Especialidades -->
            <section id="module-specialties" class="module-section">
                <div class="module-header">
                    <h2>Gestión de Especialidades</h2>
                    <button class="btn btn-primary" onclick="openModal('specialty-modal')">+ Agregar Especialidad</button>
                </div>
                <div class="card">
                    <table id="specialties-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<!-- Dashboard Estudiante -->
<div id="student-dashboard" class="dashboard">
    <div class="dashboard-header">
        <h1>Portal del Estudiante - SIEA</h1>
        <div class="user-info">
            <span id="student-name">Estudiante</span>
            <button class="btn-logout" onclick="logout()">Cerrar Sesión</button>
        </div>
    </div>
    <div class="dashboard-content">
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="#" class="active" onclick="showModuleStudent('student-overview'); return false;">Mi Perfil</a></li>
                <li><a href="#" onclick="showModuleStudent('student-grades'); return false;">Mis Calificaciones</a></li>
                <li><a href="#" onclick="showModuleStudent('student-events'); return false;">Eventos</a></li>
                <li><a href="#" onclick="showModuleStudent('student-appointments'); return false;">Mis Citas</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <!-- Módulo Perfil Estudiante -->
            <section id="module-student-overview" class="module-section active">
                <h2>Mi Perfil</h2>
                <div class="card">
                    <h3>Información Personal</h3>
                    <p><strong>Nombre:</strong> <span id="student-info-name"></span></p>
                    <p><strong>Número de Control:</strong> <span id="student-info-control"></span></p>
                    <p><strong>Especialidad:</strong> <span id="student-info-specialty"></span></p>
                    <p><strong>Semestre:</strong> <span id="student-info-semester"></span></p>
                </div>
            </section>
            
            <!-- Módulo Calificaciones Estudiante -->
            <section id="module-student-grades" class="module-section">
                <h2>Mis Calificaciones</h2>
                <div class="card">
                    <table id="student-grades-table">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>Docente</th>
                                <th>Calificación</th>
                                <th>Periodo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Eventos Estudiante -->
            <section id="module-student-events" class="module-section">
                <h2>Eventos</h2>
                <div class="card">
                    <table id="student-events-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Lugar</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Módulo Citas Estudiante -->
            <section id="module-student-appointments" class="module-section">
                <div class="module-header">
                    <h2>Mis Citas</h2>
                    <button class="btn btn-primary" onclick="openModal('student-appointment-modal')">+ Agendar Cita</button>
                </div>
                <div class="card">
                    <table id="student-appointments-table">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<!-- Modal Alumno -->
<div id="student-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Agregar/Editar Alumno</h3>
            <button class="close-modal" onclick="closeModal('student-modal')">&times;</button>
        </div>
        <form id="student-form">
            <input type="hidden" name="id">
            <div class="form-group">
                <label>Número de Control *</label>
                <input type="text" name="numero_control" required>
            </div>
            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="nombre_completo" required>
            </div>
            <div class="form-group">
                <label>Fecha de Nacimiento *</label>
                <input type="date" name="fecha_nacimiento" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono">
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion">
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <select name="especialidad_id" id="student-specialty-select">
                    <option value="">Seleccionar...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Semestre *</label>
                <input type="number" name="semestre_actual" min="1" max="6" value="1" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal Docente -->
<div id="teacher-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Agregar Docente</h3>
            <button class="close-modal" onclick="closeModal('teacher-modal')">&times;</button>
        </div>
        <form id="teacher-form">
            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="nombre_completo" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono">
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad">
            </div>
            <div class="form-group">
                <label>Horario de Disponibilidad</label>
                <textarea name="horario_disponibilidad" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal Calificación -->
<div id="grade-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Registrar Calificación</h3>
            <button class="close-modal" onclick="closeModal('grade-modal')">&times;</button>
        </div>
        <form id="grade-form">
            <div class="form-group">
                <label>Alumno *</label>
                <select name="alumno_id" required id="grade-student-select">
                    <option value="">Seleccionar alumno...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Materia *</label>
                <select name="materia_id" required id="grade-subject-select">
                    <option value="">Seleccionar materia...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Docente</label>
                <select name="docente_id" id="grade-teacher-select">
                    <option value="">Seleccionar docente...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Calificación *</label>
                <input type="number" name="calificacion" min="0" max="10" step="0.1" required>
            </div>
            <div class="form-group">
                <label>Periodo *</label>
                <input type="text" name="periodo" placeholder="Ej: 2024-1" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal Evento -->
<div id="event-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Crear Evento</h3>
            <button class="close-modal" onclick="closeModal('event-modal')">&times;</button>
        </div>
        <form id="event-form">
            <div class="form-group">
                <label>Título *</label>
                <input type="text" name="titulo" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Fecha *</label>
                <input type="date" name="fecha_evento" required>
            </div>
            <div class="form-group">
                <label>Hora</label>
                <input type="time" name="hora_evento">
            </div>
            <div class="form-group">
                <label>Lugar</label>
                <input type="text" name="lugar">
            </div>
            <div class="form-group">
                <label>Tipo *</label>
                <select name="tipo" required>
                    <option value="academico">Académico</option>
                    <option value="cultural">Cultural</option>
                    <option value="deportivo">Deportivo</option>
                    <option value="social">Social</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal Especialidad -->
<div id="specialty-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Agregar Especialidad</h3>
            <button class="close-modal" onclick="closeModal('specialty-modal')">&times;</button>
        </div>
        <form id="specialty-form">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal Cita Estudiante -->
<div id="student-appointment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Agendar Cita</h3>
            <button class="close-modal" onclick="closeModal('student-appointment-modal')">&times;</button>
        </div>
        <form id="student-appointment-form">
            <div class="form-group">
                <label>Docente *</label>
                <select name="docente_id" required id="appointment-teacher-select">
                    <option value="">Seleccionar docente...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha *</label>
                <input type="date" name="fecha_cita" required>
            </div>
            <div class="form-group">
                <label>Hora *</label>
                <input type="time" name="hora_cita" required>
            </div>
            <div class="form-group">
                <label>Motivo</label>
                <textarea name="motivo" rows="3" placeholder="Describe brevemente el motivo de tu cita..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Agendar</button>
        </form>
    </div>
</div>

<!-- Modal Cita Administrador -->
<div id="appointment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Agendar Cita</h3>
            <button class="close-modal" onclick="closeModal('appointment-modal')">&times;</button>
        </div>
        <form id="appointment-form">
            <div class="form-group">
                <label>Alumno *</label>
                <select name="alumno_id" required id="appointment-student-select">
                    <option value="">Seleccionar alumno...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Docente *</label>
                <select name="docente_id" required id="appointment-teacher-select-admin">
                    <option value="">Seleccionar docente...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha *</label>
                <input type="date" name="fecha_cita" required>
            </div>
            <div class="form-group">
                <label>Hora *</label>
                <input type="time" name="hora_cita" required>
            </div>
            <div class="form-group">
                <label>Motivo</label>
                <textarea name="motivo" rows="3" placeholder="Describe brevemente el motivo de la cita..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Agendar</button>
        </form>
    </div>
</div>

<script>
// Variables globales
let currentUser = null;
let currentStudent = null;

// Navegación entre páginas
function showPage(page) {
    document.getElementById('home-page').style.display = 'none';
    document.getElementById('admin-login').classList.remove('active');
    document.getElementById('student-login').classList.remove('active');
    document.getElementById('admin-dashboard').classList.remove('active');
    document.getElementById('student-dashboard').classList.remove('active');
    
    if (page === 'home') {
        document.getElementById('home-page').style.display = 'flex';
    } else if (page === 'admin-login') {
        document.getElementById('admin-login').classList.add('active');
    } else if (page === 'student-login') {
        document.getElementById('student-login').classList.add('active');
    } else if (page === 'admin-dashboard') {
        document.getElementById('admin-dashboard').classList.add('active');
        loadAdminDashboard();
    } else if (page === 'student-dashboard') {
        document.getElementById('student-dashboard').classList.add('active');
        loadStudentDashboard();
    }
}

// Navegación entre módulos admin
function showModule(module) {
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        link.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.querySelectorAll('#admin-dashboard .module-section').forEach(section => {
        section.classList.remove('active');
    });
    
    document.getElementById('module-' + module).classList.add('active');
    
    // Cargar datos del módulo
    switch(module) {
        case 'students':
            loadStudents();
            break;
        case 'teachers':
            loadTeachers();
            break;
        case 'grades':
            loadGrades();
            break;
        case 'events':
            loadEvents();
            break;
        case 'appointments':
            loadAppointments();
            break;
        case 'specialties':
            loadSpecialties();
            break;
    }
}

// Navegación entre módulos estudiante
function showModuleStudent(module) {
    document.querySelectorAll('#student-dashboard .sidebar-nav a').forEach(link => {
        link.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.querySelectorAll('#student-dashboard .module-section').forEach(section => {
        section.classList.remove('active');
    });
    
    document.getElementById('module-' + module).classList.add('active');
    
    // Cargar datos del módulo
    switch(module) {
        case 'student-grades':
            loadStudentGrades();
            break;
        case 'student-events':
            loadStudentEvents();
            break;
        case 'student-appointments':
            loadStudentAppointments();
            break;
    }
}

// Modales
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    
    if (modalId === 'appointment-modal') {
        loadStudentsSelectForAppointment();
        loadTeachersSelectForAppointmentAdmin();
    } else if (modalId === 'student-appointment-modal') {
        loadTeachersSelectForAppointment();
    } else if (modalId === 'student-modal') {
        loadSpecialtiesSelect();
    } else if (modalId === 'grade-modal') {
        loadStudentsSelect();
        loadSubjectsSelect();
        loadTeachersSelect();
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.getElementById(modalId).querySelector('form').reset();
}

// Login Administrador
document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'login_admin');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            currentUser = result.user;
            document.getElementById('admin-name').textContent = result.user.nombre;
            showPage('admin-dashboard');
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al iniciar sesión');
    }
});

// Login Estudiante
document.getElementById('student-login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'login_student');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            currentStudent = result.student;
            document.getElementById('student-name').textContent = result.student.nombre;
            document.getElementById('student-info-name').textContent = result.student.nombre;
            document.getElementById('student-info-control').textContent = result.student.numero_control;
            document.getElementById('student-info-specialty').textContent = result.student.especialidad || 'No asignada';
            document.getElementById('student-info-semester').textContent = result.student.semestre;
            showPage('student-dashboard');
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al iniciar sesión');
    }
});

// Logout
function logout() {
    currentUser = null;
    currentStudent = null;
    showPage('home');
}

// Cargar dashboard admin
async function loadAdminDashboard() {
    try {
        // Cargar estadísticas
        const [students, teachers, events, appointments] = await Promise.all([
            fetch('index.php?action=get_students').then(r => r.json()),
            fetch('index.php?action=get_teachers').then(r => r.json()),
            fetch('index.php?action=get_events').then(r => r.json()),
            fetch('index.php?action=get_appointments').then(r => r.json())
        ]);
        
        document.getElementById('stat-students').textContent = students.data?.length || 0;
        document.getElementById('stat-teachers').textContent = teachers.data?.length || 0;
        document.getElementById('stat-events').textContent = events.data?.length || 0;
        document.getElementById('stat-appointments').textContent = 
            appointments.data?.filter(a => a.estado === 'pendiente').length || 0;
    } catch (error) {
        console.error('Error al cargar dashboard:', error);
    }
}

// Cargar alumnos
async function loadStudents() {
    try {
        const response = await fetch('index.php?action=get_students');
        const result = await response.json();
        
        const tbody = document.querySelector('#students-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(student => {
                const row = `
                    <tr>
                        <td>${student.numero_control}</td>
                        <td>${student.nombre_completo}</td>
                        <td>${student.especialidad_nombre || 'No asignada'}</td>
                        <td>${student.semestre_actual}</td>
                        <td>
                            <button class="btn-small btn-edit" onclick="editStudent(${student.id})">Editar</button>
                            <button class="btn-small btn-delete" onclick="deleteStudent(${student.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay alumnos registrados</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar alumnos:', error);
    }
}

// Guardar alumno
document.getElementById('student-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    const id = formData.get('id');
    formData.append('action', id ? 'update_student' : 'add_student');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('student-modal');
            loadStudents();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar alumno');
    }
});

// Eliminar alumno
async function deleteStudent(id) {
    if (!confirm('¿Estás seguro de eliminar este alumno?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_student');
    formData.append('id', id);
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            loadStudents();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar alumno');
    }
}

// Cargar docentes
async function loadTeachers() {
    try {
        const response = await fetch('index.php?action=get_teachers');
        const result = await response.json();
        
        const tbody = document.querySelector('#teachers-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(teacher => {
                const row = `
                    <tr>
                        <td>${teacher.nombre_completo}</td>
                        <td>${teacher.especialidad || '-'}</td>
                        <td>${teacher.email || '-'}</td>
                        <td>${teacher.telefono || '-'}</td>
                        <td>
                            <button class="btn-small btn-edit">Editar</button>
                            <button class="btn-small btn-delete">Eliminar</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay docentes registrados</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar docentes:', error);
    }
}

// Guardar docente
document.getElementById('teacher-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_teacher');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('teacher-modal');
            loadTeachers();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar docente');
    }
});

// Cargar calificaciones
async function loadGrades() {
    try {
        const response = await fetch('index.php?action=get_grades');
        const result = await response.json();
        
        const tbody = document.querySelector('#grades-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(grade => {
                const row = `
                    <tr>
                        <td>${grade.alumno_nombre}</td>
                        <td>${grade.numero_control}</td>
                        <td>${grade.materia_nombre}</td>
                        <td>${grade.calificacion}</td>
                        <td>${grade.periodo}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay calificaciones registradas</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar calificaciones:', error);
    }
}

// Guardar calificación
document.getElementById('grade-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_grade');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('grade-modal');
            loadGrades();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar calificación');
    }
});

// Cargar eventos
async function loadEvents() {
    try {
        const response = await fetch('index.php?action=get_events');
        const result = await response.json();
        
        const tbody = document.querySelector('#events-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(event => {
                const row = `
                    <tr>
                        <td>${event.titulo}</td>
                        <td>${event.fecha_evento}</td>
                        <td>${event.hora_evento || '-'}</td>
                        <td>${event.lugar || '-'}</td>
                        <td>${event.tipo}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay eventos registrados</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar eventos:', error);
    }
}

// Guardar evento
document.getElementById('event-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_event');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('event-modal');
            loadEvents();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar evento');
    }
});

// Cargar citas
async function loadAppointments() {
    try {
        const response = await fetch('index.php?action=get_appointments');
        const result = await response.json();
        
        const tbody = document.querySelector('#appointments-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(appointment => {
                const row = `
                    <tr>
                        <td>${appointment.alumno_nombre}</td>
                        <td>${appointment.docente_nombre || 'Sin asignar'}</td>
                        <td>${appointment.fecha_cita}</td>
                        <td>${appointment.hora_cita}</td>
                        <td>${appointment.estado}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay citas registradas</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar citas:', error);
    }
}

// Cargar especialidades
async function loadSpecialties() {
    try {
        const response = await fetch('index.php?action=get_specialties');
        const result = await response.json();
        
        const tbody = document.querySelector('#specialties-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(specialty => {
                const row = `
                    <tr>
                        <td>${specialty.nombre}</td>
                        <td>${specialty.descripcion || '-'}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="2" style="text-align:center">No hay especialidades registradas</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar especialidades:', error);
    }
}

// Guardar especialidad
document.getElementById('specialty-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_specialty');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('specialty-modal');
            loadSpecialties();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar especialidad');
    }
});

// Funciones auxiliares para cargar selects
async function loadSpecialtiesSelect() {
    try {
        const response = await fetch('index.php?action=get_specialties');
        const result = await response.json();
        
        const select = document.getElementById('student-specialty-select');
        select.innerHTML = '<option value="">Seleccionar...</option>';
        
        if (result.success) {
            result.data.forEach(specialty => {
                select.innerHTML += `<option value="${specialty.id}">${specialty.nombre}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadStudentsSelect() {
    try {
        const response = await fetch('index.php?action=get_students');
        const result = await response.json();
        
        const select = document.getElementById('grade-student-select');
        select.innerHTML = '<option value="">Seleccionar alumno...</option>';
        
        if (result.success) {
            result.data.forEach(student => {
                select.innerHTML += `<option value="${student.id}">${student.numero_control} - ${student.nombre_completo}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadSubjectsSelect() {
    try {
        const response = await fetch('index.php?action=get_subjects');
        const result = await response.json();
        
        const select = document.getElementById('grade-subject-select');
        select.innerHTML = '<option value="">Seleccionar materia...</option>';
        
        if (result.success) {
            result.data.forEach(subject => {
                select.innerHTML += `<option value="${subject.id}">${subject.nombre}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadTeachersSelect() {
    try {
        const response = await fetch('index.php?action=get_teachers');
        const result = await response.json();
        
        const select = document.getElementById('grade-teacher-select');
        select.innerHTML = '<option value="">Seleccionar docente...</option>';
        
        if (result.success) {
            result.data.forEach(teacher => {
                select.innerHTML += `<option value="${teacher.id}">${teacher.nombre_completo}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadTeachersSelectForAppointment() {
    try {
        const response = await fetch('index.php?action=get_teachers');
        const result = await response.json();
        
        const select = document.getElementById('appointment-teacher-select');
        select.innerHTML = '<option value="">Seleccionar docente...</option>';
        
        if (result.success) {
            result.data.forEach(teacher => {
                select.innerHTML += `<option value="${teacher.id}">${teacher.nombre_completo} - ${teacher.especialidad || ''}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Funciones del portal estudiantil
function loadStudentDashboard() {
    loadStudentGrades();
    loadStudentEvents();
    loadStudentAppointments();
}

async function loadStudentGrades() {
    if (!currentStudent) return;
    
    try {
        const response = await fetch(`index.php?action=get_grades&alumno_id=${currentStudent.id}`);
        const result = await response.json();
        
        const tbody = document.querySelector('#student-grades-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(grade => {
                const row = `
                    <tr>
                        <td>${grade.materia_nombre}</td>
                        <td>${grade.docente_nombre || '-'}</td>
                        <td>${grade.calificacion}</td>
                        <td>${grade.periodo}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center">No tienes calificaciones registradas</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar calificaciones:', error);
    }
}

async function loadStudentEvents() {
    try {
        const response = await fetch('index.php?action=get_events');
        const result = await response.json();
        
        const tbody = document.querySelector('#student-events-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(event => {
                const row = `
                    <tr>
                        <td>${event.titulo}</td>
                        <td>${event.fecha_evento}</td>
                        <td>${event.hora_evento || '-'}</td>
                        <td>${event.lugar || '-'}</td>
                        <td>${event.tipo}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay eventos próximos</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar eventos:', error);
    }
}

async function loadStudentAppointments() {
    if (!currentStudent) return;
    
    try {
        const response = await fetch(`index.php?action=get_appointments&alumno_id=${currentStudent.id}`);
        const result = await response.json();
        
        const tbody = document.querySelector('#student-appointments-table tbody');
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(appointment => {
                const row = `
                    <tr>
                        <td>${appointment.docente_nombre || 'Sin asignar'}</td>
                        <td>${appointment.fecha_cita}</td>
                        <td>${appointment.hora_cita}</td>
                        <td>${appointment.estado}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center">No tienes citas agendadas</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar citas:', error);
    }
}

// Agendar cita estudiante
document.getElementById('student-appointment-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!currentStudent) {
        alert('Error: No hay sesión de estudiante activa');
        return;
    }
    
    const formData = new FormData(e.target);
    formData.append('action', 'add_appointment');
    formData.append('alumno_id', currentStudent.id);
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('student-appointment-modal');
            loadStudentAppointments();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al agendar cita');
    }
});


async function loadStudentsSelectForAppointment() {
    try {
        const response = await fetch('index.php?action=get_students');
        const result = await response.json();
        
        const select = document.getElementById('appointment-student-select');
        select.innerHTML = '<option value="">Seleccionar alumno...</option>';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.nombre_completo} (${student.numero_control})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error al cargar alumnos:', error);
    }
}

async function loadTeachersSelectForAppointmentAdmin() {
    try {
        const response = await fetch('index.php?action=get_teachers');
        const result = await response.json();
        
        const select = document.getElementById('appointment-teacher-select-admin');
        select.innerHTML = '<option value="">Seleccionar docente...</option>';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = teacher.nombre_completo;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error al cargar docentes:', error);
    }
}

document.getElementById('appointment-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'add_appointment');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeModal('appointment-modal');
            loadAppointments();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al agendar cita');
    }
});

</script>

</body>
</html>
