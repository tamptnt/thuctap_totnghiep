<?php
/**
 * Sinh ~200 sản phẩm demo (đa dạng danh mục/thương hiệu) kèm ảnh minh hoạ dạng vector đơn giản,
 * cùng phong cách với ảnh placeholder sẵn có của dự án (thẻ bo góc, icon minh hoạ theo danh mục,
 * KHÔNG dùng logo/ảnh thật của hãng để tránh vấn đề bản quyền).
 *
 * Chạy: php generate_products.php
 * Kết quả: uploads/seed_xxx.png (ảnh) + seed_extra_products.sql (câu lệnh INSERT)
 */

$outputDir = __DIR__ . '/uploads';
$fontRegular = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
$fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

$categories = [
    1 => ['name' => 'Laptop', 'color' => [37, 99, 235], 'tag' => 'LAPTOP'],
    2 => ['name' => 'Điện thoại', 'color' => [124, 58, 237], 'tag' => 'PHONE'],
    3 => ['name' => 'Máy tính bảng', 'color' => [13, 148, 136], 'tag' => 'TABLET'],
    4 => ['name' => 'Màn hình', 'color' => [79, 70, 229], 'tag' => 'MONITOR'],
    5 => ['name' => 'Phụ kiện', 'color' => [234, 88, 12], 'tag' => 'PHỤ KIỆN'],
    6 => ['name' => 'Thiết bị mạng', 'color' => [22, 163, 74], 'tag' => 'NETWORK'],
    7 => ['name' => 'Âm thanh', 'color' => [219, 39, 119], 'tag' => 'AUDIO'],
    8 => ['name' => 'Linh kiện máy tính', 'color' => [220, 38, 38], 'tag' => 'PC PARTS'],
];

