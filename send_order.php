<?php
include 'db.php';

// استقبال البيانات المرسلة بصيغة JSON من الـ JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $client_name     = $conn->real_escape_string($data['name']);
    $client_phone    = $conn->real_escape_string($data['phone']);
    $client_location = $conn->real_escape_string($data['location']);
    $discount_code   = isset($data['discount_code']) ? $conn->real_escape_string($data['discount_code']) : NULL;
    $discount_value  = isset($data['discount_value']) ? floatval($data['discount_value']) : 0.00;
    $total_price     = floatval($data['total_price']);
    $cart_items      = $data['cart_items']; // مصفوفة المنتجات داخل السلة

    // 1. إدراج بيانات الزبون والطلب في جدول orders
    $sql_order = "INSERT INTO orders (client_name, client_phone, client_location, discount_code, discount_value, total_price) 
                  VALUES ('$client_name', '$client_phone', '$client_location', '$discount_code', '$discount_value', '$total_price')";

    if ($conn->query($sql_order) === TRUE) {
        $order_id = $conn->insert_id; // الحصول على رقم الفاتورة التي تم إنشاؤها للتو

        // 2. إدراج عناصر السلة داخل جدول order_items بالربط مع رقم الفاتورة
        $sql_items = "INSERT INTO order_items (order_id, product_name, price, quantity) VALUES ";
        $rows = [];
        foreach ($cart_items as $item) {
            $p_name = $conn->real_escape_string($item['name']);
            $p_price = floatval($item['price']);
            $p_qty = intval($item['quantity']);
            $rows[] = "('$order_id', '$p_name', '$p_price', '$p_qty')";
        }
        
        $sql_items .= implode(", ", $rows);

        if ($conn->query($sql_items) === TRUE) {
            // إرسال رد نجاح للـ JS مع رقم الطلب
            echo json_encode(["status" => "success", "message" => "تم تسجيل طلبك بنجاح!", "order_id" => $order_id]);
        } else {
            echo json_encode(["status" => "error", "message" => "فشل حفظ تفاصيل الطلب: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "فشل تسجيل الطلب الرئيسي: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات الطلب غير مكتملة."]);
}

$conn->close();
?>