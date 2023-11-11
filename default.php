<?php
require_once(__DIR__."/dao.php");

include __DIR__."/auth.php";

function moneyFormat($dollars) {
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY );
    return $fmt->formatCurrency($dollars, 'USD');
}

$amountDollars = moneyFormat(loadAmount() / 100.0);

$totalTxSpend = "";
$totalTxEarn = "";

function loadTransactionsArray() {
    $transactions = [];
    $transactionsItr = loadTransactionsDate(date('Y-m-01') . " 00:00:00");
    while ($tx = $transactionsItr->fetch_assoc()) {
        $transactions[] = $tx;
    }
    return $transactions;
}

$transactions = loadTransactionsArray();

function renderTransactions() {
    global $transactions;
    foreach ($transactions as &$tx) {
        echo "<tr>";
            echo "<td>" . substr(explode(" ", $tx["date_added"])[0], 5) . "</td>";
            echo "<td><b>" . moneyFormat($tx["amount"] / 100.0) . "</b></td>";
            echo "<td>" . $tx["description"] . "</td>";
        echo "</tr>";
    }
}

function renderTransactionData() {
    global $transactions;
    echo json_encode(
        array_map(fn($tx) => 
            [
                intval(explode("-", explode(" ", $tx["date_added"])[0])[2]),
                $tx["amount"]
            ], $transactions)
    );
}

function loadSpendEarn() {
    global $transactions;
    global $totalTxSpend;
    global $totalTxEarn;
    $spend = 0;
    $earn = 0;
    foreach ($transactions as &$tx) {
        if ($tx["amount"] > 0) {
            $spend += $tx["amount"];
        } else {
            $earn -= $tx["amount"];
        }
    }

    $totalTxSpend = moneyFormat($spend/100.0);
    $totalTxEarn = moneyFormat($earn/100.0);
}
loadSpendEarn();

function renderGoals() {
    $goals = loadGoals(100);
    
    while ($goal = $goals->fetch_assoc()) {
        $id = $goal["id"];
        $name = $goal["name"];
        $amount = moneyFormat($goal["amount"] / 100.0);
        $total = moneyFormat($goal["total"] / 100.0);
        echo "renderGoal($id, \"$name\", \"$amount\", \"$total\");";
    }
}

?>

<html>
<head>
<style>
table {
    margin: auto;
}
#tx_amount {
    width: 60px;
}
h1 {
    margin-top: 30px;
    text-align: center;
}

table * td {
    padding: 2px 5px 2px 5px;
}

table * th {
    padding: 2px 5px 2px 5px;
}

#amount_head {
    display: flex;
    flex-wrap: nowrap;
}

td, .soft_underline {
  border-bottom: 1px solid #ddd;
}

#tx_desc {
    width: 100%;
}

#goal_add {
    float: left;
}

.short_input {
    width: 60px;
}

.med_input {
    width: 80px;
}

.long_input {
    width: 100px;
}

#tx_table thead, #tx_table tbody {
    display: block;
}

#tx_table tbody {
    max-height: 245px;
    overflow-y: scroll;
}

#tx_table {
    margin-top: 20px;
}

#current_amount {
    margin-bottom: 0px;
}

#total_spend {
    font-size: .80em;
    margin: auto;
    text-align: center;
}

#chart {
    margin: auto;
    margin-top: 20px;
    display: block;
}

.hidden {
    display: none !important;
}

</style>
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
    <h1 id="current_amount"><?=$amountDollars?></h1>

    <div id="total_spend"></div>
    <canvas id="chart" class="hidden" width="300" height="100"></canvas>

    <table id="tx_table" cellspacing="0">
        <thead>
            <tr>
                <th style="color: white">Date</th>
                <th id="amount_head">$&nbsp;<input type="number" id="tx_amount" placeholder="amount"></input></th>
                <th><input type="text" id="tx_desc" placeholder="description"></input></th>
                <th><button id="tx_add" onclick="submitAddTransaction()">+</button>
            </tr>
        </thead>
        <tbody>
            <?php
                renderTransactions();
            ?>
        </tbody>
    </table>

    <h1>Goals</h1>
    <table id="goal_table" cellspacing="0">
        <thead>
            <tr>
                <th><input type="text" id="goal_name" placeholder="name" class="med_input"></input></th>
                <th><input type="number" id="goal_total" placeholder="total" class="long_input"></input></th>
                <th><button id="goal_add" onclick="submitAddGoal()">+</button></th>
            </tr>
        </thead>
        <tbody id="goal_render_slot"></tbody>
    </table>