// ===== Bộ sinh tên sản phẩm theo từng danh mục =====
function buildCatalog()
{
    $items = [];

    // 1) LAPTOP
    $laptopBrands = [
        'ASUS' => ['Vivobook 15 OLED', 'Zenbook 14 OLED', 'TUF Gaming A15', 'ROG Strix G16'],
        'Lenovo' => ['ThinkPad E14', 'IdeaPad Slim 5', 'Legion 5', 'Yoga Slim 7'],
        'Dell' => ['Inspiron 14', 'Vostro 15', 'XPS 13', 'Latitude 5440'],
        'Apple' => ['MacBook Air M2 13 inch', 'MacBook Air M3 15 inch', 'MacBook Pro 14 M3', 'MacBook Pro 16 M3 Pro'],
    ];
    $cpus = ['i3-1315U', 'i5-1340P', 'i5-13500H', 'i7-13700H', 'Ryzen 5 7530U', 'Ryzen 7 7735HS'];
    $rams = ['8GB', '16GB', '32GB'];
    $storages = ['256GB', '512GB', '1TB'];
    $priceBase = 11000000;
    foreach ($laptopBrands as $brand => $models) {
        foreach ($models as $model) {
            for ($i = 0; $i < 4; $i++) {
                $isApple = $brand === 'Apple';
                $ram = $rams[array_rand($rams)];
                $storage = $storages[array_rand($storages)];
                $name = $isApple ? "$model $ram $storage" : "Laptop $brand $model " . $cpus[array_rand($cpus)] . " $ram $storage";
                $price = $priceBase + rand(0, 34) * 1000000 + ($isApple ? 8000000 : 0);
                $items[] = ['cat' => 1, 'brand' => $brand, 'name' => $name, 'price' => $price];
            }
        }
    }

    // 2) ĐIỆN THOẠI
    $phoneBrands = [
        'Apple' => ['iPhone 13', 'iPhone 14', 'iPhone 15', 'iPhone 15 Pro', 'iPhone 15 Pro Max'],
        'Samsung' => ['Galaxy A15', 'Galaxy A55', 'Galaxy S23', 'Galaxy S24', 'Galaxy S24 Ultra', 'Galaxy Z Flip5'],
        'Xiaomi' => ['Redmi 13C', 'Redmi Note 13', 'Redmi Note 13 Pro', 'Xiaomi 13T', 'Xiaomi 14'],
    ];
    $storagesPhone = ['64GB', '128GB', '256GB', '512GB'];
    foreach ($phoneBrands as $brand => $models) {
        foreach ($models as $model) {
            foreach (['128GB', '256GB'] as $storage) {
                $name = "$model $storage";
                $price = ($brand === 'Apple' ? 15000000 : ($brand === 'Samsung' ? 6000000 : 3500000)) + rand(0, 20) * 1000000;
                $items[] = ['cat' => 2, 'brand' => $brand, 'name' => $name, 'price' => $price];
            }
        }
    }

    // 3) MÁY TÍNH BẢNG
    $tabletBrands = [
        'Apple' => ['iPad Gen 10', 'iPad Air M2 11 inch', 'iPad Pro M4 11 inch', 'iPad mini 6'],
        'Samsung' => ['Galaxy Tab A9+', 'Galaxy Tab S9', 'Galaxy Tab S9 FE', 'Galaxy Tab S9 Ultra'],
        'Xiaomi' => ['Xiaomi Pad 6', 'Redmi Pad SE'],
    ];
    foreach ($tabletBrands as $brand => $models) {
        foreach ($models as $model) {
            foreach (['WiFi 128GB', 'WiFi 256GB'] as $variant) {
                $name = "$model $variant";
                $price = ($brand === 'Apple' ? 9000000 : ($brand === 'Samsung' ? 6000000 : 4500000)) + rand(0, 16) * 1000000;
                $items[] = ['cat' => 3, 'brand' => $brand, 'name' => $name, 'price' => $price];
            }
        }
    }

    // 4) MÀN HÌNH
    $monitorBrands = [
        'Dell' => ['UltraSharp 24 inch FHD', 'UltraSharp 27 inch 4K', 'Curved 32 inch QHD'],
        'ASUS' => ['TUF Gaming 27 inch 165Hz', 'ProArt 27 inch 4K', 'VA24 24 inch FHD'],
        'Samsung' => ['Odyssey G5 27 inch', 'ViewFinity S7 32 inch 4K', 'Smart Monitor M7 32 inch'],
    ];
    foreach ($monitorBrands as $brand => $models) {
        foreach ($models as $model) {
            for ($i = 0; $i < 3; $i++) {
                $name = "Màn hình $brand $model";
                $price = 3000000 + rand(0, 12) * 1000000;
                $items[] = ['cat' => 4, 'brand' => $brand, 'name' => $name, 'price' => $price];
            }
        }
    }

    // 5) PHỤ KIỆN
    $accBrands = [
        'Logitech' => ['Chuột không dây MX Master 3S', 'Chuột M331 Silent', 'Bàn phím K380', 'Bàn phím cơ G Pro X', 'Webcam C920'],
        'Apple' => ['Magic Mouse', 'Magic Keyboard', 'Bút cảm ứng Apple Pencil'],
        'Kingston' => ['Sạc dự phòng 10000mAh', 'USB DataTraveler 64GB', 'USB DataTraveler 128GB'],
        'Xiaomi' => ['Sạc dự phòng 20000mAh', 'Cáp sạc nhanh Type-C', 'Đế sạc không dây'],
        'Samsung' => ['Củ sạc nhanh 25W', 'Ốp lưng chính hãng', 'Cáp USB-C to USB-C'],
    ];
    foreach ($accBrands as $brand => $models) {
        foreach ($models as $model) {
            for ($i = 0; $i < 3; $i++) {
                $name = "$model ($brand)";
                $price = 300000 + rand(0, 40) * 100000;
                $items[] = ['cat' => 5, 'brand' => $brand, 'name' => $name, 'price' => $price];
            }
        }
    }

    // 6) THIẾT BỊ MẠNG
    $networkModels = ['Router WiFi Archer AX10', 'Router WiFi Archer AX55', 'Bộ phát WiFi Mesh Deco X20 (gói 2)', 'Bộ phát WiFi Mesh Deco X60 (gói 3)', 'Router WiFi 6 Archer AX73', 'Switch chia mạng 8 port', 'Bộ khuếch đại sóng WiFi RE605X'];
    foreach ($networkModels as $model) {
        for ($i = 0; $i < 4; $i++) {
            $name = "TP-Link $model";
            $price = 500000 + rand(0, 55) * 100000;
            $items[] = ['cat' => 6, 'brand' => 'TP-Link', 'name' => $name, 'price' => $price];
        }
    }

    // 7) ÂM THANH
    $audioBrands = [
        'Sony' => ['Tai nghe WH-1000XM5', 'Tai nghe WF-1000XM5', 'Loa Bluetooth SRS-XB13', 'Loa Bluetooth SRS-XB23', 'Tai nghe thể thao WI-C100'],
        'Apple' => ['AirPods 3', 'AirPods Pro 2', 'AirPods Max'],
        'Logitech' => ['Loa vi tính Z207', 'Tai nghe chụp tai G335'],
        'Xiaomi' => ['Tai nghe Redmi Buds 4', 'Loa Bluetooth Mi Portable'],
    ];
    foreach ($audioBrands as $brand => $models) {
        foreach ($models as $model) {
            for ($i = 0; $i < 3; $i++) {
                $name = "$model ($brand)";
                $price = 500000 + rand(0, 90) * 100000;
                $items[] = ['cat' => 7, 'brand' => $brand, 'name' => $name, 'price' => $price];
            }
        }
    }

    // 8) LINH KIỆN MÁY TÍNH
    $partModels = ['RAM Fury Beast 8GB DDR4', 'RAM Fury Beast 16GB DDR4', 'RAM Fury Beast 32GB DDR5', 'SSD NV2 500GB NVMe', 'SSD NV2 1TB NVMe', 'SSD A400 480GB SATA', 'USB Fury 128GB'];
    foreach ($partModels as $model) {
        for ($i = 0; $i < 4; $i++) {
            $name = "Kingston $model";
            $price = 500000 + rand(0, 30) * 100000;
            $items[] = ['cat' => 8, 'brand' => 'Kingston', 'name' => $name, 'price' => $price];
        }
    }

    return $items;
}

