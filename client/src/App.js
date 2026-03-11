import { useState, useEffect } from "react";
import TransactionsSection, {
    AddEditTransactionDialog,
} from "./Transactions.js";
import { toDollars, toDollarsNoCents } from "./Utils.js";
import GoalsSection, {
    AddEditGoalDialog,
    AddGoalTransactionDialog,
} from "./Goals.js";
import FiltersSection from "./Filters.js";
import * as API from "./API.js";
import { DrawdownChart } from "./Charts.js";
import RecurringTransactionsSection from "./RecurringTransactions.js";
import MonthSelector from "./MonthSelector.js";

function LoginForm({ onTryLogin, disabled }) {
    let [username, setUsername] = useState("");
    let [password, setPassword] = useState("");

    function doSubmit(e) {
        e.preventDefault();
        onTryLogin(username, password);
    }

    return (
        <form id="form-container" method="post" onSubmit={doSubmit}>
            <fieldset disabled={disabled}>
                <input
                    type="text"
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    placeholder="username"
                />
                <br />
                <input
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="password"
                />
                <br />
                <input id="submit-button" type="submit" />
            </fieldset>
        </form>
    );
}

function BudgetApp() {
    const [monthOffset, setMonthOffset] = useState(0);
    const [filters, setFilters] = useState(null);
    const [goals, setGoals] = useState([]);
    const [transactions, setTransactions] = useState([]);
    const [amountTotal, setAmountTotal] = useState(0);

    const [showAddTransaction, setShowAddTransaction] = useState(false);
    const [editingTransactionId, setEditingTransactionId] = useState(null);
    const [showAddGoal, setShowAddGoal] = useState(false);
    const [editingGoalId, setEditingGoalId] = useState(null);
    const [contributingGoalId, setContributingGoalId] = useState(null);

    const editingTransaction =
        editingTransactionId !== null
            ? transactions.find((t) => t.id === editingTransactionId) ?? null
            : null;
    const editingGoal =
        editingGoalId !== null
            ? goals.find((g) => g.id === editingGoalId) ?? null
            : null;

    const isCurrentMonth = monthOffset === 0;
    const isNextMonth = monthOffset === -1;
    function getMonthName(monthOffset) {
        const monthNames = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December",
        ];
        const monthAdjustedDate = new Date();
        monthAdjustedDate.setMonth(monthAdjustedDate.getMonth() - monthOffset);
        return monthNames[monthAdjustedDate.getMonth()];
    }

    function getDaysLeftInTheMonth() {
        const now = new Date();
        return (
            new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate() -
            now.getDate()
        );
    }

    const filteredTransactions =
        filters === null
            ? transactions
            : transactions.filter((t) => filters.has(t.user));

    let totalTxSpentDollars = transactions
        .filter((tx) => tx.active && tx.amount > 0)
        .map((tx) => tx.amount)
        .reduce((acc, amount) => acc + amount, 0);
    let users = [
        ...new Set(
            transactions
                .map((transaction) => transaction.user)
                .filter((user) => user && user.length > 0),
        ),
    ];

    function loadAmountTotal() {
        API.loadAmount()
            .then((json) => {
                if (json.success) {
                    setAmountTotal(json.amount);
                }
            })
            .catch(console.error);
    }
    function loadGoals() {
        API.loadGoals()
            .then((json) => {
                if (json.success) {
                    setGoals(json.goals);
                }
            })
            .catch(console.error);
    }
    function loadTransactions() {
        if (isNextMonth) return;
        setTransactions([]);
        API.reloadTransactions(monthOffset)
            .then((json) => {
                if (json.success) {
                    setTransactions(json.transactions);
                }
            })
            .catch(console.error);
    }

    useEffect(loadAmountTotal, []);
    useEffect(loadGoals, []);
    useEffect(() => {
        setFilters(null);
        loadTransactions();
    }, [monthOffset]);
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
        setMonthOffset(monthOffset + 1);
    }
    function nextMonth() {
        setMonthOffset(Math.max(-1, monthOffset - 1));
    }
    function startAddTransaction() {
        setShowAddTransaction(true);
    }
    function startEditTransaction(transactionId) {
        setEditingTransactionId(transactionId);
    }
    function startDeleteTransaction(transactionId) {
        const transaction = transactions.find((t) => t.id === transactionId) || null;
        API.deleteTransaction(transactionId)
            .then((result) => {
                if (result.success) {
                    loadAmountTotal();
                    if (transaction?.goal_id) {
                        loadGoals();
                    }
                    setTransactions((prev) =>
                        prev.filter((t) => t.id !== transactionId),
                    );
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete");
            });
    }
    function startAddGoal() {
        setShowAddGoal(true);
    }
    function startEditGoal(goalId) {
        setEditingGoalId(goalId);
    }
    function startDeleteGoal(goalId) {
        API.deleteGoal(goalId)
            .then((result) => {
                if (result.success) {
                    loadGoals();
                } else if (result.message) {
                    alert(result.message);
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete");
            });
    }
    function startContributeGoal(goalId) {
        setContributingGoalId(goalId);
    }
    function changeFilterState(name, showTransactions) {
        setFilters((prev) => {
            const newFilters = new Set(prev ?? users);
            if (showTransactions) {
                newFilters.add(name);
            } else {
                newFilters.delete(name);
            }
            return newFilters;
        });
    }
    return (
        <>
            {showAddTransaction && (
                <AddEditTransactionDialog
                    onCancel={() => setShowAddTransaction(false)}
                    onSave={(id, amount, description) => {
                        setShowAddTransaction(false);
                        API.saveTransaction(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    loadAmountTotal();
                                    loadTransactions();
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save");
                            });
                    }}
                />
            )}
            {editingTransaction !== null && (
                <AddEditTransactionDialog
                    id={editingTransaction.id}
                    amount={editingTransaction.amount / 100}
                    description={editingTransaction.description}
                    onCancel={() => setEditingTransactionId(null)}
                    onSave={(id, amount, description) => {
                        const goalId = editingTransaction.goal_id ?? null;
                        setEditingTransactionId(null);
                        API.saveTransaction(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    loadAmountTotal();
                                    loadTransactions();
                                    if (goalId) {
                                        loadGoals();
                                    }
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save");
                            });
                    }}
                />
            )}
            {showAddGoal && (
                <AddEditGoalDialog
                    onCancel={() => setShowAddGoal(false)}
                    onSave={(id, amount, description) => {
                        setShowAddGoal(false);
                        API.saveGoal(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    loadGoals();
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save");
                            });
                    }}
                />
            )}
            {editingGoal !== null && (
                <AddEditGoalDialog
                    id={editingGoal.id}
                    description={editingGoal.name}
                    amount={editingGoal.total / 100}
                    onCancel={() => setEditingGoalId(null)}
                    onSave={(id, amount, description) => {
                        setEditingGoalId(null);
                        API.saveGoal(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    loadGoals();
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save");
                            });
                    }}
                />
            )}
            {contributingGoalId !== null && (
                <AddGoalTransactionDialog
                    id={contributingGoalId}
                    onCancel={() => setContributingGoalId(null)}
                    onSave={(id, amount) => {
                        setContributingGoalId(null);
                        API.saveGoalTransaction(id, amount)
                            .then((result) => {
                                if (result.success) {
                                    loadAmountTotal();
                                    loadTransactions();
                                    loadGoals();
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save");
                            });
                    }}
                />
            )}

            <MonthSelector
                previousMonth={previousMonth}
                nextMonth={nextMonth}
                showNextMonth={monthOffset > -1}
            >
                <span>
                    {isCurrentMonth
                        ? toDollars(amountTotal / 100)
                        : getMonthName(monthOffset)}
                    <br />
                    {!isNextMonth ? (
                        <div id="total_spend">
                            {toDollarsNoCents(totalTxSpentDollars / 100)} spent
                            {isCurrentMonth ? ", " + getDaysLeftInTheMonth() + " days left" : null}
                        </div>) : null}
                </span>

            </MonthSelector>

            {!isNextMonth ? (
                <h1 className="chart-wrapper">
                    <DrawdownChart
                        transactions={transactions}
                        width={300}
                        height={100}
                    />
                    <br />
                </h1>
            ) : null}

            {
                isNextMonth ? (
                    <RecurringTransactionsSection />
                ) : (
                    <TransactionsSection
                        readonly={!isCurrentMonth}
                        transactions={filteredTransactions}
                        startAddTransaction={startAddTransaction}
                        startEditTransaction={startEditTransaction}
                        startDeleteTransaction={startDeleteTransaction}
                    />
                )
            }

            {
                isCurrentMonth ? (
                    <GoalsSection
                        goals={goals}
                        startAddGoal={startAddGoal}
                        startEditGoal={startEditGoal}
                        startDeleteGoal={startDeleteGoal}
                        startContributeGoal={startContributeGoal}
                    />
                ) : null
            }

            {
                users.length > 0 && !isNextMonth ? (
                    <FiltersSection
                        names={users}
                        filters={filters}
                        changeFilterState={changeFilterState}
                    />
                ) : null
            }
        </>
    );
}

