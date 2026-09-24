<?php
require_once '../php/db.php'; // Database connection ($conn)
header('Content-Type: application/json');

try {
    // === INPUTS ===
    $search       = $_GET['search'] ?? '';
    $fetchAll     = ($_GET['all'] ?? '') === 'true';
    $category     = $_GET['category'] ?? 'all';
    $page         = $fetchAll ? 1 : max(1, (int)($_GET['page'] ?? 1));
    $itemsPerPage = 12;
    $offset       = ($page - 1) * $itemsPerPage;

    $search_param = "%$search%";

    // === BUILD WHERE CLAUSE ===
    $where  = [];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $types   .= 'ss';
    }

    if ($category !== 'all') {
        $where[] = "p.category_id = ?";
        $params[] = (int)$category;
        $types   .= 'i';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // === PAGINATION (count only if not fetching all) ===
    $totalItems = 0;
    if (!$fetchAll) {
        $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p $whereSql";
        $countStmt = $conn->prepare($countSql);

        if ($types) {
            $countStmt->bind_param($types, ...$params);
        }

        $countStmt->execute();
        $totalItems = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
        $countStmt->close();

        $totalPages  = ceil($totalItems / $itemsPerPage);
        $currentPage = $page;
    } else {
        $totalPages  = 1;
        $currentPage = 1;
    }

    // === MAIN QUERY ===
    $mainSql = "
        SELECT 
            p.id, p.name, p.selling_price, p.stock, p.category_id,
            COALESCE(p.images, 'default.jpg') AS images,
            GROUP_CONCAT(DISTINCT pv.color) AS colors,
            GROUP_CONCAT(DISTINCT pv.size)  AS sizes
        FROM products p
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        $whereSql
        GROUP BY p.id
    ";

    if (!$fetchAll) {
        $mainSql .= " LIMIT ? OFFSET ?";
    }

    $stmt = $conn->prepare($mainSql);

    // === BIND PARAMETERS ===
    if ($fetchAll) {
        // Only search/category params
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
    } else {
        // Include LIMIT and OFFSET
        $stmtTypes  = $types . 'ii';
        $stmtParams = [...$params, $itemsPerPage, $offset];
        $stmt->bind_param($stmtTypes, ...$stmtParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['colors'] = $row['colors'] ? array_unique(explode(',', $row['colors'])) : [];
        $row['sizes']  = $row['sizes']  ? array_unique(explode(',', $row['sizes']))  : [];
        $row['images'] = $row['images'] ? explode(',', $row['images']) : ['default.jpg'];
        $products[] = $row;
    }

    $stmt->close();

    // === RESPONSE ===
    echo json_encode([
        'success'     => true,
        'products'    => $products,
        'totalPages'  => $totalPages,
        'currentPage' => $currentPage,
        'totalItems'  => $totalItems ?: count($products)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