// ===== Vẽ icon minh hoạ theo danh mục (không dùng logo/ảnh hãng thật) =====
function drawCategoryIcon($im, $catId, $cx, $cy, $accentColor)
{
    $accent = imagecolorallocate($im, $accentColor[0], $accentColor[1], $accentColor[2]);
    $dark = imagecolorallocate($im, 30, 41, 59);
    $light = imagecolorallocate($im, 226, 232, 240);
    $mid = imagecolorallocate($im, 148, 163, 184);
    $white = imagecolorallocate($im, 255, 255, 255);

    switch ($catId) {
        case 1: // Laptop
            imagefilledrectangle($im, $cx - 210, $cy - 150, $cx + 210, $cy + 130, $dark);
            imagefilledrectangle($im, $cx - 185, $cy - 125, $cx + 185, $cy + 105, $light);
            imagefilledrectangle($im, $cx - 160, $cy - 95, $cx + 60, $cy - 40, $accent);
            imagefilledrectangle($im, $cx - 160, $cy - 10, $cx + 140, $cy + 25, $mid);
            $poly = [$cx - 260, $cy + 155, $cx + 260, $cy + 155, $cx + 205, $cy + 210, $cx - 205, $cy + 210];
            imagefilledpolygon($im, $poly, $mid);
            imagefilledrectangle($im, $cx - 55, $cy + 178, $cx + 55, $cy + 190, imagecolorallocate($im, 100, 116, 139));
            break;
        case 2: // Điện thoại
            imagefilledrectangle($im, $cx - 110, $cy - 220, $cx + 110, $cy + 220, $dark);
            imagefilledrectangle($im, $cx - 90, $cy - 195, $cx + 90, $cy + 165, $light);
            imagefilledrectangle($im, $cx - 65, $cy - 160, $cx + 65, $cy - 60, $accent);
            imagefilledrectangle($im, $cx - 65, $cy - 40, $cx + 40, $cy - 15, $mid);
            imagefilledrectangle($im, $cx - 65, $cy, $cx + 65, $cy + 25, $mid);
            imagefilledellipse($im, $cx, $cy + 192, 26, 26, $mid);
            break;
        case 3: // Máy tính bảng
            imagefilledrectangle($im, $cx - 190, $cy - 150, $cx + 190, $cy + 150, $dark);
            imagefilledrectangle($im, $cx - 165, $cy - 125, $cx + 165, $cy + 110, $light);
            imagefilledrectangle($im, $cx - 140, $cy - 95, $cx + 20, $cy - 40, $accent);
            imagefilledrectangle($im, $cx - 140, $cy - 10, $cx + 120, $cy + 20, $mid);
            imagefilledellipse($im, $cx, $cy + 132, 16, 16, $mid);
            break;
        case 4: // Màn hình
            imagefilledrectangle($im, $cx - 220, $cy - 160, $cx + 220, $cy + 90, $dark);
            imagefilledrectangle($im, $cx - 195, $cy - 135, $cx + 195, $cy + 65, $light);
            imagefilledrectangle($im, $cx - 165, $cy - 105, $cx + 30, $cy - 50, $accent);
            imagefilledrectangle($im, $cx - 165, $cy - 20, $cx + 145, $cy + 15, $mid);
            imagefilledrectangle($im, $cx - 18, $cy + 90, $cx + 18, $cy + 140, $mid);
            imagefilledrectangle($im, $cx - 90, $cy + 140, $cx + 90, $cy + 158, $mid);
            break;
        case 5: // Phụ kiện (chuột)
            imagefilledellipse($im, $cx, $cy, 260, 340, $dark);
            imagefilledellipse($im, $cx, $cy - 10, 220, 300, $light);
            imagefilledrectangle($im, $cx - 4, $cy - 160, $cx + 4, $cy + 10, $mid);
            imagefilledellipse($im, $cx, $cy - 90, 46, 70, $accent);
            break;
        case 6: // Thiết bị mạng (router)
            imagefilledrectangle($im, $cx - 220, $cy - 30, $cx + 220, $cy + 90, $dark);
            imagefilledrectangle($im, $cx - 195, $cy - 5, $cx + 195, $cy + 65, $light);
            for ($i = 0; $i < 4; $i++) {
                $ex = $cx - 135 + $i * 90;
                imagefilledellipse($im, $ex, $cy + 30, 20, 20, $accent);
            }
            foreach ([-150, 150] as $ax) {
                imagesetthickness($im, 10);
                imageline($im, $cx + $ax, $cy - 30, $cx + $ax - ($ax > 0 ? -40 : 40), $cy - 190, $mid);
                imagefilledellipse($im, $cx + $ax - ($ax > 0 ? -40 : 40), $cy - 195, 22, 22, $mid);
            }
            break;
        case 7: // Âm thanh (tai nghe) - vẽ dạng vòng khuyên mượt rồi che nửa dưới để ra hình chữ C
            $ringCenterY = $cy - 40;
            imagefilledellipse($im, $cx, $ringCenterY, 360, 360, $dark);
            imagefilledellipse($im, $cx, $ringCenterY, 292, 292, $white);
            imagefilledrectangle($im, $cx - 200, $ringCenterY, $cx + 200, $ringCenterY + 200, $white);
            imagefilledellipse($im, $cx - 180, $ringCenterY + 55, 90, 130, $accent);
            imagefilledellipse($im, $cx + 180, $ringCenterY + 55, 90, 130, $accent);
            imagefilledellipse($im, $cx - 180, $ringCenterY + 55, 50, 90, $light);
            imagefilledellipse($im, $cx + 180, $ringCenterY + 55, 50, 90, $light);
            break;
        case 8: // Linh kiện (RAM/SSD)
            imagefilledrectangle($im, $cx - 220, $cy - 90, $cx + 220, $cy + 90, $dark);
            imagefilledrectangle($im, $cx - 195, $cy - 65, $cx + 195, $cy + 65, $light);
            for ($i = 0; $i < 6; $i++) {
                $rx = $cx - 165 + $i * 60;
                imagefilledrectangle($im, $rx, $cy - 40, $rx + 36, $cy + 40, $accent);
            }
            for ($i = 0; $i < 10; $i++) {
                imagefilledrectangle($im, $cx - 195 + $i * 39, $cy + 70, $cx - 195 + $i * 39 + 14, $cy + 90, $mid);
            }
            break;
    }
}

