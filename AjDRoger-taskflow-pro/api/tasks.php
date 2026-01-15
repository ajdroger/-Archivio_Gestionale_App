php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = __DIR__ . '/../data.json';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            getTasks();
            break;
        case 'POST':
            addTask();
            break;
        case 'PUT':
            updateTask();
            break;
        case 'DELETE':
            deleteTask();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo non consentito']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore del server: ' . $e->getMessage()]);
}

function getTasks() {
    global $dataFile;
    $tasks = json_decode(file_get_contents($dataFile), true);
    echo json_encode($tasks ?: []);
}

function addTask() {
    global $dataFile;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['text'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Testo dell\'attività mancante']);
        return;
    }

    $tasks = json_decode(file_get_contents($dataFile), true);
    
    $task = [
        'id' => $data['id'] ?? time() . rand(1000, 9999),
        'text' => htmlspecialchars($data['text']),
        'completed' => $data['completed'] ?? false,
        'created_at' => $data['created_at'] ?? date('c')
    ];
    
    $tasks[] = $task;
    file_put_contents($dataFile, json_encode($tasks, JSON_PRETTY_PRINT));
    
    http_response_code(201);
    echo json_encode(['message' => 'Attività aggiunta', 'task' => $task]);
}

function updateTask() {
    global $dataFile;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID dell\'attività mancante']);
        return;
    }

    $tasks = json_decode(file_get_contents($dataFile), true);
    $taskIndex = array_search($data['id'], array_column($tasks, 'id'));
    
    if ($taskIndex === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Attività non trovata']);
        return;
    }

    if (isset($data['action']) && $data['action'] === 'toggle' && isset($data['completed'])) {
        $tasks[$taskIndex]['completed'] = (bool)$data['completed'];
    } elseif (isset($data['action']) && $data['action'] === 'edit' && isset($data['text'])) {
        $tasks[$taskIndex]['text'] = htmlspecialchars($data['text']);
    }
    
    file_put_contents($dataFile, json_encode($tasks, JSON_PRETTY_PRINT));
    
    echo json_encode(['message' => 'Attività aggiornata', 'task' => $tasks[$taskIndex]]);
}

function deleteTask() {
    global $dataFile;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id']) && !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID dell\'attività mancante']);
        return;
    }

    $tasks = json_decode(file_get_contents($dataFile), true);
    
    if (isset($data['action']) && $data['action'] === 'clearCompleted') {
        $tasks = array_filter($tasks, function($task) {
            return !$task['completed'];
        });
        $tasks = array_values($tasks);
        file_put_contents($dataFile, json_encode($tasks, JSON_PRETTY_PRINT));
        echo json_encode(['message' => 'Attività completate eliminate']);
    } else {
        $taskIndex = array_search($data['id'], array_column($tasks, 'id'));
        
        if ($taskIndex === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Attività non trovata']);
            return;
        }
        
        array_splice($tasks, $taskIndex, 1);
        file_put_contents($dataFile, json_encode($tasks, JSON_PRETTY_PRINT));
        
        echo json_encode(['message' => 'Attività eliminata']);
    }
}

// Ensure proper JSON output
if (!headers_sent()) {
    header('Content-Type: application/json');
}
?>

</html>