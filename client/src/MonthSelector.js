export default function MonthSelector({ children, previousMonth, nextMonth, showNextMonth }) {
    return (
        <h1 className="no_bottom_space center_spaced">
            <button className="btn-icon" onClick={previousMonth}>‹</button>
            {children}
            <button
                className="btn-icon"
                onClick={nextMonth}
                style={showNextMonth ? {} : { visibility: "hidden" }}
            >
                ›
            </button>
        </h1>
    );
}