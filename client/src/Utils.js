import { useState, useRef, useEffect } from "react";

export function useDialog() {
    const dialogRef = useRef(null);
    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);
    return dialogRef;
}

export function useRowActions({ disabled = false } = {}) {
    const [showActions, setShowActions] = useState(false);
    const canHover = window.matchMedia("(hover: hover)").matches;

    function handleMouseEnter() {
        if (disabled) return;
        if (!canHover) return;
        setShowActions(true);
    }

    function handleMouseLeave() {
        if (!canHover) return;
        setShowActions(false);
    }

    function handleClick() {
        if (disabled) return;
        if (!canHover) setShowActions((s) => !s);
    }

    return { showActions, handleMouseEnter, handleMouseLeave, handleClick };
}

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
