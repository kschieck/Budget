import { useState, useEffect, useRef } from "react";
import { toDollars } from "./Utils.js";

function TransactionRow({
    readonly,
    transaction,
    onTransactionClicked,
    onDeleteClicked,
}) {
    const [showActions, setShowActions] = useState(false);

    let dateString = transaction.date_added.substring(5, 10);

    function clickedTransaction() {
        if (readonly) {
            return;
        }
        setShowActions(!showActions);
    }

    function TransactionAmount(num) {
        return num > 0
            ? toDollars(num / 100)
            : `(${toDollars(Math.abs(num / 100))})`;
    }

    return (
        <tr>
            <td>{dateString}</td>
            <td>{TransactionAmount(transaction.amount)}</td>
            <td className="small_cell">
                {showActions ? (
                    <>
                        <button
                            className="btn-icon-sm space_right"
                            onClick={() => onTransactionClicked(transaction.id)}
                        >
                            ✎
                        </button>
                        <button
                            className="btn-icon-sm space_right"
                            onClick={() => onDeleteClicked(transaction.id)}
                        >
                            ✕
                        </button>
                    </>
                ) : null}
                <div
                    style={{ display: "inline-block" }}
                    onClick={clickedTransaction}
                >
                    {transaction.description}
                </div>
            </td>
        </tr>
    );
}

export function AddEditTransactionDialog({
    id = -1,
    amount = "",
    description = "",
    onSave,
    onCancel,
}) {
    const [txAmount, setTxAmount] = useState(amount);
    const [txDesc, setTxDesc] = useState(description);
    const [saving, setSaving] = useState(false);
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    function handleSave() {
        setSaving(true);
        onSave(id, txAmount, txDesc)
            .then((success) => { if (!success) setSaving(false); })
            .catch(() => setSaving(false));
    }

    return (
        <dialog ref={dialogRef}>
            <h3 className="form_title">
                {id == -1 ? "Add" : "Edit"} Transaction
            </h3>
            <input
                type="number"
                id="tx_amount"
                placeholder="amount"
                value={txAmount}
                onChange={(e) => setTxAmount(e.target.value)}
            />
            <br />
            <br />
            <input
                type="text"
                id="tx_desc"
                list="tx_names"
                placeholder="description"
                value={txDesc}
                onChange={(e) => setTxDesc(e.target.value)}
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
            <button
                style={{ float: "right" }}
                value="cancel"
                disabled={saving}
                onClick={onCancel}
            >
                Cancel
            </button>
        </dialog>
    );
}

export default function TransactionsSection({
    readonly,
    transactions = [],
    goals = [],
    startAddTransaction,
    startEditTransaction,
    startDeleteTransaction,
}) {

    transactions.sort((a, b) => {
        return new Date(b.date_added) - new Date(a.date_added);
    });

    return (
        <>
            <h1 id="tx_title">
                Transactions&nbsp;
                {!readonly ? (
                    <button className="btn-icon" onClick={startAddTransaction}>+</button>
                ) : null}
            </h1>
            <table id="tx_table" cellSpacing="0">
                <tbody className="limited_height">
                    {transactions
                        .filter((transaction) => transaction.active)
                        .map((transaction) => (
                            <TransactionRow
                                key={transaction.id}
                                readonly={readonly || (transaction.goal_id != null && !goals.some((g) => g.id === transaction.goal_id))}
                                transaction={transaction}
                                onTransactionClicked={startEditTransaction}
                                onDeleteClicked={startDeleteTransaction}
                            />
                        ))}
                </tbody>
            </table>
        </>
    );
}
