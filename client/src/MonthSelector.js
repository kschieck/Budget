import { useBudget } from "./BudgetContext.js";

export default function MonthSelector({ children }) {
    const { monthOffset, previousMonth, nextMonth } = useBudget();
    return (
        <h1 className="no_bottom_space center_spaced">
            <button className="btn-icon" onClick={previousMonth}>‹</button>
            {children}
            <button
                className="btn-icon"
                onClick={nextMonth}
                style={monthOffset > -1 ? {} : { visibility: "hidden" }}
            >
                ›
            </button>
        </h1>
    );
}