import { createContext, useContext, useState, useEffect } from "react";
import { useAdvancedMode } from "./AdvancedModeContext.js";
import * as API from "./API.js";

const CategoriesContext = createContext({
    categories: [],
    setCategories: () => {},
    categoryById: () => null,
});

export function useCategories() {
    return useContext(CategoriesContext);
}

export function CategoriesProvider({ children }) {
    const advanced = useAdvancedMode();
    const [categories, setCategories] = useState([]);

    function loadCategories() {
        if (!advanced) return;
        API.loadCategories()
            .then((result) => {
                if (result.success) {
                    setCategories(result.categories);
                }
            })
            .catch(() => alert("Failed to load categories"));
    }

    useEffect(loadCategories, [advanced]);

    function categoryById(id) {
        if (id === null || id === undefined) return null;
        return categories.find((c) => c.id === id) ?? null;
    }

    return (
        <CategoriesContext.Provider
            value={{ categories, setCategories, categoryById }}
        >
            {children}
        </CategoriesContext.Provider>
    );
}

export default CategoriesContext;
