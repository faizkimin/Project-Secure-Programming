<?php
require "../db_connect.php";
require "../message_display.php";
require "verify_member.php";
require "header_member.php";

// Validate input data
if (isset($_POST['b_return'])) {
    $books = array();
    for ($i = 0; $i < $rows; $i++) {
        if (isset($_POST['cb_book'. $i])) {
            $isbn = $_POST['cb_book'. $i];
            if (!preg_match('/^[0-9]{10,13}$/', $isbn)) {
                die(error_without_field("Invalid ISBN format"));
            }
            $books[] = $isbn;
        }
    }
}

// Parameterized queries for database interactions
$query = $con->prepare("SELECT book_isbn FROM book_issue_log WHERE member =?;");
$query->bind_param("s", $_SESSION['username']);
$query->execute();
$result = $query->get_result();
$rows = mysqli_num_rows($result);

if ($rows == 0) {
    echo "<h2 align='center'>There Are No Issued Books Yet!</h2>";
} else {
    echo "<form class='cd-form' method='POST' action='#'>";
    echo "<center><legend>My Books</legend></center>";
    echo "<div class='success-message' id='success-message'>
            <p id='success'></p>
        </div>";
    echo "<div class='error-message' id='error-message'>
            <p id='error'></p>
        </div>";
    echo "<table width='100%' cellpadding='10' cellspacing='10'>
            <tr>
                <th></th>
                <th>ISBN<hr></th>
                <th>Title<hr></th>
                <th>Author<hr></th>
                <th>Category<hr></th>
                <th>Due Date<hr></th>
            </tr>";
    for ($i = 0; $i < $rows; $i++) {
        $isbn = mysqli_fetch_array($result)[0];
        if ($isbn!= NULL) {
            $query = $con->prepare("SELECT title, author, category FROM book WHERE isbn =?;");
            $query->bind_param("s", $isbn);
            $query->execute();
            $innerRow = mysqli_fetch_array($query->get_result());
            echo "<tr>
                    <td>
                        <label class='control control--checkbox'>
                            <input type='checkbox' name='cb_book". $i. "' value='". htmlspecialchars($isbn). "'>
                            <div class='control__indicator'></div>
                        </label>
                    </td>";
            echo "<td>". htmlspecialchars($isbn). "</td>";
            for ($j = 0; $j < 3; $j++) {
                echo "<td>". htmlspecialchars($innerRow[$j]). "</td>";
            }
            $query = $con->prepare("SELECT due_date FROM book_issue_log WHERE member =? AND book_isbn =?;");
            $query->bind_param("ss", $_SESSION['username'], $isbn);
            $query->execute();
            echo "<td>". htmlspecialchars(mysqli_fetch_array($query->get_result())[0]). "</td>";
            echo "</tr>";
        }
    }
    echo "</table><br />";
    echo "<input type='submit' name='b_return' value='Return Selected Books' />";
    echo "</form>";
}

if (isset($_POST['b_return'])) {
    foreach ($books as $isbn) {
        $query = $con->prepare("SELECT due_date FROM book_issue_log WHERE member =? AND book_isbn =?;");
        $query->bind_param("ss", $_SESSION['username'], $isbn);
        $query->execute();
        $due_date = mysqli_fetch_array($query->get_result())[0];

        $query = $con->prepare("SELECT DATEDIFF(CURRENT_DATE,?);");
        $query->bind_param("s", $due_date);
        $query->execute();
        $days = (int)mysqli_fetch_array($query->get_result())[0];

        $query = $con->prepare("DELETE FROM book_issue_log WHERE member =? AND book_isbn =?;");
        $query->bind_param("ss", $_SESSION['username'], $isbn);
        if (!$query->execute()) {
            die(error_without_field("ERROR: Couldn't return the books"));
        }

        if ($days > 0) {
            $penalty = 5 * $days;
            $query = $con->prepare("SELECT price FROM book WHERE isbn =?;");
            $query->bind_param("s", $isbn);
            $query->execute();
            $price = mysqli_fetch_array($query->get_result())[0];
            if ($price < $penalty) {
                $penalty = $price;
            }
            $query = $con->prepare("UPDATE member SET balance = balance -? WHERE username =?;");
            $query->bind_param("ds", $penalty, $_SESSION['username']);
            $query->execute();
            echo '<script>
                    document.getElementById("error").innerHTML += "A penalty of Rs. '. htmlspecialchars($penalty). ' was charged for keeping book '. htmlspecialchars($isbn). ' for '. htmlspecialchars($days). ' days after the due date.<br />";
                    document.getElementById("error-message").style.display = "block";
                </script>';
        }
    }
    $books_count = count($books);
    if ($books_count > 0) {
        echo '<script>
                document.getElementById("success").innerHTML = "Successfully returned '. htmlspecialchars($books_count). ' books";
                document.getElementById("success-message").style.display = "block";
            </script>';
        $query = $con->prepare("SELECT balance FROM member WHERE username =?;");
        $query->bind_param("s", $_SESSION['username']);
        $query->execute();

        $balance = (int)mysqli_fetch_array($query->get_result())[0];
        if ($balance < 0) {
            header("Location: ../logout.php");
        }
    } else {
        echo error_without_field("Please select a book to return");
    }
}
?>