<?php
session_start();
include('db_connection.php');

// Admin kontrolü
if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

// Kullanıcıların ilerleme verilerini çekme
$stmt = $pdo->prepare("SELECT u.first_name, u.last_name, up.progress_stage, up.progress_percentage, up.time_spent, up.last_updated
                        FROM users u
                        JOIN user_progress up ON u.id = up.user_id");
$stmt->execute();
$user_progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Modül bazlı ilerleme yüzdelerini ve zaman bilgilerini hesaplama
$modules = [];
foreach ($user_progress_data as $user) {
    $modules[$user['progress_stage']][] = [
        'percentage' => $user['progress_percentage'],
        'time_spent' => $user['time_spent'],
        'last_updated' => $user['last_updated']
    ];
}

$average_progress_per_module = [];
$average_time_per_module = [];
foreach ($modules as $module => $data) {
    $total_percentage = array_sum(array_column($data, 'percentage'));
    $total_time_spent = array_sum(array_column($data, 'time_spent'));
    
    $average_progress_per_module[$module] = $total_percentage / count($data);
    $average_time_per_module[$module] = $total_time_spent / count($data);
}

// Yapay zeka analizi için kullanıcı verilerini hazırlama
$user_prompt = "İK Analitiği'nin basit bir çalışmasını yap. Profesyonel ol ama negatif yorum yapma veriler yeterli deme basit düşün güzel bir cevap ver. Kullanıcı İlerleme Verileri
Ad Soyad	Modül	İlerleme Yüzdesi	Geçirilen Süre (Dakika)	Son Güncelleme
samet tozlu	Backend Geliştiricisi Olarak İyi Bir Başlangıç Yapın	40%	68 dakika	2025-01-11 18:20:50
samet tozlu	API Tasarımı ve Entegrasyonları	100%	69 dakika	2025-01-11 18:20:50
samet tozlu	Veritabanı Yönetimi	60%	43 dakika	2025-01-11 18:20:50
samet tozlu	Versiyon Kontrol Sistemi (Git)	20%	8 dakika	2025-01-11 18:20:50
samet tozlu	Performans İyileştirme ve Güvenlik	80%	12 dakika	2025-01-11 18:20:50
samet_x xyz	Backend Geliştiricisi Olarak İyi Bir Başlangıç Yapın	100%	32 dakika	2025-01-11 18:20:50
samet_x xyz	API Tasarımı ve Entegrasyonları	100%	24 dakika	2025-01-11 18:20:50
samet_x xyz	Veritabanı Yönetimi	60%	24 dakika	2025-01-11 18:20:50
samet_x xyz	Versiyon Kontrol Sistemi (Git)	60%	49 dakika	2025-01-11 18:20:50
samet_x xyz	Performans İyileştirme ve Güvenlik	60%	71 dakika	2025-01-11 18:20:50
ahmet x	Performans İyileştirme ve Güvenlik	60%	8 dakika	2025-01-11 18:20:50
ahmet x	Versiyon Kontrol Sistemi (Git)	100%	25 dakika	2025-01-11 18:20:50
ahmet x	Backend Geliştiricisi Olarak İyi Bir Başlangıç Yapın	100%	3 dakika	2025-01-11 18:20:50
ahmet x	API Tasarımı ve Entegrasyonları	100%	38 dakika	2025-01-11 18:20:50  rakamlarla konuş. Lütfen aşağıdaki kullanıcı verileri doğrultusunda, modüllere göre ortalama ilerleme yüzdesi, ortalama geçirilen süre ve genel performans analizi sağlayın. Ayrıca, her modül için maksimum ve minimum ilerleme yüzdesi ile geçirilen süre analizini yapın. Verilen verilerle basit ve sayısal bir sonuç çıkararak, her modülün performansı hakkında kısa bir analiz sunun.

 tamamen sayısal veriler ver. ek olarak bir analizi seçip basit bir kısa bir analiz ver. İşte kullanıcı verileri: " . json_encode($user_progress_data);

// Python betiğine veri gönderme (CURL ile)
$command = escapeshellcmd("python3 chatbot.py \"$user_prompt\"");
$ai_response = shell_exec($command);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İK Yönetim Paneli - Analitik</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            margin-bottom: 20px;
        }
        .container {
            margin-top: 30px;
        }
        h1, h3 {
            text-align: center;
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
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Çıkış Yap</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Admin Dashboard -->
    <div class="container">
        <h1>İK Yönetim Paneli - Analitik</h1>

        <!-- Modül Bazlı Ortalama İlerleme Grafik -->
        <h3>Modül Bazlı Ortalama İlerleme</h3>
        <canvas id="moduleProgressChart" width="400" height="200"></canvas>
        <script>
            var ctx = document.getElementById('moduleProgressChart').getContext('2d');
            var moduleProgressChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($average_progress_per_module)); ?>,
                    datasets: [{
                        label: 'Ortalama İlerleme Yüzdesi',
                        data: <?php echo json_encode(array_values($average_progress_per_module)); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

        <!-- Modül Bazlı Ortalama Zaman Grafik -->
        <h3>Modül Bazlı Ortalama Zaman</h3>
        <canvas id="moduleTimeChart" width="400" height="200"></canvas>
        <script>
            var ctx = document.getElementById('moduleTimeChart').getContext('2d');
            var moduleTimeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($average_time_per_module)); ?>,
                    datasets: [{
                        label: 'Ortalama Zaman (Dakika)',
                        data: <?php echo json_encode(array_values($average_time_per_module)); ?>,
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

        <!-- Modül Bazlı Pasta Grafiği -->
        <h3>Modül Bazlı İlerleme Yüzdesi Pasta Grafiği</h3>
        <canvas id="modulePieChart" width="400" height="200"></canvas>
        <script>
            var ctx = document.getElementById('modulePieChart').getContext('2d');
            var modulePieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($average_progress_per_module)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($average_progress_per_module)); ?>,
                        backgroundColor: ['rgba(75, 192, 192, 0.2)', 'rgba(255, 159, 64, 0.2)', 'rgba(153, 102, 255, 0.2)'],
                        borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 159, 64, 1)', 'rgba(153, 102, 255, 1)'],
                        borderWidth: 1
                    }]
                }
            });
        </script>

        <!-- Kullanıcıların Genel İlerleme Yüzdeleri Tablosu -->
        <h3>Kullanıcı İlerleme Verileri</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Modül</th>
                    <th>İlerleme Yüzdesi</th>
                    <th>Geçirilen Süre (Dakika)</th>
                    <th>Son Güncelleme</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_progress_data as $user): ?>
                <tr>
                    <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                    <td><?php echo $user['progress_stage']; ?></td>
                    <td><?php echo $user['progress_percentage']; ?>%</td>
                    <td><?php echo $user['time_spent']; ?> dakika</td>
                    <td><?php echo $user['last_updated']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Yapay Zeka Analizi Sonucu -->
        <h3>Yapay Zeka Analizi</h3>
        <div class="alert alert-info">
            <strong>AI Analizi:</strong>
            <p><?php echo nl2br(htmlspecialchars($ai_response)); ?></p>
        </div>

    </div>

    <!-- Bootstrap JS ve jQuery Bağlantıları -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