</body>

<script>

function renderTxAmounts() {
    document.getElementById("total_spend").innerHTML = "<?=$totalTxSpend?> spent";
}

function postData(url = "", data = {}) {
    // Default options are marked with *
    return fetch(url, {
        method: "POST", // *GET, POST, PUT, DELETE, etc.
        mode: "cors", // no-cors, *cors, same-origin
        cache: "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials: "same-origin", // include, *same-origin, omit
        headers: {
            "Content-Type": "application/json",
        },
        redirect: "follow", // manual, *follow, error
        referrerPolicy: "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: JSON.stringify(data), // body data type must match "Content-Type" header
    }).then((r) => r.json()); // parses JSON response into native JavaScript objects
}

function submitAddTransaction() {
    var amount = document.getElementById("tx_amount").value;
    var description = document.getElementById("tx_desc").value;

    // Clear form
    document.getElementById("tx_amount").value = "";
    document.getElementById("tx_desc").value = "";

    postData("./transaction.php", {amount, description}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to save.");
            document.getElementById("tx_amount").value = amount;
            document.getElementById("tx_desc").value = description;
        }
    });
}

function submitAddGoal() {
    var name = document.getElementById("goal_name").value;
    var total = document.getElementById("goal_total").value;

    // Clear form
    document.getElementById("goal_name").value = "";
    document.getElementById("goal_total").value = "";

    postData("./goal.php", {name, total}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to save.");
            document.getElementById("goal_name").value = name;
            document.getElementById("goal_total").value = total;
        }
    });
}

function submitAddGoalTransaction(goalId, amountElement) {
    var amount = amountElement.value;

    amountElement.value = "";

    postData("./goal-transaction.php", {goalId, amount}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to save.");
            amountElement.value = amount;
        }
    });
}

function renderGoal(id, name, amount, total) {
    var tr = document.createElement("tr");

    var tdName = document.createElement("td");
    tdName.innerHTML = name;
    tr.appendChild(tdName);

    var tdAmount = document.createElement("td");
    tdAmount.innerHTML = amount + " / " + total;
    tr.appendChild(tdAmount);

    var tdAddAmount = document.createElement("td");
    tr.appendChild(tdAddAmount);

    var span = document.createElement("span");
    span.innerHTML = "$";
    tdAddAmount.appendChild(span);

    var inputAddAmount = document.createElement("input");
    inputAddAmount.placeholder = "amount";
    inputAddAmount.type = "number";
    inputAddAmount.classList.add("short_input");
    inputAddAmount.id = "goal_" + id + "_add_amount";
    tdAddAmount.appendChild(inputAddAmount);

    var buttonAddAmount = document.createElement("button");
    buttonAddAmount.innerHTML = "+";
    buttonAddAmount.onclick = (event) => {
        var goalTxAmount = document.getElementById("goal_" + id + "_add_amount");
        submitAddGoalTransaction(id, goalTxAmount);
    };
    tdAddAmount.appendChild(buttonAddAmount);
    
    document.getElementById("goal_render_slot").appendChild(tr);
}

renderTxAmounts();

<?php
    // Echo renderGoal for each goal
    renderGoals();
?>

</script>

<script>

function daysInMonth() {
    var date = new Date();
    return new Date(date.getYear(), date.getMonth()+1, 0).getDate();
}

var days = daysInMonth();

function calcChartData() {
    var txAmounts = <?=renderTransactionData()?>;
    txAmounts.sort(function(a, b) {
        return a[0] - b[0];
    });

    var minAmount = 0;
    var maxAmount = 0;

    var values = [];
    var last = 0;
    txAmounts.forEach((elem) => {
        var day = elem[0];
        var amount = elem[1];

        while (values.length < day) {
            values.push(last);
        }
        last -= amount;
        values[day-1] = last;
    });

    return values;
}

