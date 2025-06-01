<?php
function generateRESULTID() {
    // Generate a random 6-digit number
    $randomNumber = mt_rand(100000, 999999);

    // Create the Counselor ID without the date
    return 'RESULT-' . $randomNumber;
}
?>