import { ref, computed } from 'vue';

// Single source of truth for the color scheme, shared across every component
// that imports this module (the ref lives at module scope). The initial value
// is read from the class the inline bootstrap in app.blade.php already set, so
// the toggle starts in sync and there's no flash on load.
const STORAGE_KEY = 'gigwithme-theme';

const isDark = ref(
    typeof document !== 'undefined' &&
        document.documentElement.classList.contains('dark'),
);

function apply(dark) {
    isDark.value = dark;
    document.documentElement.classList.toggle('dark', dark);
    try {
        localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
    } catch (e) {
        // Private mode / storage disabled — the toggle still works for the
        // current session, it just won't persist.
    }
}

export function useDarkMode() {
    return {
        isDark: computed(() => isDark.value),
        toggle: () => apply(!isDark.value),
        setDark: apply,
    };
}
