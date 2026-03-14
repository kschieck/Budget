import { useState, useEffect, useRef } from "react";
import { toDollars } from "./Utils.js";
import * as API from "./API.js";

function sortRecurring(list) {
    return [...list].sort((a, b) => {
        const aNeg = a.amount < 0;
        const bNeg = b.amount < 0;
        if (aNeg !== bNeg) {
            return aNeg ? -1 : 1;
        }
        return aNeg
            ? a.amount - b.amount   // negatives: ascending (most negative first)
            : b.amount - a.amount;  // positives: descending (highest first)
    });
}

function getCurrentMonthString() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
}

function RecurringTransactionRow({ recurring, onEditClicked, onDeleteClicked }) {
    const [showActions, setShowActions] = useState(false);

    function clickedRow() {
        setShowActions(!showActions);
    }

    function TransactionAmount(num) {
        return num > 0
            ? toDollars(num / 100)
            : `(${toDollars(Math.abs(num / 100))})`;
    }

    return (
        <tr>
            <td>{recurring.end_month ? "until " + recurring.end_month : ""}</td>
            <td>{TransactionAmount(recurring.amount)}</td>
            <td className="small_cell">
                {showActions ? (
                    <>
                        <button
                            className="btn-icon-sm space_right"
                            onClick={() => onEditClicked(recurring.id)}
                        >
                            ✎
                        </button>
                        <button
                            className="btn-icon-sm space_right"
                            onClick={() => onDeleteClicked(recurring.id)}
                        >
                            ✕
                        </button>
                    </>
                ) : null}
                <div style={{ display: "inline-block" }} onClick={clickedRow}>
                    {recurring.description}
                </div>
            </td>
        </tr>
    );
}

export function AddEditRecurringDialog({
    id = -1,
    amount = "",
    description = "",
    startMonth = getCurrentMonthString(),
    endMonth = "",
    onSave,
    onCancel,
}) {
    const [txAmount, setTxAmount] = useState(amount);
    const [txDesc, setTxDesc] = useState(description);
    const [txStartMonth, setTxStartMonth] = useState(startMonth);
    const [txEndMonth, setTxEndMonth] = useState(endMonth);
    const [saving, setSaving] = useState(false);
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    function handleSave() {
        setSaving(true);
        onSave(id, txAmount, txDesc, txStartMonth, txEndMonth || null)
            .then((success) => { if (!success) setSaving(false); })
            .catch(() => setSaving(false));
    }

    return (
        <dialog ref={dialogRef}>
            <h3 className="form_title">
                {id === -1 ? "Add" : "Edit"} Recurring Transaction
            </h3>
            <input
                type="number"
                placeholder="amount"
                disabled={saving}
                value={txAmount}
                onChange={(e) => setTxAmount(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                placeholder="description"
                disabled={saving}
                value={txDesc}
                onChange={(e) => setTxDesc(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                placeholder="start month (YYYY-MM)"
                disabled={saving}
                value={txStartMonth}
                onChange={(e) => setTxStartMonth(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                placeholder="end month (YYYY-MM, optional)"
                disabled={saving}
                value={txEndMonth}
                onChange={(e) => setTxEndMonth(e.target.value)}
            />
            <br />
            <br />
            <button
                style={{ float: "left" }}
                disabled={saving}
                onClick={handleSave}
            >
                Save
            </button>
            <button style={{ float: "right" }} disabled={saving} onClick={onCancel}>
                Cancel
            </button>
        </dialog>
    );
}

export default function RecurringTransactionsSection() {
    const [recurring, setRecurring] = useState([]);
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [editingRecurring, setEditingRecurring] = useState(null);

    function loadRecurring() {
        API.loadRecurringTransactions()
            .then((result) => {
                if (result.success) {
                    setRecurring(sortRecurring(result.recurring));
                } else {
                    alert("Failed to load data");
                }
            })
            .catch(() => alert("Failed to load data"));
    }

    useEffect(loadRecurring, []);

    function handleSave(id, amount, description, startMonth, endMonth) {
        return API.saveRecurringTransaction(id, amount, description, startMonth, endMonth)
            .then((result) => {
                if (result.success) {
                    setShowAddDialog(false);
                    setEditingRecurring(null);
                    if (id === -1) {
                        setRecurring((prev) => sortRecurring([...prev, result.recurring]));
                    } else {
                        setRecurring((prev) =>
                            sortRecurring(prev.map((r) =>
                                r.id === result.recurring.id ? result.recurring : r,
                            )),
                        );
                    }
                    return true;
                } else {
                    alert(result.message || "Failed to save recurring transaction");
                    return false;
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to save recurring transaction");
                return false;
            });
    }

    function handleDelete(id) {
        API.deleteRecurringTransaction(id)
            .then((result) => {
                if (result.success) {
                    setRecurring((prev) => prev.filter((r) => r.id !== id));
                } else {
                    alert(result.message || "Failed to delete recurring transaction");
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete recurring transaction");
            });
    }

    function startEdit(id) {
        const rec = recurring.find((r) => r.id === id) || null;
        if (rec === null) return;
        setEditingRecurring(rec);
    }

    let totalIncome = recurring.reduce((prev, curr) => { return prev - Math.min(0, curr.amount); }, 0);
    let totalSpend = recurring.reduce((prev, curr) => { return prev + Math.max(0, curr.amount); }, 0);

    return (
        <>
            {showAddDialog && (
                <AddEditRecurringDialog
                    onCancel={() => setShowAddDialog(false)}
                    onSave={handleSave}
                />
            )}
            {editingRecurring && (
                <AddEditRecurringDialog
                    id={editingRecurring.id}
                    amount={editingRecurring.amount / 100}
                    description={editingRecurring.description}
                    startMonth={editingRecurring.start_month}
                    endMonth={editingRecurring.end_month || ""}
                    onCancel={() => setEditingRecurring(null)}
                    onSave={handleSave}
                />
            )}

            <h1 id="tx_title">
                <span>
                    Recurring Transactions&nbsp;
                    <button className="btn-icon" onClick={() => setShowAddDialog(true)}>
                        +
                    </button>
                    <br />
                    <div className="recurring_totals">
                        {toDollars(totalIncome / 100)} income, {toDollars(totalSpend / 100)} spend
                    </div>
                </span>
            </h1>
            <table id="tx_table" cellSpacing="0">
                <tbody>
                    {recurring.map((rec) => (
                        <RecurringTransactionRow
                            key={rec.id}
                            recurring={rec}
                            onEditClicked={startEdit}
                            onDeleteClicked={handleDelete}
                        />
                    ))}
                </tbody>
            </table>
        </>
    );
}
