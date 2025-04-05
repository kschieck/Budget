import { useEffect, useState, useRef } from "react";
import * as API from "./API.js";
import { toDollars } from "./Utils.js";

export default function NewMonthToolDialog({ onClose }) {
    const [transactions, setTransactions] = useState([]);
    const [includeSet, setIncludeSet] = useState(new Set());
    const [loading, setLoading] = useState(true);

    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    useEffect(() => {
        // Load last month's transactions
        API.reloadTransactions(1).then((result) => {
            if (result.success) {
                setTransactions(
                    result.transactions.filter(
                        (transaction) =>
                            transaction.active &&
                            new Date(transaction.date_added).getDate() == 1,
                    ),
                );
                setLoading(false);
            }
        });
    }, []);

    function includeTransaction(checked, transactionId) {
        setIncludeSet((prev) => {
            let newSet = new Set(prev);
            if (checked) {
                newSet.add(transactionId);
            } else {
                newSet.delete(transactionId);
            }
            return newSet;
        });
    }

    function createTransactions() {
        setLoading(true);
        API.duplicateTransactions(Array.from(includeSet))
            .then(onClose)
            .catch(console.error)
            .finally(() => setLoading(false));
    }

    return (
        <dialog ref={dialogRef}>
            <h3 className="form_title">
                Duplicate last month's day 1 transactions
            </h3>
            <table>
                <tbody>
                    {transactions.map((transaction) => (
                        <tr key={transaction.id}>
                            <td>
                                <input
                                    type="checkbox"
                                    disabled={loading}
                                    checked={includeSet.has(transaction.id)}
                                    onChange={(e) =>
                                        includeTransaction(
                                            e.target.checked,
                                            transaction.id,
                                        )
                                    }
                                />
                            </td>
                            <td>{transaction.description}</td>
                            <td>{toDollars(transaction.amount / 100)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <br />
            <input
                style={{ float: "left" }}
                disabled={includeSet.size == 0}
                type="button"
                onClick={createTransactions}
                value="Create Transactions"
            ></input>
            <input
                style={{ float: "right" }}
                type="button"
                onClick={onClose}
                value="Close"
            ></input>
            <br />
            <br />
        </dialog>
    );
}
