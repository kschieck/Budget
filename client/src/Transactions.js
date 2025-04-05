import { useState, useEffect, useRef } from "react";
import { toDollars } from "./Utils.js";

function TransactionRow({
    readonly,
    transaction,
    onTransactionClicked,
    onDeleteClicked,
}) {
    const [showDelete, setShowDelete] = useState(false);

    let dateString = transaction.date_added.substring(5, 10);

    function clickedTransaction() {
        if (readonly) {
            return;
        }
        setShowDelete(!showDelete);
        onTransactionClicked(transaction.id);
    }

    function clickedDeleteTransaction() {
        if (readonly) {
            return;
        }
        onDeleteClicked(transaction.id);
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
                {showDelete ? (
                    <button
                        className="space_right small_button"
                        onClick={clickedDeleteTransaction}
                    >
                        x
                    </button>
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
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

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
                onClick={() => onSave(id, txAmount, txDesc)}
            >
                Save
            </button>
            <button
                style={{ float: "right" }}
                value="cancel"
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
    startAddTransaction,
    startEditTransaction,
    startDeleteTransaction,
}) {
    return (
        <>
            <h1 id="tx_title">
                Transactions&nbsp;
                {!readonly ? (
                    <button onClick={startAddTransaction}>+</button>
                ) : null}
            </h1>
            <table id="tx_table" cellSpacing="0">
                <tbody>
                    {transactions
                        .filter((transaction) => transaction.active)
                        .map((transaction) => (
                            <TransactionRow
                                key={transaction.id}
                                readonly={readonly}
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
