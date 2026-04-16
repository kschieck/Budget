import { useState, useRef, useEffect } from "react";

export function useDialog(onCancel, canClose = () => true) {
    const dialogRef = useRef(null);
    const onCancelRef = useRef(onCancel);
    onCancelRef.current = onCancel;
    const canCloseRef = useRef(canClose);
    canCloseRef.current = canClose;
    const closedByHistory = useRef(false);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    useEffect(() => {
        history.pushState({ dialog: true }, "");

        function handlePopState() {
            if (!canCloseRef.current()) {
                history.pushState({ dialog: true }, "");
                return;
            }
            closedByHistory.current = true;
            onCancelRef.current();
        }

        window.addEventListener("popstate", handlePopState);
        return () => {
            window.removeEventListener("popstate", handlePopState);
            if (!closedByHistory.current) {
                history.back();
            }
        };
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
