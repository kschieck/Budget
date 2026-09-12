import { useState } from "react";
import { useRowActions, useDialog } from "./Utils.js";
import { useCategories } from "./CategoriesContext.js";
import * as API from "./API.js";

const DEFAULT_COLOR = "#c17f52";

export function CategoryColorDot({ color }) {
    if (!color) return null;
    return (
        <span
            className="category_dot"
            style={{ backgroundColor: color }}
        ></span>
    );
}

// Reusable <select> for choosing an optional category — used in the
// transaction / recurring / upcoming add-edit dialogs.
export function CategorySelect({ value, onChange, disabled = false }) {
    const { categories } = useCategories();

    return (
        <select
            className="category_select"
            disabled={disabled}
            value={value === null || value === undefined ? "" : value}
            onChange={(e) =>
                onChange(
                    e.target.value === "" ? null : parseInt(e.target.value, 10),
                )
            }
        >
            <option value="">No category</option>
            {categories.map((category) => (
                <option key={category.id} value={category.id}>
                    {category.name}
                </option>
            ))}
        </select>
    );
}

function CategoryRow({ category, onEditClicked, onDeleteClicked }) {
    const { showActions, handleMouseEnter, handleMouseLeave, handleClick } =
        useRowActions();

    return (
        <tr onMouseEnter={handleMouseEnter} onMouseLeave={handleMouseLeave}>
            <td>
                <CategoryColorDot color={category.color} />
            </td>
            <td className="small_cell">
                <div style={{ display: "inline-block" }} onClick={handleClick}>
                    {category.name}
                </div>
                {showActions ? (
                    <div className="row-actions">
                        <button
                            className="btn-icon-sm"
                            onClick={() => onEditClicked(category.id)}
                        >
                            ✎
                        </button>
                        <button
                            className="btn-icon-sm"
                            onClick={() => onDeleteClicked(category.id)}
                        >
                            ✕
                        </button>
                    </div>
                ) : null}
            </td>
        </tr>
    );
}

export function AddEditCategoryDialog({
    id = -1,
    name = "",
    color = DEFAULT_COLOR,
    onSave,
    onCancel,
}) {
    const [categoryName, setCategoryName] = useState(name);
    const [categoryColor, setCategoryColor] = useState(color);
    const [saving, setSaving] = useState(false);
    const dialogRef = useDialog(onCancel, () => !saving);

    function handleSave() {
        setSaving(true);
        onSave(id, categoryName, categoryColor)
            .then((success) => {
                if (!success) setSaving(false);
            })
            .catch(() => setSaving(false));
    }

    return (
        <dialog
            ref={dialogRef}
            onCancel={(e) => {
                if (saving) e.preventDefault();
                else onCancel();
            }}
        >
            <h3 className="form_title">
                {id === -1 ? "Add" : "Edit"} Category
            </h3>
            <input
                type="text"
                placeholder="name"
                disabled={saving}
                value={categoryName}
                onChange={(e) => setCategoryName(e.target.value)}
            />
            <br />
            <br />
            <input
                type="color"
                className="category_color_input"
                disabled={saving}
                value={categoryColor}
                onChange={(e) => setCategoryColor(e.target.value)}
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
                disabled={saving}
                onClick={onCancel}
            >
                Cancel
            </button>
        </dialog>
    );
}

export default function CategoriesSection() {
    const { categories, setCategories } = useCategories();
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);

    function handleSave(id, name, color) {
        return API.saveCategory(id, name, color)
            .then((result) => {
                if (result.success) {
                    setShowAddDialog(false);
                    setEditingCategory(null);
                    if (id === -1) {
                        setCategories((prev) => [...prev, result.category]);
                    } else {
                        setCategories((prev) =>
                            prev.map((c) =>
                                c.id === result.category.id
                                    ? result.category
                                    : c,
                            ),
                        );
                    }
                    return true;
                } else {
                    alert(result.message || "Failed to save category");
                    return false;
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to save category");
                return false;
            });
    }

    function handleDelete(id) {
        API.deleteCategory(id)
            .then((result) => {
                if (result.success) {
                    setCategories((prev) => prev.filter((c) => c.id !== id));
                } else {
                    alert(result.message || "Failed to delete category");
                }
            })
            .catch((e) => {
                console.error(e);
                alert("Failed to delete category");
            });
    }

    function startEdit(id) {
        const category = categories.find((c) => c.id === id) || null;
        if (category === null) return;
        setEditingCategory(category);
    }

    return (
        <>
            {showAddDialog && (
                <AddEditCategoryDialog
                    onCancel={() => setShowAddDialog(false)}
                    onSave={handleSave}
                />
            )}
            {editingCategory && (
                <AddEditCategoryDialog
                    id={editingCategory.id}
                    name={editingCategory.name}
                    color={editingCategory.color}
                    onCancel={() => setEditingCategory(null)}
                    onSave={handleSave}
                />
            )}

            <h1 id="category_title">
                Categories&nbsp;
                <button
                    className="btn-icon"
                    onClick={() => setShowAddDialog(true)}
                >
                    +
                </button>
            </h1>
            <table id="category_table" cellSpacing="0">
                <tbody>
                    {categories.map((category) => (
                        <CategoryRow
                            key={category.id}
                            category={category}
                            onEditClicked={startEdit}
                            onDeleteClicked={handleDelete}
                        />
                    ))}
                    {categories.length === 0 && (
                        <tr>
                            <td
                                colSpan="2"
                                style={{ textAlign: "center", opacity: 0.5 }}
                            >
                                No categories yet
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </>
    );
}
