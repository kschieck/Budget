export async function AuthWithUserPass(username, password) {
    return fetch("./auth.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
    }).then((response) => response.json());
}

export function AuthWithToken(token) {
    return fetch("./auth.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ rememberme: token }),
    }).then((response) => response.json());
}

// Try to auth with a stored cookie. You should check if the cookie exists before doing this
export function AuthWithCookie() {
    return fetch("./auth.php", { method: "POST" }).then((response) =>
        response.json(),
    );
}

export function loadAmount() {
    return fetch(`./amount.php`).then((response) => response.json());
}

export function reloadTransactions(monthOffset) {
    return fetch(`./transaction.php?past=${monthOffset}`).then((response) =>
        response.json(),
    );
}

export function saveTransaction(id, amount, description) {
    console.log("saveTransaction", id, amount, description);
    if (id === -1) {
        return fetch("./transaction.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ amount, description }),
        }).then((response) => response.json());
    } else {
        return fetch("./transaction.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ transactionId, amount, description }),
        }).then((response) => response.json());
    }
}

export function deleteTransaction(id) {
    console.log("deleteTransaction", id);
    return fetch("./transaction.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
    }).then((response) => response.json());
}

export function loadGoals() {
    return fetch(`./goal.php`).then((response) => response.json());
}

export function saveGoal(id, total, name) {
    console.log("saveGoal", id, total, name);
    if (id < 0) {
        return fetch("./goal.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name, total }),
        }).then((response) => response.json());
    } else {
        return fetch("./goal.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ goalId: id, amount: total }),
        }).then((response) => response.json());
    }
}

export function deleteGoal(id) {
    console.log("deleteGoal", id);
    return fetch("./goal.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
    }).then((response) => response.json());
}

export function saveGoalTransaction(goalId, amount) {
    console.log("saveGoalTransaction", goalId, amount);
    return fetch("./transaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ goalId, amount }),
    }).then((response) => response.json());
}

export function duplicateTransactions(transactionIds) {
    console.log("duplicateTransactions", transactionIds);
    return fetch("./transaction-duplicate.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ transactionIds }),
    }).then((response) => response.json());
}
