<?php
session_start();
include('db_connection.php');

// Kullanıcı girişi kontrolü
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // Eğer kullanıcı giriş yapmamışsa, login.php'ye yönlendir
    header('Location: login.php');
    exit();
}

// Kullanıcı verilerini al
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcı ilerlemesini al
$stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$user_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Oryantasyon konuları
$topics = [
    'Backend Geliştiricisi Olarak İyi Bir Başlangıç Yapın' => 'Backend geliştiricisi olarak iyi bir kariyer başlatmak için temelleri sağlam öğrenmelisiniz. PHP, Python, Node.js gibi diller ve veritabanı teknolojileri hakkında bilgi sahibi olmanız gerekir.',
    'API Tasarımı ve Entegrasyonları' => 'Backend geliştiricilerinin günlük işleri arasında API tasarımı ve dış sistemlerle entegrasyon sıklıkla yer alır. RESTful API’leri ve GraphQL API’leri hakkında bilgi sahibi olmalısınız.',
    'Veritabanı Yönetimi' => 'Veritabanlarıyla çalışmak backend geliştiricisi olarak en önemli yetkinliklerden biridir. SQL ve NoSQL veritabanları arasında farkları anlamalı ve her birini doğru kullanabilmelisiniz.',
    'Versiyon Kontrol Sistemi (Git)' => 'Git, yazılım geliştirme sürecinde önemli bir yer tutar. Kaynak kodlarını versiyon kontrolü altında tutmak, takım çalışmasında önemli bir araçtır.',
    'Performans İyileştirme ve Güvenlik' => 'Backend geliştiricileri, uygulama performansını optimize etmeli ve güvenlik önlemlerini almalıdır. SQL enjeksiyonu, XSS gibi saldırılara karşı güvenlik önlemleri alınmalıdır.'
];

// Kullanıcının genel ilerlemesini hesapla
$total_percentage = 0;
$total_topics = count($topics);
$completed_topics = 0;

foreach ($user_progress as $progress_entry) {
    if ($progress_entry['progress_percentage'] == 100) {
        $completed_topics++;
    }
    $total_percentage += $progress_entry['progress_percentage'];
}

$average_percentage = ($total_percentage / ($total_topics * 100)) * 100;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Oryantasyon Sistemi</title>

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
        <h1 class="text-center my-4">Hoş Geldiniz, <?php echo htmlspecialchars($user_data['first_name']); ?>!</h1>

        <h3>İlerleme Durumunuz</h3>

        <div class="card my-3">
            <div class="card-body">
                <h5 class="card-title">Genel İlerleme</h5>
                <p class="card-text">Genel ilerlemeniz: <strong><?php echo number_format($average_percentage, 2); ?>%</strong></p>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $average_percentage; ?>%" aria-valuenow="<?php echo $average_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="mt-2">Tamamlanan Konular: <?php echo $completed_topics; ?> / <?php echo $total_topics; ?></p>
            </div>
        </div>

        <h3>Konuların İlerlemesi</h3>
        
        <?php
        foreach ($topics as $title => $description) {
            // Kullanıcı ilerlemesini bul
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
            echo '  </div>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Bootstrap JS ve jQuery Bağlantıları -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
