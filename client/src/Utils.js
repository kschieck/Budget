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

export function getCookieValue(cookieName) {
    const cookies = document.cookie.split("; ");
    for (let cookie of cookies) {
        const [name, value] = cookie.split("=");
        if (name === cookieName) {
            try {
                return decodeURIComponent(value); // Safely decode
            } catch (e) {
                return value; // Return raw value if decoding fails
            }
        }
    }
    return null; // Return null if the cookie is not found
}
