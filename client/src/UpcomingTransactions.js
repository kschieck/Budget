import { useState, useEffect, useRef } from "react";
import { toDollars } from "./Utils.js";
import * as API from "./API.js";

export function getNextMonthString() {
    const now = new Date();
    const next = new Date(now.getFullYear(), now.getMonth() + 1, 1);
    return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, "0")}`;
}

function sortUpcoming(list) {
    return [...list].sort((a, b) => {
        if (a.target_month !== b.target_month) {
            return a.target_month < b.target_month ? -1 : 1;
        }
        return b.id - a.id;
    });
}

function UpcomingTransactionRow({ upcoming, onEditClicked, onDeleteClicked }) {
    const [showActions, setShowActions] = useState(false);

    function formatAmount(num) {
        return num > 0
            ? toDollars(num / 100)
            : `(${toDollars(Math.abs(num / 100))})`;
    }

    return (
        <tr>
            <td>{upcoming.target_month}</td>
            <td>{formatAmount(upcoming.amount)}</td>
            <td className="small_cell">
                {showActions ? (
                    <>
                        <button
                            className="btn-icon-sm space_right"
                            onClick={() => onEditClicked(upcoming.id)}
                        >
                            ✎
                        </button>
                        <button
                            className="btn-icon-sm space_right"
                            onClick={() => onDeleteClicked(upcoming.id)}
                        >
                            ✕
                        </button>
                    </>
                ) : null}
                <div style={{ display: "inline-block" }} onClick={() => setShowActions(!showActions)}>
                    {upcoming.description}
                </div>
            </td>
        </tr>
    );
}

export function AddEditUpcomingDialog({
    id = -1,
    amount = "",
    description = "",
    targetMonth = getNextMonthString(),
    onSave,
    onCancel,
}) {
    const [txAmount, setTxAmount] = useState(amount);
    const [txDesc, setTxDesc] = useState(description);
    const [txTargetMonth, setTxTargetMonth] = useState(targetMonth);
    const [saving, setSaving] = useState(false);
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    function handleSave() {
        setSaving(true);
        onSave(id, txAmount, txDesc, txTargetMonth)
            .then((success) => { if (!success) setSaving(false); })
            .catch(() => setSaving(false));
    }

    return (
        <dialog ref={dialogRef}>
            <h3 className="form_title">
                {id === -1 ? "Add" : "Edit"} Upcoming Transaction
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
                type="month"
                disabled={saving}
                value={txTargetMonth}
                onChange={(e) => setTxTargetMonth(e.target.value)}
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

export default function UpcomingTransactionsSection({ reloadKey, filterMonth = null }) {
    const [upcoming, setUpcoming] = useState([]);
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [editingUpcoming, setEditingUpcoming] = useState(null);

    function loadUpcoming() {
        API.loadUpcomingTransactions()
            .then((result) => {
                if (result.success) {
                    setUpcoming(sortUpcoming(result.upcoming));
                } else {
                    alert("Failed to load data");
                }
            })
            .catch(() => alert("Failed to load data"));
    }

    useEffect(loadUpcoming, [reloadKey]);

    function handleSave(id, amount, description, targetMonth) {
        return API.saveUpcomingTransaction(id, amount, description, targetMonth)
            .then((result) => {
                if (result.success) {
                    setShowAddDialog(false);
                    setEditingUpcoming(null);
                    if (id === -1) {
                        setUpcoming((prev) => sortUpcoming([...prev, result.upcoming]));
                    } else {
                        setUpcoming((prev) =>
                            sortUpcoming(prev.map((u) =>
                                u.id === result.upcoming.id ? result.upcoming : u,
                            )),
                        );
                    }
                    return true;
                } else {
                    alert(result.message || "Failed to save upcoming transaction");
                    return false;
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to save upcoming transaction");
                return false;
            });
    }

    function handleDelete(id) {
        API.deleteUpcomingTransaction(id)
            .then((result) => {
                if (result.success) {
                    setUpcoming((prev) => prev.filter((u) => u.id !== id));
                } else {
                    alert(result.message || "Failed to delete upcoming transaction");
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete upcoming transaction");
            });
    }

    function startEdit(id) {
        const item = upcoming.find((u) => u.id === id) || null;
        if (item === null) return;
        setEditingUpcoming(item);
    }

    const displayed = filterMonth
        ? upcoming.filter((u) => u.target_month === filterMonth)
        : upcoming;

    return (
        <>
            {showAddDialog && (
                <AddEditUpcomingDialog
                    onCancel={() => setShowAddDialog(false)}
                    onSave={handleSave}
                />
            )}
            {editingUpcoming && (
                <AddEditUpcomingDialog
                    id={editingUpcoming.id}
                    amount={editingUpcoming.amount / 100}
                    description={editingUpcoming.description}
                    targetMonth={editingUpcoming.target_month}
                    onCancel={() => setEditingUpcoming(null)}
                    onSave={handleSave}
                />
            )}

            <h1 id="tx_title">
                <span>
                    Upcoming Transactions&nbsp;
                    <button className="btn-icon" onClick={() => setShowAddDialog(true)}>
                        +
                    </button>
                </span>
            </h1>
            <table id="tx_table" cellSpacing="0">
                <tbody>
                    {displayed.map((item) => (
                        <UpcomingTransactionRow
                            key={item.id}
                            upcoming={item}
                            onEditClicked={startEdit}
                            onDeleteClicked={handleDelete}
                        />
                    ))}
                    {displayed.length === 0 && (
                        <tr>
                            <td colSpan="3" style={{ textAlign: "center", opacity: 0.5 }}>
                                No upcoming transactions
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </>
    );
}
