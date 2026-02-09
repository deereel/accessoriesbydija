<?php
$content = file_get_contents("app/products.php");

// Add sale variables after the inner foreach
$old = "                                <?php foreach (\$chunk as \$product): ?>\n                                    <div class=\"product-card";
$new = "                                <?php foreach (\$chunk as \$product):\n                                    \$isOnSale = !empty(\$product[\"is_on_sale\"]) && !empty(\$product[\"sale_price\"]) && \$product[\"sale_price\"] < \$product[\"price\"];\n                                    \$discountPercent = \$isOnSale ? (!empty(\$product[\"sale_percentage\"]) ? \$product[\"sale_percentage\"] : round((\$product[\"price\"] - \$product[\"sale_price\"]) / \$product[\"price\"] * 100)) : 0;\n                                    ?>\n                                    <div class=\"product-card";
$content = str_replace($old, $new, $content);

// Add sale badge before image
$old = "                                      <!-- Product Image -->\n                                      <a href=\"product.php?slug=<?= \$product[\"slug\"] ?>\" class=\"product-image\">\n                                          <?php if (\$product[\"main_image\"]): ?>";
$new = "                                      <!-- Product Image -->\n                                      <a href=\"product.php?slug=<?= \$product[\"slug\"] ?>\" class=\"product-image\">\n                                          <?php if (\$isOnSale): ?>\n                                              <span class=\"sale-badge-tag\">-<?= \$discountPercent ?>%</span>\n                                          <?php endif; ?>\n                                          <?php if (\$product[\"main_image\"]): ?>";
$content = str_replace($old, $new, $content);

// Update price display
$old = "                                          <div class=\"product-footer\">\n                                              <span class=\"product-price\" data-price=\"<?= \$product[\"price\"] ?>\">£<?= number_format(\$product[\"price\"], 2) ?></span>\n                                              <button class=\"cart-btn add-to-cart\"";
$new = "                                          <div class=\"product-footer\">\n                                              <?php if (\$isOnSale): ?>\n                                                  <div class=\"product-price-container\">\n                                                      <span class=\"product-price-original\">£<?= number_format(\$product[\"price\"], 2) ?></span>\n                                                      <span class=\"product-price-sale\">£<?= number_format(\$product[\"sale_price\"], 2) ?></span>\n                                                  </div>\n                                              <?php else: ?>\n                                                  <span class=\"product-price\" data-price=\"<?= \$product[\"price\"] ?>\">£<?= number_format(\$product[\"price\"], 2) ?></span>\n                                              <?php endif; ?>\n                                              <button class=\"cart-btn add-to-cart\"";
$content = str_replace($old, $new, $content);

file_put_contents("app/products.php", $content);
echo "Done\n";
