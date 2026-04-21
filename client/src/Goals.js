import { useState } from "react";
import { toDollars, toDollarsNoCents, useRowActions, useDialog } from "./Utils";

function GoalRow({
    goal,
    startEditGoal,
    startDeleteGoal,
    startContributeGoal,
}) {
    const { showActions, handleMouseEnter, handleMouseLeave, handleClick } =
        useRowActions();

    let amountString =
        goal.amount !== 0 && Math.floor(Math.abs(goal.amount) / 100) === 0
            ? toDollars(goal.amount / 100)
            : toDollarsNoCents(goal.amount / 100);
    let totalString = toDollarsNoCents(goal.total / 100);

    let percent = Math.max(0, Math.min((goal.amount / goal.total) * 100, 100));
    return (
        <tr onMouseEnter={handleMouseEnter} onMouseLeave={handleMouseLeave}>
            <td>
                <div
                    className="small_cell"
                    style={{ display: "inline-block" }}
                    onClick={handleClick}
                >
                    {goal.name}
                </div>
                {showActions ? (
                    <div className="row-actions">
                        <button
                            className="btn-icon-sm"
                            onClick={() => startEditGoal(goal.id)}
                        >
                            ✎
                        </button>
                        <button
                            className="btn-icon-sm"
                            onClick={() => startDeleteGoal(goal.id)}
                        >
                            ✕
                        </button>
                    </div>
                ) : null}
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
                <button className="btn-icon" onClick={() => startContributeGoal(goal.id)}>+</button>
            </td>
        </tr>
    );
}

function GoalTotalRow({ amount, total }) {
    let amountString = toDollarsNoCents(amount / 100);
    let totalString = toDollarsNoCents(total / 100);

    let ratio = Math.max(0, Math.min(amount / total, 1));
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
    const [saving, setSaving] = useState(false);
    const dialogRef = useDialog(onCancel, () => !saving);

    let cantEditName = id !== -1;

    function handleSave() {
        setSaving(true);
        onSave(id, goalAmount, goalDesc)
            .then((success) => { if (!success) setSaving(false); })
            .catch(() => setSaving(false));
    }

    return (
        <dialog ref={dialogRef} onCancel={(e) => { if (saving) e.preventDefault(); else onCancel(); }}>
            <h3 className="form_title">{id === -1 ? "Add" : "Edit"} Goal</h3>
            <input
                type="text"
                id="edit_goal_name"
                disabled={cantEditName || saving}
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
                disabled={saving}
                value={goalAmount}
                onChange={(e) => setGoalAmount(e.target.value)}
            ></input>
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

export function AddGoalTransactionDialog({ id, onSave, onCancel }) {
    const [amount, setAmount] = useState("");
    const [saving, setSaving] = useState(false);
    const dialogRef = useDialog(onCancel, () => !saving);

    function handleSave() {
        setSaving(true);
        onSave(id, amount)
            .then((success) => { if (!success) setSaving(false); })
            .catch(() => setSaving(false));
    }

    return (
        <dialog ref={dialogRef} onCancel={(e) => { if (saving) e.preventDefault(); else onCancel(); }}>
            <h3 className="form_title">Add Goal Transaction</h3>
            <input
                type="number"
                id="add_goal_amount"
                placeholder="amount"
                disabled={saving}
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
                <button className="btn-icon" onClick={startAddGoal}>+</button>
            </h1>
            <table>
                <tbody style={{ width: "100%", display: "table" }}>
                    {goals.map((goal) => (
                        <GoalRow
                            key={goal.id}
                            goal={goal}
                            startEditGoal={startEditGoal}
                            startDeleteGoal={startDeleteGoal}
                            startContributeGoal={startContributeGoal}
                        />
                    ))}
                    {goals.length > 1 ? (
                        <GoalTotalRow amount={goalAmountSum} total={goalTotalSum} />
                    ) : null}
                </tbody>
            </table>
        </>
    );
}
