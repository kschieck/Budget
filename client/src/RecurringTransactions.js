import { useState, useEffect, useRef } from "react";
import { toDollars } from "./Utils.js";
import * as API from "./API.js";

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
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    return (
        <dialog ref={dialogRef}>
            <h3 className="form_title">
                {id === -1 ? "Add" : "Edit"} Recurring Transaction
            </h3>
            <input
                type="number"
                placeholder="amount"
                value={txAmount}
                onChange={(e) => setTxAmount(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                placeholder="description"
                value={txDesc}
                onChange={(e) => setTxDesc(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                placeholder="start month (YYYY-MM)"
                value={txStartMonth}
                onChange={(e) => setTxStartMonth(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                placeholder="end month (YYYY-MM, optional)"
                value={txEndMonth}
                onChange={(e) => setTxEndMonth(e.target.value)}
            />
            <br />
            <br />
            <button
                style={{ float: "left" }}
                onClick={() => onSave(id, txAmount, txDesc, txStartMonth, txEndMonth || null)}
            >
                Save
            </button>
            <button style={{ float: "right" }} onClick={onCancel}>
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

                    // Sort with negative values first (highest to lowest)
                    // then positive values (highest to lowest)
                    result.recurring.sort((a, b) => {
                        const aNeg = a.amount < 0;
                        const bNeg = b.amount < 0;

                        if (aNeg !== bNeg) {
                            return aNeg ? -1 : 1;
                        }

                        return aNeg
                            ? a.amount - b.amount   // negatives: ascending
                            : b.amount - a.amount;  // positives: descending
                    });

                    setRecurring(result.recurring);
                }
            })
            .catch(console.error);
    }

    useEffect(loadRecurring, []);

    function handleSave(id, amount, description, startMonth, endMonth) {
        API.saveRecurringTransaction(id, amount, description, startMonth, endMonth)
            .then((result) => {
                if (result.success) {
                    setShowAddDialog(false);
                    setEditingRecurring(null);
                    loadRecurring();
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to save recurring transaction");
            });
    }

    function handleDelete(id) {
        API.deleteRecurringTransaction(id)
            .then((result) => {
                if (result.success) {
                    setRecurring((prev) => prev.filter((r) => r.id !== id));
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

    let totalIncome = recurring.reduce((prev, curr) => { return prev + Math.min(0, curr.amount); }, 0);
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
