<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول لعرض المحفظة', 'error');
}

try {
    $user = get_current_user_data();
    if (!$user) {
        session_destroy();
        redirect_with_message('login.html', 'حدث خطأ في الجلسة. يرجى تسجيل الدخول مرة أخرى', 'error');
    }

    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $transactions = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_amount'])) {
        $amount = floatval($_POST['deposit_amount']);
        
        if ($amount > 0 && $amount <= 10000) { // Max 10,000 SAR
            if (update_wallet_balance($_SESSION['user_id'], $amount, 'deposit', 'شحن المحفظة')) {
                redirect_with_message('wallet.php', 'تم شحن المحفظة بنجاح', 'success');
            } else {
                redirect_with_message('wallet.php', 'حدث خطأ أثناء شحن المحفظة', 'error');
            }
        } else {
            redirect_with_message('wallet.php', 'المبلغ غير صحيح. يجب أن يكون بين 1 و 10,000 دينار ليبي', 'error');
        }
    }
    
} catch (Exception $e) {
    error_log("Error in wallet.php: " . $e->getMessage());
    $transactions = [];
    if (!isset($user)) {
        redirect_with_message('login.html', 'حدث خطأ في النظام. يرجى المحاولة مرة أخرى', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحفظة - ARG Academy</title>
    <link rel="stylesheet" type="text/css" href="wallet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Doto:wght@100..900&family=Major+Mono+Display&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header dir="rtl">
        <div class="logo">
            <div class="name2" dir="ltr"><span class="letter-a">A</span><span class="letter-c">c</span><span class="letter-a1">a</span><span class="letter-d2">d</span><span class="letter-e">e</span><span class="letter-m1">m</span><span class="letter-y1">y</span></div>
            <div class="name">ARG</div>
        </div>
        
        <nav>
            <div class="links">
                <a href="index.html" class="link">الصفحة الرئيسية</a>
                <a href="courses.php" class="link">الدورات</a>
                <a href="<?php echo $_SESSION['user_type'] === 'student' ? 'Student.php' : 'teacher.php'; ?>" class="link">لوحة التحكم</a>
                <a href="auth/logout.php" class="link">تسجيل خروج</a>
            </div>
        </nav>
    </header>

    <main dir="rtl">
        <div class="container">
            <?php echo display_message(); ?>
            
            <div class="wallet-header">
                <h1>المحفظة الإلكترونية</h1>
                <p>إدارة رصيدك والمعاملات المالية</p>
            </div>

            <div class="wallet-balance">
                <div class="balance-card">
                    <div class="balance-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="balance-info">
                        <h2>الرصيد الحالي</h2>
                        <div class="balance-amount">
                            <?php echo number_format($user['wallet_balance'] ?? 0, 2); ?> دينار ليبي
                        </div>
                    </div>
                </div>
            </div>

            <div class="wallet-actions">
                <div class="action-section">
                    <h3>شحن المحفظة</h3>
                    <form method="POST" class="deposit-form">
                        <div class="form-group">
                            <label for="deposit_amount">المبلغ (دينار ليبي)</label>
                            <input type="number" id="deposit_amount" name="deposit_amount" 
                                   min="1" max="10000" step="0.01" required 
                                   placeholder="أدخل المبلغ المراد شحنه">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> شحن المحفظة
                        </button>
                    </form>
                </div>

                <div class="quick-deposit">
                    <h4>شحن سريع</h4>
                    <div class="quick-amounts">
                        <button onclick="setAmount(50)" class="amount-btn">50 دينار ليبي</button>
<button onclick="setAmount(100)" class="amount-btn">100 دينار ليبي</button>
<button onclick="setAmount(200)" class="amount-btn">200 دينار ليبي</button>
<button onclick="setAmount(500)" class="amount-btn">500 دينار ليبي</button>
                    </div>
                </div>
            </div>

            <div class="transactions-section">
                <h3>آخر المعاملات</h3>
                <?php if (!empty($transactions)): ?>
                    <div class="transactions-list">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-icon">
                                    <?php
                                    $icon_class = '';
                                    $color_class = '';
                                    switch ($transaction['transaction_type']) {
                                        case 'deposit':
                                            $icon_class = 'fas fa-plus';
                                            $color_class = 'text-success';
                                            break;
                                        case 'purchase':
                                            $icon_class = 'fas fa-shopping-cart';
                                            $color_class = 'text-danger';
                                            break;
                                        case 'withdrawal':
                                            $icon_class = 'fas fa-minus';
                                            $color_class = 'text-warning';
                                            break;
                                        case 'refund':
                                            $icon_class = 'fas fa-undo';
                                            $color_class = 'text-info';
                                            break;
                                    }
                                    ?>
                                    <i class="<?php echo $icon_class; ?> <?php echo $color_class; ?>"></i>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-title">
                                        <?php
                                        switch ($transaction['transaction_type']) {
                                            case 'deposit':
                                                echo 'شحن المحفظة';
                                                break;
                                            case 'purchase':
                                                echo 'شراء دورة';
                                                break;
                                            case 'withdrawal':
                                                echo 'سحب من المحفظة';
                                                break;
                                            case 'refund':
                                                echo 'استرداد';
                                                break;
                                        }
                                        ?>
                                    </div>
                                    <div class="transaction-description">
                                        <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                    </div>
                                    <div class="transaction-date">
                                        <?php echo format_date($transaction['created_at'] ?? '', 'Y-m-d H:i'); ?>
                                    </div>
                                </div>
                                <div class="transaction-amount <?php echo ($transaction['amount'] ?? 0) > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo (($transaction['amount'] ?? 0) > 0 ? '+' : '') . number_format($transaction['amount'] ?? 0, 2); ?> دينار ليبي
                                </div>
                                <div class="transaction-status">
                                    <span class="status-badge status-<?php echo $transaction['status'] ?? 'pending'; ?>">
                                        <?php
                                        switch ($transaction['status'] ?? 'pending') {
                                            case 'completed':
                                                echo 'مكتمل';
                                                break;
                                            case 'pending':
                                                echo 'قيد المعالجة';
                                                break;
                                            case 'failed':
                                                echo 'فشل';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-transactions">
                        <i class="fas fa-receipt"></i>
                        <p>لا توجد معاملات بعد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function setAmount(amount) {
        document.getElementById('deposit_amount').value = amount;
    }
    </script>
</body>
</html> 