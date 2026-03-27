import { useRef, useEffect, useState } from "react";
import * as d3 from "d3";
import { loadChartData } from "./API";
import { toDollarsNoCents } from "./Utils";

const MONTH_NAMES = [
    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
];

function parseYearMonth(ym) {
    const year = Math.floor(ym / 100);
    const month = ym % 100; // 1-indexed
    return {
        ym,
        label: MONTH_NAMES[month - 1] + " " + year,
    };
}

export function LineChart({ width = 600, height = 250 }) {
    const svgRef = useRef(null);
    const [data, setData] = useState([]);
    const [tooltip, setTooltip] = useState({
        visible: false,
        x: 0,
        y: 0,
        label: "",
        spent: 0,
        earned: 0,
    });

    useEffect(() => {
        loadChartData()
            .then((result) => {
                if (result.success) {
                    setData(result.months);
                }
            })
            .catch(() => alert("Failed to load chart data."));
    }, []);

    useEffect(() => {
        if (!svgRef.current || data.length === 0) return;

        const svg = d3.select(svgRef.current);
        svg.selectAll("*").remove();

        const margin = { top: 20, right: 20, bottom: 40, left: 70 };
        const innerWidth = width - margin.left - margin.right;
        const innerHeight = height - margin.top - margin.bottom;

        // Parse and sort chronologically — convert cents to dollars
        const parsed = data
            .map((d) => ({
                ...parseYearMonth(d.year_month),
                spent: d.spent / 100,
                earned: Math.abs(d.earned) / 100,
            }))
            .sort((a, b) => a.ym - b.ym);

        const labels = parsed.map((d) => d.label);

        // X: band scale over month labels
        const xScale = d3.scaleBand()
            .domain(labels)
            .range([0, innerWidth])
            .padding(0.2);

        const xCenter = (label) => xScale(label) + xScale.bandwidth() / 2;

        // Y: linear from 0 to max value with 10% headroom
        const maxVal = d3.max(parsed, (d) => Math.max(d.spent, d.earned)) || 1;
        const yScale = d3.scaleLinear()
            .domain([0, maxVal * 1.1])
            .range([innerHeight, 0]);

        const g = svg.append("g")
            .attr("transform", `translate(${margin.left},${margin.top})`);

        // Subtle horizontal grid lines
        const gridG = g.append("g").call(
            d3.axisLeft(yScale)
                .ticks(5)
                .tickSize(-innerWidth)
                .tickFormat("")
        );
        gridG.selectAll("line")
            .attr("stroke", "#e8e0dc")
            .attr("stroke-dasharray", "3,3");
        gridG.select(".domain").remove();

        // X axis
        const xAxisG = g.append("g")
            .attr("transform", `translate(0,${innerHeight})`)
            .call(d3.axisBottom(xScale));
        xAxisG.select(".domain").attr("stroke", "#c4afa5");
        xAxisG.selectAll(".tick line").attr("stroke", "#c4afa5");
        xAxisG.selectAll("text")
            .style("font-family", "'Nunito', sans-serif")
            .style("font-size", "12px")
            .style("fill", "#7a6860");

        // Y axis
        const yAxisG = g.append("g").call(
            d3.axisLeft(yScale)
                .ticks(5)
                .tickFormat((d) => toDollarsNoCents(d))
        );
        yAxisG.select(".domain").attr("stroke", "#c4afa5");
        yAxisG.selectAll(".tick line").attr("stroke", "#c4afa5");
        yAxisG.selectAll("text")
            .style("font-family", "'Nunito', sans-serif")
            .style("font-size", "11px")
            .style("fill", "#7a6860");

        // Line generators
        const spentLine = d3.line()
            .x((d) => xCenter(d.label))
            .y((d) => yScale(d.spent))
            .curve(d3.curveMonotoneX);

        const earnedLine = d3.line()
            .x((d) => xCenter(d.label))
            .y((d) => yScale(d.earned))
            .curve(d3.curveMonotoneX);

        // Expense line + dots
        g.append("path")
            .datum(parsed)
            .attr("fill", "none")
            .attr("stroke", "#c0473a")
            .attr("stroke-width", 2)
            .attr("d", spentLine);

        g.selectAll(".dot-spent")
            .data(parsed)
            .join("circle")
            .attr("class", "dot-spent")
            .attr("cx", (d) => xCenter(d.label))
            .attr("cy", (d) => yScale(d.spent))
            .attr("r", 4)
            .attr("fill", "#c0473a");

        // Income line + dots
        g.append("path")
            .datum(parsed)
            .attr("fill", "none")
            .attr("stroke", "#3a8c54")
            .attr("stroke-width", 2)
            .attr("d", earnedLine);

        g.selectAll(".dot-earned")
            .data(parsed)
            .join("circle")
            .attr("class", "dot-earned")
            .attr("cx", (d) => xCenter(d.label))
            .attr("cy", (d) => yScale(d.earned))
            .attr("r", 4)
            .attr("fill", "#3a8c54");

        // Legend (top-right of inner area)
        const legend = g.append("g")
            .attr("transform", `translate(${innerWidth - 90}, 0)`);

        legend.append("line")
            .attr("x1", 0).attr("y1", 7)
            .attr("x2", 16).attr("y2", 7)
            .attr("stroke", "#c0473a").attr("stroke-width", 2);
        legend.append("text")
            .attr("x", 20).attr("y", 11)
            .text("Expenses")
            .style("font-family", "'Nunito', sans-serif")
            .style("font-size", "12px")
            .style("fill", "#2c2420");

        legend.append("line")
            .attr("x1", 0).attr("y1", 25)
            .attr("x2", 16).attr("y2", 25)
            .attr("stroke", "#3a8c54").attr("stroke-width", 2);
        legend.append("text")
            .attr("x", 20).attr("y", 29)
            .text("Income")
            .style("font-family", "'Nunito', sans-serif")
            .style("font-size", "12px")
            .style("fill", "#2c2420");

        // Transparent hover columns — one per month band
        // Returns cleanup: remove all d3-added elements and their listeners on unmount
        const cleanup = () => svg.selectAll("*").remove();

        g.selectAll(".hover-col")
            .data(parsed)
            .join("rect")
            .attr("class", "hover-col")
            .attr("x", (d) => xScale(d.label))
            .attr("y", 0)
            .attr("width", xScale.bandwidth())
            .attr("height", innerHeight)
            .attr("fill", "transparent")
            .on("mouseover", function (event, d) {
                const cx = xCenter(d.label) + margin.left;
                setTooltip({
                    visible: true,
                    x: cx,
                    y: margin.top,
                    label: d.label,
                    spent: d.spent,
                    earned: d.earned,
                });
            })
            .on("mouseout", function () {
                setTooltip((prev) => ({ ...prev, visible: false }));
            });

        return cleanup;
    }, [data, width, height]);

    return (
        <div style={{ position: "relative", display: "inline-block" }}>
            <svg ref={svgRef} width={width} height={height} />
            {tooltip.visible && (
                <div
                    style={{
                        position: "absolute",
                        left: tooltip.x + 8,
                        top: tooltip.y,
                        background: "white",
                        border: "1px solid #c4afa5",
                        borderRadius: 4,
                        padding: "6px 10px",
                        pointerEvents: "none",
                        fontFamily: "'Nunito', sans-serif",
                        fontSize: 13,
                        color: "#2c2420",
                        boxShadow: "0 1px 4px rgba(0,0,0,0.12)",
                        zIndex: 10,
                        whiteSpace: "nowrap",
                    }}
                >
                    <div style={{ fontWeight: 700, marginBottom: 4 }}>
                        {tooltip.label}
                    </div>
                    <div style={{ color: "#c0473a" }}>
                        Expenses: {toDollarsNoCents(tooltip.spent)}
                    </div>
                    <div style={{ color: "#3a8c54" }}>
                        Income: {toDollarsNoCents(tooltip.earned)}
                    </div>
                </div>
            )}
        </div>
    );
}
