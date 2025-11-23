<?php
// Create a 200x200 image
$image = imagecreatetruecolor(200, 200);

// Set background color (light gray)
$bg = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bg);

// Set text color (dark gray)
$text_color = imagecolorallocate($image, 80, 80, 80);

// Add text
$text = "Reward";
$font = 5; // Built-in font
$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);
$x = (200 - $text_width) / 2;
$y = (200 - $text_height) / 2;
imagestring($image, $font, $x, $y, $text, $text_color);

// Save the image
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}
imagepng($image, 'uploads/default_reward.png');
imagedestroy($image);

echo "Default reward image created successfully!";
?> 