
<?php
require_once(__DIR__."/../dao.php");
include __DIR__."/../auth.php";

$dateOffset = "-4 hours";

function moneyFormat($dollars) {
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $fmt->formatCurrency($dollars, 'USD');
}

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

$transactions = loadTransactionsArray();

function renderTransactions() {
    global $transactions;
    $transactionsData = [];
    foreach ($transactions as &$tx) {
        $id = $tx["id"];
        $description = $tx["description"];
        $amount = $tx["amount"];
        $amountString = $tx["amount"] >= 0 ? 
            moneyFormat($tx["amount"] / 100.0) : 
            "(" . moneyFormat(-$tx["amount"] / 100.0) . ")";
        $active = $tx["active"];
        if ($active) {
            $transactionsData[] = array(
                "id" => $id,
                "name" => $description,
                "value" => $amount,
                "description" => $amountString
            );
        }
    }
    echo json_encode($transactionsData);
}
?>

<html>
<head>
<style>
.top_left_fixed {
    position: fixed;
    top: 5px;
    left: 5px;
}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
<a class="top_left_fixed" href="/budget?analysis=1">Back</a>

<div id="chart_slot"></div>

<!-- D3 v7 -->  
<script src="https://d3js.org/d3.v7.js"></script>
<!-- d3-sankey -->
<script src="d3-sankey.min.js"></script>

<script type="text/javascript">

var data = <?php renderTransactions(); ?>;

data.sort((a,b) =>  {
    if (a.value < 0 && b.value < 0) {
        if (a.value < b.value) {
            return 1;
        }
        if (a.value > b.value) {
            return -1;
        }
        return 0;
    }

    // Both positive (expenses)
    if (a.value < b.value) {
        return -1;
    }
    if (a.value > b.value) {
        return 1;
    }
    return 0;
});

function makeGraph(data) {
    var inflows = data.filter((elem) => elem.value < 0).reverse();
    var outflows = data.filter((elem) => elem.value > 0).reverse();

    var inflowNames = [];
    var nodes = [];
    inflows.forEach((elem) => {
        var idName = elem.id + "_" + elem.name;
        nodes.push({name: idName, displayName: elem.name});
        elem.valueLeft = elem.value * -1;
        elem.nodeId = nodes.length - 1;
        elem.idName = idName;
        inflowNames.push(idName);
    });

    outflows.forEach((elem) => {
        var idName = elem.id + "_" + elem.name;
        nodes.push({name: idName, displayName: elem.name});
        elem.valueLeft = elem.value;
        elem.nodeId = nodes.length - 1;
        elem.idName = idName;
    });

    var links = [];

    var overspendNode = null;
    var overspendNodeId = 0;
    var savedNode = null;
    var savedNodeId = 0;

    var i = 0;
    outflows.forEach((outflow) => {
        while(true) {

            if (inflows.length <= i) {

                if (overspendNode == null) {
                    overspendNode = {
                        name: "Overspent", 
                        displayName: "Overspent"
                    };
                    nodes.push(overspendNode);
                    overspendNodeId = nodes.length - 1;
                }

                links.push({
                    "source": overspendNodeId,
                    "target": outflow.nodeId,
                    "names": [
                        overspendNode.name,
                        outflow.idName
                    ],
                    "value": outflow.valueLeft
                });

                break;
            }

            // Get inflow and amount left
            var inflow = inflows[i];

            if (inflow.valueLeft > outflow.valueLeft) {
                // sweet
                links.push({
                    "source": inflow.nodeId,
                    "target": outflow.nodeId,
                    "names": [
                        inflow.idName,
                        outflow.idName
                    ],
                    "value": outflow.valueLeft
                });
                inflow.valueLeft -= outflow.valueLeft;
                outflow.valueLeft = 0;
                break;
            } else {
                outflow.valueLeft -= inflow.valueLeft;
                links.push({
                    "source": inflow.nodeId,
                    "target": outflow.nodeId,
                    "names": [
                        inflow.idName,
                        outflow.idName
                    ],
                    "value": inflow.valueLeft
                });
                i++;
            }
        }
    });

    for (; i < inflows.length; i++) {

        if (savedNode == null) {
            savedNode = {
                name: "Unspent", 
                displayName: "Unspent"
            };
            nodes.push(savedNode);
            savedNodeId = nodes.length - 1;
        }

        var inflow = inflows[i];
        links.push({
            "source": inflow.nodeId,
            "target": savedNodeId,
            "names": [ inflow.name, savedNode.name ],
            "value": inflow.valueLeft
        });
    }

    return {nodes, links, inflowNames};
}

var graph = makeGraph(data);

var inflowColors = graph.inflowNames.map(a => "#b3ffb5");
var inflowNames = [...graph.inflowNames, "Overspent"];
inflowColors.push("#ffb3b3");
const color = d3.scaleOrdinal(inflowNames, inflowColors).unknown("#ccc");

// Render the data into a chart
var chart = function makeChart(color) {
  const width = 500;
  const height = 2000;

  const sankey = d3.sankey()
    .nodeSort(null)
    .linkSort(null)
    .nodeWidth(4)
    .nodePadding(20)
    .extent([[0, 5], [width, height - 5]])

  const svg = d3.create("svg")
      .attr("viewBox", [0, 0, width, height])
      .attr("width", width)
      .attr("height", height)
      .attr("style", "max-width: 100%; height: auto;");

  const {nodes, links} = sankey({
    nodes: graph.nodes.map(d => Object.create(d)),
    links: graph.links.map(d => Object.create(d))
  });

  svg.append("g")
    .selectAll("rect")
    .data(nodes)
    .join("rect")
      .attr("x", d => d.x0)
      .attr("y", d => d.y0)
      .attr("height", d => d.y1 - d.y0)
      .attr("width", d => d.x1 - d.x0)
    .append("title")
      .text(d => `${d.name}\n${d.value.toLocaleString()}`);

  svg.append("g")
      .attr("fill", "none")
    .selectAll("g")
    .data(links)
    .join("path")
      .attr("d", d3.sankeyLinkHorizontal())
      .attr("stroke", d => color(d.names[0]))
      .attr("stroke-width", d => d.width)
      .style("mix-blend-mode", "multiply")
    .append("title")
      .text(d => `${d.names.join(" → ")}\n${d.value.toLocaleString()}`);

  svg.append("g")
      .style("font", "10px sans-serif")
    .selectAll("text")
    .data(nodes)
    .join("text")
      .attr("x", d => d.x0 < width / 2 ? d.x1 + 6 : d.x0 - 6)
      .attr("y", d => (d.y1 + d.y0) / 2)
      .attr("dy", "0.35em")
      .attr("text-anchor", d => d.x0 < width / 2 ? "start" : "end")
      .text(d => d.displayName)
    .append("tspan")
      .attr("fill-opacity", 0.7)
      .text(d => ` ${(d.value / 100).toLocaleString()}`);

  return svg.node();
}(color);

document.getElementById("chart_slot").appendChild(chart);

</script>
</body>
</html>