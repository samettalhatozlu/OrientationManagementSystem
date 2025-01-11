<?php
session_start();
include('db_connection.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.email, up.progress_stage, up.progress_percentage
                       FROM users u
                       LEFT JOIN user_progress up ON u.id = up.user_id");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSV dosyası indirildiğinde işlem yapılacak
if (isset($_GET['download_csv']) && $_GET['download_csv'] == 'true') {
    $filename = "kullanici_ilerleme_durumu.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');

    fputcsv($output, ['ID', 'Ad', 'Soyad', 'Email', 'İlerleme Durumu', 'İlerleme Yüzdesi']);

    foreach ($users as $user) {
        fputcsv($output, $user);
    }

    fclose($output);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prompt'])) {
    $user_prompt = $_POST['prompt'];

    $command = escapeshellcmd("python3 chatbot.py \"$user_prompt\"");
    $response = shell_exec($command);
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İK Yönetim Paneli</title>

    <!-- Bootstrap CSS Bağlantısı -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 20px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
        }
        .nav-item.active .nav-link {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="index.php">Oryantasyon Sistemi</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">Admin Paneli</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard2.php">İK Analitiği</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Çıkış Yap</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>İK Yönetim Paneline Hoş Geldiniz</h1>

        <h3 class="mt-4">Kullanıcılar ve İlerlemeleri</h3>
        <p>Burada kullanıcıların ilerlemelerini görebilirsiniz.</p>

        <!-- Kullanıcıların ilerleme listesi -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ad</th>
                    <th>Soyad</th>
                    <th>Email</th>
                    <th>İlerleme Durumu</th>
                    <th>İlerleme Yüzdesi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['progress_stage']); ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $user['progress_percentage']; ?>%" aria-valuenow="<?php echo $user['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $user['progress_percentage']; ?>%</div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <hr>

        <h3>Özet İstatistikler</h3>
        <div class="row">
            <div class="col-md-4">
                <h5>Toplam Kullanıcı Sayısı</h5>
                <?php
                // Toplam kullanıcı sayısını al
                $stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
                $total_users = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <p><strong><?php echo $total_users['total_users']; ?></strong></p>
            </div>
            <div class="col-md-4">
                <h5>Toplam Tamamlanan İlerleme</h5>
                <?php
                // Toplam ilerleme yüzdesini hesapla
                $stmt = $pdo->query("SELECT SUM(progress_percentage) AS total_progress FROM user_progress");
                $total_progress = $stmt->fetch(PDO::FETCH_ASSOC);
                $average_progress = $total_progress['total_progress'] / count($users);
                ?>
                <p><strong><?php echo number_format($average_progress, 2); ?>%</strong></p>
            </div>
            <div class="col-md-4">
                <h5>Son 7 Gün İçinde Kayıt Olan Kullanıcılar</h5>
                <?php
                // Son 7 gün içinde kayıt olan kullanıcı sayısını al
                $stmt = $pdo->prepare("SELECT COUNT(*) AS recent_users FROM users WHERE created_at > NOW() - INTERVAL 7 DAY");
                $stmt->execute();
                $recent_users = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <p><strong><?php echo $recent_users['recent_users']; ?></strong></p>
            </div>
        </div>

        <hr>

        <!-- CSV İndir Butonu -->
        <h3>CSV İndir</h3>
        <p>Veritabanındaki kullanıcı verilerini CSV formatında indirmek için aşağıdaki butona tıklayabilirsiniz.</p>
        <a href="admin_dashboard.php?download_csv=true" class="btn btn-primary">CSV İndir</a>

        <hr>

        <!-- Chatbot -->
        <h3>Admin Chatbot</h3>
        <form action="admin_dashboard.php" method="POST">
            <div class="form-group">
                <label for="prompt">Chatbot'a ilgili bir soru yazın:</label>
                <textarea name="prompt" id="prompt" class="form-control" rows="4"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Soruyu Gönder</button>
        </form>

        <?php
        if (isset($response)) {
            echo "<h4>AI Yanıtı:</h4>";
            echo "<p>" . nl2br(htmlspecialchars($response)) . "</p>";
        }
        ?>
    </div>

    <!-- Bootstrap JS ve jQuery Bağlantıları -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
