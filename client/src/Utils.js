export function toDollars(num) {
    return num.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    });
}

export function toDollarsNoCents(num) {
    return num.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
        maximumFractionDigits: 0,
    });
}
