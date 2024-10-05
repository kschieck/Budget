<?php
require_once(__DIR__."/dao.php");

include __DIR__."/auth.php";

$dateOffset = "-4 hours";
$clientDate = date('Y-m-d H:i:s', strtotime($dateOffset));

function moneyFormat($dollars) {
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $fmt->formatCurrency($dollars, 'USD');
}

function loadLastMonth1stTransactions() {
    global $clientDate;
    $transactions = [];
    $transactionsItr = getLastMonth1stTransactions($clientDate);
    while ($tx = $transactionsItr->fetch_assoc()) {
        $amount = $tx["amount"];
        $tx["amountString"] =  $amount >= 0 ? moneyFormat($amount / 100.0) : "(" . moneyFormat(-$amount / 100.0) . ")";;
        $transactions[] = $tx;
    }
    return $transactions;
}
$lastMonth1stTransactions = loadLastMonth1stTransactions();

?>

<html>
<head>
<style>
td, .soft_underline {
  border-bottom: 1px solid #ddd;
}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>

    <h2>Last Month's Day 1 Transactions</h2>
    <table>
        <thead></thead>
        <tbody id="transaction_slot"></tbody>
    </table>
    <br />
    <input type="button" onclick="CreateTransactions()" value="Create Transactions"></input>

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

var checkboxDefaultValue = false;

var lastMonthTransactions = <?php echo json_encode($lastMonth1stTransactions); ?>;
var checkboxStates = [];
checkboxStates.length = lastMonthTransactions.length;
checkboxStates.fill(checkboxDefaultValue);

var transactionSlot = document.getElementById("transaction_slot");

function createTransactionTableRow(transaction, checkbox) {

    var tr = document.createElement("tr");
    var td1 = document.createElement("td");
    var td2 = document.createElement("td");
    var td3 = document.createElement("td");
    tr.appendChild(td1);
    tr.appendChild(td2);
    tr.appendChild(td3);

    td1.appendChild(checkbox);
    td2.textContent = transaction.description;
    td3.textContent = transaction.amountString;

    return tr;
}

lastMonthTransactions.forEach(function(transaction, index) {

    var checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.checked = checkboxDefaultValue;
    checkbox.id = "transaction_" + transaction.id;
    transactionSlot.appendChild(checkbox);
    var checkBoxIndex = index;
    checkbox.addEventListener('change', (event) => {
        checkboxStates[checkBoxIndex] = event.currentTarget.checked;
    });

    transactionSlot.appendChild(createTransactionTableRow(transaction, checkbox));
});

function CreateTransactions() {

    var transactionIds = lastMonthTransactions.filter((element, index) => checkboxStates[index]).map((element) => element.id);
    
    if (transactionIds.length == 0)
    {
        alert("failed to save.");
        return;
    }

    postData("./transaction-duplicate.php", {transactionIds}).then((data) => {
        if (data.success) {
            location = "./"; // go to main page on success
        } else {
            alert("failed to save.");
        }
    });
}

</script>
</body>
</html>