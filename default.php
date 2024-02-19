<?php
require_once(__DIR__."/dao.php");

include __DIR__."/auth.php";

$dateOffset = "-4 hours";

// If past param is specified, show previous months
$adjustedDate = false;
if (isset($_GET["past"]) && $_GET["past"]) {
    $monthAdjust = intval($_GET["past"]);
    if ($monthAdjust > 0) {
        $dateOffset .= " -$monthAdjust months";
        $adjustedDate = true;
    }
}

$clientDate = date('Y-m-d H:i:s', strtotime($dateOffset));

function moneyFormat($dollars) {
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $fmt->formatCurrency($dollars, 'USD');
}

function dollarFormat($dollars) {
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
    return $fmt->formatCurrency($dollars, 'USD');
}

$amountDollars = moneyFormat(loadAmount() / 100.0);

$totalTxSpend = "";
$totalTxEarn = "";

function loadTransactionsArray() {
    global $dateOffset;
    $transactions = [];
    $transactionsItr = loadTransactionsStartEndDate(
        date('Y-m-01 00:00:00', strtotime($dateOffset)),
        date('Y-m-t 23:59:59', strtotime($dateOffset)));
    while ($tx = $transactionsItr->fetch_assoc()) {
        $transactions[] = $tx;
    }
    return $transactions;
}

function loadGoalsArray() {
    $goalsResult = loadGoals(100);
    $goals = [];
    while ($goal = $goalsResult->fetch_assoc()) {
        $goals[] = $goal;
    }
    return $goals;
}

$transactions = loadTransactionsArray();
$goals = loadGoalsArray();

function renderTransactions() {
    global $transactions;
    foreach ($transactions as &$tx) {

        $id = $tx["id"];
        $description = $tx["description"];
        $amountString = $tx["amount"] >= 0 ? 
            moneyFormat($tx["amount"] / 100.0) : 
            "(" . moneyFormat(-$tx["amount"] / 100.0) . ")";
        $dateString = substr(explode(" ", $tx["date_added"])[0], 5);
        $active = $tx["active"]? "true" : "false";

        echo "renderTransaction($id, \"$dateString\", \"$amountString\", \"$description\", $active);";
    }
}

function renderTransactionData() {
    global $transactions;
    echo json_encode(
        array_map(fn($tx) => 
            [
                intval(explode("-", explode(" ", $tx["date_added"])[0])[2]),
                $tx["active"]? $tx["amount"] : 0
            ], 
            array_values(array_filter($transactions, function($val) {
                return $val["active"];
            }))
        )
    );
}

function loadSpendEarn() {
    global $transactions;
    global $totalTxSpend;
    global $totalTxEarn;
    $spend = 0;
    $earn = 0;
    foreach ($transactions as &$tx) {
        if (!$tx["active"]) {
            continue;
        }
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
    global $goals;
    foreach ($goals as &$goal) {
        $id = $goal["id"];
        $name = $goal["name"];
        $amount = dollarFormat($goal["amount"] / 100.0);
        $total = dollarFormat($goal["total"] / 100.0);
        $ratio = $goal["amount"] / $goal["total"];
        echo "renderGoal($id, \"$name\", \"$amount\", \"$total\", $ratio);";
    }
}

function getGoalsTotals() {
    global $goals;
    $amount = 0;
    $total = 0;
    foreach ($goals as &$goal) {
        $amount += $goal["amount"];
        $total += $goal["total"];
    }
    return [
        dollarFormat($amount / 100.0),
        dollarFormat($total / 100.0),
        $amount / max($total, 1),
        count($goals)
    ];
}

?>

<html>
<head>
<style>
table {
    margin: auto;
}
#tx_amount, #tx_desc {
    width: 100%;
}

#tx_title, #goal_title {
    display: flex;
    justify-content: center;
    align-items: center;
}

