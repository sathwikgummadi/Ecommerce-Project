<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
/* -----------------------------
   ADD TO CART
------------------------------*/
if (isset($_POST['add_to_cart'])) {

    $product_id = (int)$_POST['product_id'];

    // Check if product already exists
    $stmt = $conn->prepare("
        SELECT id, quantity
        FROM cart
        WHERE user_id = ? AND product_id = ?
    ");

    $stmt->execute([$user_id, $product_id]);

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {

        $stmt = $conn->prepare("
            UPDATE cart
            SET quantity = quantity + 1
            WHERE user_id = ? AND product_id = ?
        ");

        $stmt->execute([$user_id, $product_id]);

    } else {

        $stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, 1)
        ");

        $stmt->execute([$user_id, $product_id]);
    }

    header("Location: cart.php");
    exit();
}

/* -----------------------------
   UPDATE QUANTITY
------------------------------*/
if (isset($_POST['update_quantity'])) {

    $product_id = (int)$_POST['product_id'];
    $quantity   = max(1, (int)$_POST['quantity']);

    $stmt = $conn->prepare("
        UPDATE cart
        SET quantity = ?
        WHERE user_id = ? AND product_id = ?
    ");

    $stmt->execute([$quantity, $user_id, $product_id]);

    header("Location: cart.php");
    exit();
}

/* -----------------------------
   REMOVE ITEM
------------------------------*/
if (isset($_POST['remove_from_cart'])) {

    $cart_id = (int)$_POST['cart_id'];

    $stmt = $conn->prepare("
        DELETE FROM cart
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$cart_id, $user_id]);

    header("Location: cart.php");
    exit();
}

/* -----------------------------
   FETCH CART ITEMS
------------------------------*/
$stmt = $conn->prepare("
    SELECT
        cart.id AS cart_id,
        cart.product_id,
        cart.quantity,
        products.name,
        products.price,
        products.image
    FROM cart
    INNER JOIN products
        ON cart.product_id = products.id
    WHERE cart.user_id = ?
");

$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
?>


<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f7fa;
            margin:0;
            padding:20px;
        }

        .cart-container{
            max-width:1200px;
            margin:auto;
        }

        .cart-title{
            text-align:center;
            margin-bottom:30px;
            font-size:40px;
        }

        .cart-item{
            display:flex;
            justify-content:space-between;
            align-items:center;
            background:#fff;
            padding:20px;
            margin-bottom:20px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,.08);
        }

        .product-info{
            display:flex;
            align-items:center;
            gap:20px;
        }

        .cart-product-image{
            width:120px;
            height:120px;
            object-fit:cover;
            border-radius:8px;
            border:1px solid #ddd;
        }

        .product-details h3{
            margin:0;
            font-size:24px;
        }

        .product-details p{
            margin-top:10px;
            font-size:18px;
            color:#555;
        }

        .actions{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .update-form{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .qty-input{
            width:70px;
            padding:8px;
            text-align:center;
        }

        .btn-update{
            background:#28a745;
            color:white;
            border:none;
            padding:10px 15px;
            border-radius:5px;
            cursor:pointer;
        }

        .btn-remove{
            background:#dc3545;
            color:white;
            border:none;
            padding:10px 15px;
            border-radius:5px;
            cursor:pointer;
        }

        .cart-total{
            text-align:right;
            font-size:28px;
            font-weight:bold;
            margin-top:20px;
        }
    </style>
</head>
<body>

<div class="cart-container">
    <a href="../index.php" class="back-btn">← Back to Products</a>

    <h1 class="cart-title">Your Cart</h1>

    <?php if(count($cart_items) > 0): ?>

        <?php foreach($cart_items as $item): ?>

            <?php
            $subtotal = $item['price'] * $item['quantity'];
            $total_cost += $subtotal;
            ?>

            <div class="cart-item">

                <div class="product-info">

                    <img
                        src="../images/<?= htmlspecialchars($item['image']); ?>"
                        alt="<?= htmlspecialchars($item['name']); ?>"
                        class="cart-product-image"
                    >

                    <div class="product-details">
                        <h3><?= htmlspecialchars($item['name']); ?></h3>

                        <p>
                            Price:
                            $<?= number_format($item['price'], 2); ?>
                        </p>

                        <p>
                            Subtotal:
                            $<?= number_format($subtotal, 2); ?>
                        </p>
                    </div>

                </div>

                <div class="actions">

                    <form method="POST" class="update-form">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= $item['product_id']; ?>"
                        >

                        <input
                            type="number"
                            name="quantity"
                            value="<?= $item['quantity']; ?>"
                            min="1"
                            class="qty-input"
                        >

                        <button
                            type="submit"
                            name="update_quantity"
                            class="btn-update"
                        >
                            Update
                        </button>

                    </form>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="cart_id"
                            value="<?= $item['cart_id']; ?>"
                        >

                        <button
                            type="submit"
                            name="remove_from_cart"
                            class="btn-remove"
                        >
                            Remove
                        </button>

                    </form>

                </div>

            </div>

        <?php endforeach; ?>

        <div class="cart-total">
            Total: $<?= number_format($total_cost, 2); ?>
        </div>

    <?php else: ?>

        <h2 style="text-align:center;">Your cart is empty.</h2>

    <?php endif; ?>

</div>

</body>
</html>

