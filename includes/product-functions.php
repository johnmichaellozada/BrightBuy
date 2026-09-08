<?php

/**
 * BrightBuy Product Functions
 *
 * Reusable database functions for product management.
 */


/* =====================================================
   GET ALL PRODUCTS
===================================================== */

function getAllProducts(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock,
            p.image,
            p.status,
            p.category_id,
            c.category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.category_id
        ORDER BY p.product_id ASC
    ");

    return $stmt->fetchAll();
}


/* =====================================================
   GET PRODUCT BY ID
===================================================== */

function getProductById(PDO $pdo, int $productId): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock,
            p.image,
            p.status,
            p.category_id,
            c.category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.category_id
        WHERE p.product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $productId
    ]);

    $product = $stmt->fetch();

    return $product ?: null;
}


/* =====================================================
   GET ALL CATEGORIES
===================================================== */

function getAllCategories(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            category_id,
            category_name
        FROM categories
        ORDER BY category_name ASC
    ");

    return $stmt->fetchAll();
}


/* =====================================================
   CREATE PRODUCT
===================================================== */

function createProduct(
    PDO $pdo,
    string $productName,
    string $description,
    float $price,
    int $stock,
    ?int $categoryId,
    string $image,
    string $status = "active"
): bool {

    $stmt = $pdo->prepare("
        INSERT INTO products (
            product_name,
            description,
            price,
            stock,
            category_id,
            image,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    return $stmt->execute([
        $productName,
        $description,
        $price,
        $stock,
        $categoryId,
        $image,
        $status
    ]);
}


/* =====================================================
   UPDATE PRODUCT
===================================================== */

function updateProduct(
    PDO $pdo,
    int $productId,
    string $productName,
    string $description,
    float $price,
    int $stock,
    ?int $categoryId,
    string $image,
    string $status
): bool {

    $stmt = $pdo->prepare("
        UPDATE products
        SET
            product_name = ?,
            description = ?,
            price = ?,
            stock = ?,
            category_id = ?,
            image = ?,
            status = ?
        WHERE product_id = ?
    ");

    return $stmt->execute([
        $productName,
        $description,
        $price,
        $stock,
        $categoryId,
        $image,
        $status,
        $productId
    ]);
}


/* =====================================================
   UPDATE PRODUCT STOCK
===================================================== */

function updateProductStock(
    PDO $pdo,
    int $productId,
    int $stock
): bool {

    $stmt = $pdo->prepare("
        UPDATE products
        SET stock = ?
        WHERE product_id = ?
    ");

    return $stmt->execute([
        $stock,
        $productId
    ]);
}


/* =====================================================
   DELETE PRODUCT
===================================================== */

function deleteProduct(
    PDO $pdo,
    int $productId
): bool {

    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE product_id = ?
    ");

    return $stmt->execute([
        $productId
    ]);
}


/* =====================================================
   CHECK PRODUCT EXISTS
===================================================== */

function productExists(
    PDO $pdo,
    int $productId
): bool {

    $stmt = $pdo->prepare("
        SELECT product_id
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $productId
    ]);

    return (bool) $stmt->fetch();
}