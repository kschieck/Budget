function FilterRow({ name, checked, changeFilterState }) {
    return (
        <tr>
            <td>
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => changeFilterState(name, e.target.checked)}
                />
            </td>
            <td>
                {String(name).charAt(0).toUpperCase() + String(name).slice(1)}
            </td>
        </tr>
    );
}

export default function FiltersSection({
    names = [],
    filters,
    changeFilterState,
}) {
    return (
        <>
            <h1>Filters</h1>
            <h3 className="sub_title">Show transactions by:</h3>
            <table>
                <tbody>
                    {names.map((name) => (
                        <FilterRow
                            key={name}
                            name={name}
                            checked={!filters.has(name)}
                            changeFilterState={changeFilterState}
                        />
                    ))}
                </tbody>
            </table>
        </>
    );
}
