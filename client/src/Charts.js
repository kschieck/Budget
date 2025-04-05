import { useRef, useEffect } from "react";

export function DrawdownChart({ width, height, transactions = [] }) {
    const canvasRef = useRef(null);

    // Parse transactions into the values we care about
    let TransactionDateAmounts = transactions
        .filter((transaction) => transaction.active)
        .map((transaction) => [
            transaction.date_added.split(" ")[0].split("-")[2],
            transaction.amount,
        ]);

    const dayOfTheMonth = new Date().getDate();
    const daysInMonth = new Date(
        new Date().getFullYear(),
        new Date().getMonth() + 1,
        0,
    ).getDate();

    function calcChartData(txAmounts) {
        txAmounts.sort(function (a, b) {
            return a[0] - b[0];
        });

        var values = [];
        var last = 0;
        txAmounts.forEach((elem) => {
            var day = elem[0];
            var amount = elem[1];

            while (values.length < day) {
                values.push(last);
            }
            last -= amount;
            values[day - 1] = last;
        });

        // Fill out chart to today
        while (values.length > 0 && values.length < dayOfTheMonth) {
            values.push(values[values.length - 1]);
        }

        // Add value at day 0 to render line on first day
        if (values.length == 1) {
            values.splice(0, 0, values[0]);
        }

        return values;
    }

    var dayValues = calcChartData(TransactionDateAmounts);
    var minValue = Math.min(0, ...dayValues);
    var maxValue = calcMaxValue(TransactionDateAmounts);

    function calcMaxValue(txAmounts) {
        var totalPositive = 0;
        txAmounts.forEach((elem) => {
            var amount = elem[1];
            if (amount < 0) {
                totalPositive -= amount;
            }
        });
        return totalPositive;
    }

    function renderChart(canvas, maxX, minY, maxY, values) {
        var height = canvas.height;
        var width = canvas.width;

        var xWidth = width / (maxX - 1);
        var y0 = height * (1 - -minY / (maxY - minY)); // Y value of 0

        // Get context
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);

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
                if (values[i] < 0 && values[i - 1] > 0) {
                    x = xWidth * (i - 1) + xWidth * lineRatio;
                } else if (values[i] > 0 && values[i - 1] < 0) {
                    ctx.lineTo(xWidth * (i - 1) + xWidth * lineRatio, y0);
                }
            }
            ctx.lineTo(x, Math.min(y, y0));
            lastY = y;
        }
        ctx.lineTo((values.length - 1) * xWidth, y0); // straight down
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
                if (values[i] < 0 && values[i - 1] > 0) {
                    ctx.lineTo(xWidth * (i - 1) + xWidth * lineRatio, y0);
                } else if (values[i] > 0 && values[i - 1] < 0) {
                    x = xWidth * (i - 1) + xWidth * lineRatio;
                }
            }
            ctx.lineTo(x, Math.max(y, y0));
            lastY = y;
        }
        ctx.lineTo((values.length - 1) * xWidth, y0); // straight down
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

        if (values.length > 0) {
            // Draw amount
            ctx.font = "18px sans-serif";
            ctx.fillStyle = "#000";
            ctx.lineWidth = 1;
            var text = "$" + Math.floor(maxY / 100).toLocaleString("en-US");
            ctx.fillText(text, 5, 15, width);
        }
    }

    useEffect(() => {
        renderChart(
            canvasRef.current,
            daysInMonth,
            minValue,
            maxValue,
            dayValues,
        );
    }, [transactions, width, height]);

    return <canvas ref={canvasRef} id="chart" width={width} height={height} />;
}
