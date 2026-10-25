<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/connection.php';

function passwordMatches($providedPassword, $storedPassword)
{
    return password_verify($providedPassword, $storedPassword)
        || hash_equals($storedPassword, $providedPassword);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';
$password = $data['password'] ?? '';

if ($action === 'admin_login') {
    // ... (keep your existing admin login logic here) ...
    $username = trim($data['username'] ?? '');

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Enter your admin username and password.']);
        exit;
    }

    $statement = mysqli_prepare($conn, 'SELECT id, username, password FROM admins WHERE username = ?');
    mysqli_stmt_bind_param($statement, 's', $username);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $admin = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if (!$admin || !passwordMatches($password, $admin['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid admin username or password.']);
        exit;
    }

    session_start();
    session_regenerate_id(true);
    unset($_SESSION['user_id'], $_SESSION['user_name']);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    echo json_encode(['success' => true]);
    exit;
}

$email = trim($data['email'] ?? '');
if (!in_array($action, ['login', 'signup'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Enter a valid email and password.']);
    exit;
}

if ($action === 'signup') {
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $confirmPassword = $data['confirm_password'] ?? '';

    if ($firstName === '' || $lastName === '' || strlen($password) < 8 || $password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Complete all fields and use matching passwords of at least 8 characters.']);
        exit;
    }

    // Hash the password before saving it to the database!
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $statement = mysqli_prepare($conn, 'INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($statement, 'ssss', $firstName, $lastName, $email, $hashedPassword);

    if (mysqli_stmt_execute($statement)) {
        // We do NOT start a session here, so they must log in manually.
        echo json_encode(['success' => true]);
    } else {
        http_response_code(mysqli_stmt_errno($statement) === 1062 ? 409 : 500);
        echo json_encode(['success' => false, 'error' => mysqli_stmt_errno($statement) === 1062 ? 'An account with this email already exists.' : 'Unable to create the account.']);
    }

    mysqli_stmt_close($statement);
    exit;
}

if ($action === 'login') {
    $statement = mysqli_prepare($conn, 'SELECT id, first_name, last_name, password FROM users WHERE email = ?');
    mysqli_stmt_bind_param($statement, 's', $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if (!$user || !passwordMatches($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
        exit;
    }

    session_start();
    session_regenerate_id(true);
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown authentication action.']);
?>
