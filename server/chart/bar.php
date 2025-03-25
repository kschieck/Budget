
<?php
require_once(__DIR__."/../dao.php");
include __DIR__."/../auth.php";

$dateOffset = "-4 hours";
$startDate = "2023-11-01 00:00:00";

function loadMonthlyTotals($startDate) {
    $monthlyTotals = getMonthlyTotals($startDate);
    $monthlyTotalsArray = [];
    while ($monthTotal = $monthlyTotals->fetch_assoc()) {
        $monthlyTotalsArray[] = $monthTotal;
    }
    echo json_encode($monthlyTotalsArray);
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
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>

<a class="top_left_fixed" href="/budget?analysis=1">Back</a>

<!-- Load d3.js -->
<script src="https://d3js.org/d3.v4.js"></script>

<!-- Create a div where the graph will take place -->
<div id="my_dataviz"></div>

<script>
window.onload = function() {
    window.scrollTo({ left: document.body.scrollWidth, behavior: 'smooth' });
};

var rawData = <?php loadMonthlyTotals($startDate) ?>;

var data = rawData.map(d => {
    var earned = -parseInt(d.earned);
    var spent = parseInt(d.spent);
    var difference = earned - spent;

    const date = new Date(Math.floor(d.year_month / 100), (d.year_month % 100) - 1, 1);
    const month = date.toLocaleString('default', { month: 'long' });
    const year = Math.floor(d.year_month / 100) % 100;

    return {Name: month + " " + year, Spent: spent / 100, Earned: earned / 100, Order: d.year_month};
});

function drawGraph(data) {

    var maxValue = Math.max.apply(null, data.map(d => d.Spent));

    // set the dimensions and margins of the graph
    var margin = {top: 30, right: 60, bottom: 70, left: 60},
        width = (data.length * 100) - margin.left - margin.right,
        height = 400 - margin.top - margin.bottom;

    // append the svg object to the body of the page
    var svg = d3.select("#my_dataviz")
    .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
    .append("g")
        .attr("transform",
            "translate(" + margin.left + "," + margin.top + ")");

    // sort data
    data.sort(function(b, a) {
        return b.Order - a.Order;
    });

    // X axis
    var x = d3.scaleBand()
    .range([ 0, width ])
    .domain(data.map(function(d) { return d.Name; }))
    .padding(0.2);
    svg.append("g")
    .attr("transform", "translate(0," + height + ")")
    .call(d3.axisBottom(x))
    .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end");

    // Add Y axis
    var y = d3.scaleLinear()
        .domain([0, maxValue])
        .range([height, 0]);
    svg.append("g")
        .call(d3.axisLeft(y));
    svg.append("g")
        .attr("transform", "translate(" + width + " ,0)")
        .call(d3.axisRight(y));

    var data1 = data.filter(d => d.Spent < d.Earned);
    var data2 = data.filter(d => d.Earned < d.Spent);

    let dollar = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });

    var blue = "#2c96c6";
    var red = "#e85539";
    var green = "#6cb537";
    var white = "#FFF";
    var black = "#000";

    svg.selectAll("mybar")
        .data(data)
        .enter()
        .append("rect")
            .attr("x", function(d) { return x(d.Name); })
            .attr("y", function(d) { return y(d.Spent); })
            .attr("width", x.bandwidth())
            .attr("height", function(d) { return height - y(d.Spent); })
            .attr("fill", red);

    svg.selectAll("mybar")
        .data(data)
        .enter()
        .append("rect")
            .attr("x", function(d) { return x(d.Name); })
            .attr("y", function(d) { return y(d.Earned); })
            .attr("width", x.bandwidth())
            .attr("height", function(d) { return height - y(d.Earned); })
            .attr("fill", blue);

    svg.selectAll("mybar")
        .data(data1)
        .enter()
        .append("rect")
            .attr("x", function(d) { return x(d.Name); })
            .attr("y", function(d) { return y(d.Earned - d.Spent); })
            .attr("width", x.bandwidth())
            .attr("height", function(d) { return height - y(d.Earned - d.Spent); })
            .attr("fill", green);

    // Text label (earned)
    svg.selectAll("mybar")
        .data(data)
        .enter()
        .append("text")
        .attr("y", d => y(d.Earned) + 15)
        .attr("x", function(d){ return x(d.Name) + x.bandwidth()/2})
        .attr('text-anchor', 'middle')
        .text(d => dollar.format(d.Earned))
        .attr("fill", white);

    svg.selectAll("mybar")
        .data(data2)
        .enter()
        .append("text")
        .attr("y", d => y(d.Spent) - 3)
        .attr("x", function(d){ return x(d.Name) + x.bandwidth()/2})
        .attr('text-anchor', 'middle')
        .text(d => dollar.format(d.Spent))
        .attr("fill", black);

    svg.selectAll("mybar")
        .data(data1)
        .enter()
        .append("text")
        .attr("y", d => y(d.Earned - d.Spent) - 3)
        .attr("x", function(d){ return x(d.Name) + x.bandwidth()/2})
        .attr('text-anchor', 'middle')
        .text(d => dollar.format(d.Earned - d.Spent))
        .attr("fill", white);

}

drawGraph(data);

</script>

</body>
</html>