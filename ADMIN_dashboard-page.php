<?php
include 'connect.php';
session_start();

$adminUsernames = ['zmtabinas', 'feligwapo', 'admin'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['username'], $adminUsernames)) {
    header('Location: login-page.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login-page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $id = intval($_POST['id']);
        if ($_POST['type'] === 'user') {
            $sql = "UPDATE tbluserprofile SET firstname = ?, lastname = ?, gender = ?, birthdate = ? WHERE userid = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ssssi", $_POST['firstname'], $_POST['lastname'], $_POST['gender'], $_POST['birthdate'], $id);
        } elseif ($_POST['type'] === 'answer') {
            $sql = "UPDATE answers SET UserID = ?, QuestionID = ?, AnswerText = ? WHERE AnswerID = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("iisi", $_POST['UserID'], $_POST['QuestionID'], $_POST['AnswerText'], $id);
        } elseif ($_POST['type'] === 'question') {
            $sql = "UPDATE tblquestion SET UserID = ?, QuestionText = ? WHERE QuestionID = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("iss", $_POST['UserID'], $_POST['QuestionText'], $id);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: ADMIN_dashboard-page.php?view=" . $_POST['type'] . 's');
        exit();
    }

    if (isset($_POST['delete'])) {
        $deleteId = intval($_POST['id']);
        if ($_POST['type'] === 'user') {
            $sql = "DELETE FROM tbluserprofile WHERE userid = ?";
        } elseif ($_POST['type'] === 'answer') {
            $sql = "DELETE FROM answers WHERE AnswerID = ?";
        } elseif ($_POST['type'] === 'question') {
            $sql = "DELETE FROM tblquestion WHERE QuestionID = ?";
        }
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();
        header("Location: ADMIN_dashboard-page.php?view=" . $_POST['type'] . 's');
        exit();
    }
}

$search = '';
if (isset($_GET['query'])) {
    $search = $connection->real_escape_string($_GET['query']);
}

$view = isset($_GET['view']) ? $_GET['view'] : 'records';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin.css">
    <title>Curious KeyPie - Admin Dashboard</title>
</head>
<body>
    <header class="navbar">
        <div class="company-name">Curious KeyPie</div>
        <div class="admin-info">
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="?logout=true" class="btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="?view=records">User Records</a></li>
                <li><a href="?view=answers">All User Answers</a></li>
                <li><a href="?view=questions">All User Questions</a></li>
            </ul>
        </nav>

        <div class="content">
    <?php
    if ($view === 'records') {
        echo "<h2>User Records</h2>";
        $sql = $search ? "SELECT * FROM tbluserprofile WHERE firstname LIKE '%$search%' OR lastname LIKE '%$search%'" : "SELECT * FROM tbluserprofile";
        $result = $connection->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>User ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Birthdate</th><th>Actions</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><form method='POST'>";
                echo "<td>" . htmlspecialchars($row["userid"]) . "<input type='hidden' name='id' value='" . $row["userid"] . "'><input type='hidden' name='type' value='user'></td>";
                echo "<td><input type='text' name='firstname' value='" . htmlspecialchars($row["firstname"]) . "'></td>";
                echo "<td><input type='text' name='lastname' value='" . htmlspecialchars($row["lastname"]) . "'></td>";
                echo "<td><input type='text' name='gender' value='" . htmlspecialchars($row["gender"]) . "'></td>";
                echo "<td><input type='date' name='birthdate' value='" . htmlspecialchars($row["birthdate"]) . "'></td>";
                echo "<td><button type='submit' name='update'>Update</button>
                        <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                echo "</form></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No results found.</p>";
        }
    } elseif ($view === 'answers') {
        echo "<h2>All User Answers</h2>"; 
        $sql = $search ? "SELECT * FROM answers WHERE UserID LIKE '%$search%' OR QuestionID LIKE '%$search%' OR AnswerText LIKE '%$search%'" : "SELECT * FROM answers";
        $result = $connection->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Answer ID</th><th>User ID</th><th>Question ID</th><th>Answer Text</th><th>Timestamp</th><th>Actions</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><form method='POST'>";
                echo "<td>" . htmlspecialchars($row["AnswerID"]) . "<input type='hidden' name='id' value='" . $row["AnswerID"] . "'><input type='hidden' name='type' value='answer'></td>";
                echo "<td>" . htmlspecialchars($row["UserID"]) . "</td>";
                echo "<td><input type='number' name='QuestionID' value='" . htmlspecialchars($row["QuestionID"]) . "'></td>";
                echo "<td><input type='text' name='AnswerText' value='" . htmlspecialchars($row["AnswerText"]) . "'></td>";
                echo "<td>" . htmlspecialchars($row["Timestamp"]) . "</td>";
                echo "<td><button type='submit' name='update'>Update</button>
                        <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                echo "</form></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No answers to display.</p>";
        }
    } elseif ($view === 'questions') {
        echo "<h2>All User Questions</h2>"; 
        $sql = $search ? "SELECT * FROM tblquestion WHERE UserID LIKE '%$search%' OR QuestionID LIKE '%$search%' OR QuestionText LIKE '%$search%'" : "SELECT * FROM tblquestion";
        $result = $connection->query($sql);
    
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Question ID</th><th>User ID</th><th>Question Text</th><th>Timestamp</th><th>Actions</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><form method='POST'>";
                echo "<td>" . htmlspecialchars($row["QuestionID"]) . "<input type='hidden' name='id' value='" . $row["QuestionID"] . "'><input type='hidden' name='type' value='question'></td>";
                echo "<td>" . htmlspecialchars($row["UserID"]) . "</td>";
                echo "<td><input type='text' name='QuestionText' value='" . htmlspecialchars($row["QuestionText"]) . "'></td>";
                echo "<td>" . htmlspecialchars($row["Timestamp"]) . "</td>";
                echo "<td><button type='submit' name='update'>Update</button>
                        <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                echo "</form></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No Questions to display.</p>";
        }
    }
    ?>
</div>

    </div>
</body>
</html>

