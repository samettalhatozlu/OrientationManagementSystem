<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
session_start();
include('db_connection.php');


include('db_connection.php');

$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    header('Location: login.php');
    exit();
}

// Oryantasyon konuları
$topics = [
    'Backend Geliştiricisi Olarak İyi Bir Başlangıç Yapın' => 'Backend geliştiricisi olarak iyi bir kariyer başlatmak için temelleri sağlam öğrenmelisiniz. PHP, Python, Node.js gibi diller ve veritabanı teknolojileri hakkında bilgi sahibi olmanız gerekir.',
    'API Tasarımı ve Entegrasyonları' => 'Backend geliştiricilerinin günlük işleri arasında API tasarımı ve dış sistemlerle entegrasyon sıklıkla yer alır. RESTful API’leri ve GraphQL API’leri hakkında bilgi sahibi olmalısınız.',
    'Veritabanı Yönetimi' => 'Veritabanlarıyla çalışmak backend geliştiricisi olarak en önemli yetkinliklerden biridir. SQL ve NoSQL veritabanları arasında farkları anlamalı ve her birini doğru kullanabilmelisiniz.',
    'Versiyon Kontrol Sistemi (Git)' => 'Git, yazılım geliştirme sürecinde önemli bir yer tutar. Kaynak kodlarını versiyon kontrolü altında tutmak, takım çalışmasında önemli bir araçtır.',
    'Performans İyileştirme ve Güvenlik' => 'Backend geliştiricileri, uygulama performansını optimize etmeli ve güvenlik önlemlerini almalıdır. SQL enjeksiyonu, XSS gibi saldırılara karşı güvenlik önlemleri alınmalıdır.'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_topic'])) {
    $completed_topic = $_POST['complete_topic'];
    
    // Konu ile ilgili ilerlemeyi güncelle
    $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :id AND progress_stage = :stage");
    $stmt->execute(['id' => $user_id, 'stage' => $completed_topic]);
    $existing_progress = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_progress) {
        // İlerlemeyi güncelle
        $new_percentage = $existing_progress['progress_percentage'] + 20; // Her bir adım %20 artacak
        if ($new_percentage > 100) {
            $new_percentage = 100; 
        }
        $stmt = $pdo->prepare("UPDATE user_progress SET progress_percentage = :percentage, last_updated = NOW() WHERE id = :id");
        $stmt->execute(['percentage' => $new_percentage, 'id' => $existing_progress['id']]);
    } else {
        // Yeni bir ilerleme kaydı ekle
        $stmt = $pdo->prepare("INSERT INTO user_progress (user_id, progress_stage, progress_percentage) VALUES (:id, :stage, :percentage)");
        $stmt->execute([
            'id' => $user_id,  
            'stage' => $completed_topic,
            'percentage' => 20, // İlk adım için %20
        ]);
    }
}

$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Ziyaretçi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oryantasyon Sistemi | Backend Developer</title>

    <!-- Bootstrap CSS Bağlantısı -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
            <li class="nav-item active">
                <a class="nav-link" href="index.php">Ana Sayfa</a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Çıkış Yap</a>
                </li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <!-- Admin Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Giriş Yap</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">Yeni Hesap Oluştur</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>


    <div class="container">
        <h1 class="text-center my-4">Hoş Geldiniz, <?php echo htmlspecialchars($user_name); ?>!</h1>

        <?php
        foreach ($topics as $title => $description) {
            $progress_percentage = 0;
            foreach ($user_progress as $progress_entry) {
                if ($progress_entry['progress_stage'] == $title) {
                    $progress_percentage = $progress_entry['progress_percentage'];
                }
            }

            $completed_class = $progress_percentage == 100 ? 'bg-success text-white' : '';
            echo '<div class="card my-3 ' . $completed_class . '">';
            echo '  <div class="card-body">';
            echo '    <h5 class="card-title">' . $title . '</h5>';
            echo '    <p class="card-text">' . $description . '</p>';
            echo '    <p><strong>İlerleme:</strong> ' . $progress_percentage . '%</p>';

            // Eğer konu tamamlanmamışsa, buton göster
            if ($progress_percentage < 100) {
                echo '    <form method="POST">';
                echo '      <input type="hidden" name="complete_topic" value="' . htmlspecialchars($title) . '">';
                echo '      <button type="submit" class="btn btn-primary">Tamamla</button>';
                echo '    </form>';
            } else {
                echo '    <p class="text-muted">Bu konu tamamlandı.</p>';
            }

            echo '  </div>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Chatbot ve Python Entegrasyonu -->
    <div class="container">
        <h3>Chatbot</h3>
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="prompt">Chatbot'a bir soru yazın:</label>
                <textarea name="prompt" id="prompt" class="form-control" rows="4"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Soruyu Gönder</button>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prompt'])) {
            $user_prompt = $_POST['prompt'];

            // Python script'ini çalıştırıp, sonucu al
            $command = escapeshellcmd("python3 chatbot.py \"$user_prompt\"");
            $response = shell_exec($command);

            if ($response) {
                echo "<h4>AI Yanıtı:</h4>";
                echo "<p>" . nl2br(htmlspecialchars($response)) . "</p>";
            }
        }
        ?>
    </div>

    <!-- Bootstrap JS ve jQuery Bağlantıları -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
