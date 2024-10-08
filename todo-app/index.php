<?php 
$host = "localhost";
$dbname = "tododb";
$user = "my_user";
$pass = "my_password";

// الاتصال بقاعدة البيانات
$conn = pg_connect("host=$host dbname=$dbname user=$user password=$pass");

if (!$conn) {
    echo json_encode(['error' => 'حدث خطأ في الاتصال بقاعدة البيانات.']);
    exit;
}

// إضافة أو تعديل مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'];
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // تعديل مهمة
        $id = $_POST['id'];
        $query = "UPDATE todos SET task = '$task' WHERE id = $id";
        $result = pg_query($conn, $query);
    } else {
        // إضافة مهمة جديدة
        $query = "INSERT INTO todos (task) VALUES ('$task')";
        $result = pg_query($conn, $query);
    }

    header("Location: /index.html");
    exit;
}

// حذف مهمة
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM todos WHERE id = $id";
    $result = pg_query($conn, $query);

    header("Location: /index.html");
    exit;
}

// استرجاع جميع المهام وعرضها في JSON
$query = "SELECT * FROM todos";
$result = pg_query($conn, $query);
$todos = pg_fetch_all($result);

header('Content-Type: application/json');
echo json_encode($todos);