h1 {
    margin-top: 30px;
    text-align: center;
    margin-bottom: 0px;
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

.short_input {
    width: 60px;
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

.no_bottom_space {
    margin-bottom: 0px;
}

.center_spaced {
    display: flex;
    justify-content: space-evenly;
    align-items: center;
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

.small_cell {
    max-width: 200px;
}

.space_right {
    margin-right: 5px;
}

.small_button {
    padding: 0px 5px;
}

.goal_progress {
  height: 1.5em;
  width: 100%;
  background-color: #eee;
  position: relative;
  border-radius: 4px;
}
.goal_progress:before {
  content: attr(data-label);
  font-size: 1em;
  position: absolute;
  text-align: center;
  top: 0.15em;
  left: 0;
  right: 0;
}
.goal_progress .value {
  background-color: #bbe0ff;
  display: inline-block;
  height: 100%;
  border-radius: 4px;
}

.form_title {
    margin-top: 0px;
}

.goal_display_total > td {
    border-bottom: none;
}

</style>
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>

    <dialog id="add_transaction">
        <form method="dialog" novalidate>
            <h3 class="form_title">Add Transaction</h3>
            <input type="number" id="tx_amount" placeholder="amount"></input>
            <br /><br />
            <input id="tx_desc" list="tx_names" placeholder="description">
            <datalist id="tx_names"></datalist>
            <br /><br />
            <button style="float: left" onclick="saveTransaction(event)">Save</button>
            <button style="float: right" value="cancel">Cancel</button>
        </form>
    </dialog>

    <dialog id="add_goal">
        <form method="dialog" novalidate>
            <h3 class="form_title">Add Goal</h3>
            <input type="text" id="goal_name" placeholder="name"></input>
            <br /><br />
            <input type="number" id="goal_total" placeholder="total"></input>
            <br /><br />
            <button style="float: left" onclick="saveGoal(event)">Save</button>
            <button style="float: right" value="cancel">Cancel</button>
        </form>
    </dialog>

    <dialog id="add_goal_tx">
        <form method="dialog">
            <h3 class="form_title">Add Goal Transaction</h3>
            <input type="number" id="add_goal_amount" placeholder="amount"></input>
            <br /><br />
            <input type="text" disabled value="Goal contribution"></input>
            <br /><br />
            <button style="float: left" onclick="saveGoalTransaction(event)">Save</button>
            <button style="float: right" id="add_goal_tx_cancel" value="cancel">Cancel</button>
        </form>
    </dialog>

    <div id="totals">
        <h1 class="no_bottom_space center_spaced">
            <button onclick="previousMonth()">&lt;</button>
            <span id="current_amount"><?=$amountDollars?></span>
            <button id="next_month_button" onclick="nextMonth()">&gt;</button>
        </h1>
        <div id="total_spend"></div>
    </div>
    <div id="month_display" class="center_spaced hidden">
        <button onclick="previousMonth()">&lt;</button>
        <h1 id="month"></h1>
        <button onclick="nextMonth()">&gt;</button>
    </div>

    <canvas id="chart" class="hidden" width="300" height="100"></canvas>

    <h1 id="tx_title">
        Transactions&nbsp;
        <button id="tx_showform" onclick="showAddTransactionForm()">+</button>
    </h1>
    <table id="tx_table" cellspacing="0">
        <tbody id="tx_render_slot"></tbody>
    </table>

    <div id="goals">
        <h1 id="goal_title">
            Goals&nbsp;
            <button onclick="showAddGoalForm()">+</button>
        </h1>
        <table id="goal_table" cellspacing="0">
            <tbody id="goal_render_slot"></tbody>   
        </table>
    </div>
</body>

<script>

var data = <?php echo json_encode($transactions); ?>;
var datalist = document.getElementById("tx_names");
var dataSet = new Set(data.map(d => d.description));
dataSet.forEach((elem) => {
    var option = document.createElement("option");
    option.value = elem;
    datalist.appendChild(option);
});

var addTransactionDialog = document.getElementById("add_transaction");
addTransactionDialog.addEventListener("close", (e) => {
    var rv = addTransactionDialog.returnValue;
    if (rv != "cancel") {
        var transactionData = JSON.parse(rv);
        submitAddTransaction(transactionData.amount, transactionData.description);
    }
});

var addGoalDialog = document.getElementById("add_goal");
addGoalDialog.addEventListener("close", (e) => {
    var rv = addGoalDialog.returnValue;
    if (rv != "cancel") {
        var goalData = JSON.parse(rv);
        submitAddGoal(goalData.name, goalData.total);
    }
});

var lastGoalId;
var addGoalAmountDialog = document.getElementById("add_goal_tx");
addGoalAmountDialog.addEventListener("close", (e) => {
    var rv = addGoalAmountDialog.returnValue;
    if (rv != "cancel") {
        var goalTxData = JSON.parse(rv);
        submitAddGoalTransaction(goalTxData.id, goalTxData.amount);
    }
});
var addGoalAmountCancelButton = document.getElementById("add_goal_tx_cancel");
addGoalAmountCancelButton.addEventListener("click", (e) => {
    // Reset the form because we could open this dialog again for a different goal.
    e.target.form.reset();
});

// Render total amount
document.getElementById("total_spend").textContent = "<?=$totalTxSpend?> spent, " + daysLeftInTheMonth() + " days left";

function daysLeftInTheMonth() {
    return <?php echo date("t", strtotime($clientDate)) . " + 1 - " . date("d", strtotime($clientDate)); ?>;
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


function showAddTransactionForm() {
    addTransactionDialog.showModal();
}

function showAddGoalForm() {
    addGoalDialog.showModal();
}

function showAddGoalAmountForm(id) {
    lastGoalId = id;
    addGoalAmountDialog.showModal();
}


function saveTransaction(event) {
    event.preventDefault();

    var amount = document.getElementById("tx_amount").value;
    var description = document.getElementById("tx_desc").value;

    // Don't close the form if it fails (basic) validation
    if (amount.length == 0 || description.length == 0) {
        return;
    }

    event.target.form.reset();
    event.target.closest("dialog").close(JSON.stringify({ amount, description }));
}

function saveGoal(event) {
    event.preventDefault();
    
    var name = document.getElementById("goal_name").value;
    var total = document.getElementById("goal_total").value;

    // Don't close the form if it fails (basic) validation
    if (name.length == 0 || total.length == 0) {
        return;
    }

    event.target.form.reset();
    event.target.closest("dialog").close(JSON.stringify({ name, total }));
}

function saveGoalTransaction(event) {
    event.preventDefault();

    var goalAmount = document.getElementById("add_goal_amount").value;
    var goalId = lastGoalId;

    if (goalAmount.length == 0) {
        return;
    }

    event.target.form.reset();
    event.target.closest("dialog").close(JSON.stringify({id: goalId, amount: goalAmount}));
}


function submitAddTransaction(amount, description) {
    postData("./transaction.php", {amount, description}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to save.");
        }
    });
}

function submitAddGoal(name, total) {
    postData("./goal.php", {name, total}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to save.");
        }
    });
}

