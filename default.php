<?php
require_once(__DIR__."/dao.php");

include __DIR__."/auth.php";

function moneyFormat($dollars) {
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY );
    return $fmt->formatCurrency($dollars, 'USD');
}

$amountDollars = moneyFormat(loadAmount() / 100.0);

function renderTransactions() {
    $txs = loadTransactionsDate(date('Y-m-01') . " 00:00:00");

    while ($tx = $txs->fetch_assoc()) {
        echo "<tr>";
            echo "<td>" . substr(explode(" ", $tx["date_added"])[0], 5) . "</td>";
            echo "<td><b>" . moneyFormat($tx["amount"] / 100.0) . "</b></td>";
            echo "<td>" . $tx["description"] . "</td>";
        echo "</tr>";
    }
}

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

td {
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

</style>
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
    <h1><?=$amountDollars?></h1>

    <table id="tx_table" cellspacing="0">
        <thead>
            <tr>
                <th style="color: white">Date</th>
                <th id="amount_head">$&nbsp;<input type="number" id="tx_amount" placeholder="amount"></input></th>
                <th><input type="text" id="tx_desc" placeholder="description"></input></th>
                <th><button id="tx_add" onclick="submitAddTransaction()">+</button>
            </tr>
        </thead>
        <!-- TODO make scrollable -->
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
    console.log(id, name, amount, total);

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

<?php
    // Echo renderGoal for each goal
    renderGoals();
?>

</script>
</html>