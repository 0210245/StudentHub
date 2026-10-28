<?php
/**
 * Practical 13: RESTful PHP JSON API for Events
 * Implements HTTP Status Codes: 200 (OK), 201 (Created), 400 (Bad Request), 404 (Not Found), 500 (Internal Error)
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../../Practical 8/config.php';

$conn = getMySQLiConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failure."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper to send standardized JSON response
function send_response($status_code, $success, $message, $data = null) {
    http_response_code($status_code);
    echo json_encode([
        "status"    => $success ? "success" : "error",
        "code"      => $status_code,
        "message"   => $message,
        "data"      => $data,
        "timestamp" => date('c')
    ]);
    exit;
}

// 1. GET: Retrieve all events or single event by ID
if ($method === 'GET') {
    $id = trim($_GET['id'] ?? '');
    $search = trim($_GET['search'] ?? '');

    if (!empty($id)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE event_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($event) {
            send_response(200, true, "Event retrieved.", $event);
        } else {
            send_response(404, false, "Event not found with ID '{$id}'.");
        }
    } else {
        $sql = "SELECT * FROM events";
        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE event_name LIKE ? OR description LIKE ? OR event_type LIKE ?";
            $s = "%{$search}%";
            $params = [$s, $s, $s];
        }
        $sql .= " ORDER BY event_date ASC";

        $stmt = mysqli_prepare($conn, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, "sss", ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $events = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $events[] = $row;
        }
        mysqli_stmt_close($stmt);
        send_response(200, true, "Events list retrieved.", $events);
    }
}

// Read JSON or POST body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// 2. POST: Create New Event
if ($method === 'POST' && empty($input['_method'])) {
    $id    = trim($input['event_id'] ?? '');
    $name  = trim($input['event_name'] ?? '');
    $date  = trim($input['event_date'] ?? '');
    $venue = trim($input['event_venue'] ?? 'Campus Audi');
    $type  = trim($input['event_type'] ?? 'Technical');
    $coord = trim($input['coordinator'] ?? 'Faculty Coordinator');
    $desc  = trim($input['description'] ?? '');

    if (empty($id) || empty($name) || empty($date)) {
        send_response(400, false, "Missing required fields: event_id, event_name, and event_date are mandatory.");
    }

    $stmt = mysqli_prepare($conn, 
        "INSERT INTO events (event_id, event_name, event_date, event_venue, event_type, coordinator, description, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 'open')"
    );
    mysqli_stmt_bind_param($stmt, "sssssss", $id, $name, $date, $venue, $type, $coord, $desc);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        send_response(201, true, "Event '{$name}' created successfully.", ["event_id" => $id]);
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        send_response(400, false, "Failed to create event: {$err}");
    }
}

// 3. PUT or POST with _method=PUT: Update Event
if ($method === 'PUT' || ($method === 'POST' && ($input['_method'] ?? '') === 'PUT')) {
    $id    = trim($input['event_id'] ?? '');
    $name  = trim($input['event_name'] ?? '');
    $date  = trim($input['event_date'] ?? '');
    $venue = trim($input['event_venue'] ?? '');
    $type  = trim($input['event_type'] ?? '');
    $coord = trim($input['coordinator'] ?? '');
    $desc  = trim($input['description'] ?? '');

    if (empty($id) || empty($name) || empty($date)) {
        send_response(400, false, "Missing required fields for update.");
    }

    $stmt = mysqli_prepare($conn, 
        "UPDATE events SET event_name = ?, event_date = ?, event_venue = ?, event_type = ?, coordinator = ?, description = ? WHERE event_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sssssss", $name, $date, $venue, $type, $coord, $desc, $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        send_response(200, true, "Event '{$id}' updated successfully.");
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        send_response(500, false, "Update error: {$err}");
    }
}

// 4. DELETE or POST with _method=DELETE: Delete Event
if ($method === 'DELETE' || ($method === 'POST' && ($input['_method'] ?? '') === 'DELETE')) {
    $id = trim($_GET['id'] ?? $input['event_id'] ?? '');

    if (empty($id)) {
        send_response(400, false, "Event ID is required for deletion.");
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        send_response(200, true, "Event '{$id}' deleted asynchronously.");
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        send_response(500, false, "Deletion error: {$err}");
    }
}

send_response(405, false, "Method Not Allowed.");