export default function App() {
    const [loggingIn, setLoggingIn] = useState(false);
    // null = auth check in progress, true = authenticated, false = not authenticated
    const [authSuccess, setAuthSuccess] = useState(null);

    useEffect(() => {
        // Try cookie auth first (browser sends HttpOnly cookie automatically).
        // If that fails, fall back to a localStorage token for browsers that
        // don't persist cookies (iOS PWA mode, in-app browsers, etc.).
        API.AuthWithCookie()
            .then((result) => {
                if (result.success) {
                    setAuthSuccess(true);
                    return;
                }
                const token = localStorage.getItem("rememberme");
                if (token) {
                    return API.AuthWithToken(token)
                        .then((result) => setAuthSuccess(result.success))
                        .catch(() => setAuthSuccess(false));
                }
                setAuthSuccess(false);
            })
            .catch(() => setAuthSuccess(false));
    }, []);

    function onTryLogin(username, password) {
        setLoggingIn(true);
        API.AuthWithUserPass(username, password)
            .then((result) => {
                if (result.success && result.token) {
                    localStorage.setItem("rememberme", result.token);
                }
                setAuthSuccess(result.success);
            })
            .catch(() => setAuthSuccess(false))
            .finally(() => setLoggingIn(false));
    }

    if (authSuccess === null) {
        return null;
    }

    if (!authSuccess) {
        return <LoginForm onTryLogin={onTryLogin} disabled={loggingIn} />;
    }

    return <BudgetApp />;
}
