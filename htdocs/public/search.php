<?php
session_start();
$config = include(__DIR__ . '/../private/config.php');
try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']}",
        $config['db_user'],
        $config['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user = $_SESSION['user'] ?? null;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$params = [];
$where = "status = 'active'";
if ($q !== '') {
    $safe_q = preg_replace('/([\\\\^$.|?*+(){}\[\]])/', '\\\\$1', $q);
    $where .= " AND (title REGEXP ? OR category REGEXP ?)";
    $params[] = $safe_q;
    $params[] = $safe_q;
}
if ($category !== '') {
    $where .= " AND category = ?";
    $params[] = $category;
}
$stmt = $pdo->prepare("SELECT auction_id, title, image, start_price, bid_amount, category FROM AUCTIONS WHERE $where");
$stmt->execute($params);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Search Results - BidSecond</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/search.css">
</head>
<body>
    <!-- Header Section (copy from index.php) -->
    <header id="header">
        <div class="banner">
            <div class="logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="BidSecond Logo">
                </a>
            </div>
            <div class="search-bar">
                <form id="searchForm" class="search-bar" action="search.php" method="get" style="position:relative;">
                    <input type="text" id="searchInput" name="q" placeholder="Search for items..." value="<?php echo isset($q) ? htmlspecialchars($q) : ''; ?>" required>
                    <button type="submit">Search</button>
                    <button type="button" id="filterBtn" style="background: #fff; border: 1px solid #ccc; border-left: none; border-radius: 0 5px 5px 0; padding: 0 12px; cursor:pointer; display:flex; align-items:center;">
                        <!-- SVG filter icon -->
                        <svg width="20" height="20" fill="#57a05a" viewBox="0 0 24 24"><path d="M3 5h18v2H3zm4 7h10v2H7zm2 7h6v2H9z"/></svg>
                    </button>
                    <div id="filterDropdown" style="display:none; position:absolute; top:110%; right:0; background:#fff; border:1px solid #ccc; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:15px; z-index:10; min-width:200px;">
                        <div style="font-weight:bold; margin-bottom:8px;">Category</div>
                        <?php
                        // List of categories (should match your site)
                        $categories = [
                            "Electronics", "Fashion", "Home and Garden", "Toys and Games", "Automotive",
                            "Sports and Outdoors", "Books and Media", "Health and Beauty", "Jewelry and Watches",
                            "Music and Instruments", "Collectibles and Antiques", "Art and Craft"
                        ];
                        $selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
                        foreach ($categories as $cat): ?>
                            <div>
                                <label>
                                    <input type="radio" name="category" value="<?php echo htmlspecialchars($cat); ?>" <?php if ($selectedCategory === $cat) echo 'checked'; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin-top:8px;">
                            <button type="button" id="closeFilter" style="background:#57a05a; color:#fff; border:none; border-radius:4px; padding:4px 12px; cursor:pointer;">OK</button>
                        </div>
                    </div>
                </form>
            </div>
            <nav class="nav-links">
                <a href="sell.php">Sell</a>
                <a href="wallet.php">Balance</a>
                <?php if ($user && $user['roles'] === 'admin') { ?>
                    <a href="dashboard.php">Dashboard</a>
                <?php } ?>
                <a href="account.php">Account</a>
                <a href="cart.php">Cart</a>
            </nav>
        </div>
    </header>

    <main id="main-content">
        <div class="search-title">
            Result for "<?php echo htmlspecialchars($q); ?>"
        </div>
        <div class="bidding-items">
            <?php if ($q === ''): ?>
                <div class="no-results">Please enter a search term.</div>
            <?php elseif (count($results) === 0): ?>
                <div class="no-results">No products found for "<?php echo htmlspecialchars($q); ?>".</div>
            <?php else: ?>
                <?php foreach ($results as $product): ?>
                    <div class="bidding-item">
                        <?php if (!empty($product['image'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <?php else: ?>
                            <img src="images/no-image.jpg" alt="No Image Available">
                        <?php endif; ?>
                        <p class="item-title">
                            <a href="Product.php?auction_id=<?php echo $product['auction_id']; ?>">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </a>
                        </p>
                        <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p>Start Price: ฿<?php echo number_format($product['start_price'], 2); ?></p>
                        <p>Current Bid: ฿<?php echo number_format($product['bid_amount'], 2); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer (copy from index.php) -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>
    <script src="scripts/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var filterBtn = document.getElementById('filterBtn');
        var dropdown = document.getElementById('filterDropdown');
        var closeFilter = document.getElementById('closeFilter');

        if (filterBtn && dropdown) {
            filterBtn.onclick = function(e) {
                e.preventDefault();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            };
        }
        if (closeFilter && dropdown) {
            closeFilter.onclick = function() {
                dropdown.style.display = 'none';
            };
        }
        document.addEventListener('click', function(e) {
            if (
                filterBtn && dropdown &&
                !filterBtn.contains(e.target) &&
                !dropdown.contains(e.target)
            ) {
                dropdown.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>