function submitAddGoalTransaction(goalId, amount) {

    postData("./goal-transaction.php", {goalId, amount}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to save.");
        }
    });
}


function renderTransaction(id, date, amountString, description, active) {
    if (!active) {
        return null;
    }

    var tr = document.createElement("tr");

    var tdDate = document.createElement("td");
    tdDate.textContent = date;
    tr.appendChild(tdDate);

    var tdAmount = document.createElement("td");
    tdAmount.textContent = amountString;
    tr.appendChild(tdAmount);

    var tdDescription = document.createElement("td");
    tdDescription.classList.add("small_cell");
    tr.appendChild(tdDescription);

    var divDescription = document.createElement("div");
    divDescription.textContent = description;
    divDescription.style = "display: inline-block;";

    var deleteButton = document.createElement("button");
    deleteButton.addEventListener("click", deleteTransaction.bind(this, id));
    toggleVisibilityTxDelete(deleteButton);
    deleteButton.textContent = "x";
    deleteButton.classList.add("space_right");
    deleteButton.classList.add("small_button");

    tdDescription.appendChild(deleteButton);
    tdDescription.appendChild(divDescription);

    // Handle click
    divDescription.addEventListener("click", transactionClicked.bind(this, deleteButton));

    document.getElementById("tx_render_slot").appendChild(tr);

    return tr;
}

