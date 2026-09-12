import { useRef, useEffect, useState } from "react";
import * as d3 from "d3";
import { loadCategoryChartData } from "./API.js";
import { toDollarsNoCents } from "./Utils.js";
import { useBudget } from "./BudgetContext.js";

const MONTH_NAMES = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];

function parseYearMonth(ym) {
    const year = Math.floor(ym / 100);
    const month = ym % 100; // 1-indexed
    return { ym, label: MONTH_NAMES[month - 1] + " " + year };
}

function yearMonthFromOffset(offset) {
    const d = new Date();
    d.setMonth(d.getMonth() - offset);
    return d.getFullYear() * 100 + (d.getMonth() + 1);
}

function useCategoryChartData() {
    const [data, setData] = useState(null);

    useEffect(() => {
        loadCategoryChartData()
            .then((result) => {
                if (result.success) {
                    setData(result.months);
                }
            })
            .catch(() => alert("Failed to load category chart data."));
    }, []);

    return data;
}

function NoData({ width, height, message }) {
    return (
        <div
            style={{
                width,
                height,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                color: "#7a6860",
                fontSize: 13,
            }}
        >
            {message}
        </div>
    );
}

export function CategoryPieChart({ width = 300, height = 260 }) {
    const svgRef = useRef(null);
    const data = useCategoryChartData();
    const { monthOffset } = useBudget();
    const [tooltip, setTooltip] = useState({
        visible: false,
        x: 0,
        y: 0,
        label: "",
        spent: 0,
    });

    const targetMonth = yearMonthFromOffset(Math.max(0, monthOffset));
    const monthData = (data || []).filter(
        (d) => d.year_month === targetMonth && d.spent > 0,
    );

    useEffect(() => {
        if (!svgRef.current) return;
        const svg = d3.select(svgRef.current);
        svg.selectAll("*").remove();

        if (monthData.length === 0) return;

        const radius = Math.min(width, height) / 2 - 30;
        const g = svg
            .append("g")
            .attr("transform", `translate(${width / 2},${height / 2 - 10})`);

        const pie = d3
            .pie()
            .value((d) => d.spent)
            .sort(null);
        const arc = d3
            .arc()
            .innerRadius(radius * 0.45)
            .outerRadius(radius);

        const arcs = pie(monthData);

        g.selectAll("path")
            .data(arcs)
            .join("path")
            .attr("d", arc)
            .attr("fill", (d) => d.data.category_color)
            .attr("stroke", "#fff")
            .attr("stroke-width", 1.5)
            .on("mouseover", function (event, d) {
                const [cx, cy] = arc.centroid(d);
                setTooltip({
                    visible: true,
                    x: width / 2 + cx,
                    y: height / 2 - 10 + cy,
                    label: d.data.category_name,
                    spent: d.data.spent / 100,
                });
            })
            .on("mouseout", function () {
                setTooltip((prev) => ({ ...prev, visible: false }));
            });

        // Legend
        const legend = svg
            .append("g")
            .attr("transform", `translate(10, ${height - 20})`);
        let legendX = 0;
        monthData.forEach((d) => {
            const item = legend
                .append("g")
                .attr("transform", `translate(${legendX}, 0)`);
            item.append("rect")
                .attr("width", 10)
                .attr("height", 10)
                .attr("fill", d.category_color)
                .attr("rx", 2);
            const text = item
                .append("text")
                .attr("x", 14)
                .attr("y", 9)
                .text(d.category_name)
                .style("font-family", "'Nunito', sans-serif")
                .style("font-size", "11px")
                .style("fill", "#2c2420");
            legendX += 20 + (text.node()?.getComputedTextLength() || 40);
        });

        return () => svg.selectAll("*").remove();
    }, [monthData, width, height]);

    if (data === null)
        return <NoData width={width} height={height} message="Loading…" />;
    if (monthData.length === 0)
        return (
            <NoData
                width={width}
                height={height}
                message="No categorized spending this month"
            />
        );

    return (
        <div style={{ position: "relative", display: "inline-block" }}>
            <svg
                ref={svgRef}
                width={width}
                height={height}
                viewBox={`0 0 ${width} ${height}`}
            />
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
                    <div style={{ fontWeight: 700 }}>{tooltip.label}</div>
                    <div>{toDollarsNoCents(tooltip.spent)}</div>
                </div>
            )}
        </div>
    );
}

