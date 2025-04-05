import { useState, useEffect, useRef } from "react";
import TransactionsSection, {
    AddEditTransactionDialog,
} from "./Transactions.js";
import { toDollars, toDollarsNoCents, getCookieValue } from "./Utils.js";
import GoalsSection, {
    AddEditGoalDialog,
    AddGoalTransactionDialog,
} from "./Goals.js";
import FiltersSection from "./Filters.js";
import * as API from "./API.js";
import { DrawdownChart } from "./Charts.js";
import NewMonthToolDialog from "./NewMonthTool.js";

function MonthSelector({ children, previousMonth, nextMonth, showNextMonth }) {
    return (
        <h1 className="no_bottom_space center_spaced">
            <button onClick={previousMonth}>&lt;</button>
            {children}
            <button
                onClick={nextMonth}
                style={showNextMonth ? {} : { visibility: "hidden" }}
            >
                &gt;
            </button>
        </h1>
    );
}

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
    const [filters, setFilters] = useState(new Set([]));
    const [goals, setGoals] = useState([]);
    const [transactions, setTransactions] = useState([]);
    const [amountTotal, setAmountTotal] = useState(0);
    const [activeDialog, setActiveDialog] = useState(null);
    const [showTools, setShowTools] = useState(false);

    const isCurrentMonth = monthOffset == 0;
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
        var monthAdjustedDate = new Date();
        monthAdjustedDate.setMonth(monthAdjustedDate.getMonth() - monthOffset);
        return monthNames[monthAdjustedDate.getMonth()];
    }

    function getDaysLeftInTheMonth() {
        var now = new Date();
        return (
            new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate() -
            now.getDate()
        );
    }

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
                debugger;
                if (json.success) {
                    setGoals(json.goals);
                }
            })
            .catch(console.error);
    }
    function loadTransactions() {
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
    useEffect(loadTransactions, [monthOffset]);

    function previousMonth() {
        setMonthOffset(monthOffset + 1);
    }
    function nextMonth() {
        setMonthOffset(Math.max(0, monthOffset - 1));
    }
    function startAddTransaction() {
        console.log("startAddTransaction");
        setActiveDialog(
            <AddEditTransactionDialog
                onCancel={() => setActiveDialog(null)}
                onSave={(id, amount, description) => {
                    API.saveTransaction(id, amount, description)
                        .then((result) => {
                            console.log(
                                "startAddTransaction",
                                "result",
                                result,
                            );
                            if (result.success) {
                                loadAmountTotal();
                                loadTransactions();
                            }
                        })
                        .catch((e) => {
                            console.error(e);
                            alert("Failed to save");
                        });
                    setActiveDialog(null);
                }}
            />,
        );
    }
    function startEditTransaction(transactionId) {
        console.log("startEditTransaction", transactionId);
        let transaction =
            transactions.filter(
                (transaction) => transaction.id == transactionId,
            )?.[0] || null;
        if (transaction == null) return;

        setActiveDialog(
            <AddEditTransactionDialog
                id={transactionId}
                amount={transaction.amount / 100}
                description={transaction.description}
                onCancel={() => setActiveDialog(null)}
                onSave={(id, amount, description) => {
                    API.saveTransaction(id, amount, description)
                        .then((result) => {
                            console.log(
                                "startContributeGoal",
                                "result",
                                result,
                            );
                            if (result.success) {
                                loadAmountTotal();
                                loadTransactions();
                            }
                        })
                        .catch((e) => {
                            console.error(e);
                            alert("Failed to save");
                        });
                    setActiveDialog(null);
                }}
            />,
        );
    }
    function startDeleteTransaction(transactionId) {
        console.log("startDeleteTransaction", transactionId);
        API.deleteTransaction(transactionId)
            .then((result) => {
                console.log("startContributeGoal", "result", result);
                if (result.success) {
                    loadAmountTotal(); // reload the total
                    setTransactions((prev) =>
                        prev.filter(
                            (transaction) => transaction.id !== transactionId,
                        ),
                    );
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete");
            });
    }
    function startAddGoal() {
        console.log("startAddGoal");
        setActiveDialog(
            <AddEditGoalDialog
                onCancel={() => setActiveDialog(null)}
                onSave={(id, amount, description) => {
                    API.saveGoal(id, amount, description)
                        .then((result) => {
                            console.log(
                                "startContributeGoal",
                                "result",
                                result,
                            );
                            if (result.success) {
                                loadGoals();
                            }
                        })
                        .catch((e) => {
                            console.error(e);
                            alert("Failed to save");
                        });
                    setActiveDialog(null);
                }}
            />,
        );
    }
    function startEditGoal(goalId) {
        console.log("startEditGoal", goalId);
        let goal = goals.filter((goal) => goal.id == goalId)?.[0] || null;
        if (goal == null) return;

        setActiveDialog(
            <AddEditGoalDialog
                id={goalId}
                description={goal.name}
                amount={goal.total / 100}
                onCancel={() => setActiveDialog(null)}
                onSave={(id, amount, description) => {
                    API.saveGoal(id, amount, description)
                        .then((result) => {
                            console.log(
                                "startContributeGoal",
                                "result",
                                result,
                            );
                            if (result.success) {
                                loadGoals();
                            }
                        })
                        .catch((e) => {
                            console.error(e);
                            alert("Failed to save");
                        });
                    setActiveDialog(null);
                }}
            />,
        );
    }
    function startDeleteGoal(goalId) {
        console.log("startDeleteGoal", goalId);
        API.deleteGoal(goalId)
            .then((result) => {
                console.log("startContributeGoal", "result", result);
                if (result.success) {
                    loadGoals();
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete");
            });
    }
    function startContributeGoal(goalId) {
        console.log("startContributeGoal", goalId);
        setActiveDialog(
            <AddGoalTransactionDialog
                id={goalId}
                onCancel={() => setActiveDialog(null)}
                onSave={(id, amount) => {
                    API.saveGoalTransaction(id, amount)
                        .then((result) => {
                            console.log(
                                "startContributeGoal",
                                "result",
                                result,
                            );
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
                    setActiveDialog(null);
                }}
            />,
        );
    }
    function changeFilterState(name, showTransactions) {
        setFilters((prev) => {
            const newFilters = new Set(prev);
            if (showTransactions) {
                newFilters.delete(name);
            } else {
                newFilters.add(name);
            }
            return newFilters;
        });
    }
    function showNewMonthTool() {
        setActiveDialog(
            <NewMonthToolDialog onClose={() => setActiveDialog(null)} />,
        );
    }

    return (
        <>
            {activeDialog}

            <MonthSelector
                previousMonth={previousMonth}
                nextMonth={nextMonth}
                showNextMonth={!isCurrentMonth}
            >
                <span
                    style={{ width: "150px" }}
                    onClick={() => setShowTools(!showTools)}
                >
                    {isCurrentMonth
                        ? toDollars(amountTotal / 100)
                        : getMonthName(monthOffset)}
                </span>
            </MonthSelector>
            {isCurrentMonth ? (
                <div id="total_spend">
                    {toDollarsNoCents(totalTxSpentDollars / 100)} spent,{" "}
                    {getDaysLeftInTheMonth()} days left
                </div>
            ) : null}

            {showTools || !isCurrentMonth ? (
                <>
                    <DrawdownChart
                        transactions={transactions}
                        width={300}
                        height={100}
                    />
                    <br />
                </>
            ) : null}

            {showTools ? (
                <div style={{ "text-align": "center" }}>
                    <a href="#" onClick={showNewMonthTool}>
                        New Month Tool
                    </a>
                </div>
            ) : null}

            <TransactionsSection
                readonly={!isCurrentMonth}
                transactions={transactions}
                startAddTransaction={startAddTransaction}
                startEditTransaction={startEditTransaction}
                startDeleteTransaction={startDeleteTransaction}
            />

            {isCurrentMonth ? (
                <GoalsSection
                    goals={goals}
                    startAddGoal={startAddGoal}
                    startEditGoal={startEditGoal}
                    startDeleteGoal={startDeleteGoal}
                    startContributeGoal={startContributeGoal}
                />
            ) : null}

            {users.length > 0 ? (
                <FiltersSection
                    names={users}
                    filters={filters}
                    changeFilterState={changeFilterState}
                />
            ) : null}
        </>
    );
}

export default function App() {
    const [loggingIn, setLoggingIn] = useState(false);
    const [authSuccess, setAuthSuccess] = useState(false);

    function localStoreToken(token, expire) {
        localStorage.setItem("rememberme", token);
        localStorage.setItem("rememberme_expire", expire);
    }

    function loadLoadToken() {
        let expireTime = localStorage.getItem("rememberme_expire");
        console.log("expire time", expireTime);
        return localStorage.getItem("rememberme");
    }

    function reloadAuth() {
        let cookieValue = getCookieValue("rememberme");
        if (cookieValue) {
            setLoggingIn(true);
            API.AuthWithCookie()
                .then((result) => setAuthSuccess(result.success))
                .catch((e) => console.error(e))
                .finally(() => setLoggingIn(false));

            return;
        }
        console.log("No cookie to auth with, next trying local storage token.");

        const token = loadLoadToken();
        if (token) {
            setLoggingIn(true);
            API.AuthWithToken(token)
                .then((result) => setAuthSuccess(result.success))
                .catch((e) => console.error(e))
                .finally(() => setLoggingIn(false));
            return;
        }
        console.log("No token found in local storage.");
    }

    // Attempt to login
    useEffect(reloadAuth, []);

    function onTryLogin(username, password) {
        setLoggingIn(true);
        API.AuthWithUserPass(username, password)
            .then((result) => {
                setAuthSuccess(result.success);
                if (result.success) {
                    if (
                        result.hasOwnProperty("token") &&
                        result.hasOwnProperty("expire")
                    ) {
                        localStoreToken(result.token, result.expire);
                    }
                }
            })
            .catch((e) => console.error(e))
            .finally(() => setLoggingIn(false));
    }

    if (!authSuccess) {
        return <LoginForm onTryLogin={onTryLogin} disabled={loggingIn} />;
    }

    return <BudgetApp />;
}
