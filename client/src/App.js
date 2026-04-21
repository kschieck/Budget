import { useState, useEffect } from "react";
import BudgetContext, { useBudgetNavigation } from "./BudgetContext.js";
import TransactionsSection, {
    AddEditTransactionDialog,
} from "./Transactions.js";
import { toDollars } from "./Utils.js";
import GoalsSection, {
    AddEditGoalDialog,
    AddGoalTransactionDialog,
} from "./Goals.js";
import * as API from "./API.js";
import { DrawdownChart } from "./Charts.js";
import RecurringTransactionsSection from "./RecurringTransactions.js";
import UpcomingTransactionsSection, { getNextMonthString } from "./UpcomingTransactions.js";
import MonthSelector from "./MonthSelector.js";
import { LineChart } from "./LineChart.js";

function LoginForm({ onTryLogin, disabled }) {
    const [username, setUsername] = useState("");
    const [password, setPassword] = useState("");

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
    const [goals, setGoals] = useState([]);
    const [transactions, setTransactions] = useState([]);
    const [amountTotal, setAmountTotal] = useState(0);

    const { monthOffset, filters, previousMonth, nextMonth, changeFilterState } =
        useBudgetNavigation(transactions, setTransactions);

    const [showAddTransaction, setShowAddTransaction] = useState(false);
    const [editingTransactionId, setEditingTransactionId] = useState(null);
    const [showAddGoal, setShowAddGoal] = useState(false);
    const [editingGoalId, setEditingGoalId] = useState(null);
    const [contributingGoalId, setContributingGoalId] = useState(null);
    const [chartToggle, setChartToggle] = useState(true);
    const [upcomingReloadKey, setUpcomingReloadKey] = useState(0);

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
                } else {
                    alert("Failed to load data");
                }
            })
            .catch(() => alert("Failed to load data"));
    }
    function loadGoals() {
        API.loadGoals()
            .then((json) => {
                if (json.success) {
                    setGoals(json.goals);
                } else {
                    alert("Failed to load data");
                }
            })
            .catch(() => alert("Failed to load data"));
    }
    function loadTransactions() {
        if (isNextMonth) return;
        setTransactions([]);
        API.reloadTransactions(monthOffset)
            .then((json) => {
                if (json.success) {
                    setTransactions(json.transactions);
                    if (monthOffset === 0) {
                        setUpcomingReloadKey((k) => k + 1);
                    }
                } else {
                    alert("Failed to load data");
                }
            })
            .catch(() => alert("Failed to load data"));
    }

    useEffect(loadAmountTotal, []);
    useEffect(loadGoals, []);
    useEffect(loadTransactions, [monthOffset]);

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
                    setTransactions((prev) =>
                        prev.filter((t) => t.id !== transactionId),
                    );
                    if (transaction) {
                        setAmountTotal((prev) => prev + transaction.amount);
                        if (transaction.goal_id) {
                            setGoals((prev) =>
                                prev.map((g) =>
                                    g.id === transaction.goal_id
                                        ? { ...g, amount: g.amount - transaction.amount }
                                        : g,
                                ),
                            );
                        }
                    }
                } else {
                    alert(result.message || "Failed to delete transaction");
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete transaction");
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
                    setGoals((prev) => prev.filter((g) => g.id !== goalId));
                } else {
                    alert(result.message || "Failed to delete goal");
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete goal");
            });
    }
    function startContributeGoal(goalId) {
        setContributingGoalId(goalId);
    }
    function toggleChart() {
        setChartToggle(!chartToggle);
    }
    return (
        <BudgetContext.Provider
            value={{ monthOffset, filters, previousMonth, nextMonth, changeFilterState }}
        >
            {showAddTransaction && (
                <AddEditTransactionDialog
                    onCancel={() => setShowAddTransaction(false)}
                    onSave={(id, amount, description) => {
                        return API.saveTransaction(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    setShowAddTransaction(false);
                                    setTransactions((prev) => [result.transaction, ...prev]);
                                    setAmountTotal((prev) => prev - result.transaction.amount);
                                    return true;
                                } else {
                                    alert(result.message || "Failed to save transaction");
                                    return false;
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save transaction");
                                return false;
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
                        const prevTransaction = editingTransaction;
                        return API.saveTransaction(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    setEditingTransactionId(null);
                                    setTransactions((prev) =>
                                        prev.map((t) =>
                                            t.id === result.transaction.id
                                                ? { ...t, amount: result.transaction.amount, description: result.transaction.description }
                                                : t,
                                        ),
                                    );
                                    setAmountTotal((prev) => prev + prevTransaction.amount - result.transaction.amount);
                                    if (result.transaction.goal_id) {
                                        setGoals((prev) =>
                                            prev.map((g) =>
                                                g.id === result.transaction.goal_id
                                                    ? { ...g, amount: g.amount + result.transaction.amount - prevTransaction.amount }
                                                    : g,
                                            ),
                                        );
                                    }
                                    return true;
                                } else {
                                    alert(result.message || "Failed to save transaction");
                                    return false;
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save transaction");
                                return false;
                            });
                    }}
                />
            )}
            {showAddGoal && (
                <AddEditGoalDialog
                    onCancel={() => setShowAddGoal(false)}
                    onSave={(id, amount, description) => {
                        return API.saveGoal(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    setShowAddGoal(false);
                                    setGoals((prev) => [...prev, result.goal]);
                                    return true;
                                } else {
                                    alert(result.message || "Failed to save goal");
                                    return false;
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save goal");
                                return false;
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
                        return API.saveGoal(id, amount, description)
                            .then((result) => {
                                if (result.success) {
                                    setEditingGoalId(null);
                                    setGoals((prev) =>
                                        prev.map((g) =>
                                            g.id === result.goal.id
                                                ? { ...g, total: result.goal.total }
                                                : g,
                                        ),
                                    );
                                    return true;
                                } else {
                                    alert(result.message || "Failed to save goal");
                                    return false;
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save goal");
                                return false;
                            });
                    }}
                />
            )}
            {contributingGoalId !== null && (
                <AddGoalTransactionDialog
                    id={contributingGoalId}
                    onCancel={() => setContributingGoalId(null)}
                    onSave={(id, amount) => {
                        const goalId = contributingGoalId;
                        return API.saveGoalTransaction(id, amount)
                            .then((result) => {
                                if (result.success) {
                                    setContributingGoalId(null);
                                    setTransactions((prev) => [result.transaction, ...prev]);
                                    setAmountTotal((prev) => prev - result.transaction.amount);
                                    setGoals((prev) =>
                                        prev.map((g) =>
                                            g.id === goalId
                                                ? { ...g, amount: result.goalAmount }
                                                : g,
                                        ),
                                    );
                                    return true;
                                } else {
                                    alert(result.message || "Failed to save goal contribution");
                                    return false;
                                }
                            })
                            .catch((e) => {
                                console.error(e);
                                alert("Failed to save goal contribution");
                                return false;
                            });
                    }}
                />
            )}

            <MonthSelector>
                <span>
                    {isCurrentMonth
                        ? toDollars(amountTotal / 100)
                        : getMonthName(monthOffset)}
                    <br />
                    {!isNextMonth ? (
                        <div id="days_left">
                            {isCurrentMonth ? getDaysLeftInTheMonth() + " days left" : null}
                        </div>) : null}
                </span>

            </MonthSelector>

            <div className="main-grid">
                <div className="col-primary">
                    {!isNextMonth && (
                        <div onClick={toggleChart}>
                            {!isCurrentMonth || chartToggle ?
                                <h1 className="chart-wrapper">
                                    Drawdown
                                    <DrawdownChart
                                        transactions={transactions}
                                        width={300}
                                        height={166}
                                    />
                                    <br />
                                </h1> :
                                <h1 className="chart-wrapper">
                                    Monthly Income/Expenses
                                    <LineChart width={400} height={200} />
                                    <br />
                                </h1>}
                        </div>
                    )}


                    {isNextMonth && (
                        <UpcomingTransactionsSection
                            filterMonth={getNextMonthString()}
                            reloadKey={upcomingReloadKey}
                        />
                    )}
                    {isCurrentMonth && (
                        <TransactionsSection
                            transactions={filteredTransactions}
                            goals={goals}
                            startAddTransaction={startAddTransaction}
                            startEditTransaction={startEditTransaction}
                            startDeleteTransaction={startDeleteTransaction}
                        />
                    )}
                </div>

                <div className="col-sidebar">
                    {isCurrentMonth && (
                        <>
                            <UpcomingTransactionsSection reloadKey={upcomingReloadKey} />
                            <GoalsSection
                                goals={goals}
                                startAddGoal={startAddGoal}
                                startEditGoal={startEditGoal}
                                startDeleteGoal={startDeleteGoal}
                                startContributeGoal={startContributeGoal}
                            />
                        </>
                    )}
                    {isNextMonth && <RecurringTransactionsSection />}
                    {!isCurrentMonth && !isNextMonth && (
                        <TransactionsSection
                            transactions={filteredTransactions}
                            goals={goals}
                            startAddTransaction={startAddTransaction}
                            startEditTransaction={startEditTransaction}
                            startDeleteTransaction={startDeleteTransaction}
                        />
                    )}
                </div>
            </div>

        </BudgetContext.Provider>
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