function wrapText($text, $maxCharsPerLine)
{
    $words = explode(' ', $text);
    $lines = [];
    $current = '';
    foreach ($words as $w) {
        $test = trim($current . ' ' . $w);
        if (mb_strlen($test) > $maxCharsPerLine && $current !== '') {
            $lines[] = $current;
            $current = $w;
        } else {
            $current = $test;
        }
    }
    if ($current !== '') $lines[] = $current;
    return array_slice($lines, 0, 2);
}

function generatePlaceholder($catId, $catInfo, $productName, $outputPath, $fontRegular, $fontBold)
{
    $size = 900;
    $im = imagecreatetruecolor($size, $size);
    imageantialias($im, true);

    $bg = imagecolorallocate($im, 248, 250, 252);
    imagefilledrectangle($im, 0, 0, $size, $size, $bg);

    // Lưới chấm nhẹ nền
    $dot = imagecolorallocate($im, 237, 241, 245);
    for ($x = 20; $x < $size; $x += 40) {
        for ($y = 20; $y < $size; $y += 40) {
            imagefilledellipse($im, $x, $y, 3, 3, $dot);
        }
    }

    // Thẻ trắng bo góc (giả lập bằng hình chữ nhật + 4 góc tròn)
    $cardX1 = 70; $cardY1 = 70; $cardX2 = $size - 70; $cardY2 = $size - 150;
    $white = imagecolorallocate($im, 255, 255, 255);
    $radius = 40;
    imagefilledrectangle($im, $cardX1 + $radius, $cardY1, $cardX2 - $radius, $cardY2, $white);
    imagefilledrectangle($im, $cardX1, $cardY1 + $radius, $cardX2, $cardY2 - $radius, $white);
    imagefilledellipse($im, $cardX1 + $radius, $cardY1 + $radius, $radius * 2, $radius * 2, $white);
    imagefilledellipse($im, $cardX2 - $radius, $cardY1 + $radius, $radius * 2, $radius * 2, $white);
    imagefilledellipse($im, $cardX1 + $radius, $cardY2 - $radius, $radius * 2, $radius * 2, $white);
    imagefilledellipse($im, $cardX2 - $radius, $cardY2 - $radius, $radius * 2, $radius * 2, $white);

    drawCategoryIcon($im, $catId, $size / 2, ($cardY1 + $cardY2) / 2 + 10, $catInfo['color']);

    // Tên sản phẩm (đậm, tối đa 2 dòng)
    $navy = imagecolorallocate($im, 30, 41, 59);
    $lines = wrapText($productName, 26);
    $lineY = $size - 118;
    foreach ($lines as $line) {
        imagettftext($im, 24, 0, 90, $lineY, $navy, $fontBold, $line);
        $lineY += 36;
    }

    // Dòng phụ
    $muted = imagecolorallocate($im, 130, 140, 150);
    imagettftext($im, 15, 0, 90, $lineY + 6, $muted, $fontRegular, 'NovaTech • Sản phẩm công nghệ chính hãng');

    // Nhãn danh mục góc phải dưới
    $accent = imagecolorallocate($im, $catInfo['color'][0], $catInfo['color'][1], $catInfo['color'][2]);
    $tag = $catInfo['tag'];
    $tagW = 16 * mb_strlen($tag) + 50;
    $tagX2 = $size - 90; $tagX1 = $tagX2 - $tagW; $tagY1 = $size - 150; $tagY2 = $tagY1 + 52;
    $tr = 22;
    imagefilledrectangle($im, $tagX1 + $tr, $tagY1, $tagX2 - $tr, $tagY2, $accent);
    imagefilledrectangle($im, $tagX1, $tagY1 + $tr, $tagX2, $tagY2 - $tr, $accent);
    imagefilledellipse($im, $tagX1 + $tr, $tagY1 + $tr, $tr * 2, $tr * 2, $accent);
    imagefilledellipse($im, $tagX2 - $tr, $tagY1 + $tr, $tr * 2, $tr * 2, $accent);
    imagettftext($im, 14, 0, $tagX1 + 25, $tagY1 + 33, $white, $fontBold, $tag);

    imagepng($im, $outputPath);
    imagedestroy($im);
}