function renderGoal(id, name, amount, total, ratio) {
    var tr = document.createElement("tr");

    var tdName = document.createElement("td");
    tr.appendChild(tdName);

    var divName = document.createElement("div");
    divName.textContent = name;
    divName.classList.add("small_cell");
    divName.style = "display: inline-block;";

    var deleteButton = document.createElement("button");
    deleteButton.addEventListener("click", deleteGoal.bind(this, id));
    toggleVisibilityGoalDelete(deleteButton);
    deleteButton.textContent = "x";
    deleteButton.classList.add("space_right");
    deleteButton.classList.add("small_button");

    tdName.appendChild(deleteButton);
    tdName.appendChild(divName);

    var tdAmount = document.createElement("td");
    tr.appendChild(tdAmount);

    var divAmount = document.createElement("div");
    divAmount.style="height: 0px; font-size: 1em; padding: 0px 10px;";
    divAmount.textContent = amount + " / " + total;
    tdAmount.appendChild(divAmount);

    var divProgress = document.createElement("div");
    divProgress.classList.add("goal_progress");
    divProgress.setAttribute("data-label", amount + " / " + total);
    divProgress.setAttribute("Complete", true);
    tdAmount.appendChild(divProgress);

    var spanValue = document.createElement("span");
    spanValue.classList.add("value");
    spanValue.style.width = Math.min((ratio * 100), 100) + "%";
    divProgress.appendChild(spanValue);

    var tdAddAmount = document.createElement("td");
    tr.appendChild(tdAddAmount);

    var buttonAddAmount = document.createElement("button");
    buttonAddAmount.textContent = "+";
    buttonAddAmount.onclick = showAddGoalAmountForm.bind(this, id);
    tdAddAmount.appendChild(buttonAddAmount);
    
    // Handle click
    divName.addEventListener("click", goalClicked.bind(this, deleteButton));

    document.getElementById("goal_render_slot").appendChild(tr);

    return tr;
}

function renderGoalTotal(amount, total, ratio) {
    var tr = document.createElement("tr");
    tr.classList.add("goal_display_total");

    var tdName = document.createElement("td");
    tr.appendChild(tdName);

    var tdAmount = document.createElement("td");
    tr.appendChild(tdAmount);

    var divAmount = document.createElement("div");
    divAmount.style="height: 0px; font-size: 1em; padding: 0px 10px;";
    divAmount.textContent = amount + " / " + total;
    tdAmount.appendChild(divAmount);

    var divProgress = document.createElement("div");
    divProgress.classList.add("goal_progress");
    divProgress.setAttribute("data-label", amount + " / " + total);
    divProgress.setAttribute("Complete", true);
    tdAmount.appendChild(divProgress);

    var spanValue = document.createElement("span");
    spanValue.classList.add("value");
    spanValue.style.width = Math.min((ratio * 100), 100) + "%";
    divProgress.appendChild(spanValue);

    var tdAddAmount = document.createElement("td");
    tr.appendChild(tdAddAmount);

    document.getElementById("goal_render_slot").appendChild(tr);

    return tr;
}

function transactionClicked(deleteButton) {
    toggleVisibilityTxDelete(deleteButton);
}

function goalClicked(goalDeleteButton) {
    toggleVisibilityGoalDelete(goalDeleteButton);
}


function toggleVisibilityTxDelete(txDeleteButton) {
    if (txDeleteButton.classList.contains("hidden")) {
        txDeleteButton.classList.remove("hidden");
    } else {
        txDeleteButton.classList.add("hidden");
    }
}

function toggleVisibilityGoalDelete(goalDeleteButton) {
    if (goalDeleteButton.classList.contains("hidden")) {
        goalDeleteButton.classList.remove("hidden");
    } else {
        goalDeleteButton.classList.add("hidden");
    }
}


function deleteTransaction(id) {
    postData("./transaction-hide.php", {id}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to delete.");
        }
    });
}

function deleteGoal(id) {
    postData("./goal-hide.php", {id}).then((data) => {
        if (data.success) {
            location.reload();
        } else {
            alert("failed to delete.");
        }
    });
}

<?php
    // Echo renderGoal for each goal
    renderGoals();
    $goalTotals = getGoalsTotals();
    if ($goalTotals[3] >= 0) {
        $amountString = $goalTotals[0];
        $totalString = $goalTotals[1];
        $ratio = $goalTotals[2];
        echo "renderGoalTotal(\"$amountString\", \"$totalString\", $ratio);";
    }

    // Echo renderTransaction for each transaction
    renderTransactions();
