<?php
session_start();

// === Připojení k databázi ===
$host = "127.0.0.1";
$dbname = "webypekarkovacz";
$username = "webypekarkova001";
$password = "Poklop22";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Chyba připojení: " . $e->getMessage());
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function setFlash(string $type, string $message): void
{
    $_SESSION["flash"] = [
        "type" => $type,
        "message" => $message,
    ];
}

$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);

$authError = "";
$taskError = "";
$isLoggedIn = isset($_SESSION["user_id"]);
$currentUserId = $isLoggedIn ? (int) $_SESSION["user_id"] : null;
$currentUserName = $isLoggedIn ? (string) $_SESSION["username"] : "";

if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    unset($_SESSION["flash"]);

    if ($action === "register") {
        $name = trim($_POST["username"] ?? "");
        $pass = $_POST["password"] ?? "";
        $passConfirm = $_POST["password_confirm"] ?? "";

        if ($name === "" || $pass === "") {
            $authError = "Vyplň uživatelské jméno i heslo.";
        } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 50) {
            $authError = "Uživatelské jméno musí mít 3 až 50 znaků.";
        } elseif ($pass !== $passConfirm) {
            $authError = "Hesla se neshodují.";
        } elseif (
            mb_strlen($pass) < 8
            || !preg_match('/[a-z]/', $pass)
            || !preg_match('/[A-Z]/', $pass)
            || !preg_match('/[0-9]/', $pass)
        ) {
            $authError = "Heslo musí mít min. 8 znaků, velké i malé písmeno a číslo.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                $stmt->execute([
                    "username" => $name,
                    "password" => password_hash($pass, PASSWORD_DEFAULT),
                ]);
                setFlash("success", "Účet byl úspěšně vytvořen. Teď se přihlas.");
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                $authError = $e->getCode() === "23000"
                    ? "Tento uživatel už existuje."
                    : "Registrace se nezdařila.";
            }
        }
    }

    if ($action === "login") {
        $name = trim($_POST["username"] ?? "");
        $pass = $_POST["password"] ?? "";

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(["username" => $name]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user["password"])) {
            $_SESSION["user_id"] = (int) $user["id"];
            $_SESSION["username"] = $user["username"];
            setFlash("success", "Přihlášení proběhlo úspěšně. Vítej, " . $user["username"] . "!");
            header("Location: index.php");
            exit();
        }

        $authError = "Neplatné přihlašovací údaje.";
    }

    if ($action === "save_task") {
        if (!$isLoggedIn) {
            header("Location: index.php");
            exit();
        }

        $task = trim($_POST["task"] ?? "");
        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

        if ($task === "" || mb_strlen($task) > 50) {
            $taskError = "Úkol musí mít 1 až 50 znaků.";
        } else {
            if ($id > 0) {
                $sql = "UPDATE tasks SET task = :task WHERE id = :id AND user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    "task" => $task,
                    "id" => $id,
                    "user_id" => $currentUserId,
                ]);
            } else {
                $sql = "INSERT INTO tasks (user_id, task) VALUES (:user_id, :task)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    "user_id" => $currentUserId,
                    "task" => $task,
                ]);
            }
            setFlash("success", $id > 0 ? "Úkol byl upraven." : "Úkol byl přidán.");
            header("Location: index.php");
            exit();
        }
    }
}

$taskToEdit = null;
if ($isLoggedIn && isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        "id" => (int) $_GET["edit"],
        "user_id" => $currentUserId,
    ]);
    $taskToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($isLoggedIn && isset($_GET["delete"])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        "id" => (int) $_GET["delete"],
        "user_id" => $currentUserId,
    ]);
    setFlash("success", "Úkol byl smazán.");
    header("Location: index.php");
    exit();
}

if ($isLoggedIn && isset($_GET["done"])) {
    $stmt = $pdo->prepare("UPDATE tasks SET done = 1 WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        "id" => (int) $_GET["done"],
        "user_id" => $currentUserId,
    ]);
    setFlash("success", "Úkol je označen jako hotový.");
    header("Location: index.php");
    exit();
}