// ===== Chạy sinh dữ liệu =====
$catalog = buildCatalog();
shuffle($catalog);
$catalog = array_slice($catalog, 0, 200);

$brandIds = ['Apple' => 1, 'Samsung' => 2, 'ASUS' => 3, 'Lenovo' => 4, 'Dell' => 5, 'Logitech' => 6, 'Sony' => 7, 'TP-Link' => 8, 'Kingston' => 9, 'Xiaomi' => 10];

$sql = "-- =====================================================================\n";
$sql .= "-- SEED: ~200 sản phẩm demo bổ sung (đa dạng danh mục/thương hiệu) kèm ảnh minh hoạ.\n";
$sql .= "-- Chạy file này SAU KHI đã import tech_store.sql (hoặc sau khi đã chạy các migration khác).\n";
$sql .= "-- Ảnh minh hoạ nằm trong thư mục uploads/ đi kèm, đặt tên seed_0001.png ... seed_0200.png.\n";
$sql .= "-- =====================================================================\n\n";
$sql .= "USE `tech_store`;\n\n";
$sql .= "INSERT INTO `products` (`category_id`,`brand_id`,`name`,`image`,`price`,`sale_price`,`sales_count`,`stock_quantity`,`is_hot`,`warranty_text`,`description`) VALUES\n";