?>

</script>

<script>

function daysInMonth() {
    return <?php echo date("t", strtotime($clientDate)); ?>;
}

function dayOfTheMonth() {
    return <?php echo date("d", strtotime($clientDate)); ?>;
}

function monthNumber() {
    return <?php echo date("m", strtotime($clientDate)); ?>;
}

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

    // Fill out chart to today
    while (values.length > 0 && values.length < dayOfTheMonth()) {
        values.push(values[values.length - 1]);
    }

    // Add value at day 0 to render line on first day
    if (values.length == 1) {
        values.splice(0, 0, values[0]);
    }

    return values;
}

function calcMaxValue() {
    var txAmounts = <?=renderTransactionData()?>;

    var totalPositive = 0;
    txAmounts.forEach((elem) => {
        var amount = elem[1];
        if (amount < 0) {
            totalPositive -= amount;
        }
    });

    return totalPositive;
}

var dayValues = calcChartData();
var minValue = Math.min(0, ...dayValues);
var maxValue = calcMaxValue();

function renderChart(canvas, maxX, minY, maxY, values) {
    var height = canvas.height;
    var width = canvas.width;

    var xWidth = width / (maxX - 1);
    var y0 = height * (1 - (-minY) / (maxY - minY)); // Y value of 0

    // Get context
    const ctx = canvas.getContext("2d");

    if (values.length > 0) {
        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#AAA";
        var x = 0;
        var y = height * (1 - (values[0] - minY) / (maxY - minY));
        ctx.moveTo(x, y);
        ctx.lineTo(width, y0);
        ctx.stroke();
    }
    ctx.strokeStyle = "#000";

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
    lastY = 0;
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

    // Draw amount
    ctx.font = "18px sans-serif";
    ctx.fillStyle = "#000";
    ctx.lineWidth = 1;
    var text = "$" + Math.floor(maxY / 100).toLocaleString("en-US");
    ctx.fillText(text, 5, 15, width);
}

renderChart(document.getElementById("chart"), daysInMonth(), minValue, maxValue, dayValues);

function toggleChartDisplay() {
    var chart = document.getElementById("chart");
    if (chart.classList.contains("hidden")) {
        chart.classList.remove("hidden");
    } else {
        chart.classList.add("hidden");
    }
}
</script>

<script type="text/javascript">

// Quick functions for removing unnecessary elements in read-only mode
function hideGoals() {
    document.getElementById("goals").remove();
}
function hideTotals() {
    document.getElementById("totals").remove();
}
function showDateTitle() {
    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
    document.getElementById("month").textContent = monthNames[monthNumber() - 1];
}
function toggleMonthDisplay() {
    var monthDisplay = document.getElementById("month_display");
    if (monthDisplay.classList.contains("hidden")) {
        monthDisplay.classList.remove("hidden");
    } else {
        monthDisplay.classList.add("hidden");
    }
}
function hideAddTransaction() {
    var element = document.getElementById("tx_showform");
    element.classList.add("hidden");
}

// date browsing functions
function getPastValue() {
    const params = new URLSearchParams(document.location.search);
    var past = parseInt(params.get("past")) || 0;
    return past;
}
function previousMonth() {
    var past = getPastValue();
    const params = new URLSearchParams(document.location.search);
    params.set("past", Math.max(past + 1, 0));
    window.location.search = params.toString();
}
function nextMonth() {
    var past = getPastValue();
    if (past > 0) {
        const params = new URLSearchParams(document.location.search);
        params.set("past", past - 1);
        window.location.search = params.toString();
    }
}
if (getPastValue() <= 0) {
    document.getElementById("next_month_button").style.visibility = "hidden";
}

document.getElementById("current_amount").addEventListener("click", toggleChartDisplay);

<?php

// Read-only mode when viewing past months
if ($adjustedDate) {
    echo "toggleChartDisplay();\n";
    echo "hideGoals();\n";
    echo "hideTotals();\n";
    echo "showDateTitle();\n";
    echo "toggleMonthDisplay();\n";
    echo "hideAddTransaction();\n";
}

?>

</script>

</html>