$tasks = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id ORDER BY id DESC");
    $stmt->execute(["user_id" => $currentUserId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
    <link rel="manifest" href="manifest.json">
    <title>ToDoList</title>
</head>
<body>
    <div class="form-container">
        <?php if (!$isLoggedIn): ?>
            <h1>Přihlášení</h1>

            <?php if ($flash): ?>
                <p class="message flash-message <?php echo h($flash["type"]); ?>"><?php echo h($flash["message"]); ?></p>
            <?php endif; ?>

            <?php if ($authError !== ""): ?>
                <p class="message error"><?php echo h($authError); ?></p>
            <?php endif; ?>

            <div class="auth-grid">
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <h2>Už mám účet</h2>
                    <div class="input-box">
                        <input type="text" name="username" class="input-add" maxlength="50" placeholder="Uživatelské jméno" required>
                    </div>
                    <div class="input-box">
                        <input type="password" name="password" class="input-add" placeholder="Heslo" required>
                    </div>
                    <button class="submit-btn" type="submit">Přihlásit</button>
                </form>

                <form method="post">
                    <input type="hidden" name="action" value="register">
                    <h2>Registrace</h2>
                    <div class="input-box">
                        <input type="text" name="username" class="input-add" maxlength="50" placeholder="Uživatelské jméno" required>
                    </div>
                    <div class="input-box">
                        <input type="password" name="password" class="input-add" placeholder="Heslo" required>
                    </div>
                    <div class="input-box">
                        <input type="password" name="password_confirm" class="input-add" placeholder="Potvrzení hesla" required>
                    </div>
                    <button class="submit-btn" type="submit">Vytvořit účet</button>
                </form>
            </div>
        <?php else: ?>
            <div class="user-header">
                <h1>Moje úkoly</h1>
                <div class="user-meta">
                    Přihlášen: <strong><?php echo h($currentUserName); ?></strong>
                    <a href="?logout=1" class="btn-icon">Odhlásit</a>
                </div>
            </div>

            <?php if ($flash): ?>
                <p class="message flash-message <?php echo h($flash["type"]); ?>"><?php echo h($flash["message"]); ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="save_task">
                <?php if ($taskToEdit): ?>
                    <input type="hidden" name="id" value="<?php echo (int) $taskToEdit["id"]; ?>">
                <?php endif; ?>

                <div class="input-box">
                    <input
                        type="text"
                        name="task"
                        class="input-add"
                        value="<?php echo $taskToEdit ? h($taskToEdit["task"]) : ""; ?>"
                        maxlength="50"
                        placeholder="Jaký je Tvůj úkol?..."
                        autocomplete="off"
                        required
                    >
                </div>

                <?php if ($taskError !== ""): ?>
                    <p class="message error"><?php echo h($taskError); ?></p>
                <?php endif; ?>

                <button class="submit-btn" type="submit">
                    <?php echo $taskToEdit ? "Upravit úkol" : "Přidat úkol"; ?>
                </button>
            </form>

            <div class="input-box">
                <h2>Seznam úkolů</h2>
            </div>

            <ul>
                <?php foreach ($tasks as $t): ?>
                    <li>
                        <span class="task-text">
                            <?php if ($t["done"]): ?>
                                <s><?php echo h($t["task"]); ?></s>
                            <?php else: ?>
                                <?php echo h($t["task"]); ?>
                            <?php endif; ?>
                        </span>

                        <span class="task-actions">
                            <a href="?delete=<?php echo (int) $t["id"]; ?>" class="btn-icon delete"><i class="fa-solid fa-trash"></i></a>
                            <?php if (!$t["done"]): ?>
                                <a href="?done=<?php echo (int) $t["id"]; ?>" class="btn-icon done"><i class="fa-solid fa-check"></i></a>
                                <a href="?edit=<?php echo (int) $t["id"]; ?>" class="btn-icon edit"><i class="fa-solid fa-pen"></i></a>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const input = document.querySelector(".input-add");
            if (input) input.focus();

            const flashMessage = document.querySelector(".flash-message");
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.classList.add("fade-out");
                    setTimeout(() => flashMessage.remove(), 500);
                }, 3000);
            }
        });
    </script>

    <footer>
         Copyright &copy; 2026 Markéta Pekárková
    </footer>
</body>
</html>