$rows = [];
$index = 1;
foreach ($catalog as $item) {
    $catId = $item['cat'];
    $catInfo = $categories[$catId];
    $brandId = $brandIds[$item['brand']] ?? 1;
    $name = $item['name'];
    $price = round($item['price'] / 10000) * 10000;

    $hasSale = (rand(1, 100) <= 35);
    $salePrice = $hasSale ? round($price * (rand(80, 93) / 100) / 10000) * 10000 : null;
    $isHot = (rand(1, 100) <= 15) ? 1 : 0;
    $salesCount = rand(0, 400);
    $stock = rand(0, 60);
    $warranty = "Bảo hành 12 tháng chính hãng · Đổi trả 7 ngày · Giao hàng toàn quốc";
    $description = "$name thuộc danh mục {$catInfo['name']}, hàng chính hãng NovaTech, đầy đủ phụ kiện, hoá đơn VAT theo yêu cầu.";

    $imgName = sprintf('seed_%04d.png', $index);
    generatePlaceholder($catId, $catInfo, $name, $outputDir . '/' . $imgName, $fontRegular, $fontBold);

    $nameEsc = $conn_escape = str_replace("'", "\\'", $name);
    $descEsc = str_replace("'", "\\'", $description);
    $saleSql = $salePrice === null ? 'NULL' : $salePrice;

    $rows[] = "($catId,$brandId,'$nameEsc','$imgName',$price,$saleSql,$salesCount,$stock,$isHot,'$warranty','$descEsc')";
    $index++;
}

$sql .= implode(",\n", $rows) . ";\n";

file_put_contents(__DIR__ . '/seed_extra_products.sql', $sql);

echo "Đã sinh " . count($rows) . " sản phẩm và " . ($index - 1) . " ảnh minh hoạ.\n";
echo "File SQL: seed_extra_products.sql\n";
