import { createContext, useContext, useState } from "react";

// Generic "advanced mode" flag any feature can gate itself behind.
// Enabled for the session via the ?advanced=1 URL param — no persistence,
// so a plain reload/share of the app URL stays in the default, simple view.
const AdvancedModeContext = createContext(false);

export function useAdvancedMode() {
    return useContext(AdvancedModeContext);
}

export function AdvancedModeProvider({ children }) {
    const [advanced] = useState(
        () =>
            new URLSearchParams(window.location.search).get("advanced") === "1",
    );

    return (
        <AdvancedModeContext.Provider value={advanced}>
            {children}
        </AdvancedModeContext.Provider>
    );
}

export default AdvancedModeContext;
