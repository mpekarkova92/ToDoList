<?php
// Spuštění session pro udržení dat mezi stránkami a CSRF token
session_start();

// CSRF ochrana
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


    // === Připojení k databázi ===

    // Název serevru
    $host = "localhost";

    // Název databáze
    $dbname= "todolist";

    // Uživatelské jméno k databázi
    $username= "root";

    // Heslo k databázi
    $password= "";

    // Vyjimka pro zachycení případných chyb při připojování k databázi
    try {
        // PDO = moderní způsob práce s databází v PHP
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

        // Zapnutí režimu chyb pro PDO
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $e) {
        // Pokud se nepodaří připojit k databázi, vypíše se chybová zpráva
        die("Chyba připojení k databázi: " . $e->getMessage());
    }



    // === ULOŽENÍ ÚKOLU DO DATABÁZE ===

    // Podmínka pro zpracování formuláře - kontrola, zda byl formulář odeslán metodou POST
    if($_SERVER["REQUEST_METHOD"] == "POST") {

    // Získání textu úkolu z formuláře
    $task = trim($_POST["task"]) ?? "" ; // Trim odstraní mezery

    // Kontrola zda není text prázdný
    if($task === "") {
        // Pokud text splňuje podmínku, tak provede nasledující
        } elseif (mb_strlen($task) > 50) {
            echo "Text je příliš dlouhý (max 50 znaků)";
        } else {
           //SQL dotaz pro vložení úkolu do tabulky "tasks"
        $sql = "INSERT INTO tasks (task) VALUES (:task)";

        // Připravení dotazu k vykonání, prepared statement pro bezpečné vkládání dat do databáze
        $stmt = $pdo->prepare($sql);

        // Dosadíme hodnotu do parametru :task, execute() provede dotaz s danými parametry, což zajistí bezpečné vložení dat do databáze a ochranu proti SQL injection
        $stmt->execute(["task" => $task]); 
        } 

    // Přesměrování zpět na hlavní stránku (aby se formulář znovu neodesílal při obnovení stránky)
    if (mb_strlen($task) <= 50){
        header("Location: index.php");
        exit();
        }
    }

    // === SMAZÁNÍ ÚKOLU Z DATABÁZE ===

    // Kontrola, zda je v URL přítomen parametr "delete", což znamená, že uživatel chce smazat úkol
    if(isset($_GET["delete"])) {

        // Získání ID úkolu, který má být smazán, z parametru "delete" v URL
        $id = $_GET["delete"];

        // SQL dotaz pro smazání úkolu z tabulky "tasks" na základě jeho ID
        $sql = "DELETE FROM tasks WHERE id = :id";

        // Připravení dotazu k vykonání
        $stmt = $pdo->prepare($sql);

        // Dosadíme hodnotu do parametru :id a provedeme dotaz, což smaže úkol z databáze
        $stmt->execute(["id" => $id]);

        // Přesměrování zpět na hlavní stránku (aby se zabránilo opětovnému odesílání požadavku při obnovení stránky)
        header("Location: index.php");
        exit();
    }

    // === OZNAČENÍ ÚKOLU JAKO HOTOVÉHO ===

    // Kontrola, zda je v URL přítomen parametr "done", což znamená, že uživatel chce označit úkol jako hotový
    if(isset($_GET["done"])) {

        // Získání ID úkolu, který má být označen jako hotový, z parametru "done" v URL
        $id = $_GET["done"];

        // SQL dotaz pro aktualizaci stavu úkolu na "hotový" (done = 1) v tabulce "tasks" na základě jeho ID
        $sql = "UPDATE tasks SET done = 1 WHERE id = :id";

        // Připravení dotazu k vykonání
        $stmt = $pdo->prepare($sql);

        // Dosadíme hodnotu do parametru :id a provedeme dotaz, což aktualizuje stav úkolu v databázi
        $stmt->execute(["id" => $id]);

        // Přesměrování zpět na hlavní stránku (aby se zabránilo opětovnému odesílání požadavku při obnovení stránky)
        header("Location: index.php");
        exit();
    }

    // === ÚPRAVA ÚKOLU ===
        $taskToEdit = null; // musí být inicializováno předem

        if(isset($_GET["edit"])) {
        $id = $_GET['edit'];

        // SQL dotaz pro získání informací o úkolu, který má být upraven
        $sql = "SELECT * FROM tasks WHERE id = :id";

        // Připravení dotazu
        $stmt = $pdo->prepare($sql);

        // Provedení dotazu
        $stmt->execute(["id" => $id]);

        // Uložení získaných informací do proměnné
        $taskToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
        


    // === NAČÍTÁNÍ ÚKOLŮ Z DATABÁZE ===

    // SQL dotaz pro získání všech úkolů z tabulky "tasks"
    $sql = "SELECT * FROM tasks ORDER BY id DESC";

    // Provedení dotazu 
    $stmt = $pdo->query($sql);

    // Uložení výsledků do pole $tasks, které bude obsahovat všechny úkoly z databáze
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


    ?>




<!DOCTYPE html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">
    <title>ToDoList</title>
</head>
<body>
    <div class="form-container">
        
        <form method="post">
            <h1>Moje úkoly</h1>
            <div class="input-box">
                <input type="text" name="task"
                    value="<?php echo $taskToEdit ? htmlspecialchars($taskToEdit['task']) : ''; ?>"
                    maxlength="50"
                    pattern="[A-Za-z0-9\s.,!?-]{1,50}"
                    placeholder="Jaký je Tvůj úkol?..."
                    class="input-add"
                    autocomplete="off"
                    autofocus>
            </div>
            <button class="submit-btn" type="submit">
                <?php echo $taskToEdit ? 'Upravit úkol' : 'Přidat úkol'; ?>
            </button>
        </form>

        <div class="input-box">
            <h2>Seznam úkolů</h2>
        </div>
    
        <ul>
            <?php foreach ($tasks as $t): ?>
                <li>
                    <span class="task-text">
                        <?php if ($t['done']): ?>
                            <s><?php echo htmlspecialchars($t["task"]); ?></s>
                        <?php else: ?>
                            <?php echo htmlspecialchars($t["task"]); ?>
                        <?php endif; ?>
                    </span>

                    <span class="task-actions">
                        <a href="?delete=<?php echo $t["id"]; ?>" class="btn-icon delete"> 
                            <i class="fa-solid fa-trash"></i>
                        </a>
                        <?php if (!$t['done']): ?>
                        <a href="?done=<?php echo $t["id"]; ?>" class="btn-icon done">
                            <i class="fa-solid fa-check"></i>
                        </a>
                        <a href="?edit=<?php echo $t["id"]; ?>" class="btn-icon edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

    </div> 
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const input = document.querySelector(".input-add"); // Opravila jsem i classu pro autofocus
            if (input) {
                input.focus();
            }
        });
    </script>
</body>
</html>
