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
            $redirectType = 'records';
        } elseif ($_POST['type'] === 'answer') {
            $sql = "UPDATE answers SET UserID = ?, QuestionID = ?, AnswerText = ? WHERE AnswerID = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("iisi", $_POST['UserID'], $_POST['QuestionID'], $_POST['AnswerText'], $id);
            $redirectType = 'answers';
        } elseif ($_POST['type'] === 'question') {
            $sql = "UPDATE tblquestion SET UserID = ?, QuestionText = ? WHERE QuestionID = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("iss", $_POST['UserID'], $_POST['QuestionText'], $id);
            $redirectType = 'questions';
        } else if ($_POST['type'] === 'female_records') {
           $sql = "UPDATE tbluserprofile SET firstname = ?, lastname = ?, gender = ? WHERE userid = ?";
           $stmt = $connection->prepare($sql);
           $stmt->bind_param("ssss", $_POST['firstname'], $_POST['lastname'], $_POST['gender'], $id);
           $redirectType = 'records';
        } else if ($_POST['type'] === 'male_records') {
           $sql = "UPDATE tbluserprofile SET firstname = ?, lastname = ?, gender = ? WHERE userid = ?";
           $stmt = $connection->prepare($sql);
           $stmt->bind_param("ssss", $_POST['firstname'], $_POST['lastname'], $_POST['gender'], $id);
           $redirectType = 'records';
       } else if ($_POST['type'] === 'other_gender_records') {
           $sql = "UPDATE tbluserprofile SET firstname = ?, lastname = ?, gender = ? WHERE userid = ?";
           $stmt = $connection->prepare($sql);
           $stmt->bind_param("ssss", $_POST['firstname'], $_POST['lastname'], $_POST['gender'], $id);
           $redirectType = 'records';
       } elseif ($view === 'QnA_reports') {
             echo "<h2>Question and Answer Reports (May 4)</h2>";

             $startOfDay = date('Y-m-d 00:00:00', strtotime('2024-05-04'));
             $endOfDay = date('Y-m-d 23:59:59', strtotime('2024-05-04'));

             $sql = "SELECT COUNT(DISTINCT a.UserID) AS total_users
                     FROM answers a
                     LEFT JOIN tblquestion q ON a.QuestionID = q.QuestionID
                     WHERE (a.Timestamp >= ? AND a.Timestamp <= ?)
                        OR (q.Timestamp >= ? AND q.Timestamp <= ?)";

             $stmt = $connection->prepare($sql);

             $stmt->bind_param("ssss", $startOfDay, $endOfDay, $startOfDay, $endOfDay);

             $stmt->execute();

             $result = $stmt->get_result();

             if ($result) {
                 if ($result->num_rows > 0) {
                     $row = $result->fetch_assoc();
                     $totalUsers = $row['total_users'];

                     echo "<p>Total users who submitted an answer or question on May 4th: $totalUsers</p>";
                 } else {
                     echo "<p>No users submitted answers or questions on May 4th.</p>";
                 }
             } else {
                 echo "<p>Error retrieving data from the database.</p>";
             }

             $stmt->close();
         }



        $stmt->execute();
        $stmt->close();
        header("Location: ADMIN_dashboard-page.php?view=" . $redirectType);
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
            <a href="index.php" class="admin-name">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <?php if (in_array($_SESSION['username'], $adminUsernames)) echo "<span>(Super Admin)</span>"; ?>
            </a>
            <a href="?logout=true" class="logout-button">Logout</a>
        </div>
    </header>

    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="?view=records" class="<?php echo ($view === 'records') ? 'active' : ''; ?>">User Records</a></li>
                <li><a href="?view=answers" class="<?php echo ($view === 'answers') ? 'active' : ''; ?>">All User Answers</a></li>
                <li><a href="?view=questions" class="<?php echo ($view === 'questions') ? 'active' : ''; ?>">All User Questions</a></li>
                <li><a href="?view=female_records" class="<?php echo ($view === 'female_records') ? 'active' : ''; ?>">(Gender) Female Records</a></li>
                <li><a href="?view=male_records" class="<?php echo ($view === 'male_records') ? 'active' : ''; ?>">(Gender) Male Records</a></li>
                <li><a href="?view=other_gender_records" class="<?php echo ($view === 'other_gender_records') ? 'active' : ''; ?>">(Gender) Other Records</a></li>
                <li><a href="?view=QnA_reports" class="<?php echo ($view === 'QnA_reports') ? 'active' : ''; ?>">(1hr) Question and Answer Reports</a></li>
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
                $sql = $search ?
                    "SELECT a.*, u.firstname, u.lastname FROM answers a JOIN tbluserprofile u ON a.UserID = u.userid WHERE a.UserID LIKE '%$search%' OR a.QuestionID LIKE '%$search%' OR a.AnswerText LIKE '%$search%' OR u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%'" :
                    "SELECT a.*, u.firstname, u.lastname FROM answers a JOIN tbluserprofile u ON a.UserID = u.userid";
                $result = $connection->query($sql);

                if ($result && $result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>Answer ID</th><th>User</th><th>Question ID</th><th>Answer Text</th><th>Timestamp</th><th>Actions</th></tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr><form method='POST'>";
                        echo "<td>" . htmlspecialchars($row["AnswerID"]) . "<input type='hidden' name='id' value='" . $row["AnswerID"] . "'><input type='hidden' name='type' value='answer'></td>";
                        echo "<td>" . htmlspecialchars($row["firstname"]) . " " . htmlspecialchars($row["lastname"]) . "</td>";
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
                $sql = $search ?
                    "SELECT q.*, u.firstname, u.lastname FROM tblquestion q JOIN tbluserprofile u ON q.UserID = u.userid WHERE q.UserID LIKE '%$search%' OR q.QuestionID LIKE '%$search%' OR q.QuestionText LIKE '%$search%' OR u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%'" :
                    "SELECT q.*, u.firstname, u.lastname FROM tblquestion q JOIN tbluserprofile u ON q.UserID = u.userid";
                $result = $connection->query($sql);

                if ($result && $result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>Question ID</th><th>User</th><th>Question Text</th><th>Timestamp</th><th>Actions</th></tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr><form method='POST'>";
                        echo "<td>" . htmlspecialchars($row["QuestionID"]) . "<input type='hidden' name='id' value='" . $row["QuestionID"] . "'><input type='hidden' name='type' value='question'></td>";
                        echo "<td>" . htmlspecialchars($row["firstname"]) . " " . htmlspecialchars($row["lastname"]) . "</td>";
                        echo "<td><input type='text' name='QuestionText' value='" . htmlspecialchars($row["QuestionText"]) . "'></td>";
                        echo "<td>" . htmlspecialchars($row["Timestamp"]) . "</td>";
                        echo "<td><button type='submit' name='update'>Update</button>
                                <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                        echo "</form></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No questions to display.</p>";
                }
            } else if ($view === 'female_records') {
                  echo "<h2>(Gender) Female User Records</h2>";
                  $sql = $search ? "SELECT * FROM tbluserprofile WHERE (firstname LIKE '%$search%' OR lastname LIKE '%$search%') AND gender = 'Female'" : "SELECT * FROM tbluserprofile WHERE gender = 'Female'";
                  $result = $connection->query($sql);

                  if ($result && $result->num_rows > 0) {
                      echo "<table>";
                      echo "<tr><th>User ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Actions</th></tr>";
                      while ($row = $result->fetch_assoc()) {
                          echo "<tr><form method='POST'>";
                          echo "<td>" . htmlspecialchars($row["userid"]) . "<input type='hidden' name='id' value='" . $row["userid"] . "'><input type='hidden' name='type' value='user'></td>";
                          echo "<td><input type='text' name='firstname' value='" . htmlspecialchars($row["firstname"]) . "'></td>";
                          echo "<td><input type='text' name='lastname' value='" . htmlspecialchars($row["lastname"]) . "'></td>";
                          echo "<td><input type='text' name='gender' value='" . htmlspecialchars($row["gender"]) . "' readonly></td>";
                          echo "<td><button type='submit' name='update'>Update</button>
                                  <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                          echo "</form></tr>";
                      }
                      echo "</table>";
                  } else {
                      echo "<p>No female user records found.</p>";
                  }
          } else if ($view === 'male_records') {
                echo "<h2>(Gender) Male User Records</h2>";
                $sql = $search ? "SELECT * FROM tbluserprofile WHERE (firstname LIKE '%$search%' OR lastname LIKE '%$search%') AND gender = 'Male'" : "SELECT * FROM tbluserprofile WHERE gender = 'Male'";
                $result = $connection->query($sql);

                if ($result && $result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>User ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Actions</th></tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr><form method='POST'>";
                        echo "<td>" . htmlspecialchars($row["userid"]) . "<input type='hidden' name='id' value='" . $row["userid"] . "'><input type='hidden' name='type' value='user'></td>";
                        echo "<td><input type='text' name='firstname' value='" . htmlspecialchars($row["firstname"]) . "'></td>";
                        echo "<td><input type='text' name='lastname' value='" . htmlspecialchars($row["lastname"]) . "'></td>";
                        echo "<td><input type='text' name='gender' value='" . htmlspecialchars($row["gender"]) . "' readonly></td>";
                        echo "<td><button type='submit' name='update'>Update</button>
                                <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                        echo "</form></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No male user records found.</p>";
                }
            } else if ($view === 'other_gender_records') {
                  echo "<h2>(Gender) Other User Records</h2>";
                  $sql = $search ? "SELECT * FROM tbluserprofile WHERE (firstname LIKE '%$search%' OR lastname LIKE '%$search%') AND gender = 'Other'" : "SELECT * FROM tbluserprofile WHERE gender = 'Other'";
                  $result = $connection->query($sql);

                  if ($result && $result->num_rows > 0) {
                      echo "<table>";
                      echo "<tr><th>User ID</th><th>First Name</th><th>Last Name</th><th>Gender</th><th>Actions</th></tr>";
                      while ($row = $result->fetch_assoc()) {
                          echo "<tr><form method='POST'>";
                          echo "<td>" . htmlspecialchars($row["userid"]) . "<input type='hidden' name='id' value='" . $row["userid"] . "'><input type='hidden' name='type' value='user'></td>";
                          echo "<td><input type='text' name='firstname' value='" . htmlspecialchars($row["firstname"]) . "'></td>";
                          echo "<td><input type='text' name='lastname' value='" . htmlspecialchars($row["lastname"]) . "'></td>";
                          echo "<td><input type='text' name='gender' value='" . htmlspecialchars($row["gender"]) . "' readonly></td>";
                          echo "<td><button type='submit' name='update'>Update</button>
                                  <button type='submit' name='delete' onclick='return confirm(\"Are you sure?\");'>Delete</button></td>";
                          echo "</form></tr>";
                      }
                      echo "</table>";
                  } else {
                      echo "<p>No male user records found.</p>";
                  }
              } elseif ($view === 'QnA_reports') {
                    echo "<h2>Question and Answer Reports (May 4)</h2>";

                    $startOfDay = date('Y-m-d 00:00:00', strtotime('2024-05-04'));
                    $endOfDay = date('Y-m-d 23:59:59', strtotime('2024-05-04'));

                    $sql = "SELECT COUNT(DISTINCT a.UserID) AS total_users
                            FROM answers a
                            LEFT JOIN tblquestion q ON a.QuestionID = q.QuestionID
                            WHERE (a.Timestamp >= '$startOfDay' AND a.Timestamp <= '$endOfDay')
                               OR (q.Timestamp >= '$startOfDay' AND q.Timestamp <= '$endOfDay')";


                    $result = $connection->query($sql);

                    if ($result) {
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $totalUsers = $row['total_users'];

                            echo "<p>Total users who submitted an answer or question on May 4th: $totalUsers</p>";
                        } else {
                            echo "<p>No users submitted answers or questions on May 4th.</p>";
                        }
                    } else {
                        echo "<p>Error retrieving data from the database.</p>";
                    }
                }
            ?>
        </div>
    </div>
</body>
</html>