export function CategoryBarChart({ width = 400, height = 240 }) {
    const svgRef = useRef(null);
    const data = useCategoryChartData();
    const [tooltip, setTooltip] = useState({
        visible: false,
        x: 0,
        y: 0,
        label: "",
        spent: 0,
    });

    useEffect(() => {
        if (!svgRef.current || !data || data.length === 0) return;

        const svg = d3.select(svgRef.current);
        svg.selectAll("*").remove();

        const margin = { top: 20, right: 20, bottom: 40, left: 60 };
        const innerWidth = width - margin.left - margin.right;
        const innerHeight = height - margin.top - margin.bottom;

        const months = [...new Set(data.map((d) => d.year_month))]
            .sort((a, b) => a - b)
            .map(parseYearMonth);
        const labels = months.map((m) => m.label);

        const categoryIds = [...new Set(data.map((d) => d.category_id))];
        const categoryMeta = new Map(
            data.map((d) => [
                d.category_id,
                { name: d.category_name, color: d.category_color },
            ]),
        );

        // Pivot: month label -> { total, segments: [{category_id, spent}] }
        const byMonth = new Map(
            labels.map((label) => [label, { total: 0, segments: [] }]),
        );
        months.forEach((m) => {
            categoryIds.forEach((categoryId) => {
                const row = data.find(
                    (d) =>
                        d.year_month === m.ym && d.category_id === categoryId,
                );
                const spent = row ? row.spent / 100 : 0;
                if (spent > 0) {
                    const entry = byMonth.get(m.label);
                    entry.segments.push({ categoryId, spent });
                    entry.total += spent;
                }
            });
        });

        const xScale = d3
            .scaleBand()
            .domain(labels)
            .range([0, innerWidth])
            .padding(0.3);
        const maxTotal =
            d3.max(labels, (label) => byMonth.get(label).total) || 1;
        const yScale = d3
            .scaleLinear()
            .domain([0, maxTotal * 1.1])
            .range([innerHeight, 0]);

        const g = svg
            .append("g")
            .attr("transform", `translate(${margin.left},${margin.top})`);

        const gridG = g
            .append("g")
            .call(
                d3
                    .axisLeft(yScale)
                    .ticks(5)
                    .tickSize(-innerWidth)
                    .tickFormat(""),
            );
        gridG
            .selectAll("line")
            .attr("stroke", "#e8e0dc")
            .attr("stroke-dasharray", "3,3");
        gridG.select(".domain").remove();

        const xAxisG = g
            .append("g")
            .attr("transform", `translate(0,${innerHeight})`)
            .call(d3.axisBottom(xScale));
        xAxisG.select(".domain").attr("stroke", "#c4afa5");
        xAxisG.selectAll(".tick line").attr("stroke", "#c4afa5");
        xAxisG
            .selectAll("text")
            .style("font-family", "'Nunito', sans-serif")
            .style("font-size", "12px")
            .style("fill", "#7a6860");

        const yAxisG = g.append("g").call(
            d3
                .axisLeft(yScale)
                .ticks(5)
                .tickFormat((d) => toDollarsNoCents(d)),
        );
        yAxisG.select(".domain").attr("stroke", "#c4afa5");
        yAxisG.selectAll(".tick line").attr("stroke", "#c4afa5");
        yAxisG
            .selectAll("text")
            .style("font-family", "'Nunito', sans-serif")
            .style("font-size", "11px")
            .style("fill", "#7a6860");

        labels.forEach((label) => {
            const { segments } = byMonth.get(label);
            let cumulative = 0;
            segments.forEach((seg) => {
                const meta = categoryMeta.get(seg.categoryId);
                const y0 = cumulative;
                const y1 = cumulative + seg.spent;
                cumulative = y1;
                g.append("rect")
                    .attr("x", xScale(label))
                    .attr("y", yScale(y1))
                    .attr("width", xScale.bandwidth())
                    .attr("height", yScale(y0) - yScale(y1))
                    .attr("fill", meta.color)
                    .on("mouseover", function (event) {
                        setTooltip({
                            visible: true,
                            x:
                                xScale(label) +
                                margin.left +
                                xScale.bandwidth() / 2,
                            y: yScale(y1) + margin.top,
                            label: meta.name,
                            spent: seg.spent,
                        });
                    })
                    .on("mouseout", function () {
                        setTooltip((prev) => ({ ...prev, visible: false }));
                    });
            });
        });

        return () => svg.selectAll("*").remove();
    }, [data, width, height]);

    if (data === null)
        return <NoData width={width} height={height} message="Loading…" />;
    if (data.length === 0)
        return (
            <NoData
                width={width}
                height={height}
                message="No categorized spending yet"
            />
        );

    return (
        <div style={{ position: "relative", display: "inline-block" }}>
            <svg
                ref={svgRef}
                width={width}
                height={height}
                viewBox={`0 0 ${width} ${height}`}
            />
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
                    <div style={{ fontWeight: 700 }}>{tooltip.label}</div>
                    <div>{toDollarsNoCents(tooltip.spent)}</div>
                </div>
            )}
        </div>
    );
}
