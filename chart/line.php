
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

// Function to create the path for a triangle
function trianglePath(x, y, size) {
  const height = size * Math.sqrt(3) / 2;  // Calculate the height of the equilateral triangle
  return `M ${x},${y - height / 2} ` +   // Move to the top vertex
         `L ${x - size / 2},${y + height / 2} ` +  // Bottom-left vertex
         `L ${x + size / 2},${y + height / 2} ` +  // Bottom-right vertex
         `Z`;  // Close the path (back to the starting point)
}


function drawGraph(data) {

    var maxValue = Math.max.apply(null, data.map(d => d.Spent));

    // set the dimensions and margins of the graph
    var margin = {top: 10, right: 60, bottom: 70, left: 60},
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

    // Add the line
    svg.append("path")
      .datum(data)
      .attr("fill", "none")
      .attr("stroke", "red")
      .attr("stroke-width", 2)
      .attr("d", d3.line()
        .x(function(d) { return x(d.Name) + x.bandwidth()/2 })
        .y(function(d) { return y(d.Spent) })
        );

    // Add the line
    svg.append("path")
      .datum(data)
      .attr("fill", "none")
      .attr("stroke", "green")
      .attr("stroke-width", 2)
      .attr("d", d3.line()
        .x(function(d) { return x(d.Name) + x.bandwidth()/2 })
        .y(function(d) { return y(d.Earned) })
        );

    // Add circles at each Earned data point
    svg.selectAll("circle")
    .data(data)
    .enter()
    .append("circle")
    .attr("cx", function(d) { return x(d.Name) + x.bandwidth()/2 }) // X coordinate for circle
    .attr("cy", function(d) { return y(d.Earned) })                 // Y coordinate for circle
    .attr("r", 4)                                                   // Radius of the circle
    .attr("fill", "green");   

    // Add circles at each Spent data point
    svg.selectAll("path.triangle")
    .data(data)
    .enter()
    .append("path")
    .attr("class", "triangle")
    .attr("d", function(d) { return trianglePath(x(d.Name) + x.bandwidth()/2, y(d.Spent), 10); }) // Create triangle path
    .attr("fill", "red");  // Set the fill color

}

drawGraph(data);

</script>

</body>
</html>