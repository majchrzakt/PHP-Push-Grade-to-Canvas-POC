<?php
$basePath = isset($_SERVER['SCRIPT_FILENAME']) ? dirname($_SERVER['SCRIPT_FILENAME']) : getcwd();
require_once $basePath . '/env.php';
require_once $basePath . '/push_grade_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

loadEnv();

function loadStudentLookup($csvPath)
{
    if (!is_readable($csvPath)) {
        return array();
    }

    $handle = fopen($csvPath, 'r');
    if ($handle === false) {
        return array();
    }

    $headers = array();
    $lookup = array();
    $row = 0;

    while (($data = fgetcsv($handle)) !== false) {
        $row++;

        if ($row === 1) {
            $headers = array_map('trim', $data);
            continue;
        }

        if (count($data) < 2) {
            continue;
        }

        $record = array();
        foreach ($data as $index => $value) {
            $key = isset($headers[$index]) ? $headers[$index] : 'col_' . $index;
            $record[$key] = trim($value);
        }

        $email = strtolower(trim(isset($record['email']) ? $record['email'] : ''));
        if ($email === '') {
            continue;
        }

        $canvasId = '';
        if (isset($record['canvas_id']) && $record['canvas_id'] !== '') {
            $canvasId = $record['canvas_id'];
        } elseif (isset($record['canvasid']) && $record['canvasid'] !== '') {
            $canvasId = $record['canvasid'];
        }

        $userId = $email;
        if (isset($record['user_id']) && $record['user_id'] !== '') {
            $userId = $record['user_id'];
        } elseif (isset($record['userid']) && $record['userid'] !== '') {
            $userId = $record['userid'];
        } elseif ($canvasId !== '') {
            $userId = $canvasId;
        }

        $lookup[$email] = array(
            'email' => $email,
            'canvas_id' => $canvasId,
            'user_id' => $userId,
        );
    }

    fclose($handle);
    return $lookup;
}

$error = '';
$studentLookup = loadStudentLookup($basePath . '/students.csv');
$csvError = (count($studentLookup) === 0) ? 'Unable to read students.csv. Login is unavailable until the file is present and valid.' : '';

$welcomeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error = 'Please enter a valid school email address.';
    } elseif (count($studentLookup) === 0) {
        $error = 'Student lookup data is missing or invalid. Please contact your administrator.';
    } else {
        $emailKey = strtolower(trim($email));
        $student = isset($studentLookup[$emailKey]) ? $studentLookup[$emailKey] : null;

        if ($student === null) {
            $error = 'No account was found for that email address.';
        } else {
            session_regenerate_id(true);
            $_SESSION['email'] = $student['email'];
            $_SESSION['canvas_id'] = $student['canvas_id'];
            $_SESSION['user_id'] = $student['user_id'];
            $_SESSION['logged_at'] = time();

            $gradeValue = getEnvValue('CANVAS_GRADE');
            if ($gradeValue === null || $gradeValue === '') {
                $gradeValue = 'unknown';
                $gradeMessage = 'Grade is not configured in .env.';
            } else {
                $pushResult = pushGradeToCanvas($student['canvas_id'], $gradeValue);
                if ($pushResult['success']) {
                    $gradeMessage = sprintf('Your grade of %s out of 10 on this task has been posted.', htmlspecialchars($gradeValue));
                } else {
                    $gradeMessage = sprintf('Failed to post grade: %s', htmlspecialchars($pushResult['message']));
                }
            }

            $_SESSION['grade_message'] = $gradeMessage;
            $_SESSION['show_welcome_message'] = true;
            header('Location: simple_login.php?welcome=1');
            exit;
        }
    }
} elseif (!empty($_GET['welcome']) && !empty($_SESSION['show_welcome_message']) && !empty($_SESSION['email']) && !empty($_SESSION['user_id'])) {
    $gradeMessage = isset($_SESSION['grade_message']) ? $_SESSION['grade_message'] : '';
    $userId = htmlspecialchars($_SESSION['user_id']);
    $welcomeMessage = sprintf('Hello, %s! %s', $userId, $gradeMessage);
    unset($_SESSION['show_welcome_message'], $_SESSION['grade_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Simple Login</title>
</head>
<body>
    <h1>Sign in with your school email</h1>
    <?php if ($welcomeMessage): ?>
        <p style="color: green;"><?= htmlspecialchars($welcomeMessage) ?></p>
    <?php endif; ?>
    <?php if ($csvError): ?>
        <p style="color: darkred;"><?= htmlspecialchars($csvError) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Email:<br>
            <input type="email" name="email" required>
        </label>
        <br><br>
        <button type="submit">Sign in</button>
    </form>
</body>
</html>
