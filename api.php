<?php
// Simple Todo List API

header('Content-Type: application/json');

// Path to data file
$dataFile = __DIR__ . '/todos.json';

// Helper: Load todos
function loadTodos($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

// Helper: Save todos
function saveTodos($file, $todos) {
    file_put_contents($file, json_encode($todos, JSON_PRETTY_PRINT));
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle API routes
switch ($method) {
    case 'GET':
        // List all todos
        $todos = loadTodos($dataFile);
        echo json_encode($todos);
        break;

    case 'POST':
        // Add new todo
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['task']) || trim($input['task']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Task is required']);
            exit;
        }
        $todos = loadTodos($dataFile);
        $newTodo = [
            'id' => uniqid(),
            'task' => $input['task'],
            'done' => false
        ];
        $todos[] = $newTodo;
        saveTodos($dataFile, $todos);
        echo json_encode($newTodo);
        break;

    case 'PUT':
        // Update todo (mark as done/undone)
        parse_str(file_get_contents("php://input"), $input);
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        $todos = loadTodos($dataFile);
        foreach ($todos as &$todo) {
            if ($todo['id'] === $input['id']) {
                if (isset($input['done'])) {
                    $todo['done'] = filter_var($input['done'], FILTER_VALIDATE_BOOLEAN);
                }
                saveTodos($dataFile, $todos);
                echo json_encode($todo);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Todo not found']);
        break;

    case 'DELETE':
        // Delete todo
        parse_str(file_get_contents("php://input"), $input);
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        $todos = loadTodos($dataFile);
        $found = false;
        foreach ($todos as $i => $todo) {
            if ($todo['id'] === $input['id']) {
                array_splice($todos, $i, 1);
                $found = true;
                break;
            }
        }
        if ($found) {
            saveTodos($dataFile, $todos);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Todo not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}