# 📝 Modern ToDoList (PHP & MySQL)

Jednoduchá a efektivní webová aplikace pro správu každodenních úkolů. Projekt byl vytvořen s důrazem na čistý kód, bezpečnost dat a moderní "cyberpunk" vizuální styl.

## 🚀 Hlavní funkce
* **CRUD operace**: Kompletní správa úkolů (Vytvoření, Čtení, Úprava, Mazání).
* **Stav úkolu**: Možnost označit úkol jako hotový (vizuální odlišení).
* **Responzivní design**: Plně funkční na desktopu i mobilních zařízeních.
* **Autofocus**: Okamžitá připravenost k psaní po načtení stránky.

## 🛠️ Použité technologie
* **Backend**: PHP 8.4
* **Databáze**: MySQL (propojení přes PDO)
* **Frontend**: HTML5, CSS3 (Flexbox), FontAwesome ikony
* **Bezpečnost**: Ošetření vstupů pomocí Prepared Statements (ochrana proti SQL injection) a XSS ochrana (htmlspecialchars).

## 💡 Analytické detaily
Při vývoji jsem se zaměřila na uživatelskou přívětivost (HCI). Aplikace využívá logické přesměrování (header location) po každé akci, aby se zabránilo opětovnému odeslání formuláře při obnovení stránky.

## ⚙️ Instalace
1. Importujte databázi ze souboru `database.sql` (pokud je přiložen).
2. Nastavte údaje k databázi v souboru `index.php`.
3. Spusťte přes lokální server (např. XAMPP).