var dayValues = calcChartData();
var minValue = Math.min(0, ...dayValues);
var maxValue = Math.max(0, ...dayValues);

function renderChart(canvas, maxX, minY, maxY, values) {
    var height = canvas.height;
    var width = canvas.width;

    var xWidth = (width - 1) / maxX;
    var y0 = height * (1 - (-minY) / (maxY - minY)); // Y value of 0

    // Get context
    const ctx = canvas.getContext("2d");

    // Shape fill (above)
    ctx.beginPath();
    ctx.fillStyle = "#b3ffb5";
    ctx.moveTo(0, 0); // top left
    var lastY = 0;
    for (var i = 0; i < values.length; i++) {
        var x = xWidth * i;
        var y = height * (1 - (values[i] - minY) / (maxY - minY));
        if (i > 1) {
            var lineRatio = (lastY - y0) / (lastY - y);
            if (values[i] < 0 && values[i-1] > 0) {
                x = (xWidth * (i - 1)) + (xWidth * lineRatio);
            } else if (values[i] > 0 && values[i-1] < 0) {
                ctx.lineTo(xWidth * (i - 1) + (xWidth * lineRatio), y0);
            }
        }
        ctx.lineTo(x, Math.min(y, y0));
        lastY = y;
    }
    ctx.lineTo((values.length - 1) * xWidth, y0) // straight down
    ctx.lineTo(0, y0); // bottom left (close shape)
    ctx.fill(); // Fill the shape

    // Shape fill (below)
    ctx.beginPath();
    ctx.fillStyle = "#ffb3b3";
    ctx.moveTo(0, 0); // top left
    var lastY = 0;
    for (var i = 0; i < values.length; i++) {
        var x = xWidth * i;
        var y = height * (1 - (values[i] - minY) / (maxY - minY));

        if (i > 1) {
            var lineRatio = (lastY - y0) / (lastY - y);
            if (values[i] < 0 && values[i-1] > 0) {
                ctx.lineTo((xWidth * (i - 1)) + (xWidth * lineRatio), y0);
            } else if (values[i] > 0 && values[i-1] < 0) {
                x = xWidth * (i - 1) + (xWidth * lineRatio);
            }
        }
        ctx.lineTo(x, Math.max(y, y0));
        lastY = y;
    }
    ctx.lineTo((values.length - 1) * xWidth, y0) // straight down
    ctx.lineTo(0, y0); // bottom left (close shape)
    ctx.fill(); // Fill the shape

    // Line highlight
    ctx.beginPath();
    ctx.moveTo(0, 0); // top left
    for (var i = 0; i < values.length; i++) {
        var x = xWidth * i;
        var y = height * (1 - (values[i] - minY) / (maxY - minY));
        ctx.lineTo(x, y);
        //ctx.arc(x, y, 2, 0, 2 * Math.PI);
    }
    ctx.stroke();
    
    // Left hand side bound
    ctx.beginPath();
    ctx.lineWidth = 4; // 2 pixels wide on the edge
    ctx.moveTo(0, 0); // top left
    ctx.lineTo(0, height); // bottom left
    ctx.stroke(); // Draw the line

    // 0-line
    if (minY != 0) {
        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.moveTo(0, y0); // the value of 0
        ctx.lineTo(width, y0);
        ctx.stroke(); // Draw the line
    } else {
        ctx.beginPath();
        ctx.lineWidth = 4; // 2 pixels wide on the edge
        ctx.moveTo(0, height); // bottom left
        ctx.lineTo(width, height); // bottom left
        ctx.stroke(); // Draw the line
    }
}

renderChart(document.getElementById("chart"), days, minValue, maxValue, dayValues);

document.getElementById("current_amount").addEventListener("click", function(event) {
    var chart = document.getElementById("chart");
    if (chart.classList.contains("hidden")) {
        chart.classList.remove("hidden");
    } else {
        chart.classList.add("hidden");
    }
});

</script>
</html>