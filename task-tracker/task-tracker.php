<?php
// Uso: php main.php comando [argumentos]
// Comandos: add, update, delete, mark-in-progress, mark-done, list

const TASK_FILE = 'tasks.json';
const STATUS_TODO = 'todo';
const STATUS_IN_PROGRESS = 'in-progress';
const STATUS_DONE = 'done';

function loadTasks(): array {
    if (!file_exists(TASK_FILE)) {
        return [];
    }
    
    $content = file_get_contents(TASK_FILE);
    if ($content === false) {
        echo "Error: No se pudo leer el archivo de tareas\n";
        return [];
    }
    
    $tasks = json_decode($content, true);
    
    return $tasks;
}

function saveTasks(array $tasks): bool {
    $result = file_put_contents(filename: TASK_FILE, data: json_encode($tasks, JSON_PRETTY_PRINT));
    return $result !== false;
}

function getNextId(array $tasks): int {
    // Empezamos con 0; si no hay tareas al final devolvemos 1.
    $maxId = 0;

    // Recorremos cada tarea y sacamos su id (si existe).
    foreach ($tasks as $task) {
        $id = 0;
        if (is_array($task) && isset($task['id'])) {
            $id = (int) $task['id'];
        }

        // Si este id es mayor que el máximo conocido, lo guardamos.
        if ($id > $maxId) {
            $maxId = $id;
        }
    }

    // El siguiente id disponible es el máximo + 1.
    return $maxId + 1;
}

function findTaskById($tasks, $id){
    $id = (int) $id;
    foreach ($tasks as $task) {
       $taskId = $task['id'];
       if ($taskId === $id) {
           return $task;
       }
    }
    return null;
}

function addTask(array &$tasks, string $description): void {
    $newTask = [
        'id' => getNextId($tasks),
        'task' => trim($description),
        'status' => STATUS_TODO,
        'createdAt' => date('c'),
        'updatedAt' => date('c')
    ];
    
    $tasks[] = $newTask;
    
    if (saveTasks($tasks)) {
        echo "Tarea agregada (ID {$newTask['id']}): {$newTask['task']}\n";
    } else {
        echo "Error: No se pudo guardar la tarea\n";
        exit(1);
    }
}

function updateTask(&$tasks, $id, $newDescription) {
    $id = (int) $id;

    
    for ($i = 0; $i < count($tasks); $i++) {
        if (!isset($tasks[$i]['id'])) {
            continue;
        }


        $taskId = (int) $tasks[$i]['id'];
        if ($taskId === $id) {
            $tasks[$i]['task'] = $newDescription;
            $tasks[$i]['updatedAt'] = date('Y-m-d\TH:i:sP');

           
            if (saveTasks($tasks)) {
                echo "Tarea $id actualizada.\n";
            } else {
                echo "Error: No se pudo guardar la tarea\n";
                exit(1);
            }

         
            return;
        }
    }

}

function deleteTask(array &$tasks, int $id): void {
    $id = (int) $id;

    for ($i = 0; $i < count($tasks); $i++) {
        if (!isset($tasks[$i]['id'])) {
            continue;
        }

        $taskId = (int) $tasks[$i]['id'];
        if ($taskId === $id) {
            array_splice($tasks, $i, 1);
            
            if (saveTasks($tasks)) {
                echo "Tarea $id eliminada.\n";
            } else {
                echo "Error: No se pudo guardar la tarea\n";
                exit(1);
            }
            return;
        }
    }
}

function changeStatus(array &$tasks, int $id, string $newStatus): void {
    $id = (int) $id;

    for ($i = 0; $i < count($tasks); $i++) {
        if (!isset($tasks[$i]['id'])) {
            continue;
        }

        $taskId = (int) $tasks[$i]['id'];
        if ($taskId === $id) {
            $tasks[$i]['status'] = $newStatus;
            $tasks[$i]['updatedAt'] = date('Y-m-d\TH:i:sP');

            if (saveTasks($tasks)) {
                echo "Tarea $id marcada como '$newStatus'.\n";
            } else {
                echo "Error: No se pudo guardar la tarea\n";
                exit(1);
            }
            return;
        }
    }
    
}

function listTasks(array $tasks): void {
    if (empty($tasks)) {
        echo "No hay tareas.\n";
        return;
    }
    
    foreach ($tasks as $task) {
        echo "ID: " . ($task['id'] ?? 'N/A') . "\n";
        echo "Task: " . ($task['task'] ?? '') . "\n";
        echo "Status: " . ($task['status'] ?? 'unknown') . "\n";
        echo "Created: " . ($task['createdAt'] ?? '') . "\n";
        echo "Updated: " . ($task['updatedAt'] ?? '') . "\n";
        echo "-----------------------\n";
    }
}

// Validación inicial
if ($argc < 2) {
    echo "Uso: php main.php comando [argumentos]\n";
    echo "Comandos: add, update, delete, mark-in-progress, mark-done, list\n";
    exit(1);
}

$command = $argv[1];
$tasks = loadTasks();

try {
    switch ($command) {
        case 'add':
            if ($argc < 3) {
                throw new InvalidArgumentException('Uso: php main.php add "Texto de la tarea"');
            }
            addTask($tasks, $argv[2]);
            break;

        case 'update':
            if ($argc < 4) {
                throw new InvalidArgumentException('Uso: php main.php update ID "Nuevo texto"');
            }
            updateTask($tasks, (int)$argv[2], $argv[3]);
            break;

        case 'delete':
            if ($argc < 3) {
                throw new InvalidArgumentException('Uso: php main.php delete ID');
            }
            deleteTask($tasks, (int)$argv[2]);
            break;

        case 'mark-in-progress':
            if ($argc < 3) {
                throw new InvalidArgumentException("Uso: php main.php $command ID");
            }
            changeStatus($tasks, (int)$argv[2], STATUS_IN_PROGRESS);
            break;

        case 'mark-done':
            if ($argc < 3) {
                throw new InvalidArgumentException("Uso: php main.php $command ID");
            }
            changeStatus($tasks, (int)$argv[2], STATUS_DONE);
            break;

        case 'list':
            listTasks($tasks);
            break;

        default:
            throw new InvalidArgumentException("Comando desconocido: $command\nComandos válidos: add, update, delete, mark-in-progress, mark-done, list");
    }
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
?>