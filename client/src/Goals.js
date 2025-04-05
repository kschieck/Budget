import { useState, useRef, useEffect } from "react";
import { toDollarsNoCents } from "./Utils";

function GoalRow({
    goal,
    startEditGoal,
    startDeleteGoal,
    startContributeGoal,
}) {
    const [showDelete, setShowDelete] = useState(false);

    let amountString = toDollarsNoCents(goal.amount / 100);
    let totalString = toDollarsNoCents(goal.total / 100);

    function handleGoalClick() {
        startEditGoal(goal.id);
        setShowDelete(!showDelete);
    }

    let percent = Math.min((goal.amount / goal.total) * 100, 100);
    return (
        <tr>
            <td>
                {showDelete ? (
                    <button
                        className="space_right small_button"
                        onClick={() => startDeleteGoal(goal.id)}
                    >
                        x
                    </button>
                ) : null}
                <div
                    className="small_cell"
                    style={{ display: "inline-block" }}
                    onClick={handleGoalClick}
                >
                    {goal.name}
                </div>
            </td>
            <td>
                <div
                    style={{
                        height: "0px",
                        fontSize: "1em",
                        padding: "0px 10px",
                    }}
                >
                    {amountString} / {totalString}
                </div>
                <div
                    className="goal_progress"
                    data-label={amountString + " / " + totalString}
                    complete="true"
                >
                    <span
                        className="value"
                        style={{ width: percent + "%" }}
                    ></span>
                </div>
            </td>
            <td>
                <button onClick={() => startContributeGoal(goal.id)}>+</button>
            </td>
        </tr>
    );
}

function GoalTotalRow({ amount, total }) {
    let amountString = toDollarsNoCents(amount);
    let totalString = toDollarsNoCents(total);

    let ratio = Math.min(amount / total, 1);
    return (
        <tr className="goal_display_total">
            <td></td>
            <td>
                <div
                    style={{
                        height: "0px",
                        fontSize: "1em",
                        padding: "0px 10px",
                    }}
                >
                    {amountString} / {totalString}
                </div>
                <div
                    className="goal_progress"
                    data-label={amountString + " / " + totalString}
                >
                    <span
                        className="value"
                        style={{ width: Math.min(ratio * 100, 100) + "%" }}
                    ></span>
                </div>
            </td>
            <td></td>
        </tr>
    );
}

export function AddEditGoalDialog({
    id = -1,
    amount = "",
    description = "",
    onSave,
    onCancel,
}) {
    const [goalAmount, setGoalAmount] = useState(amount);
    const [goalDesc, setGoalDesc] = useState(description);
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    let cantEditName = id !== -1;

    return (
        <dialog ref={dialogRef}>
            <h3 className="form_title">{id === -1 ? "Add" : "Edit"} Goal</h3>
            <input
                type="text"
                id="edit_goal_name"
                disabled={cantEditName}
                placeholder="name"
                value={goalDesc}
                onChange={(e) => setGoalDesc(e.target.value)}
            ></input>
            <br />
            <br />
            <input
                type="number"
                id="edit_goal_total"
                placeholder="total"
                value={goalAmount}
                onChange={(e) => setGoalAmount(e.target.value)}
            ></input>
            <br />
            <br />
            <button
                style={{ float: "left" }}
                onClick={() => onSave(id, goalAmount, goalDesc)}
            >
                Save
            </button>
            <button style={{ float: "right" }} onClick={onCancel}>
                Cancel
            </button>
        </dialog>
    );
}

export function AddGoalTransactionDialog({ id, onSave, onCancel }) {
    const [amount, setAmount] = useState("");
    const dialogRef = useRef(null);

    useEffect(() => {
        if (dialogRef.current && !dialogRef.current.open) {
            dialogRef.current.showModal();
        }
    }, []);

    return (
        <dialog ref={dialogRef} onCancel={onCancel}>
            <h3 className="form_title">Add Goal Transaction</h3>
            <input
                type="number"
                id="add_goal_amount"
                placeholder="amount"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
            ></input>
            <br />
            <br />
            <input
                type="text"
                disabled
                value="Goal contribution/subtraction"
            ></input>
            <br />
            <br />
            <button
                style={{ float: "left" }}
                onClick={() => onSave(id, amount)}
            >
                Save
            </button>
            <button style={{ float: "right" }} onClick={onCancel}>
                Cancel
            </button>
        </dialog>
    );
}

export default function GoalsSection({
    goals = [],
    startAddGoal,
    startDeleteGoal,
    startEditGoal,
    startContributeGoal,
}) {
    let goalAmountSum = 0;
    let goalTotalSum = 0;

    goals.forEach((goal) => {
        goalAmountSum += goal.amount;
        goalTotalSum += goal.total;
    });

    return (
        <>
            <h1 id="goal_title">
                Goals&nbsp;
                <button onClick={startAddGoal}>+</button>
            </h1>
            <table>
                <tbody>
                    {goals.map((goal) => (
                        <GoalRow
                            key={goal.id}
                            goal={goal}
                            startEditGoal={startEditGoal}
                            startDeleteGoal={startDeleteGoal}
                            startContributeGoal={startContributeGoal}
                        />
                    ))}
                    {goals.length > 0 ? (
                        <GoalTotalRow amount={50} total={100} />
                    ) : null}
                </tbody>
            </table>
        </>
    );
}
