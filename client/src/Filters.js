import { useBudget } from "./BudgetContext.js";

function FilterRow({ name, checked, changeFilterState }) {
    return (
        <tr>
            <td style={{ width: "1px", whiteSpace: "nowrap" }}>
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => changeFilterState(name, e.target.checked)}
                />
            </td>
            <td style={{ width: "100%" }}>
                {String(name).charAt(0).toUpperCase() + String(name).slice(1)}
            </td>
        </tr>
    );
}

export default function FiltersSection({ names = [] }) {
    const { filters, changeFilterState } = useBudget();
    return (
        <>
            <h1>Filters</h1>
            <h3 className="sub_title">Show transactions by:</h3>
            <table>
                <tbody style={{ display: "table", width: "100%" }}>
                    {names.map((name) => (
                        <FilterRow
                            key={name}
                            name={name}
                            checked={filters ? filters.has(name) : true}
                            changeFilterState={changeFilterState}
                        />
                    ))}
                </tbody>
            </table>
        </>
    );
}
