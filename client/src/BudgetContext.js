import { createContext, useContext, useState, useEffect } from "react";

const BudgetContext = createContext(null);

export function useBudget() {
    const context = useContext(BudgetContext);
    if (context === null) {
        throw new Error("useBudget must be used within a BudgetContext.Provider");
    }
    return context;
}

export function useBudgetNavigation(transactions, setTransactions) {
    const [monthOffset, setMonthOffset] = useState(0);
    const [filters, setFilters] = useState(null);

    useEffect(() => {
        setFilters(null);
        setTransactions([]);
    }, [monthOffset, setTransactions]);

    useEffect(() => {
        if (filters === null && transactions.length > 0) {
            setFilters(
                new Set(
                    transactions
                        .map((t) => t.user)
                        .filter((u) => u && u.length > 0),
                ),
            );
        }
    }, [filters, transactions]);

    function previousMonth() {
        setMonthOffset((prev) => prev + 1);
    }

    function nextMonth() {
        setMonthOffset((prev) => Math.max(-1, prev - 1));
    }

    function changeFilterState(name, showTransactions) {
        if (filters === null) return;
        setFilters((prev) => {
            const newFilters = new Set(prev);
            if (showTransactions) {
                newFilters.add(name);
            } else {
                newFilters.delete(name);
            }
            return newFilters;
        });
    }

    return { monthOffset, filters, previousMonth, nextMonth, changeFilterState };
}

export default BudgetContext;
