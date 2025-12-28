<style>
    .card {
        display: inline-block;
        width: 50px;
        height: 70px;
        border: 1px solid #000;
        border-radius: 10px;
        text-align: center;
        line-height: 70px;
        margin: 5px;
    }
    .selected-card {
        background-color: #ccf;
    }
</style>

<?php
// Initialize mapping arrays
$suitNames = ['H', 'S', 'D', 'C'];
$cardValues = ['2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A'];
$suitEmojis = ['♥️', '♠️', '♦️', '♣️'];

// Get the hand from query parameter
$hand_param = isset($_GET['hand']) ? $_GET['hand'] : '';

if (empty($hand_param)) {
    echo "No hand provided.";
    exit;
}

// Split the hand into individual cards
$hand_strings = explode(' ', $hand_param);

// Function to parse a card string and return the numerical value
function parse_card($card_str) {
    global $suitNames, $cardValues;

    $card_str = strtoupper(trim($card_str));

    if ($card_str == 'JJ') {
        return 52; // Joker
    }

    // Extract value and suit
    $value_char = substr($card_str, 0, -1);
    $suit_char = substr($card_str, -1);

    // Get the value index
    $value_index = array_search($value_char, $cardValues);
    if ($value_index === false) {
        echo "Invalid card value: $value_char";
        exit;
    }

    // Get the suit index
    $suit_index = array_search($suit_char, $suitNames);
    if ($suit_index === false) {
        echo "Invalid suit: $suit_char";
        exit;
    }

    // Calculate the numerical representation
    $card_num = ($value_index << 2) | $suit_index;
    return $card_num;
}

// Parse each card
$hand = [];
foreach ($hand_strings as $card_str) {
    $hand[] = parse_card($card_str);
}

// Helper functions
function C($n, $k) {
    if ($k > $n) return 0;
    if ($k * 2 > $n) $k = $n - $k;
    if ($k == 0) return 1;

    $result = $n;
    for ($i = 2; $i <= $k; ++$i) {
        $result *= ($n - $i + 1);
        $result /= $i;
    }
    return $result;
}

function next_combi(&$c, $n, $max) {
    for ($i = $n - 1; $i >= 0; $i--) {
        $c[$i]++;
        if ($c[$i] <= $max - $n + $i + 1) break;
    }
    if ($i < 0) return false;
    for ($i++; $i < $n; $i++) $c[$i] = $c[$i - 1] + 1;
    return true;
}

function win($a) {
    $h = $a;
    sort($h);

    $c = [1, 0, 0, 0, 0];
    $n = 0;
    $flush = (($h[0] & 3) == ($h[1] & 3)) && (($h[0] & 3) == ($h[2] & 3)) && (($h[0] & 3) == ($h[3] & 3));

    if ($h[4] == 52) { // Joker
        $h[0] >>= 2;
        for ($i = 1; $i < 4; $i++) {
            $h[$i] >>= 2;
            if ($h[$i] != $h[$i - 1]) $n++;
            $c[$n]++;
        }

        if ($c[0] == 4) return 50; // Five of a kind
        if ($c[0] == 3 || $c[1] == 3) return 15; // Four of a kind
        if ($c[2] == 0) return 8; // Full house
        if ($c[0] == 2 || $c[1] == 2 || $c[2] == 2) return 2;

        if ($h[3] - $h[0] <= 4 || ($h[2] <= 3 && $h[3] == 12))
            return $flush ? 30 : 3; // Straight (flush)
    } else {
        $flush = $flush && (($h[0] & 3) == ($h[4] & 3));

        $h[0] >>= 2;
        for ($i = 1; $i < 5; $i++) {
            $h[$i] >>= 2;
            if ($h[$i] != $h[$i - 1]) $n++;
            $c[$n]++;
        }

        if ($c[2] == 0) return ($c[0] == 4 || $c[1] == 4) ? 15 : 8; // Four of a kind or Full house
        if ($c[3] == 0) return 2; // Three of a kind

        if ($n == 4 && ($h[4] - $h[0] == 4 || ($h[3] == 3 && $h[4] == 12)))
            return $flush ? 30 : 3; // Straight (flush)
    }

    return $flush ? 4 : 0;
}

function ONE_BIT($v) {
    return !($v & ($v - 1));
}

function is_paired($a) {
    $c = [];
    foreach ($a as $card) {
        $value = $card >> 2;
        if (isset($c[$value])) return true;
        $c[$value] = 1;
    }
    return false;
}

function optimal_selection($h, $opti = true) {
    $left = [];
    $ans = [];

    for ($c = 0; $c < 53; $c++)
        if (!in_array($c, $h)) $left[] = $c;

    $paired = is_paired($h);

    $ans[] = ['p' => 0, 's' => 0]; // Index corresponds to selection

    // Let's leave this optimization out for now, as it doesn't work for unsorted hands
    //$start_s = ($opti && in_array(52, $h)) ? 16 : 1;

    for ($s = 1; $s < 32; $s++) {
        if ($opti && ONE_BIT($s) && $paired) continue; // No single beats a pair

        $S = $I = $n = 0;
        $ci = [0, 1, 2, 3];

        $sel = [];
        for ($j = 0; $j < 5; $j++)
            if (($s >> $j) & 1) $sel[$n++] = $h[$j];

        do {
            for ($j = 0; $j < 5 - $n; $j++) $sel[$n + $j] = $left[$ci[$j]];
            $S += win($sel);
            $I++;
        } while (next_combi($ci, 5 - $n, count($left) - 1));

        $ans[] = ['p' => $S / $I, 's' => $s];
    }

    return $ans;
}

// Compute optimal selections
$ans = optimal_selection($hand, true);

// Sort the 'ans' array by 'p' in descending order
usort($ans, function ($a, $b) {
    return $b['p'] <=> $a['p'];
});

// Get the top 5 choices
$top_choices = array_slice($ans, 0, 5);

// Function to format card number into string with emoji
function format_card($card_num) {
    global $cardValues, $suitEmojis;

    if ($card_num == 52) {
        return 'JJ';
    }

    $suit_index = $card_num & 3;
    $value_index = $card_num >> 2;

    $value_char = $cardValues[$value_index];
    $suit_emoji = $suitEmojis[$suit_index];

    return $value_char . $suit_emoji;
}

// Display the top 5 choices
echo "<h1>Top 5 Choices</h1>";
echo "<table><tr><th>Selected Cards</th><th>Win Likelihood</th></tr>";

foreach ($top_choices as $choice) {
    $s = $choice['s'];
    $selected_cards = [];
    for ($j = 0; $j < 5; $j++) {
        $card_num = $hand[$j];
        $card_str = format_card($card_num);
        if (($s >> $j) & 1) {
            // The card at position $j is selected
            $selected_cards[] = "<span class='card selected-card'>$card_str</span>";
        } else {
            $selected_cards[] = "<span class='card'>$card_str</span>";
        }
    }
    $selected_cards_str = implode(' ', $selected_cards);
    $win_likelihood = round($choice['p'], 2);

    echo "<tr>";
    echo "<td>$selected_cards_str</td>";
    echo "<td>${win_likelihood}x</td>";
    echo "</tr>";
}

echo "</table>";
?>