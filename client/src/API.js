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

export function saveTransaction(id, amount, description, categoryId = null) {
    if (id === -1) {
        return fetch("./transaction.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                amount,
                description,
                category_id: categoryId,
            }),
        }).then((response) => response.json());
    } else {
        return fetch("./transaction.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                transactionId: id,
                amount,
                description,
                category_id: categoryId,
            }),
        }).then((response) => response.json());
    }
}

export function deleteTransaction(id) {
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
    return fetch("./goal.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
    }).then((response) => response.json());
}

export function saveGoalTransaction(goalId, amount) {
    return fetch("./transaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ goalId, amount }),
    }).then((response) => response.json());
}

export function loadRecurringTransactions() {
    return fetch("./recurring.php").then((response) => response.json());
}

export function saveRecurringTransaction(
    id,
    amount,
    description,
    startMonth,
    endMonth,
    categoryId = null,
) {
    if (id === -1) {
        return fetch("./recurring.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                amount,
                description,
                start_month: startMonth,
                end_month: endMonth,
                category_id: categoryId,
            }),
        }).then((response) => response.json());
    } else {
        return fetch("./recurring.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                id,
                amount,
                description,
                start_month: startMonth,
                end_month: endMonth,
                category_id: categoryId,
            }),
        }).then((response) => response.json());
    }
}

export function deleteRecurringTransaction(id) {
    return fetch("./recurring.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
    }).then((response) => response.json());
}

export function loadChartData() {
    return fetch("./chart-data.php").then((response) => response.json());
}

export function loadCategories() {
    return fetch("./category.php").then((response) => response.json());
}

export function saveCategory(id, name, color) {
    if (id === -1) {
        return fetch("./category.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name, color }),
        }).then((response) => response.json());
    } else {
        return fetch("./category.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id, name, color }),
        }).then((response) => response.json());
    }
}

export function deleteCategory(id) {
    return fetch("./category.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
    }).then((response) => response.json());
}

export function loadCategoryChartData() {
    return fetch("./category-chart-data.php").then((response) =>
        response.json(),
    );
}
