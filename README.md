
# تطبيق Todo باستخدام PostgreSQL و PHP و Nginx

هذا المشروع هو تطبيق ويب بسيط لإدارة المهام (Todo) باستخدام لغة PHP وقاعدة بيانات PostgreSQL. يتم استضافة المشروع على خادم Nginx مع دعم HTTPS باستخدام شهادة SSL من Let's Encrypt.


## إعداد المشروع

### 1. تحديث النظام

ابدأ بتحديث النظام للتأكد من أن جميع الحزم محدثة:

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. تثبيت الحزم المطلوبة

قم بتثبيت Nginx، PHP، PostgreSQL، والإضافات اللازمة لـ PHP:

```bash
sudo apt install nginx php-fpm php-pgsql postgresql postgresql-contrib -y
```

### 3. إعداد PostgreSQL

#### الدخول إلى مستخدم PostgreSQL:

```bash
sudo -u postgres psql
```

#### إنشاء قاعدة بيانات ومستخدم:

داخل واجهة PostgreSQL، قم بإنشاء قاعدة بيانات ومستخدم لتطبيق Todo ثم الخروج:

```sql
CREATE DATABASE tododb;
CREATE USER my_user WITH PASSWORD 'my_password';
GRANT ALL PRIVILEGES ON DATABASE tododb TO my_user;
\q
```

### 4. إعداد PHP والاتصال بقاعدة البيانات

#### إنشاء مجلد المشروع:

```bash
sudo mkdir -p /var/www/todo-app
sudo chown -R $USER:$USER /var/www/todo-app
```

#### إنشاء ملف `index.php`:

في المجلد `/var/www/todo-app/`، قم بإنشاء ملف `index.php`:

```bash
nano /var/www/todo-app/index.php
```

ثم أضف الكود التالي:

```php
<?php
$host = "localhost";
$dbname = "tododb";
$user = "my_user";
$pass = "my_password";

try {
    $db = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    echo "تم الاتصال بقاعدة البيانات بنجاح!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

### 5. إعداد Nginx

#### إنشاء ملف تكوين Nginx:

```bash
sudo nano /etc/nginx/sites-available/todo-app
```

ثم أضف التكوين التالي:

```nginx
server {
    listen 80;
    server_name TodoApp.com;

    root /var/www/todo-app;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### تفعيل الموقع:

```bash
sudo ln -s /etc/nginx/sites-available/todo-app /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 6. تطبيق عمليات CRUD (الإضافة، التعديل، الحذف)

#### إعداد الجداول في قاعدة البيانات:

قم بفتح PostgreSQL وأنشئ جدول المهام:

```bash
sudo -i -u postgres
psql
```

ثم:

```sql
\c tododb;
CREATE TABLE todos (
    id SERIAL PRIMARY KEY,
    task VARCHAR(255) NOT NULL,
    status BOOLEAN DEFAULT FALSE
);
\q
exit
```

#### كتابة سكربت CRUD في PHP:

في ملف `index.php`، أضف أكواد لإدارة المهام باستخدام عمليات CRUD:

```php
<?php
$host = "localhost";
$dbname = "tododb";
$user = "my_user";
$pass = "my_password";

try {
    $db = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// إضافة مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $task = $_POST['task'];
    $query = $db->prepare("INSERT INTO todos (task) VALUES (:task)");
    $query->execute(['task' => $task]);
    header("Location: /");
}

// حذف مهمة
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = $db->prepare("DELETE FROM todos WHERE id = :id");
    $query->execute(['id' => $id]);
    header("Location: /");
}

// عرض المهام
$query = $db->query("SELECT * FROM todos");
$todos = $query->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Todo App</title>
</head>
<body>
    <h1>قائمة المهام</h1>
    <form method="POST" action="">
        <input type="text" name="task" required>
        <button type="submit">إضافة المهمة</button>
    </form>

    <ul>
        <?php foreach ($todos as $todo): ?>
            <li>
                <?= htmlspecialchars($todo['task']) ?>
                <a href="?delete=<?= $todo['id'] ?>">حذف</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
```

### 7. إعداد SSL وتحويل الموقع إلى HTTPS

#### تثبيت Certbot:

```bash
sudo apt install certbot python3-certbot-nginx -y
```

#### إعداد SSL:

احصل على شهادة SSL باستخدام Certbot:

```bash
sudo certbot --nginx -d your_domain
```

اتبع التعليمات التي تظهر على الشاشة لتثبيت الشهادة.

#### تأكيد التثبيت:

بعد انتهاء Certbot من إعداد SSL، سيقوم تلقائيًا بتعديل تكوين Nginx ليشمل HTTPS. تأكد من إعادة تحميل Nginx:

```bash
sudo systemctl reload nginx
```

### 8. تجربة الموقع

افتح المتصفح وانتقل إلى `https://localhost:80` أو `https://Todoapp.com`، يجب أن يظهر الموقع مع اتصال آمن (HTTPS).
