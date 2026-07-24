<?php
$basePath = isset($_SERVER['SCRIPT_FILENAME']) ? dirname($_SERVER['SCRIPT_FILENAME']) : getcwd();
require_once $basePath . '/env.php';

function pushGradeToCanvas($userId, $grade)
{
    loadEnv();

    $canvasUrl = getEnvValue('CANVAS_URL');
    $token = getEnvValue('CANVAS_TOKEN');
    $courseId = getEnvValue('COURSE_ID');
    $assignmentId = getEnvValue('CANVAS_ASSIGNMENT_ID');

    if (!$canvasUrl || !$token || !$courseId || !$assignmentId) {
        return [
            'success' => false,
            'message' => 'Missing required Canvas environment configuration.',
            'details' => compact('canvasUrl', 'token', 'courseId', 'assignmentId'),
        ];
    }

    $url = rtrim($canvasUrl, '/') . "/api/v1/courses/" . urlencode($courseId) . "/assignments/" . urlencode($assignmentId) . "/submissions/" . urlencode($userId);
    $postFields = http_build_query([
        'submission' => [
            'posted_grade' => $grade,
        ],
    ]);

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: MyCanvasGradePusher/1.0 (admin@example.com)',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = null;

    if ($response === false) {
        $curlError = curl_error($ch);
    }

    curl_close($ch);

    if ($response === false) {
        return array(
            'success' => false,
            'message' => 'cURL error while pushing grade.',
            'details' => $curlError,
        );
    }

    return array(
        'success' => $httpStatus >= 200 && $httpStatus < 300,
        'status' => $httpStatus,
        'response' => $response,
        'grade' => $grade,
        'user_id' => $userId,
    );
}

